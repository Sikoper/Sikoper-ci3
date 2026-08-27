import sys

content = open('application/libraries/Tabungan_import_service.php', 'r', encoding='utf-8').read()
start = content.find('public function import_full_migration')
end = content.find('return $results;', start) + 16
end_function = content.find('}', end) + 1

new_func = """public function import_full_migration($file_path, $pegawai_id, $jenistabungan_id, $sheets_to_import = null, $year = null)
	{
		if (!$year) $year = date('Y');
		require_once APPPATH . 'third_party/SimpleXLS.php';
		ini_set('memory_limit', '2048M');
		ini_set('max_execution_time', 1200); // 20 minutes for full import

		$results = [
			'success' => false,
			'batch_id' => 'IMP' . date('YmdHis'),
			'simpanan' => ['inserted' => 0, 'updated' => 0, 'errors' => 0],
			'nasabah' => ['created' => 0, 'found' => 0],
			'setoran' => ['inserted' => 0],
			'penarikan' => ['inserted' => 0],
            'bunga' => ['inserted' => 0],
			'details' => [],
			'errors' => []
		];

		if (!file_exists($file_path)) {
			$results['errors'][] = 'File tidak ditemukan: ' . $file_path;
			return $results;
		}

		$month_map = [
			'JAN' => '01', 'FEB' => '02', 'MAR' => '03', 'APR' => '04',
			'MEI' => '05', 'JUNI' => '06', 'JULI' => '07', 'AGS' => '08',
			'SEP' => '09', 'OKT' => '10', 'NOP' => '11', 'DES' => '12'
		];

		$xls = \Shuchkin\SimpleXLS::parse($file_path);
		if (!$xls) {
			$results['errors'][] = 'Gagal membaca file Excel: ' . \Shuchkin\SimpleXLS::parseError();
			return $results;
		}

		$sheets = $xls->sheetNames();
		$results['sheets_found'] = $sheets;

		$norek_to_id = [];
		$nama_to_nasabah = [];

		$this->CI->db->trans_start();
        $this->CI->db->query('SET FOREIGN_KEY_CHECKS=0');

		// Extract mapping names from JAN
		$jan_index = array_search('JAN', $sheets);
		if ($jan_index === false) $jan_index = 0;
		$noTab_to_nama = [];
		$jan_rows = $xls->rows($jan_index);
		for ($i = 3; $i < count($jan_rows); $i++) {
			$jan_no_tab = intval($jan_rows[$i][2] ?? 0);
			$jan_nama = trim($jan_rows[$i][1] ?? '');
			$jan_alamat = trim($jan_rows[$i][3] ?? '-');
			if ($jan_no_tab > 0 && !empty($jan_nama)) {
				$noTab_to_nama[$jan_no_tab] = ['nama' => $jan_nama, 'alamat' => $jan_alamat];
			}
		}

        $batch_setoran = [];
        $batch_penarikan = [];
        $batch_transaksi = [];

		// Loop over all sheets
		for ($sheet_idx = 0; $sheet_idx < 12; $sheet_idx++) {
			if (!isset($sheets[$sheet_idx])) continue;
			$sheet_name = strtoupper($sheets[$sheet_idx]);
			if (!isset($month_map[$sheet_name])) continue;

			$month = $month_map[$sheet_name];
			$rows = $xls->rows($sheet_idx);
			$col_offset = ($sheet_name === 'FEB') ? 2 : 0;

			for ($i = 3; $i < count($rows); $i++) {
				$row = $rows[$i];
				$no_urut = intval($row[$col_offset + 0] ?? 0);
				$no_tab = intval($row[$col_offset + 2] ?? 0);
				if ($no_urut <= 0 && $no_tab <= 0) continue;

				$norek_num = $no_tab > 0 ? $no_tab : $no_urut;
				$no_rekening = 'T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);

				if (isset($noTab_to_nama[$norek_num])) {
					$nama = $noTab_to_nama[$norek_num]['nama'];
					$alamat = $noTab_to_nama[$norek_num]['alamat'];
				} else {
					$nama = trim($row[$col_offset + 1] ?? '');
					$alamat = trim($row[$col_offset + 3] ?? '-');
					if (empty($nama)) $nama = 'Nasabah ' . $no_rekening;
				}

				if (stripos($nama, 'JUMLAH') !== false || stripos($nama, 'TOTAL') !== false) continue;

				$existing = $this->CI->db->where('no_rekening', $no_rekening)->get('tbsimpanan')->row();

				if ($existing) {
					$simpanan_id = $existing->id;
					$nasabah_id = $existing->nasabah_id;
                    if ($month === '01') {
                        $results['simpanan']['updated']++;
                        $is_placeholder = (strpos($nama, 'Nasabah T') === 0);
                        if (!$is_placeholder) {
                            $this->CI->db->where('id', $simpanan_id)->update('tbsimpanan', ['nama_nasabah' => $nama]);
                            $this->CI->db->where('id', $nasabah_id)->update('tbnasabah', ['nama_lengkap' => $nama, 'alamat' => $alamat ?: '-']);
                        }
                    }
				} else {
                    if ($month !== '01') continue; // Only create new accounts in JAN
					if (isset($nama_to_nasabah[$no_rekening])) {
						$nasabah_id = $nama_to_nasabah[$no_rekening];
						$results['nasabah']['found']++;
					} else {
						$nasabah = $this->_find_nasabah_by_name($nama);
						if ($nasabah) {
							$nasabah_id = $nasabah->id;
							$results['nasabah']['found']++;
						} else {
							$nasabah_id = $this->_create_nasabah_from_import($nama, $alamat, '-', $pegawai_id);
							$results['nasabah']['created']++;
						}
						$nama_to_nasabah[$no_rekening] = $nasabah_id;
					}

					$simpanan_data = [
						'no_rekening' => $no_rekening,
						'nasabah_id' => $nasabah_id,
						'pegawai_id' => $pegawai_id,
						'jenistabungan_id' => $jenistabungan_id,
						'nama_nasabah' => $nama,
						'jumlah_simpanan' => 0,
						'jumlah_bunga' => 0,
						'tanggal_simpanan' => $year . '-01-01',
						'status' => 'aktif'
					];
					$this->CI->db->insert('tbsimpanan', $simpanan_data);
					$simpanan_id = $this->CI->db->insert_id();
					$results['simpanan']['inserted']++;
				}

				$norek_to_id[$no_rekening] = $simpanan_id;

				if ($month === '01') {
					$saldo_awal = $this->_parse_amount($row[$col_offset + 4] ?? 0);
					if ($saldo_awal > 0) {
                        $batch_setoran[] = [
                            'simpanan_id' => $simpanan_id,
                            'tanggal_setoran' => $year . '-01-01 08:00:00',
                            'jumlah_setoran' => $saldo_awal,
                            'pegawai_id' => $pegawai_id
                        ];
					}
				}

				for ($day = 1; $day <= 31; $day++) {
					$col = $col_offset + 4 + $day;
					$amount = $this->_parse_amount($row[$col] ?? 0);
					if ($amount > 0 && checkdate((int)$month, $day, (int)$year)) {
						$date = sprintf('%s-%s-%02d', $year, $month, $day);
						$batch_setoran[] = [
                            'simpanan_id' => $simpanan_id,
                            'tanggal_setoran' => $date . ' 12:00:00',
                            'jumlah_setoran' => $amount,
                            'pegawai_id' => $pegawai_id
                        ];
						$results['setoran']['inserted']++;
					}
				}

				for ($day = 1; $day <= 31; $day++) {
					$col = $col_offset + 35 + $day;
					$amount = $this->_parse_amount($row[$col] ?? 0);
					if ($amount > 0 && checkdate((int)$month, $day, (int)$year)) {
						$date = sprintf('%s-%s-%02d', $year, $month, $day);
						$batch_penarikan[] = [
                            'simpanan_id' => $simpanan_id,
                            'penarikan_id' => 0,
                            'tanggal_penarikan' => $date . ' 12:00:00',
                            'jumlah_penarikan' => $amount,
                            'pegawai_id' => $pegawai_id,
                            'status' => 'disetujui'
                        ];
						$results['penarikan']['inserted']++;
					}
				}

                $bunga = $this->_parse_amount($row[$col_offset + 101] ?? $row[$col_offset + 99] ?? 0);
                if ($bunga > 0) {
                    $last_day = date('t', strtotime("$year-$month-01"));
                    $batch_transaksi[] = [
                        'simpanan_id' => $simpanan_id,
                        'no_rekening' => $no_rekening,
                        'nama_nasabah' => $nama,
                        'tanggal_transaksi' => "$year-$month-$last_day",
                        'jumlah_transaksi' => $bunga,
                        'rate_bunga' => 0,
                        'bunga_riil' => $bunga
                    ];
                    $results['bunga']['inserted']++;
                }

                if (count($batch_setoran) >= 500) {
                    $this->CI->db->insert_batch('tbdetail_simpanan', $batch_setoran);
                    $batch_setoran = [];
                }
                if (count($batch_penarikan) >= 500) {
                    $this->CI->db->insert_batch('tbdetail_penarikan', $batch_penarikan);
                    $batch_penarikan = [];
                }
                if (count($batch_transaksi) >= 500) {
                    $this->CI->db->insert_batch('tbtransaksi', $batch_transaksi);
                    $batch_transaksi = [];
                }
			}
		}

        if (!empty($batch_setoran)) {
            $this->CI->db->insert_batch('tbdetail_simpanan', $batch_setoran);
        }
        if (!empty($batch_penarikan)) {
            $this->CI->db->insert_batch('tbdetail_penarikan', $batch_penarikan);
        }
        if (!empty($batch_transaksi)) {
            $this->CI->db->insert_batch('tbtransaksi', $batch_transaksi);
        }

        try {
            $this->CI->db->query("
                UPDATE tbsimpanan s
                SET 
                    jumlah_bunga = COALESCE((
                        SELECT COALESCE(SUM(jumlah_transaksi), 0) FROM tbtransaksi WHERE simpanan_id = s.id
                    ), 0),
                    jumlah_simpanan = COALESCE((
                        SELECT COALESCE(SUM(jumlah_setoran), 0) FROM tbdetail_simpanan WHERE simpanan_id = s.id
                    ), 0) - COALESCE((
                        SELECT COALESCE(SUM(jumlah_penarikan), 0) FROM tbdetail_penarikan WHERE simpanan_id = s.id AND status = 'disetujui'
                    ), 0) + COALESCE((
                        SELECT COALESCE(SUM(jumlah_transaksi), 0) FROM tbtransaksi WHERE simpanan_id = s.id
                    ), 0)
            ");
        } catch (Exception $e) {
            $results['errors'][] = "Mass saldo update error: " . $e->getMessage();
        }

        $this->CI->db->query('SET FOREIGN_KEY_CHECKS=1');
		$this->CI->db->trans_complete();
		$results['success'] = $this->CI->db->trans_status();
		$results['total_processed'] = count($norek_to_id);
		return $results;
	}"""

with open('application/libraries/Tabungan_import_service.php', 'w', encoding='utf-8') as f:
    f.write(content[:start])
    f.write(new_func)
    f.write(content[end_function:])
