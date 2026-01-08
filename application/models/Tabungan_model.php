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
        $this->db->join('tbpegawai', 'tbpegawai.id = trans.pegawai_id');

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

            // --- REMOVED: The entire block that tried to find the "first setor" and use it as saldo awal. This logic was incorrect. ---

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
     * Helper: Find nasabah by name
     */
    private function _find_nasabah_by_name($nama)
    {
        if (empty($nama))
            return null;

        // Try exact match first
        $result = $this->db->where('LOWER(nama_lengkap)', strtolower(trim($nama)))
            ->get('tbnasabah')->row();

        if ($result)
            return $result;

        // Try LIKE match
        $result = $this->db->like('nama_lengkap', trim($nama), 'both')
            ->limit(1)
            ->get('tbnasabah')->row();

        return $result;
    }

    /**
     * Helper: Create nasabah from import
     */
    private function _create_nasabah_from_import($nama, $alamat = '-', $telp = '-', $pegawai_id = null)
    {
        $data = [
            'nik' => 'IMP' . date('YmdHis') . substr(uniqid(), -4),
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
    public function import_december_only($file_path, $pegawai_id, $jenistabungan_id, $delete_existing = true)
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

        // PHASE 1: Process each nasabah and create simpanan + transactions
        for ($i = 3; $i < count($rows); $i++) {
            $row = $rows[$i];

            $no_urut = intval($row[0] ?? 0);
            $no_tab = intval($row[2] ?? 0);

            // Skip if no valid identifier (no_urut or no_tab)
            if ($no_urut <= 0 && $no_tab <= 0)
                continue;

            // Get nama - if empty, generate placeholder based on no_tab
            $nama = trim($row[1] ?? '');
            if (empty($nama)) {
                $norek_num = $no_tab > 0 ? $no_tab : $no_urut;
                $nama = 'Nasabah T' . str_pad($norek_num, 3, '0', STR_PAD_LEFT);
            }

            // Skip summary rows
            if (stripos($nama, 'JUMLAH') !== false || stripos($nama, 'TOTAL') !== false)
                continue;

            try {
                $alamat = trim($row[3] ?? '-');
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

        // Process DES sheet starting from row 3 (data rows)
        for ($i = 3; $i < count($des_rows); $i++) {
            $row = $des_rows[$i];

            $nama = trim($row[1] ?? '');
            if (empty($nama))
                continue;
            if (stripos($nama, 'JUMLAH') !== false || stripos($nama, 'TOTAL') !== false)
                continue;

            try {
                $no_tab = trim((string) ($row[2] ?? ''));
                $alamat = trim($row[3] ?? '-');
                $saldo_awal = $this->_parse_amount($row[4] ?? 0);
                $saldo_akhir = $this->_parse_amount($row[102] ?? 0);

                // Get bunga from lookup
                $bunga = $bunga_lookup[$no_tab] ?? 0;

                // Generate no_rekening: T + 3 digit padded
                $no_rekening = 'T' . str_pad($no_tab, 3, '0', STR_PAD_LEFT);

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
}

