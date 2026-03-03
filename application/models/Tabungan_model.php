<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tabungan_model extends CI_Model
{
	var $table = 'tbsimpanan';
	var $column_order = array(null, 'tanggal', 'jumlah_uang', 'keterangan', 'pegawai', null);
	var $column_search = array('trans.tanggal', 'trans.keterangan', 'tbpegawai.nama_lengkap');
	var $order = array('tanggal' => 'ASC');

	private function _get_datatables_query($id = null)
	{
		$subquery = "
        (
            SELECT 
                ds.id AS detail_id,
                tbs.id AS simpanan_id,
                tbs.pegawai_id,
                ds.tanggal_setoran AS tanggal,
                ds.jumlah_setoran AS jumlah_uang,
                tp.nama_lengkap as pegawai,
                'Setor' AS keterangan
            FROM tbdetail_simpanan ds
            JOIN tbpegawai tp ON tp.id = ds.pegawai_id
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id

            UNION ALL

            SELECT 
                dp.id AS detail_id,
                tbs.id AS simpanan_id,
                tbs.pegawai_id,
                dp.tanggal_penarikan AS tanggal,
                dp.jumlah_penarikan AS jumlah_uang,
                tp.nama_lengkap as pegawai,
                'Tarik' AS keterangan
            FROM tbdetail_penarikan dp
            JOIN tbpegawai tp ON tp.id = dp.pegawai_id
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
        ) AS trans
        ";

		$this->db->from($subquery);

		if ($id !== null) {
			$this->db->where('trans.simpanan_id', $id);
		}

		$i = 0;
		foreach ($this->column_search as $item) {
			if ($_POST['search']['value']) {
				if ($i === 0) {
					$this->db->group_start();
					$this->db->like($item, $_POST['search']['value']);
				} else {
					$this->db->or_like($item, $_POST['search']['value']);
				}

				if (count($this->column_search) - 1 == $i)
					$this->db->group_end();
			}
			$i++;
		}

		if (isset($_POST['order'])) {
			$this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
		} else if (isset($this->order)) {
			$order = $this->order;
			$this->db->order_by(key($order), $order[key($order)]);
		}
	}

	function get_datatables($id = null)
	{
		$this->_get_datatables_query($id);
		if ($_POST['length'] != -1)
			$this->db->limit($_POST['length'], $_POST['start']);
		$query = $this->db->get();
		return $query->result();
	}

	function count_filtered($id = null)
	{
		$this->_get_datatables_query($id);
		$query = $this->db->get();
		return $query->num_rows();
	}

	public function count_all($id = null)
	{
		$where = "";
		$bind = [];

		if ($id !== null) {
			$where = "WHERE simpanan_id = ?";
			$bind[] = $id;
		}

		$sql = "
        SELECT COUNT(*) AS total FROM (
            SELECT 
                tbs.id AS simpanan_id
            FROM tbdetail_simpanan ds
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id

            UNION ALL

            SELECT 
                tbs.id AS simpanan_id
            FROM tbdetail_penarikan dp
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
        ) AS trans
        $where
    ";

		$query = $this->db->query($sql, $bind);
		return $query->row()->total;
	}

	public function get_data_by_id($id)
	{
		$sql = "
        SELECT tbs.id, tbs.no_rekening, tbs.nasabah_id, tbnasabah.nama_lengkap
        FROM (
            SELECT tbs.id, tbs.no_rekening, tbs.nasabah_id
            FROM tbdetail_simpanan ds
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id
            WHERE tbs.id = ?

            UNION ALL

            SELECT tbs.id, tbs.no_rekening, tbs.nasabah_id
            FROM tbdetail_penarikan dp
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
            WHERE tbs.id = ?
        ) AS tbs
        JOIN tbnasabah ON tbnasabah.id = tbs.nasabah_id
        LIMIT 1
    ";

		$query = $this->db->query($sql, [$id, $id]);
		return $query->row();
	}

	public function get_transaksi_rekening_koran($source_id, $tanggal_mulai = null, $tanggal_akhir = null, $jenis_laporan = 3, $jenis_tabungan = 'simpanan')
	{
		$queries = [];
		$saldo_awal = 0;

		if ($jenis_tabungan === 'simpanan') {
			// --- Hitung saldo awal Simpanan (Corrected and Simplified) ---
			$this->db->select_sum('jumlah_setoran', 'total');
			$this->db->where('simpanan_id', $source_id);
			if ($tanggal_mulai) {
				// FIX: Cast the datetime column to date for accurate comparison
				$this->db->where('DATE(tanggal_setoran) <', $tanggal_mulai);
			}
			$q1 = $this->db->get('tbdetail_simpanan');
			$setor_awal = ($q1->num_rows() > 0 && $q1->row()->total !== null) ? (float) $q1->row()->total : 0;

			$this->db->select_sum('jumlah_penarikan', 'total');
			$this->db->where('simpanan_id', $source_id);
			if ($tanggal_mulai) {
				// FIX: Cast the datetime column to date for accurate comparison
				$this->db->where('DATE(tanggal_penarikan) <', $tanggal_mulai);
			}
			$q2 = $this->db->get('tbdetail_penarikan');
			$tarik_awal = ($q2->num_rows() > 0 && $q2->row()->total !== null) ? (float) $q2->row()->total : 0;

			$this->db->select_sum('jumlah_transaksi', 'total');
			$this->db->where('simpanan_id', $source_id);
			if ($tanggal_mulai) {
				// FIX: Assuming tanggal_transaksi is also datetime or date, casting is safest.
				$this->db->where('DATE(tanggal_transaksi) <', $tanggal_mulai);
			}
			$q3 = $this->db->get('tbtransaksi');
			$bunga_awal = ($q3->num_rows() > 0 && $q3->row()->total !== null) ? (float) $q3->row()->total : 0;

			$saldo_awal = ($setor_awal + $bunga_awal) - $tarik_awal;

			// --- Hitung saldo pindahan (initial balance dari import yg tidak tercatat sebagai transaksi) ---
			$simpanan_data = $this->db->where('id', $source_id)->get('tbsimpanan')->row();
			if ($simpanan_data) {
				// Total SEMUA transaksi yang ada di database (tanpa filter tanggal)
				$all_setor_q = $this->db->select_sum('jumlah_setoran', 'total')->where('simpanan_id', $source_id)->get('tbdetail_simpanan');
				$all_setor = ($all_setor_q->num_rows() > 0 && $all_setor_q->row()->total !== null) ? (float) $all_setor_q->row()->total : 0;

				$all_tarik_q = $this->db->select_sum('jumlah_penarikan', 'total')->where('simpanan_id', $source_id)->get('tbdetail_penarikan');
				$all_tarik = ($all_tarik_q->num_rows() > 0 && $all_tarik_q->row()->total !== null) ? (float) $all_tarik_q->row()->total : 0;

				$all_bunga_q = $this->db->select_sum('jumlah_transaksi', 'total')->where('simpanan_id', $source_id)->get('tbtransaksi');
				$all_bunga = ($all_bunga_q->num_rows() > 0 && $all_bunga_q->row()->total !== null) ? (float) $all_bunga_q->row()->total : 0;

				// Saldo pindahan = jumlah_simpanan saat ini - total semua transaksi tercatat
				$saldo_pindahan = (float) $simpanan_data->jumlah_simpanan - (($all_setor + $all_bunga) - $all_tarik);
				if ($saldo_pindahan > 0) {
					$saldo_awal += $saldo_pindahan;
				}
			}

			// --- Transaksi Setoran ---
			if ($jenis_laporan == 1 || $jenis_laporan == 3) {
				$this->db->select("
                    ds.tanggal_setoran AS tanggal,
                    0 AS debit,
                    ds.jumlah_setoran AS kredit,
                    'Setoran Tunai' AS keterangan,
                    p.nama_lengkap AS pegawai
                ");
				$this->db->from('tbdetail_simpanan ds');
				$this->db->join('tbpegawai p', 'p.id = ds.pegawai_id', 'left');
				$this->db->where('ds.simpanan_id', $source_id);
				if ($tanggal_mulai && $tanggal_akhir) {
					// FIX: Use DATE() to correctly filter the range on a DATETIME column
					$this->db->where('DATE(ds.tanggal_setoran) >=', $tanggal_mulai);
					$this->db->where('DATE(ds.tanggal_setoran) <=', $tanggal_akhir);
				}
				// --- REMOVED: The condition that excluded the "first setor" date ---
				$queries[] = $this->db->get_compiled_select();
			}

			// --- Transaksi Penarikan ---
			if ($jenis_laporan == 2 || $jenis_laporan == 3) {
				$this->db->select("
                    dp.tanggal_penarikan AS tanggal,
                    dp.jumlah_penarikan AS debit,
                    0 AS kredit,
                    'Penarikan Tunai' AS keterangan,
                    p.nama_lengkap AS pegawai
                ");
				$this->db->from('tbdetail_penarikan dp');
				$this->db->join('tbpegawai p', 'p.id = dp.pegawai_id', 'left');
				$this->db->where('dp.simpanan_id', $source_id);
				if ($tanggal_mulai && $tanggal_akhir) {
					// FIX: Use DATE() to correctly filter the range on a DATETIME column
					$this->db->where('DATE(dp.tanggal_penarikan) >=', $tanggal_mulai);
					$this->db->where('DATE(dp.tanggal_penarikan) <=', $tanggal_akhir);
				}
				$queries[] = $this->db->get_compiled_select();
			}

			// --- Transaksi Bunga Simpanan ---
			$this->db->select("
                tanggal_transaksi AS tanggal,
                0 AS debit,
                jumlah_transaksi AS kredit,
                'Bunga Simpanan' AS keterangan,
                'SYSTEM' AS pegawai
            ");
			$this->db->from('tbtransaksi');
			$this->db->where('simpanan_id', $source_id);
			if ($tanggal_mulai && $tanggal_akhir) {
				// FIX: Use DATE() for consistency and safety
				$this->db->where('DATE(tanggal_transaksi) >=', $tanggal_mulai);
				$this->db->where('DATE(tanggal_transaksi) <=', $tanggal_akhir);
			}
			$queries[] = $this->db->get_compiled_select();
		} else if ($jenis_tabungan === 'deposito') {
			// ... (Your deposito logic can remain, applying the DATE() fix if necessary) ...
		}

		// --- Final Execution (No changes needed here) ---
		if (empty($queries)) {
			return [
				'saldo_awal' => $saldo_awal,
				'total_setor' => 0,
				'total_tarik' => 0,
				'saldo_akhir' => $saldo_awal,
				'transaksi' => []
			];
		}

		$final_query = implode(" UNION ALL ", $queries) . " ORDER BY tanggal ASC";
		$transaksi = $this->db->query($final_query)->result();

		$total_setor_periode = array_sum(array_column($transaksi, 'kredit'));
		$total_tarik_periode = array_sum(array_column($transaksi, 'debit'));
		$saldo_akhir = $saldo_awal + $total_setor_periode - $total_tarik_periode;

		return [
			'saldo_awal' => $saldo_awal,
			'total_setor' => $total_setor_periode,
			'total_tarik' => $total_tarik_periode,
			'saldo_akhir' => $saldo_akhir,
			'transaksi' => $transaksi
		];
	}

	/**
	 * Import full migration from Excel file - ALL SHEETS with DAILY transactions
	 * Imports: JAN-DES (12 sheets) with setoran/penarikan per day
	 * 
	 * @param string $file_path Path to Excel file
	 * @param int $pegawai_id Employee ID performing import
	 * @param int $jenistabungan_id Savings type ID
	 * @return array Import results with details
	 */
	public function import_full_migration($file_path, $pegawai_id, $jenistabungan_id, $sheets_to_import = null)
	{
		require_once APPPATH . 'third_party/SimpleXLS.php';
		ini_set('memory_limit', '2048M');
		ini_set('max_execution_time', 600); // 10 minutes

		$results = [
			'success' => false,
			'batch_id' => 'IMP' . date('YmdHis'),
			'simpanan' => ['inserted' => 0, 'updated' => 0, 'errors' => 0],
			'nasabah' => ['created' => 0, 'found' => 0],
			'setoran' => ['inserted' => 0],
			'penarikan' => ['inserted' => 0],
			'details' => [],
			'errors' => []
		];

		// Check if file exists
		if (!file_exists($file_path)) {
			$results['errors'][] = 'File tidak ditemukan: ' . $file_path;
			return $results;
		}

		// Month mapping for date construction
		$month_map = [
			'JAN' => '01',
			'FEB' => '02',
			'MAR' => '03',
			'APR' => '04',
			'MEI' => '05',
			'JUNI' => '06',
			'JULI' => '07',
			'AGS' => '08',
			'SEP' => '09',
			'OKT' => '10',
			'NOP' => '11',
			'DES' => '12'
		];

		// Parse Excel file
		$xls = \Shuchkin\SimpleXLS::parse($file_path);
		if (!$xls) {
			$results['errors'][] = 'Gagal membaca file Excel: ' . \Shuchkin\SimpleXLS::parseError();
			return $results;
		}

		$sheets = $xls->sheetNames();
		$results['sheets_found'] = $sheets;

		// Track no_rekening to simpanan_id mapping
		$norek_to_id = [];
		// Track nama to nasabah_id mapping for deduplication
		$nama_to_nasabah = [];

		$this->db->trans_start();

		// PHASE 1: Process JAN sheet first to create master data
		$jan_rows = $xls->rows(0);
		$results['importing_sheet'] = 'ALL (JAN-DES)';

		for ($i = 3; $i < count($jan_rows); $i++) {
			$row = $jan_rows[$i];

			$nama = trim($row[1] ?? '');
			if (empty($nama))
				continue;
			if (stripos($nama, 'JUMLAH') !== false || stripos($nama, 'TOTAL') !== false)
				continue;

			try {
				$no_urut = intval($row[0] ?? 0);
				$no_tab = intval($row[2] ?? 0);
				$alamat = trim($row[3] ?? '-');

				// Generate no_rekening
				$no_rekening = $no_tab > 0 ? 'S' . str_pad($no_tab, 4, '0', STR_PAD_LEFT) : 'S' . str_pad($no_urut, 4, '0', STR_PAD_LEFT);

				// Find or create nasabah (deduplicate by name)
				$nama_lower = strtolower(trim($nama));
				if (isset($nama_to_nasabah[$nama_lower])) {
					$nasabah_id = $nama_to_nasabah[$nama_lower];
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
					$nama_to_nasabah[$nama_lower] = $nasabah_id;
				}

				// Check if simpanan exists by no_rekening
				$existing = $this->db->where('no_rekening', $no_rekening)->get('tbsimpanan')->row();

				if (!$existing) {
					// Create simpanan master record
					$simpanan_data = [
						'no_rekening' => $no_rekening,
						'nasabah_id' => $nasabah_id,
						'pegawai_id' => $pegawai_id,
						'jenistabungan_id' => $jenistabungan_id,
						'nama_nasabah' => $nama,
						'jumlah_simpanan' => 0,
						'jumlah_bunga' => 0,
						'tanggal_simpanan' => '2025-01-01', // Start of the year
						'status' => 'aktif'
					];
					$this->db->insert('tbsimpanan', $simpanan_data);
					$simpanan_id = $this->db->insert_id();
					$results['simpanan']['inserted']++;
				} else {
					$simpanan_id = $existing->id;
					$results['simpanan']['updated']++;
				}

				$norek_to_id[$no_rekening] = $simpanan_id;

				// INSERT SALDO AWAL (JAN) AS INITIAL DEPOSIT
				$saldo_awal = $this->_parse_amount($row[4] ?? 0);
				if ($saldo_awal > 0) {
					$this->db->insert('tbdetail_simpanan', [
						'simpanan_id' => $simpanan_id,
						'tanggal_setoran' => '2025-01-01 00:00:01',
						'jumlah_setoran' => $saldo_awal,
						'pegawai_id' => $pegawai_id
					]);
					$results['setoran']['inserted']++;
				}

				$results['details'][] = ['row' => $i + 1, 'nama' => $nama, 'no_rekening' => $no_rekening, 'action' => 'master'];

			} catch (Exception $e) {
				$results['simpanan']['errors']++;
				$results['errors'][] = "JAN Row " . ($i + 1) . ": " . $e->getMessage();
			}
		}

		// PHASE 2: Process ALL monthly sheets for transactions
		$year = '2025'; // Adjust based on file name if needed

		for ($sheet_idx = 0; $sheet_idx < 12; $sheet_idx++) {
			if (!isset($sheets[$sheet_idx]))
				continue;

			$sheet_name = strtoupper($sheets[$sheet_idx]);
			if (!isset($month_map[$sheet_name]))
				continue;

			$month = $month_map[$sheet_name];
			$rows = $xls->rows($sheet_idx);

			for ($i = 3; $i < count($rows); $i++) {
				$row = $rows[$i];

				$no_urut = intval($row[0] ?? 0);
				$no_tab = intval($row[2] ?? 0);
				$no_rekening = $no_tab > 0 ? 'S' . str_pad($no_tab, 4, '0', STR_PAD_LEFT) : 'S' . str_pad($no_urut, 4, '0', STR_PAD_LEFT);

				if (!isset($norek_to_id[$no_rekening]))
					continue;
				$simpanan_id = $norek_to_id[$no_rekening];

				// Process daily SETORAN (columns 5-35 for days 1-31)
				for ($day = 1; $day <= 31; $day++) {
					$col = 4 + $day; // Column 5 = day 1
					$amount = $this->_parse_amount($row[$col] ?? 0);

					if ($amount > 0) {
						$date = sprintf('%s-%s-%02d', $year, $month, $day);
						// Validate date
						if (checkdate((int) $month, $day, (int) $year)) {
							$this->db->insert('tbdetail_simpanan', [
								'simpanan_id' => $simpanan_id,
								'tanggal_setoran' => $date . ' 12:00:00',
								'jumlah_setoran' => $amount,
								'pegawai_id' => $pegawai_id
							]);
							$results['setoran']['inserted']++;
						}
					}
				}

				// Process daily PENARIKAN (columns 36-66 for days 1-31)
				for ($day = 1; $day <= 31; $day++) {
					$col = 35 + $day; // Column 36 = day 1
					$amount = $this->_parse_amount($row[$col] ?? 0);

					if ($amount > 0) {
						$date = sprintf('%s-%s-%02d', $year, $month, $day);
						// Validate date
						if (checkdate((int) $month, $day, (int) $year)) {
							$this->db->insert('tbdetail_penarikan', [
								'simpanan_id' => $simpanan_id,
								'penarikan_id' => 0, // No parent penarikan record
								'tanggal_penarikan' => $date . ' 12:00:00',
								'jumlah_penarikan' => $amount,
								'pegawai_id' => $pegawai_id,
								'status' => 'disetujui'
							]);
							$results['penarikan']['inserted']++;
						}
					}
				}
			}
		}

		// PHASE 3: Update final balances from DES sheet
		$des_rows = $xls->rows(11);
		for ($i = 3; $i < count($des_rows); $i++) {
			$row = $des_rows[$i];
			$no_urut = intval($row[0] ?? 0);
			$no_tab = intval($row[2] ?? 0);
			$no_rekening = $no_tab > 0 ? 'S' . str_pad($no_tab, 4, '0', STR_PAD_LEFT) : 'S' . str_pad($no_urut, 4, '0', STR_PAD_LEFT);

			if (!isset($norek_to_id[$no_rekening]))
				continue;

			$saldo_akhir = $this->_parse_amount($row[102] ?? $row[103] ?? 0);
			$bunga = $this->_parse_amount($row[99] ?? 0);

			$this->db->where('id', $norek_to_id[$no_rekening])
				->update('tbsimpanan', [
					'jumlah_simpanan' => $saldo_akhir,
					'jumlah_bunga' => $bunga
				]);
		}

		$this->db->trans_complete();

		$results['success'] = $this->db->trans_status();
		$results['total_processed'] = count($norek_to_id);

		return $results;
	}

	/**
	 * Helper: Parse amount from Excel cell
	 */
	private function _parse_amount($value)
	{
		if (empty($value))
			return 0;
		if (is_numeric($value))
			return floatval($value);

		// Remove currency symbols and formatting
		$value = str_replace(['$', 'Rp', ',', ' '], '', $value);
		return floatval($value);
	}

	/**
	 * Helper: Find nasabah by name (exact match only)
	 * LIKE fallback removed to prevent matching wrong nasabah with similar names
	 */
	private function _find_nasabah_by_name($nama)
	{
		if (empty($nama))
			return null;

		// Exact match only - no LIKE to prevent wrong matches
		// e.g. "SITI" should NOT match "SITI AMINAH" or "SITI NURHALIZA"
		$result = $this->db->where('LOWER(nama_lengkap)', strtolower(trim($nama)))
			->get('tbnasabah')->row();

		return $result;
	}

	/**
	 * Helper: Create nasabah from import
	 */
	private function _create_nasabah_from_import($nama, $alamat = '-', $telp = '-', $pegawai_id = null)
	{
		$data = [
			'nik' => '-',
			'nama_lengkap' => $nama,
			'jenis_kelamin' => '?',
			'tempat_lahir' => '-',
			'tanggal_lahir' => null,
			'agama' => '-',
			'alamat' => $alamat ?: '-',
			'pekerjaan' => '-',
			'telp' => $telp ?: '-',
			'nama_ibu_kandung' => '-',
			'pegawai_id' => $pegawai_id ?: 1,
			'created_at' => date('Y-m-d H:i:s')
		];

		$this->db->insert('tbnasabah', $data);
		return $this->db->insert_id();
	}

	/**
	 * Import December only from Excel file - TABUNGAN 2026
	 * Imports: DES sheet only with daily setoran/penarikan per day
	 * Format no_rekening: T001 (3 digits)
	 * 
	 * @param string $file_path Path to Excel file
	 * @param int $pegawai_id Employee ID performing import
	 * @param int $jenistabungan_id Savings type ID
	 * @param bool $delete_existing Delete existing data before import
	 * @return array Import results with details
	 */
	public function import_saldo_akhir_tahun($file_path, $pegawai_id, $jenistabungan_id, $delete_existing = true)
	{
		ini_set('memory_limit', '2048M');
		ini_set('max_execution_time', 600);

		$results = [
			'success' => false,
			'batch_id' => 'DES' . date('YmdHis'),
			'simpanan' => ['inserted' => 0, 'errors' => 0],
			'nasabah' => ['created' => 0, 'found' => 0],
			'setoran' => ['inserted' => 0],
			'penarikan' => ['inserted' => 0],
			'bunga' => ['total' => 0, 'count' => 0],
			'deleted' => ['simpanan' => 0, 'nasabah' => 0, 'setoran' => 0, 'penarikan' => 0, 'transaksi' => 0],
			'details' => [],
			'errors' => []
		];

		if (!file_exists($file_path)) {
			$results['errors'][] = 'File tidak ditemukan: ' . $file_path;
			return $results;
		}

		// Detect file type and use appropriate library
		$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

		if ($file_ext === 'xlsx') {
			// Use SimpleXLSX for .xlsx files
			require_once APPPATH . 'third_party/SimpleXLSX.php';
			$xls = \Shuchkin\SimpleXLSX::parse($file_path);
			if (!$xls) {
				$results['errors'][] = 'Gagal membaca file Excel (.xlsx): ' . \Shuchkin\SimpleXLSX::parseError();
				return $results;
			}
		} else {
			// Use SimpleXLS for .xls files
			require_once APPPATH . 'third_party/SimpleXLS.php';
			$xls = \Shuchkin\SimpleXLS::parse($file_path);
			if (!$xls) {
				$results['errors'][] = 'Gagal membaca file Excel (.xls): ' . \Shuchkin\SimpleXLS::parseError();
				return $results;
			}
		}

		$sheets = $xls->sheetNames();
		$results['sheets_found'] = $sheets;

		// Find DES sheet (index 11 or by name)
		$des_index = array_search('DES', $sheets);
		if ($des_index === false) {
			$des_index = 11;
		}

		if (!isset($sheets[$des_index])) {
			$results['errors'][] = 'Sheet DES tidak ditemukan';
			return $results;
		}

		$results['importing_sheet'] = 'DES (Desember 2025)';
		$year = '2025';
		$month = '12';

		$this->db->trans_start();

		// PHASE 0: Delete existing data if requested
		if ($delete_existing) {
			// Delete in proper order due to FK constraints
			// 1. tbtransaksi (references tbsimpanan)
			$this->db->query("DELETE FROM tbtransaksi WHERE simpanan_id IN (SELECT id FROM tbsimpanan)");
			$results['deleted']['transaksi'] = $this->db->affected_rows();

			// 2. tbdetail_penarikan (references tbsimpanan, tbpenarikan)
			$this->db->query("DELETE FROM tbdetail_penarikan WHERE simpanan_id IN (SELECT id FROM tbsimpanan)");
			$results['deleted']['penarikan'] = $this->db->affected_rows();

			// 3. tbpenarikan (references tbsimpanan)
			$this->db->query("DELETE FROM tbpenarikan WHERE simpanan_id IN (SELECT id FROM tbsimpanan)");

			// 4. tbdetail_simpanan (references tbsimpanan)
			$this->db->query("DELETE FROM tbdetail_simpanan WHERE simpanan_id IN (SELECT id FROM tbsimpanan)");
			$results['deleted']['setoran'] = $this->db->affected_rows();

			// 5. tbsimpanan
			$this->db->query("DELETE FROM tbsimpanan");
			$results['deleted']['simpanan'] = $this->db->affected_rows();

			// 6. tbnasabah (only those without deposito)
			$this->db->query("DELETE FROM tbnasabah WHERE id NOT IN (SELECT DISTINCT nasabah_id FROM tbdeposito)");
			$results['deleted']['nasabah'] = $this->db->affected_rows();
		}

		// Get DES sheet rows
		$rows = $xls->rows($des_index);
		$norek_to_id = [];
		$nama_to_nasabah = [];

		// PHASE 0.5: Build nama mapping from JAN sheet (since DES sheet has empty NAMA column)
		// JAN sheet has NAMA in Col[1], NO_TAB in Col[2]
		$jan_index = array_search('JAN', $sheets);
		if ($jan_index === false)
			$jan_index = 0;

		$noTab_to_nama = [];
		$jan_rows = $xls->rows($jan_index);
		for ($i = 3; $i < count($jan_rows); $i++) {
			$jan_row = $jan_rows[$i];
			$jan_no_tab = intval($jan_row[2] ?? 0);
			$jan_nama = trim($jan_row[1] ?? '');
			$jan_alamat = trim($jan_row[3] ?? '-');

			if ($jan_no_tab > 0 && !empty($jan_nama)) {
				$noTab_to_nama[$jan_no_tab] = [
					'nama' => $jan_nama,
					'alamat' => $jan_alamat
				];
			}
		}
		$results['nama_mapping_count'] = count($noTab_to_nama);


		// PHASE 1: Process each nasabah and create simpanan + transactions
		for ($i = 3; $i < count($rows); $i++) {
			$row = $rows[$i];

			$no_urut = intval($row[0] ?? 0);
			$no_tab = intval($row[2] ?? 0);

			// Skip if no valid identifier (no_urut or no_tab)
			if ($no_urut <= 0 && $no_tab <= 0)
				continue;

			// Get nama - lookup from JAN sheet mapping, fallback to placeholder
			$norek_num = $no_tab > 0 ? $no_tab : $no_urut;
			if (isset($noTab_to_nama[$norek_num])) {
				$nama = $noTab_to_nama[$norek_num]['nama'];
				$alamat = $noTab_to_nama[$norek_num]['alamat'];
			} else {
				// Fallback to DES sheet column (usually empty)
				$nama = trim($row[1] ?? '');
				$alamat = trim($row[3] ?? '-');
				if (empty($nama)) {
					$nama = 'Nasabah T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);
				}
			}

			// Skip summary rows
			if (stripos($nama, 'JUMLAH') !== false || stripos($nama, 'TOTAL') !== false)
				continue;

			try {
				$saldo_sebelum = $this->_parse_amount($row[4] ?? 0);

				// Generate no_rekening with T prefix and 3 digits
				$norek_num = $no_tab > 0 ? $no_tab : $no_urut;
				$no_rekening = 'T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);

				// Find or create nasabah using no_rekening as key (since nama might be placeholder)
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

				// Get bunga from column 99 (BUNGA) or column 101 (BUNGA RIIL)
				$bunga = $this->_parse_amount($row[101] ?? $row[99] ?? 0);
				if ($bunga > 0) {
					$results['bunga']['total'] += $bunga;
					$results['bunga']['count']++;
				}

				// Get saldo akhir from column 102 (SALDO BULAN INI) - the actual balance
				$saldo_akhir = $this->_parse_amount($row[102] ?? 0);

				// Create simpanan master record with T001 format
				$simpanan_data = [
					'no_rekening' => $no_rekening,
					'nasabah_id' => $nasabah_id,
					'pegawai_id' => $pegawai_id,
					'jenistabungan_id' => $jenistabungan_id,
					'nama_nasabah' => $nama,
					'jumlah_simpanan' => $saldo_akhir,
					'jumlah_bunga' => $bunga,
					'tanggal_simpanan' => $year . '-12-01',
					'status' => 'aktif'
				];
				$this->db->insert('tbsimpanan', $simpanan_data);
				$simpanan_id = $this->db->insert_id();
				$results['simpanan']['inserted']++;

				$norek_to_id[$no_rekening] = $simpanan_id;

				// INSERT SALDO SEBELUM AS INITIAL DEPOSIT
				if ($saldo_sebelum > 0) {
					$this->db->insert('tbdetail_simpanan', [
						'simpanan_id' => $simpanan_id,
						'tanggal_setoran' => $year . '-12-01 00:00:01', // Anfang des Monats
						'jumlah_setoran' => $saldo_sebelum,
						'pegawai_id' => $pegawai_id
					]);
					$results['setoran']['inserted']++;
				}

				// Process daily SETORAN (columns 5-35 for days 1-31)
				for ($day = 1; $day <= 31; $day++) {
					$col = 4 + $day; // Column 5 = day 1
					$amount = $this->_parse_amount($row[$col] ?? 0);

					if ($amount > 0) {
						$date = sprintf('%s-%s-%02d', $year, $month, $day);
						if (checkdate((int) $month, $day, (int) $year)) {
							$this->db->insert('tbdetail_simpanan', [
								'simpanan_id' => $simpanan_id,
								'tanggal_setoran' => $date . ' 12:00:00',
								'jumlah_setoran' => $amount,
								'pegawai_id' => $pegawai_id
							]);
							$results['setoran']['inserted']++;
						}
					}
				}

				// Process daily PENARIKAN (columns 36-66 for days 1-31)
				for ($day = 1; $day <= 31; $day++) {
					$col = 35 + $day; // Column 36 = day 1
					$amount = $this->_parse_amount($row[$col] ?? 0);

					if ($amount > 0) {
						$date = sprintf('%s-%s-%02d', $year, $month, $day);
						if (checkdate((int) $month, $day, (int) $year)) {
							$this->db->insert('tbdetail_penarikan', [
								'simpanan_id' => $simpanan_id,
								'penarikan_id' => 0,
								'tanggal_penarikan' => $date . ' 12:00:00',
								'jumlah_penarikan' => $amount,
								'pegawai_id' => $pegawai_id,
								'status' => 'disetujui'
							]);
							$results['penarikan']['inserted']++;
						}
					}
				}

				// Insert bunga as tbtransaksi record
				if ($bunga > 0) {
					$this->db->insert('tbtransaksi', [
						'simpanan_id' => $simpanan_id,
						'no_rekening' => $no_rekening,
						'nama_nasabah' => $nama,
						'tanggal_transaksi' => $year . '-12-31',
						'jumlah_transaksi' => $bunga,
						'rate_bunga' => 0,
						'bunga_riil' => $bunga
					]);
				}

				$results['details'][] = [
					'row' => $i + 1,
					'nama' => $nama,
					'no_rekening' => $no_rekening,
					'bunga' => $bunga,
					'saldo_akhir' => $saldo_akhir,
					'action' => 'imported'
				];

			} catch (Exception $e) {
				$results['simpanan']['errors']++;
				$results['errors'][] = "Row " . ($i + 1) . ": " . $e->getMessage();
			}
		}

		$this->db->trans_complete();

		$results['success'] = $this->db->trans_status();
		$results['total_processed'] = count($norek_to_id);

		return $results;
	}

	/**
	 * Import using DES sheet + REKAP BUNGA sheet
	 * Creates complete tabungan records with annual interest
	 * 
	 * @param string $file_path Path to TABUNGAN 2026.xls file
	 * @param int $pegawai_id Employee ID
	 * @param int $jenistabungan_id Savings type ID
	 * @param bool $delete_existing Delete existing data first
	 * @return array Results
	 */
	public function import_with_rekap_bunga($file_path, $pegawai_id, $jenistabungan_id, $delete_existing = true)
	{
		ini_set('memory_limit', '2048M');
		ini_set('max_execution_time', 600);

		$results = [
			'success' => false,
			'batch_id' => 'FULL' . date('YmdHis'),
			'simpanan' => ['inserted' => 0, 'errors' => 0],
			'nasabah' => ['created' => 0, 'found' => 0],
			'bunga' => ['total' => 0, 'count' => 0],
			'deleted' => [],
			'errors' => []
		];

		if (!file_exists($file_path)) {
			$results['errors'][] = 'File tidak ditemukan: ' . $file_path;
			return $results;
		}

		// Use SimpleXLS for .xls files
		require_once APPPATH . 'third_party/SimpleXLS.php';
		$xls = \Shuchkin\SimpleXLS::parse($file_path);

		if (!$xls) {
			$results['errors'][] = 'Gagal membaca file: ' . \Shuchkin\SimpleXLS::parseError();
			return $results;
		}

		$sheets = $xls->sheetNames();
		$results['sheets_found'] = $sheets;

		// Find DES sheet (index 11) and REKAP BUNGA sheet (index 12)
		$des_idx = array_search('DES', $sheets);
		$rekap_idx = null;
		foreach ($sheets as $idx => $name) {
			if (stripos($name, 'REKAP') !== false) {
				$rekap_idx = $idx;
				break;
			}
		}

		if ($des_idx === false) {
			$results['errors'][] = 'Sheet DES tidak ditemukan';
			return $results;
		}
		if ($rekap_idx === null) {
			$results['errors'][] = 'Sheet REKAP BUNGA tidak ditemukan';
			return $results;
		}

		$des_rows = $xls->rows($des_idx);
		$rekap_rows = $xls->rows($rekap_idx);

		// Build bunga lookup from REKAP BUNGA sheet (by NO.TAB)
		// Structure: Col 2 = NO.TAB, Col 15 = Total Bunga
		$bunga_lookup = [];
		for ($i = 4; $i < count($rekap_rows); $i++) {
			$row = $rekap_rows[$i];
			$no_tab = trim((string) ($row[2] ?? ''));
			$total_bunga = $this->_parse_amount($row[15] ?? 0);
			if (!empty($no_tab) && $total_bunga > 0) {
				$bunga_lookup[$no_tab] = $total_bunga;
			}
		}
		$results['bunga_lookup_count'] = count($bunga_lookup);

		$this->db->trans_start();

		// Delete existing if requested
		if ($delete_existing) {
			$this->db->query("DELETE FROM tbtransaksi WHERE simpanan_id IN (SELECT id FROM tbsimpanan)");
			$results['deleted']['transaksi'] = $this->db->affected_rows();
			$this->db->query("DELETE FROM tbdetail_penarikan WHERE simpanan_id IN (SELECT id FROM tbsimpanan)");
			$this->db->query("DELETE FROM tbpenarikan WHERE simpanan_id IN (SELECT id FROM tbsimpanan)");
			$this->db->query("DELETE FROM tbdetail_simpanan WHERE simpanan_id IN (SELECT id FROM tbsimpanan)");
			$results['deleted']['setoran'] = $this->db->affected_rows();
			$this->db->query("DELETE FROM tbsimpanan");
			$results['deleted']['simpanan'] = $this->db->affected_rows();
			$this->db->query("DELETE FROM tbnasabah WHERE id NOT IN (SELECT DISTINCT nasabah_id FROM tbdeposito)");
			$results['deleted']['nasabah'] = $this->db->affected_rows();
		}

		$nama_to_nasabah = [];
		$norek_to_id = [];
		$year = '2025'; // Data is for 2025

		// PHASE 0.5: Build nama mapping from JAN sheet (since DES sheet has empty NAMA column)
		$jan_index = array_search('JAN', $sheets);
		if ($jan_index === false)
			$jan_index = 0;

		$noTab_to_nama = [];
		$jan_rows = $xls->rows($jan_index);
		for ($i = 3; $i < count($jan_rows); $i++) {
			$jan_row = $jan_rows[$i];
			$jan_no_tab = intval($jan_row[2] ?? 0);
			$jan_nama = trim($jan_row[1] ?? '');
			$jan_alamat = trim($jan_row[3] ?? '-');

			if ($jan_no_tab > 0 && !empty($jan_nama)) {
				$noTab_to_nama[$jan_no_tab] = [
					'nama' => $jan_nama,
					'alamat' => $jan_alamat
				];
			}
		}
		$results['nama_mapping_count'] = count($noTab_to_nama);

		// Process DES sheet starting from row 3 (data rows)
		for ($i = 3; $i < count($des_rows); $i++) {
			$row = $des_rows[$i];

			$no_tab = intval($row[2] ?? 0);
			$no_urut = intval($row[0] ?? 0);
			$norek_num = $no_tab > 0 ? $no_tab : $no_urut;

			// Skip if no valid identifier
			if ($norek_num <= 0)
				continue;

			// Get nama from JAN sheet mapping, fallback to DES sheet or placeholder
			if (isset($noTab_to_nama[$norek_num])) {
				$nama = $noTab_to_nama[$norek_num]['nama'];
				$alamat = $noTab_to_nama[$norek_num]['alamat'];
			} else {
				$nama = trim($row[1] ?? '');
				$alamat = trim($row[3] ?? '-');
				if (empty($nama)) {
					$nama = 'Nasabah T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);
				}
			}
			if (stripos($nama, 'JUMLAH') !== false || stripos($nama, 'TOTAL') !== false)
				continue;

			try {
				$saldo_awal = $this->_parse_amount($row[4] ?? 0);
				$saldo_akhir = $this->_parse_amount($row[102] ?? 0);

				// Get bunga from lookup (use string key for bunga_lookup)
				$no_tab_str = trim((string) $norek_num);
				$bunga = $bunga_lookup[$no_tab_str] ?? 0;

				// Generate no_rekening: T + 3 digit padded
				$no_rekening = 'T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);

				// Find or create nasabah
				$nama_lower = strtolower(trim($nama));
				if (isset($nama_to_nasabah[$nama_lower])) {
					$nasabah_id = $nama_to_nasabah[$nama_lower];
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
					$nama_to_nasabah[$nama_lower] = $nasabah_id;
				}

				// Check if simpanan exists
				$existing = $this->db->where('no_rekening', $no_rekening)->get('tbsimpanan')->row();

				if (!$existing) {
					$simpanan_data = [
						'no_rekening' => $no_rekening,
						'nasabah_id' => $nasabah_id,
						'pegawai_id' => $pegawai_id,
						'jenistabungan_id' => $jenistabungan_id,
						'nama_nasabah' => $nama,
						'jumlah_simpanan' => $saldo_akhir,
						'jumlah_bunga' => $bunga,
						'tanggal_simpanan' => $year . '-01-01',
						'status' => 'aktif'
					];
					$this->db->insert('tbsimpanan', $simpanan_data);
					$simpanan_id = $this->db->insert_id();
					$results['simpanan']['inserted']++;
				} else {
					$simpanan_id = $existing->id;
					$this->db->where('id', $simpanan_id)->update('tbsimpanan', [
						'jumlah_simpanan' => $saldo_akhir,
						'jumlah_bunga' => $bunga
					]);
				}
				$norek_to_id[$no_rekening] = $simpanan_id;

				// Insert saldo awal as opening balance transaction
				if ($saldo_awal > 0) {
					$this->db->insert('tbdetail_simpanan', [
						'simpanan_id' => $simpanan_id,
						'tanggal_setoran' => $year . '-01-01 00:00:01',
						'jumlah_setoran' => $saldo_awal,
						'pegawai_id' => $pegawai_id
					]);
				}

				// Insert bunga as end-of-year transaction
				if ($bunga > 0) {
					$this->db->insert('tbtransaksi', [
						'simpanan_id' => $simpanan_id,
						'tanggal_transaksi' => $year . '-12-31',
						'jenis_transaksi' => 'Bunga Tahunan',
						'jumlah_transaksi' => $bunga,
						'rate_bunga' => 0,
						'bunga_riil' => $bunga
					]);
					$results['bunga']['total'] += $bunga;
					$results['bunga']['count']++;
				}

			} catch (Exception $e) {
				$results['simpanan']['errors']++;
				$results['errors'][] = "Row " . ($i + 1) . ": " . $e->getMessage();
			}
		}

		$this->db->trans_complete();

		$results['success'] = $this->db->trans_status();
		$results['total_processed'] = count($norek_to_id);

		return $results;
	}

	/**
	 * Import from specific month sheet (e.g., FEB for February)
	 * Imports setoran/penarikan per day for the specified month
	 * Deletes existing transactions for that month before import
	 * 
	 * @param string $file_path Path to Excel file (TABUNGAN 2026.xls)
	 * @param string $month_code Month code (JAN, FEB, MAR, APR, MEI, JUNI, JULI, AGS, SEP, OKT, NOP, DES)
	 * @param string $year Year (2026)
	 * @param int $pegawai_id Employee ID
	 * @param int $jenistabungan_id Savings type ID  
	 * @param bool $delete_month Delete existing transactions for this month
	 * @return array Results
	 */
	public function import_by_month($file_path, $month_code, $year, $pegawai_id, $jenistabungan_id, $delete_month = true)
	{
		ini_set('memory_limit', '2048M');
		ini_set('max_execution_time', 600);

		// Month mapping
		$month_map = [
			'JAN' => '01',
			'FEB' => '02',
			'MAR' => '03',
			'APR' => '04',
			'MEI' => '05',
			'JUNI' => '06',
			'JULI' => '07',
			'AGS' => '08',
			'SEP' => '09',
			'OKT' => '10',
			'NOP' => '11',
			'DES' => '12'
		];

		$month_code = strtoupper(trim($month_code));

		$results = [
			'success' => false,
			'batch_id' => $month_code . date('YmdHis'),
			'importing_sheet' => $month_code . ' (' . $year . ')',
			'simpanan' => ['inserted' => 0, 'updated' => 0, 'errors' => 0],
			'nasabah' => ['created' => 0, 'found' => 0],
			'setoran' => ['inserted' => 0],
			'penarikan' => ['inserted' => 0],
			'deleted' => ['setoran' => 0, 'penarikan' => 0],
			'details' => [],
			'errors' => []
		];

		if (!file_exists($file_path)) {
			$results['errors'][] = 'File tidak ditemukan: ' . $file_path;
			return $results;
		}

		if (!isset($month_map[$month_code])) {
			$results['errors'][] = 'Kode bulan tidak valid: ' . $month_code;
			return $results;
		}

		$month = $month_map[$month_code];

		// Detect file type and use appropriate library
		$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

		if ($file_ext === 'xlsx') {
			require_once APPPATH . 'third_party/SimpleXLSX.php';
			$xls = \Shuchkin\SimpleXLSX::parse($file_path);
			if (!$xls) {
				$results['errors'][] = 'Gagal membaca file Excel (.xlsx): ' . \Shuchkin\SimpleXLSX::parseError();
				return $results;
			}
		} else {
			require_once APPPATH . 'third_party/SimpleXLS.php';
			$xls = \Shuchkin\SimpleXLS::parse($file_path);
			if (!$xls) {
				$results['errors'][] = 'Gagal membaca file Excel (.xls): ' . \Shuchkin\SimpleXLS::parseError();
				return $results;
			}
		}

		$sheets = $xls->sheetNames();
		$results['sheets_found'] = $sheets;

		// Find sheet by month code
		$sheet_index = array_search($month_code, $sheets);
		if ($sheet_index === false) {
			// Try case-insensitive search
			foreach ($sheets as $idx => $name) {
				if (strtoupper(trim($name)) === $month_code) {
					$sheet_index = $idx;
					break;
				}
			}
		}

		if ($sheet_index === false) {
			$results['errors'][] = 'Sheet ' . $month_code . ' tidak ditemukan dalam file Excel';
			return $results;
		}

		// No transaction wrapper - direct commits for reliability

		// Disable FK checks for import (to allow penarikan_id = 0)
		$this->db->query('SET FOREIGN_KEY_CHECKS=0');

		// PHASE 0: Delete existing transactions for the month if requested
		if ($delete_month) {
			$date_start = $year . '-' . $month . '-01';
			$date_end = $year . '-' . $month . '-31';

			// Delete setoran for this month
			$this->db->where('tanggal_setoran >=', $date_start)
				->where('tanggal_setoran <=', $date_end . ' 23:59:59')
				->delete('tbdetail_simpanan');
			$results['deleted']['setoran'] = $this->db->affected_rows();

			// Delete penarikan for this month
			$this->db->where('tanggal_penarikan >=', $date_start)
				->where('tanggal_penarikan <=', $date_end . ' 23:59:59')
				->delete('tbdetail_penarikan');
			$results['deleted']['penarikan'] = $this->db->affected_rows();
		}

		// Get sheet rows
		$rows = $xls->rows($sheet_index);
		$nama_to_nasabah = [];
		$norek_to_id = [];
		$norek_saldo_awal = []; // Track saldo_awal per account (NOT inserted as setoran)

		// PHASE 0.5: Build nama mapping from JAN sheet (since some sheets have empty NAMA column)
		// JAN sheet has NAMA in Col[1], NO_TAB in Col[2]
		$jan_index = array_search('JAN', $sheets);
		if ($jan_index === false)
			$jan_index = 0;

		$noTab_to_nama = [];
		$jan_rows = $xls->rows($jan_index);
		for ($i = 3; $i < count($jan_rows); $i++) {
			$jan_row = $jan_rows[$i];
			$jan_no_tab = intval($jan_row[2] ?? 0);
			$jan_nama = trim($jan_row[1] ?? '');
			$jan_alamat = trim($jan_row[3] ?? '-');

			if ($jan_no_tab > 0 && !empty($jan_nama)) {
				$noTab_to_nama[$jan_no_tab] = [
					'nama' => $jan_nama,
					'alamat' => $jan_alamat
				];
			}
		}
		$results['nama_mapping_count'] = count($noTab_to_nama);

		// COLUMN OFFSET: FEB starts at column C (offset 2), other months start at column A (offset 0)
		$col_offset = ($month_code === 'FEB') ? 2 : 0;

		// DEBUG: Track column info
		$results['debug'] = [
			'total_rows' => count($rows),
			'first_row_col_count' => isset($rows[3]) ? count($rows[3]) : 0,
			'col_offset' => $col_offset,
			'month_code' => $month_code
		];

		// PHASE 1: Process each row (nasabah) and create/update simpanan + transactions
		for ($i = 3; $i < count($rows); $i++) {
			$row = $rows[$i];

			// Apply column offset
			$no_urut = intval($row[$col_offset + 0] ?? 0);
			$no_tab = intval($row[$col_offset + 2] ?? 0);

			// Skip if no valid identifier
			if ($no_urut <= 0 && $no_tab <= 0)
				continue;

			// Get nama - lookup from JAN sheet mapping, fallback to placeholder
			$norek_num = $no_tab > 0 ? $no_tab : $no_urut;
			if (isset($noTab_to_nama[$norek_num])) {
				$nama = $noTab_to_nama[$norek_num]['nama'];
				$alamat = $noTab_to_nama[$norek_num]['alamat'];
			} else {
				// Fallback to sheet column (usually empty for non-JAN sheets)
				$nama = trim($row[$col_offset + 1] ?? '');
				$alamat = trim($row[$col_offset + 3] ?? '-');
				if (empty($nama)) {
					$nama = 'Nasabah T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);
				}
			}

			// Skip summary rows
			if (stripos($nama, 'JUMLAH') !== false || stripos($nama, 'TOTAL') !== false)
				continue;

			try {
				$saldo_sebelum = $this->_parse_amount($row[$col_offset + 4] ?? 0);

				// Generate no_rekening with T prefix and 3 digits
				$norek_num = $no_tab > 0 ? $no_tab : $no_urut;
				$no_rekening = 'T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);

				// FIX: Check existing simpanan FIRST to avoid wrong nasabah matching
				$existing = $this->db->where('no_rekening', $no_rekening)->get('tbsimpanan')->row();

				if ($existing) {
					// Existing account — use its nasabah_id (no name matching needed)
					$simpanan_id = $existing->id;
					$nasabah_id = $existing->nasabah_id;
					$results['nasabah']['found']++;
					$results['simpanan']['updated']++;

					// Update nama in BOTH tbsimpanan AND tbnasabah to keep them in sync
					$this->db->where('id', $simpanan_id)->update('tbsimpanan', [
						'nama_nasabah' => $nama
					]);
					$this->db->where('id', $nasabah_id)->update('tbnasabah', [
						'nama_lengkap' => $nama,
						'alamat' => $alamat ?: '-'
					]);
				} else {
					// New account — find or create nasabah by exact name
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

					// Create simpanan master record
					$simpanan_data = [
						'no_rekening' => $no_rekening,
						'nasabah_id' => $nasabah_id,
						'pegawai_id' => $pegawai_id,
						'jenistabungan_id' => $jenistabungan_id,
						'nama_nasabah' => $nama,
						'jumlah_simpanan' => $saldo_sebelum,
						'jumlah_bunga' => 0,
						'tanggal_simpanan' => $year . '-' . $month . '-01',
						'status' => 'aktif'
					];
					$this->db->insert('tbsimpanan', $simpanan_data);
					$simpanan_id = $this->db->insert_id();
					$results['simpanan']['inserted']++;
				}

				// FIX: saldo_awal stored in tbsimpanan.jumlah_simpanan (NOT as setoran)
				// This prevents ghost setoran records from appearing in rekapitulasi harian
				$norek_saldo_awal[$no_rekening] = $saldo_sebelum;

				$norek_to_id[$no_rekening] = $simpanan_id;

				// Process daily SETORAN (columns 5-35 for days 1-31, adjusted for offset)
				for ($day = 1; $day <= 31; $day++) {
					$col = $col_offset + 4 + $day; // Offset + Column 5 = day 1
					$amount = $this->_parse_amount($row[$col] ?? 0);

					if ($amount > 0) {
						// Validate date
						if (checkdate((int) $month, $day, (int) $year)) {
							$date = sprintf('%s-%s-%02d', $year, $month, $day);
							$this->db->insert('tbdetail_simpanan', [
								'simpanan_id' => $simpanan_id,
								'tanggal_setoran' => $date . ' 12:00:00',
								'jumlah_setoran' => $amount,
								'pegawai_id' => $pegawai_id
							]);
							$results['setoran']['inserted']++;
						}
					}
				}

				// Process daily PENARIKAN (columns 36-66 for days 1-31)
				// NOTE: Penarikan columns are at FIXED positions (AK onwards = index 36+)
				// regardless of metadata offset, so we don't apply col_offset here
				for ($day = 1; $day <= 31; $day++) {
					$col = 35 + $day; // Column 36 = day 1 (NO offset applied)
					$raw_value = $row[$col] ?? '';
					$amount = $this->_parse_amount($raw_value);

					// DEBUG: Track if we found penarikan amounts
					if (!isset($results['debug']['penarikan_found'])) {
						$results['debug']['penarikan_found'] = 0;
					}
					if ($amount > 0) {
						$results['debug']['penarikan_found']++;
					}

					if ($amount > 0) {
						// Validate date
						if (checkdate((int) $month, $day, (int) $year)) {
							$date = sprintf('%s-%s-%02d', $year, $month, $day);
							$insert_result = $this->db->insert('tbdetail_penarikan', [
								'simpanan_id' => $simpanan_id,
								'penarikan_id' => 0, // Use 0 as default (column doesn't allow NULL)
								'tanggal_penarikan' => $date . ' 12:00:00',
								'jumlah_penarikan' => $amount,
								'pegawai_id' => $pegawai_id,
								'status' => 'disetujui'
							]);
							if ($insert_result) {
								$results['penarikan']['inserted']++;
							} else {
								$results['debug']['penarikan_insert_error'] = $this->db->error();
							}
						}
					}
				}

				// IMPORT BUNGA (Column 101) - Added to match Excel calculation
				// Logic: Bunga (Net) is in Column 101. Add as Setoran at end of month.
				$bunga_net = $this->_parse_amount($row[101] ?? 0);
				if ($bunga_net > 0) {
					// Use end of month date
					$last_day = date('t', strtotime("$year-$month-01"));
					$date_bunga = "$year-$month-$last_day 23:55:00";

					// Insert as setoran
					$this->db->insert('tbdetail_simpanan', [
						'simpanan_id' => $simpanan_id,
						'tanggal_setoran' => $date_bunga,
						'jumlah_setoran' => $bunga_net,
						'pegawai_id' => $pegawai_id
					]);
					$results['setoran']['inserted']++;
				}

				$results['details'][] = [
					'row' => $i + 1,
					'nama' => $nama,
					'no_rekening' => $no_rekening,
					'action' => $existing ? 'updated' : 'created'
				];

			} catch (Exception $e) {
				$results['simpanan']['errors']++;
				$results['errors'][] = "Row " . ($i + 1) . ": " . $e->getMessage();
			}
		}

		// PHASE 2: Update saldo for each simpanan
		// FIX: Use saldo_awal + setoran - penarikan (not depend on ghost setoran records)
		foreach ($norek_to_id as $no_rekening => $simpanan_id) {
			try {
				// Get saldo_awal for this account
				$saldo_awal = isset($norek_saldo_awal[$no_rekening]) ? $norek_saldo_awal[$no_rekening] : 0;

				// Calculate total setoran (actual deposits only, no saldo_awal)
				$setoran_result = $this->db->select_sum('jumlah_setoran')
					->where('simpanan_id', $simpanan_id)
					->get('tbdetail_simpanan')
					->row();
				$total_setoran = ($setoran_result && $setoran_result->jumlah_setoran) ? floatval($setoran_result->jumlah_setoran) : 0;

				// Calculate total penarikan
				$penarikan_result = $this->db->select_sum('jumlah_penarikan')
					->where('simpanan_id', $simpanan_id)
					->get('tbdetail_penarikan')
					->row();
				$total_penarikan = ($penarikan_result && $penarikan_result->jumlah_penarikan) ? floatval($penarikan_result->jumlah_penarikan) : 0;

				// SALDO = SALDO_AWAL + SETORAN - PENARIKAN
				$saldo = $saldo_awal + $total_setoran - $total_penarikan;
				$this->db->where('id', $simpanan_id)
					->update('tbsimpanan', ['jumlah_simpanan' => $saldo]);
			} catch (Exception $e) {
				// Log error but don't fail the entire transaction
				$results['errors'][] = "Saldo update error for $no_rekening: " . $e->getMessage();
			}
		}

		// Re-enable FK checks
		$this->db->query('SET FOREIGN_KEY_CHECKS=1');

		$results['success'] = true; // Data committed directly
		$results['total_processed'] = count($norek_to_id);

		return $results;
	}

	/**
	 * Get sheet names from an Excel file
	 * 
	 * @param string $file_path Path to Excel file
	 * @return array List of sheet names
	 */
	public function get_excel_sheet_names($file_path)
	{
		// Increase limits for large Excel files
		ini_set('memory_limit', '1024M');
		ini_set('max_execution_time', 300);

		$result = [
			'success' => false,
			'sheets' => [],
			'errors' => []
		];

		if (!file_exists($file_path)) {
			$result['errors'][] = 'File tidak ditemukan: ' . $file_path;
			return $result;
		}

		$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

		if ($file_ext === 'xlsx') {
			require_once APPPATH . 'third_party/SimpleXLSX.php';
			$xls = \Shuchkin\SimpleXLSX::parse($file_path);
			if (!$xls) {
				$result['errors'][] = 'Gagal membaca file: ' . \Shuchkin\SimpleXLSX::parseError();
				return $result;
			}
		} else {
			require_once APPPATH . 'third_party/SimpleXLS.php';
			$xls = \Shuchkin\SimpleXLS::parse($file_path);
			if (!$xls) {
				$result['errors'][] = 'Gagal membaca file: ' . \Shuchkin\SimpleXLS::parseError();
				return $result;
			}
		}

		$sheets = $xls->sheetNames();
		$result['sheets'] = [];

		foreach ($sheets as $index => $name) {
			$result['sheets'][] = [
				'index' => $index,
				'name' => $name
			];
		}

		$result['success'] = true;
		return $result;
	}

	/**
	 * Preview sheet data for column mapping
	 * Returns first N rows of a specific sheet with column headers
	 * 
	 * @param string $file_path Path to Excel file
	 * @param string $month_code Month code (JAN, FEB, etc.)
	 * @param int $limit Number of rows to return
	 * @return array Preview data with headers and rows
	 */
	public function preview_sheet_data($file_path, $month_code, $limit = 10)
	{
		// Increase limits for large Excel files
		ini_set('memory_limit', '2048M');
		ini_set('max_execution_time', 300);

		$result = [
			'success' => false,
			'sheet_name' => $month_code,
			'headers' => [],
			'rows' => [],
			'total_columns' => 0,
			'total_rows' => 0,
			'suggested_mapping' => [],
			'errors' => []
		];

		if (!file_exists($file_path)) {
			$result['errors'][] = 'File tidak ditemukan: ' . $file_path;
			return $result;
		}

		// Detect file type
		$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

		if ($file_ext === 'xlsx') {
			require_once APPPATH . 'third_party/SimpleXLSX.php';
			$xls = \Shuchkin\SimpleXLSX::parse($file_path);
			if (!$xls) {
				$result['errors'][] = 'Gagal membaca file: ' . \Shuchkin\SimpleXLSX::parseError();
				return $result;
			}
		} else {
			require_once APPPATH . 'third_party/SimpleXLS.php';
			$xls = \Shuchkin\SimpleXLS::parse($file_path);
			if (!$xls) {
				$result['errors'][] = 'Gagal membaca file: ' . \Shuchkin\SimpleXLS::parseError();
				return $result;
			}
		}

		$sheets = $xls->sheetNames();
		$result['available_sheets'] = $sheets;

		// Find sheet by month code
		$sheet_index = array_search(strtoupper($month_code), array_map('strtoupper', $sheets));
		if ($sheet_index === false) {
			$result['errors'][] = 'Sheet ' . $month_code . ' tidak ditemukan';
			return $result;
		}

		$rows = $xls->rows($sheet_index);
		$result['total_rows'] = count($rows);
		$result['total_columns'] = isset($rows[0]) ? count($rows[0]) : 0;

		// Generate column letters (A, B, C, ... AA, AB, etc.)
		$col_letters = [];
		for ($i = 0; $i < $result['total_columns']; $i++) {
			$col_letters[] = $this->_column_letter($i);
		}
		$result['column_letters'] = $col_letters;

		// Get header row (row 3 typically contains headers)
		if (isset($rows[2])) {
			$result['headers'] = array_map(function ($val) {
				return trim((string) $val);
			}, $rows[2]);
		}

		// Build name mapping from JAN sheet (for non-JAN sheets)
		$jan_name_map = [];
		$is_jan_sheet = strtoupper($month_code) === 'JAN';

		if (!$is_jan_sheet) {
			$jan_index = array_search('JAN', array_map('strtoupper', $sheets));
			if ($jan_index !== false) {
				$jan_rows = $xls->rows($jan_index);
				// Default JAN column indices: 0=NO, 1=NAMA, 2=NO_TAB, 3=ALAMAT
				for ($i = 3; $i < count($jan_rows); $i++) {
					$jan_row = $jan_rows[$i];
					$jan_no_tab = intval($jan_row[2] ?? 0);
					$jan_nama = trim($jan_row[1] ?? '');
					$jan_alamat = trim($jan_row[3] ?? '-');

					if ($jan_no_tab > 0 && !empty($jan_nama)) {
						$jan_name_map[$jan_no_tab] = [
							'nama' => $jan_nama,
							'alamat' => $jan_alamat
						];
					}
				}
			}
		}
		$result['jan_name_map'] = $jan_name_map;
		$result['is_jan_sheet'] = $is_jan_sheet;

		// Get data rows (starting from row 4)
		for ($i = 3; $i < min(count($rows), 3 + $limit); $i++) {
			$row_data = [];
			foreach ($rows[$i] as $idx => $cell) {
				$row_data[] = [
					'col_index' => $idx,
					'col_letter' => $this->_column_letter($idx),
					'value' => $this->_format_preview_value($cell)
				];
			}
			$result['rows'][] = $row_data;
		}

		// Try to auto-detect column mapping based on month
		$result['suggested_mapping'] = $this->_detect_column_mapping($rows, $month_code);

		$result['success'] = true;
		return $result;
	}

	/**
	 * Convert column index to Excel letter (0=A, 1=B, 26=AA, etc.)
	 */
	private function _column_letter($index)
	{
		$letter = '';
		while ($index >= 0) {
			$letter = chr(65 + ($index % 26)) . $letter;
			$index = intval($index / 26) - 1;
		}
		return $letter;
	}

	/**
	 * Format cell value for preview display
	 */
	private function _format_preview_value($value)
	{
		if ($value === null || $value === '') {
			return '';
		}
		if (is_numeric($value) && $value > 1000000) {
			return number_format($value, 0, ',', '.');
		}
		return (string) $value;
	}

	/**
	 * Auto-detect column mapping based on header keywords
	 */
	private function _detect_column_mapping($rows, $month_code = '')
	{
		// Determine structure based on month:
		// JAN: NO(0), NAMA(1), NO_TAB(2), ALAMAT(3), SALDO(4), SETORAN(5-35), PENARIKAN(36-66)
		// FEB+: NO(0), NAMA(1), NO(2), NAMA(3), NO_TAB(4), ALAMAT(5), SALDO(6), SETORAN(7-35), PENARIKAN(36-64)

		$isJan = (strtoupper($month_code) === 'JAN');

		if ($isJan) {
			// JAN structure: NO(0), NAMA(1), NO_TAB(2), ALAMAT(3), SALDO(4)
			// SETORAN(5-35), PENARIKAN(36-66), SALDO_HARIAN(67-97)
			// E-MIN(98), BUNGA_RAW(99), BULAT(100), BUNGA_BULAT(101), SALDO_AKHIR(102)
			$mapping = [
				'no_urut' => 0,
				'nama' => 1,
				'no_tab' => 2,
				'alamat' => 3,
				'saldo_awal' => 4,
				'setoran_start' => 5,
				'setoran_end' => 35,
				'penarikan_start' => 36,
				'penarikan_end' => 66,
				'e_min' => 98,             // E-MIN (base saldo for bunga calc)
				'bunga_raw' => 99,         // BUNGA raw (E-MIN * rate)
				'bunga' => 101             // BUNGA BULAT (rounded interest)
			];
		} else {
			// FEB+ structure: NO(0), NAMA(1), NO(2), NAMA(3), NO_TAB(4), ALAMAT(5), SALDO(6)
			// SETORAN(7-35), PENARIKAN(36-64), SALDO_HARIAN(65-93)
			// E-MIN(94), BUNGA_RAW(95), BULAT(96), BUNGA_BULAT(97), SALDO_AKHIR(98)
			$mapping = [
				'no_urut' => 0,
				'nama' => 1,
				'no_tab' => 4,
				'alamat' => 5,
				'saldo_awal' => 6,
				'setoran_start' => 7,
				'setoran_end' => 35,
				'penarikan_start' => 36,
				'penarikan_end' => 64,
				'e_min' => 94,             // E-MIN (base saldo for bunga calc)
				'bunga_raw' => 95,         // BUNGA raw (E-MIN * rate)
				'bunga' => 97              // BUNGA BULAT (rounded interest)
			];
		}

		// Try to find SALDO header to verify/adjust mapping
		if (isset($rows[2])) {
			$headers = $rows[2];
			foreach ($headers as $idx => $header) {
				$h = strtoupper(trim((string) $header));

				// Find SALDO column to verify structure
				if (strpos($h, 'SALDO') !== false) {
					$mapping['saldo_awal'] = $idx;
					$mapping['setoran_start'] = $idx + 1;

					// Recalculate based on detected saldo position
					if ($idx == 4) {
						// JAN structure: setoran=5-35, penarikan=36-66, saldo_harian=67-97
						$mapping['setoran_end'] = 35;
						$mapping['penarikan_start'] = 36;
						$mapping['penarikan_end'] = 66;
						$mapping['e_min'] = 98;  // E-MIN for JAN
						$mapping['bunga_raw'] = 99; // BUNGA raw for JAN
						$mapping['bunga'] = 101; // BUNGA BULAT for JAN
					} else if ($idx == 6) {
						// FEB+ structure: setoran=7-35, penarikan=36-64, saldo_harian=65-93
						$mapping['setoran_end'] = 35;
						$mapping['penarikan_start'] = 36;
						$mapping['penarikan_end'] = 64;
						$mapping['e_min'] = 94;  // E-MIN for FEB+
						$mapping['bunga_raw'] = 95; // BUNGA raw for FEB+
						$mapping['bunga'] = 97;  // BUNGA BULAT for FEB+
					}
					break;
				}
			}
		}

		return $mapping;
	}

	/**
	 * Import from specific month sheet with user-defined column mapping
	 * 
	 * @param string $file_path Path to Excel file
	 * @param string $month_code Month code (JAN, FEB, etc.)
	 * @param string $year Year
	 * @param int $pegawai_id Employee ID
	 * @param int $jenistabungan_id Savings type ID
	 * @param array $mapping Column mapping array
	 * @param bool $delete_month Delete existing transactions for this month
	 * @return array Results
	 */
	public function import_by_month_with_mapping($file_path, $month_code, $year, $pegawai_id, $jenistabungan_id, $mapping, $delete_month = true)
	{
		// Debug Log
		$log_file = APPPATH . 'logs/import_debug_' . date('Y-m-d') . '.log';
		$log_msg = "\n[" . date('H:i:s') . "] START IMPORT MAPPING\n";
		$log_msg .= "File: $file_path\nMonth: $month_code, Year: $year\n";
		$log_msg .= "Mapping: " . json_encode($mapping) . "\n";

		ini_set('memory_limit', '2048M');
		ini_set('max_execution_time', 600);

		// Month mapping
		$month_map = [
			'JAN' => '01',
			'FEB' => '02',
			'MAR' => '03',
			'APR' => '04',
			'MEI' => '05',
			'JUNI' => '06',
			'JULI' => '07',
			'AGS' => '08',
			'SEP' => '09',
			'OKT' => '10',
			'NOP' => '11',
			'DES' => '12'
		];

		// Clean month code to handle potential extra chars
		// Remove any numeric prefix like "0: " or "1: "
		$clean_month_code = preg_replace('/^\d+:\s*/', '', $month_code);
		$clean_month_code = strtoupper(trim($clean_month_code));

		// If clean code is not in map, try to find a key that is contained in it
		if (!isset($month_map[$clean_month_code])) {
			foreach ($month_map as $key => $val) {
				if (stripos($clean_month_code, $key) !== false) {
					$clean_month_code = $key;
					break;
				}
			}
		}

		$log_msg .= "Clean Month Code: $clean_month_code\n";

		// Use the clean code for logic
		$month = $month_map[$clean_month_code] ?? null;

		$results = [
			'success' => false,
			'batch_id' => $clean_month_code . date('YmdHis'),
			'importing_sheet' => $month_code . ' (' . $year . ')',
			'mapping_used' => $mapping,
			'simpanan' => ['inserted' => 0, 'updated' => 0, 'errors' => 0],
			'nasabah' => ['created' => 0, 'found' => 0],
			'setoran' => ['inserted' => 0],
			'penarikan' => ['inserted' => 0],
			'bunga' => ['inserted' => 0],
			'deleted' => ['setoran' => 0, 'penarikan' => 0, 'bunga' => 0],
			'details' => [],
			'errors' => []
		];

		if (!file_exists($file_path)) {
			$log_msg .= "ERROR: File not found\n";
			file_put_contents($log_file, $log_msg, FILE_APPEND);
			$results['errors'][] = 'File tidak ditemukan: ' . $file_path;
			return $results;
		}

		if (!$month) {
			$log_msg .= "ERROR: Invalid month map for $clean_month_code\n";
			file_put_contents($log_file, $log_msg, FILE_APPEND);
			$results['errors'][] = 'Kode bulan tidak valid: ' . $month_code;
			return $results;
		}

		$month = $month_map[$clean_month_code];

		// Detect file type
		$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

		if ($file_ext === 'xlsx') {
			require_once APPPATH . 'third_party/SimpleXLSX.php';
			$xls = \Shuchkin\SimpleXLSX::parse($file_path);
			if (!$xls) {
				$results['errors'][] = 'Gagal membaca file Excel (.xlsx): ' . \Shuchkin\SimpleXLSX::parseError();
				return $results;
			}
		} else {
			require_once APPPATH . 'third_party/SimpleXLS.php';
			$xls = \Shuchkin\SimpleXLS::parse($file_path);
			if (!$xls) {
				$results['errors'][] = 'Gagal membaca file Excel (.xls): ' . \Shuchkin\SimpleXLS::parseError();
				return $results;
			}
		}

		$sheets = $xls->sheetNames();

		// Find sheet by month code
		$sheet_index = array_search($month_code, array_map('strtoupper', $sheets));
		if ($sheet_index === false) {
			$results['errors'][] = 'Sheet ' . $month_code . ' tidak ditemukan';
			return $results;
		}

		// Disable FK checks
		$this->db->query('SET FOREIGN_KEY_CHECKS=0');

		// Delete existing transactions for the month if requested
		if ($delete_month) {
			$date_start = $year . '-' . $month . '-01';
			$date_end = $year . '-' . $month . '-31';

			// Delete ALL setoran for this month
			$this->db->where('tanggal_setoran >=', $date_start)
				->where('tanggal_setoran <=', $date_end . ' 23:59:59')
				->delete('tbdetail_simpanan');
			$results['deleted']['setoran'] = $this->db->affected_rows();

			// Delete ALL penarikan for this month
			$this->db->where('tanggal_penarikan >=', $date_start)
				->where('tanggal_penarikan <=', $date_end . ' 23:59:59')
				->delete('tbdetail_penarikan');
			$results['deleted']['penarikan'] = $this->db->affected_rows();

			// Delete ALL bunga (tbtransaksi) for this month
			$this->db->where('tanggal_transaksi >=', $date_start)
				->where('tanggal_transaksi <=', $date_end . ' 23:59:59')
				->delete('tbtransaksi');
			$results['deleted']['bunga'] = $this->db->affected_rows();

			// Reset jumlah_bunga to 0 for all simpanan
			$this->db->update('tbsimpanan', ['jumlah_bunga' => 0]);
		}

		$rows = $xls->rows($sheet_index);
		$nama_to_nasabah = [];
		$norek_to_id = [];
		$norek_saldo_awal = []; // Track saldo_awal per account for final calculation

		// Get column indices from mapping
		$col_no_urut = $mapping['no_urut'];
		$col_nama = $mapping['nama'];
		$col_no_tab = $mapping['no_tab'];
		$col_alamat = $mapping['alamat'];
		$col_saldo = $mapping['saldo_awal'];
		$col_setoran_start = $mapping['setoran_start'];
		$col_setoran_end = $mapping['setoran_end'] ?? ($col_setoran_start + 28); // Default 29 columns
		$col_penarikan_start = $mapping['penarikan_start'];
		$col_penarikan_end = $mapping['penarikan_end'] ?? ($col_penarikan_start + 28); // Default 29 columns
		$col_bunga = $mapping['bunga'] ?? ($col_penarikan_end + 1);
		$col_e_min = $mapping['e_min'] ?? ($col_bunga - 3);
		$col_bunga_raw = $mapping['bunga_raw'] ?? ($col_bunga - 2);

		// Build nama mapping from JAN sheet
		$jan_index = array_search('JAN', array_map('strtoupper', $sheets));
		if ($jan_index === false)
			$jan_index = 0;

		$noTab_to_nama = [];
		$jan_rows = $xls->rows($jan_index);
		for ($i = 3; $i < count($jan_rows); $i++) {
			$jan_row = $jan_rows[$i];
			// For JAN sheet, use default indices (0, 1, 2, 3)
			$jan_no_tab = intval($jan_row[2] ?? 0);
			$jan_nama = trim($jan_row[1] ?? '');
			$jan_alamat = trim($jan_row[3] ?? '-');

			if ($jan_no_tab > 0 && !empty($jan_nama)) {
				$noTab_to_nama[$jan_no_tab] = [
					'nama' => $jan_nama,
					'alamat' => $jan_alamat
				];
			}
		}

		// Process rows
		$log_msg = "Rows found: " . count($rows) . "\n";
		file_put_contents($log_file, $log_msg, FILE_APPEND);

		// Debug: Dump first data row structure
		if (isset($rows[3])) {
			$log_msg = "Row[3] has " . count($rows[3]) . " columns\n";
			$log_msg .= "Row[3] content (first 40 cols): " . json_encode(array_slice($rows[3], 0, 40)) . "\n";
			file_put_contents($log_file, $log_msg, FILE_APPEND);
		}

		for ($i = 3; $i < count($rows); $i++) {
			$row = $rows[$i];

			$no_urut = intval($row[$col_no_urut] ?? 0);
			$no_tab = intval($row[$col_no_tab] ?? 0);

			if ($no_urut <= 0 && $no_tab <= 0)
				continue;

			$norek_num = $no_tab > 0 ? $no_tab : $no_urut;

			// Get nama from mapping
			if (isset($noTab_to_nama[$norek_num])) {
				$nama = $noTab_to_nama[$norek_num]['nama'];
				$alamat = $noTab_to_nama[$norek_num]['alamat'];
			} else {
				$nama = trim($row[$col_nama] ?? '');
				$alamat = trim($row[$col_alamat] ?? '-');
				if (empty($nama)) {
					$nama = 'Nasabah T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);
				}
			}

			if (stripos($nama, 'JUMLAH') !== false || stripos($nama, 'TOTAL') !== false)
				continue;

			try {
				$saldo_sebelum = $this->_parse_amount($row[$col_saldo] ?? 0);
				$no_rekening = 'T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);

				// FIX: Check existing simpanan FIRST to avoid wrong nasabah matching
				$existing = $this->db->where('no_rekening', $no_rekening)->get('tbsimpanan')->row();

				if ($existing) {
					// Existing account — use its nasabah_id (no name matching needed)
					$simpanan_id = $existing->id;
					$nasabah_id = $existing->nasabah_id;
					$results['nasabah']['found']++;
					$results['simpanan']['updated']++;

					// Update nama in BOTH tbsimpanan AND tbnasabah to keep them in sync
					$this->db->where('id', $simpanan_id)->update('tbsimpanan', [
						'nama_nasabah' => $nama
					]);
					$this->db->where('id', $nasabah_id)->update('tbnasabah', [
						'nama_lengkap' => $nama,
						'alamat' => $alamat ?: '-'
					]);
				} else {
					// New account — find or create nasabah by exact name
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

					// Create simpanan master record
					$simpanan_data = [
						'no_rekening' => $no_rekening,
						'nasabah_id' => $nasabah_id,
						'pegawai_id' => $pegawai_id,
						'jenistabungan_id' => $jenistabungan_id,
						'nama_nasabah' => $nama,
						'jumlah_simpanan' => $saldo_sebelum,
						'jumlah_bunga' => 0,
						'tanggal_simpanan' => $year . '-' . $month . '-01',
						'status' => 'aktif'
					];
					$this->db->insert('tbsimpanan', $simpanan_data);
					$simpanan_id = $this->db->insert_id();
					$results['simpanan']['inserted']++;
				}

				// Store saldo_awal for this account (used in final saldo calculation)
				// saldo_sebelum goes directly to tbsimpanan.jumlah_simpanan, NOT as setoran transaction
				$norek_saldo_awal[$no_rekening] = $saldo_sebelum;

				$norek_to_id[$no_rekening] = $simpanan_id;

				// Process SETORAN columns from start to end (each column = 1 day)
				$setoran_cols = $col_setoran_end - $col_setoran_start + 1;

				// Log first row processing
				if ($i == 3) {
					$log_msg = "Row 3 Debug:\nSetoran Cols: $setoran_cols (Start: $col_setoran_start, End: $col_setoran_end)\n";
					file_put_contents($log_file, $log_msg, FILE_APPEND);
				}

				for ($day = 1; $day <= $setoran_cols; $day++) {
					$col = $col_setoran_start + ($day - 1);
					$raw_val = $row[$col] ?? 0;
					$amount = $this->_parse_amount($raw_val);

					// Debug: Log first few setoran values for first row
					if ($i == 3 && $day <= 5) {
						$log_msg = "Row3 Day$day: Col=$col, Raw='$raw_val', Parsed=$amount, Month=$month\n";
						file_put_contents($log_file, $log_msg, FILE_APPEND);
					}

					// Only check if day is valid for this month (some months have 28/29/30/31 days)
					if ($amount > 0 && $day <= 31) {
						// Use the actual day from the loop
						if (checkdate((int) $month, $day, (int) $year)) {
							$date = sprintf('%s-%s-%02d', $year, $month, $day);
							$this->db->insert('tbdetail_simpanan', [
								'simpanan_id' => $simpanan_id,
								'tanggal_setoran' => $date . ' 12:00:00',
								'jumlah_setoran' => $amount,
								'pegawai_id' => $pegawai_id
							]);
							$results['setoran']['inserted']++;
						}
					}
				}

				// Process PENARIKAN columns from start to end (each column = 1 day)
				$penarikan_cols = $col_penarikan_end - $col_penarikan_start + 1;
				for ($day = 1; $day <= $penarikan_cols; $day++) {
					$col = $col_penarikan_start + ($day - 1);
					$amount = $this->_parse_amount($row[$col] ?? 0);

					if ($amount > 0 && checkdate((int) $month, $day, (int) $year)) {
						$date = sprintf('%s-%s-%02d', $year, $month, $day);
						$this->db->insert('tbdetail_penarikan', [
							'simpanan_id' => $simpanan_id,
							'penarikan_id' => 0,
							'tanggal_penarikan' => $date . ' 12:00:00',
							'jumlah_penarikan' => $amount,
							'pegawai_id' => $pegawai_id,
							'status' => 'disetujui'
						]);
						$results['penarikan']['inserted']++;
					}
				}

				// Import bunga from mapped columns:
				// - BUNGA BULAT (rounded) → jumlah_transaksi in tbtransaksi
				// - BUNGA RAW (E-MIN * rate) → bunga_riil in tbtransaksi
				// - E-MIN (base saldo) → used to calculate rate_bunga
				$bunga_net = $this->_parse_amount($row[$col_bunga] ?? 0);      // Rounded bunga
				$bunga_raw = $this->_parse_amount($row[$col_bunga_raw] ?? 0);  // Raw bunga (before rounding)
				$e_min = $this->_parse_amount($row[$col_e_min] ?? 0);          // E-MIN (base saldo)

				// Calculate bunga rate: rate = bunga_raw / e_min * 100
				// Formula: =IF(CU4>="","",IF(CU4>=50000,CU4*0.2%,0))
				$rate_bunga = 0;
				if ($e_min > 0 && $bunga_raw > 0) {
					$rate_bunga = round(($bunga_raw / $e_min) * 100, 2); // e.g., 0.2 for 0.2%
				}

				// VALIDATION: Bunga must be > 0 and not equal to saldo_sebelum
				$is_valid_bunga = ($bunga_net > 0 && $bunga_net != $saldo_sebelum);
				if ($is_valid_bunga) {
					// Update tbsimpanan.jumlah_bunga
					$this->db->where('id', $simpanan_id)->update('tbsimpanan', [
						'jumlah_bunga' => $bunga_net
					]);

					// Insert into tbtransaksi for Detail bunga display
					$last_day = date('t', strtotime("$year-$month-01"));
					$this->db->insert('tbtransaksi', [
						'simpanan_id' => $simpanan_id,
						'no_rekening' => $no_rekening,
						'nama_nasabah' => $nama,
						'tanggal_transaksi' => "$year-$month-$last_day",
						'jumlah_transaksi' => $bunga_net,   // Rounded bunga (31,900)
						'rate_bunga' => $rate_bunga,         // Rate percentage (0.2)
						'bunga_riil' => $bunga_raw           // Raw bunga before rounding (31,912)
					]);
					$results['bunga']['inserted']++;
				}

				$results['details'][] = [
					'row' => $i + 1,
					'nama' => $nama,
					'no_rekening' => $no_rekening,
					'action' => $existing ? 'updated' : 'created'
				];

			} catch (Exception $e) {
				$results['simpanan']['errors']++;
				$results['errors'][] = "Row " . ($i + 1) . ": " . $e->getMessage();
				$log_msg = "Row " . ($i + 1) . " Exception: " . $e->getMessage() . "\n";
				file_put_contents($log_file, $log_msg, FILE_APPEND);
			}
		}

		// Update saldo for each simpanan: saldo = saldo_awal + setoran(this month) - penarikan(this month) + bunga
		$date_start = $year . '-' . $month . '-01';
		$date_end = $year . '-' . $month . '-31';
		$debug_count = 0;
		foreach ($norek_to_id as $no_rekening => $simpanan_id) {
			try {
				// Get saldo_awal for this account
				$saldo_awal = isset($norek_saldo_awal[$no_rekening]) ? $norek_saldo_awal[$no_rekening] : 0;

				// Sum setoran only for THIS month
				$setoran_result = $this->db->select_sum('jumlah_setoran')
					->where('simpanan_id', $simpanan_id)
					->where('tanggal_setoran >=', $date_start)
					->where('tanggal_setoran <=', $date_end . ' 23:59:59')
					->get('tbdetail_simpanan')->row();
				$total_setoran = ($setoran_result && $setoran_result->jumlah_setoran) ? floatval($setoran_result->jumlah_setoran) : 0;

				// Sum penarikan only for THIS month
				$penarikan_result = $this->db->select_sum('jumlah_penarikan')
					->where('simpanan_id', $simpanan_id)
					->where('tanggal_penarikan >=', $date_start)
					->where('tanggal_penarikan <=', $date_end . ' 23:59:59')
					->get('tbdetail_penarikan')->row();
				$total_penarikan = ($penarikan_result && $penarikan_result->jumlah_penarikan) ? floatval($penarikan_result->jumlah_penarikan) : 0;

				// Get bunga from tbsimpanan.jumlah_bunga
				$simpanan = $this->db->where('id', $simpanan_id)->get('tbsimpanan')->row();
				$bunga = ($simpanan && $simpanan->jumlah_bunga) ? floatval($simpanan->jumlah_bunga) : 0;

				// SALDO = SALDO_AWAL + SETORAN(this month) - PENARIKAN(this month) + BUNGA
				$saldo = $saldo_awal + $total_setoran - $total_penarikan + $bunga;

				// Debug: Log saldo calculation for first 5 accounts
				$debug_count++;
				if ($debug_count <= 5) {
					$log_msg = "SALDO CALC $no_rekening: awal=$saldo_awal + setor=$total_setoran - tarik=$total_penarikan + bunga=$bunga = $saldo (month=$month)\n";
					file_put_contents($log_file, $log_msg, FILE_APPEND);
				}

				$this->db->where('id', $simpanan_id)->update('tbsimpanan', ['jumlah_simpanan' => $saldo]);
			} catch (Exception $e) {
				$results['errors'][] = "Saldo update error for $no_rekening: " . $e->getMessage();
			}
		}

		$log_msg = "IMPORT COMPLETE. Results: " . json_encode($results) . "\n";
		file_put_contents($log_file, $log_msg, FILE_APPEND);

		$this->db->query('SET FOREIGN_KEY_CHECKS=1');

		$results['success'] = true;
		$results['total_processed'] = count($norek_to_id);

		return $results;
	}
}


