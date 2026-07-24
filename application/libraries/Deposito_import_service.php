<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Deposito_import_service
{
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
		$this->CI->load->model('Deposito_model');
	}

    // ========== FULL MIGRATION IMPORT ==========

    /**
     * Import full migration from Excel file
     * Handles: DAFTAR DEPOSAN, PEMBAYARAN BUNGA, HUTANG BUNGA sheets
     * 
     * @param string $file_path Path to Excel file
     * @param int $pegawai_id Employee ID performing import
     * @param int $jenistabungan_id Deposit type ID
     * @return array Import results with details
     */
    public function import_full_migration($file_path, $pegawai_id, $jenistabungan_id)
    {
        require_once APPPATH . 'third_party/SimpleXLS.php';

        $results = [
            'success' => false,
            'batch_id' => 'IMP' . date('YmdHis'),
            'deposito' => ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
            'bunga_log' => ['inserted' => 0, 'errors' => 0],
            'nasabah' => ['created' => 0, 'found' => 0],
            'details' => [],
            'errors' => []
        ];

        // Parse Excel file
        $xls = \Shuchkin\SimpleXLS::parse($file_path);
        if (!$xls) {
            $results['errors'][] = 'Gagal membaca file Excel: ' . \Shuchkin\SimpleXLS::parseError();
            return $results;
        }

        $sheets = $xls->sheetNames();
        $results['sheets_found'] = $sheets;

        // Find sheet indexes
        $sheet_deposan = array_search('DAFTAR DEPOSAN', $sheets);
        $sheet_bunga = array_search('PEMBAYARAN BUNGA DEPOSITO', $sheets);
        $sheet_hutang = array_search('HUTANG BUNGA', $sheets);

        if ($sheet_deposan === false) {
            $results['errors'][] = 'Sheet "DAFTAR DEPOSAN" tidak ditemukan';
            return $results;
        }

        $this->CI->db->trans_start();

        // Step 1: Import DAFTAR DEPOSAN
        $rows_deposan = $xls->rows($sheet_deposan);
        $no_seri_to_deposito_id = []; // Map no_seri to deposito_id for linking bunga

        // Header is at row 3 (index 2), data starts at row 4 (index 3)
        for ($i = 3; $i < count($rows_deposan); $i++) {
            $row = $rows_deposan[$i];

            // Skip empty rows
            $nama = trim($row[1] ?? '');
            if (empty($nama))
                continue;

            try {
                // Excel Column Mapping for DAFTAR DEPOSAN sheet (0-indexed):
                // 0=NO, 1=NAMA, 2=ALAMAT, 3=NO.SERI, 4=JUMLAH DEPOSITO, 5=TGL DEPOSITO
                // 6=JANGKA WAKTU, 7=JATUH TEMPO, 8=SUKU BUNGA, 9=BUNGA(calc), 10=TELP, 11=KET

                // Parse NO.SERI as integer (handles 1.0 -> 1)
                $no_seri = $this->_parse_int_safe($row[3] ?? 0) ?? 0;

                // Parse amounts with safe handling for #NUM!, -, empty
                $jumlah_deposito = $this->_parse_amount_safe($row[4] ?? 0);

                // Parse dates with safe handling for #NUM! and invalid dates
                $tanggal_deposito = $this->_parse_date_safe($row[5] ?? '');
                $tanggal_jatuh_tempo = $this->_parse_date_safe($row[7] ?? '');

                // Parse duration - default to 12 if invalid
                $durasi = $this->_parse_int_safe($row[6] ?? 12) ?? 12;
                if ($durasi <= 0)
                    $durasi = 12;

                // Parse rate from Excel (0.6, 0.7, 0.8)
                $rate_bunga = $this->_parse_rate_from_excel($row[8] ?? 0);

                // Other fields
                $alamat = trim($row[2] ?? '-');
                $telp = trim($row[10] ?? '-');
                $keterangan = trim($row[11] ?? '');

                // Detect status from keterangan
                $status = $this->_detect_status($keterangan, $jumlah_deposito);

                // Find or create nasabah
                $nasabah = $this->CI->Deposito_model->find_nasabah_by_name($nama);
                if (!$nasabah) {
                    $nasabah_id = $this->CI->Deposito_model->create_nasabah_from_import($nama, $alamat, $telp, $pegawai_id);
                    $results['nasabah']['created']++;
                } else {
                    $nasabah_id = $nasabah->id;
                    $results['nasabah']['found']++;
                }

                // Check if deposito exists by no_seri
                $existing = $no_seri > 0 ? $this->CI->Deposito_model->find_deposito_by_no_seri($no_seri) : null;

                $deposito_data = [
                    'no_seri' => $no_seri > 0 ? $no_seri : null,
                    'nasabah_id' => $nasabah_id,
                    'pegawai_id' => $pegawai_id,
                    'jenistabungan_id' => $jenistabungan_id,
                    'nama_nasabah' => $nama,
                    'telp_nasabah' => $telp,
                    'jumlah_deposito' => $jumlah_deposito,
                    'rate_bunga' => $rate_bunga,
                    'tanggal_deposito' => $tanggal_deposito ?: date('Y-m-d'),
                    'durasi' => $durasi,
                    'tanggal_jatuh_tempo' => $tanggal_jatuh_tempo,
                    'status' => $status,
                    'import_batch_id' => $results['batch_id'],
                    'import_notes' => $keterangan
                ];

                // Calculate jatuh tempo if not provided
                if (empty($deposito_data['tanggal_jatuh_tempo']) && !empty($deposito_data['tanggal_deposito']) && $durasi > 0) {
                    $deposito_data['tanggal_jatuh_tempo'] = date('Y-m-d', strtotime($deposito_data['tanggal_deposito'] . " + $durasi months"));
                }

                if ($existing) {
                    // Update existing
                    unset($deposito_data['nasabah_id']); // Don't change nasabah
                    $this->CI->db->where('id', $existing->id)->update($this->table, $deposito_data);
                    $deposito_id = $existing->id;
                    $results['deposito']['updated']++;
                    $results['details'][] = ['row' => $i + 1, 'nama' => $nama, 'action' => 'updated', 'no_seri' => $no_seri];
                } else {
                    // Insert new
                    $deposito_data['no_rekening'] = $this->CI->Deposito_model->generate_next_rekening();
                    $this->CI->Deposito_model->insert_data($deposito_data);
                    $deposito_id = $this->CI->db->insert_id();
                    $results['deposito']['inserted']++;
                    $results['details'][] = ['row' => $i + 1, 'nama' => $nama, 'action' => 'inserted', 'no_seri' => $no_seri];
                }

                // Store mapping for bunga import
                if ($no_seri > 0) {
                    $no_seri_to_deposito_id[$no_seri] = $deposito_id;
                }

            } catch (Exception $e) {
                $results['deposito']['errors']++;
                $results['errors'][] = "Row " . ($i + 1) . " ($nama): " . $e->getMessage();
            }
        }

        // Step 2: Import PEMBAYARAN BUNGA DEPOSITO (if sheet exists)
        // This sheet tracks interest payments history
        // Cols 7-30 = monthly payments (date + amount pairs) - these are sudah_ditarik
        if ($sheet_bunga !== false && count($no_seri_to_deposito_id) > 0) {
            $rows_bunga = $xls->rows($sheet_bunga);

            // DELETE old bunga log entries for all depositos being imported
            // This prevents duplicate data when re-importing
            $deposito_ids_to_clear = array_values($no_seri_to_deposito_id);
            if (!empty($deposito_ids_to_clear)) {
                $this->CI->db->where_in('deposito_id', $deposito_ids_to_clear);
                $this->CI->db->where('input_method', 'import'); // Only delete imported entries, keep manual entries
                $deleted_count = $this->CI->db->delete('tb_bunga_deposito_log');
                $results['bunga_log']['deleted'] = $this->CI->db->affected_rows();
            }

            // CORRECTED Column mapping based on Excel structure (verified via debug):
            // Col A(0)=NIN, B(1)=NAMA, C(2)=ALAMAT, D(3)=empty, E(4)=empty, F(5)=PINDAHAN TGL, G(6)=PINDAHAN SALDO
            // Col H(7)=JAN TGL, I(8)=JAN Amount, J(9)=FEB TGL, K(10)=FEB Amount, etc.
            $months = [
                7 => '01',  // JAN: date=7 (H), amount=8 (I)
                9 => '02',  // FEB: date=9 (J), amount=10 (K)
                11 => '03', // MAR: date=11 (L), amount=12 (M)
                13 => '04', // APR: date=13 (N), amount=14 (O)
                15 => '05', // MAY: date=15 (P), amount=16 (Q)
                17 => '06', // JUN: date=17 (R), amount=18 (S)
                19 => '07', // JUL: date=19 (T), amount=20 (U)
                21 => '08', // AUG: date=21 (V), amount=22 (W)
                23 => '09', // SEP: date=23 (X), amount=24 (Y)
                25 => '10', // OCT: date=25 (Z), amount=26 (AA)
                27 => '11', // NOV: date=27 (AB), amount=28 (AC)
                29 => '12'  // DEC: date=29 (AD), amount=30 (AE)
            ];

            for ($i = 3; $i < count($rows_bunga); $i++) {
                $row = $rows_bunga[$i];
                $nin = intval($row[0] ?? 0); // NIN = No Seri

                if ($nin <= 0 || !isset($no_seri_to_deposito_id[$nin]))
                    continue;

                $deposito_id = $no_seri_to_deposito_id[$nin];

                // Import SALDO PINDAHAN (Col G = index 6) - Bunga yg belum ditarik dari periode sblmnya
                $saldo_pindahan = $this->_parse_amount_safe($row[6] ?? 0);
                $total_bunga_tersedia = 0;

                if ($saldo_pindahan > 0) {
                    $pindahan_date = $this->_parse_date_safe($row[5] ?? '') ?: '2024-12-31';
                    $this->_insert_bunga_log($deposito_id, $saldo_pindahan, $pindahan_date, 'belum_ditarik', $results['batch_id'], 'Saldo pindahan (bunga periode sebelumnya)');
                    $results['bunga_log']['inserted']++;
                    $total_bunga_tersedia += $saldo_pindahan;
                }

                // Insert monthly bunga payments (User wants these to be available for withdrawal in the app)
                foreach ($months as $col => $month) {
                    $date_col = $col;
                    $amount_col = $col + 1;

                    $payment_date = $this->_parse_date_safe($row[$date_col] ?? '');
                    $payment_amount = $this->_parse_amount_safe($row[$amount_col] ?? 0);

                    if ($payment_amount > 0 && $payment_date) {
                        $this->_insert_bunga_log($deposito_id, $payment_amount, $payment_date, 'belum_ditarik', $results['batch_id'], "Pembayaran bunga bulan $month");
                        $results['bunga_log']['inserted']++;
                        $total_bunga_tersedia += $payment_amount;
                    }
                }

                // Update denormalized column tbdeposito
                if ($total_bunga_tersedia > 0) {
                    $this->CI->db->set('bunga_belum_ditarik', 'bunga_belum_ditarik + ' . $total_bunga_tersedia, FALSE);
                    $this->CI->db->where('id', $deposito_id);
                    $this->CI->db->update($this->table);
                }

            }
        }

        // Step 3: Import HUTANG BUNGA (outstanding interest balances)
        // This updates the denormalized bunga_belum_ditarik field
        // #NUM! values should be skipped (return 0 from _parse_amount_safe)
        if ($sheet_hutang !== false && count($no_seri_to_deposito_id) > 0) {
            $rows_hutang = $xls->rows($sheet_hutang);

            // Use DES column (Col 15 = December) as current outstanding balance
            for ($i = 3; $i < count($rows_hutang); $i++) {
                $row = $rows_hutang[$i];
                $nin = intval($row[0] ?? 0);

                if ($nin <= 0 || !isset($no_seri_to_deposito_id[$nin]))
                    continue;

                $deposito_id = $no_seri_to_deposito_id[$nin];

                // Get outstanding balance from DES column (Col 15)
                // Use _parse_amount_safe to handle #NUM! errors
                $hutang_bunga = $this->_parse_amount_safe($row[15] ?? 0);

                // Hutang bunga is negative in Excel, convert to positive
                // If it's 0 or #NUM!, we skip the update
                $bunga_belum_ditarik = abs($hutang_bunga);

                if ($bunga_belum_ditarik > 0) {
                    $this->CI->db->where('id', $deposito_id)
                        ->update($this->table, ['bunga_belum_ditarik' => $bunga_belum_ditarik]);

                    // Insert into tb_bunga_deposito_log so it can be withdrawn
                    $this->_insert_bunga_log(
                        $deposito_id,
                        $bunga_belum_ditarik,
                        date('Y-m-d'),
                        'belum_ditarik',
                        $results['batch_id'],
                        'Hutang Bunga (Bunga Belum Ditarik)'
                    );
                    $results['bunga_log']['inserted']++;
                }
            }
        }

        $this->CI->db->trans_complete();

        $results['success'] = $this->CI->db->trans_status();

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
     * Helper: Parse amount from Excel cell with SAFE handling
     * Handles #NUM!, empty cells, dashes, and other invalid values
     * 
     * @param mixed $value The cell value
     * @return float Returns 0 for invalid values, otherwise the parsed amount
     */
    private function _parse_amount_safe($value)
    {
        // Handle empty, null, 0
        if ($value === null || $value === '' || $value === 0 || $value === '0')
            return 0.0;

        // Convert to string for pattern checking
        $str_value = trim((string) $value);

        // Handle Excel error values: #NUM!, #VALUE!, #REF!, #DIV/0!, etc.
        if (strpos($str_value, '#') === 0) {
            return 0.0;
        }

        // Handle dash (often used for empty/null in Excel)
        if ($str_value === '-') {
            return 0.0;
        }

        // If already numeric, return as float
        if (is_numeric($value)) {
            return floatval($value);
        }

        // Remove currency symbols, thousands separators, and spaces
        $cleaned = str_replace(['$', 'Rp', ',', ' ', '.'], '', $str_value);

        // Handle negative values in parentheses: (1000) => -1000
        if (preg_match('/^\((.+)\)$/', $cleaned, $matches)) {
            $cleaned = '-' . $matches[1];
        }

        // Try to parse as float
        if (is_numeric($cleaned)) {
            return floatval($cleaned);
        }

        // Default to 0 for any other invalid value
        return 0.0;
    }

    /**
     * Helper: Parse integer/ID from Excel cell (NO, NO.SERI, NIN)
     * Handles float values like 1.0 -> 1
     * 
     * @param mixed $value The cell value
     * @return int|null Returns null for invalid values (should skip row)
     */
    private function _parse_int_safe($value)
    {
        if ($value === null || $value === '' || $value === '-')
            return null;

        // Convert to string for pattern checking
        $str_value = trim((string) $value);

        // Handle Excel error values
        if (strpos($str_value, '#') === 0) {
            return null;
        }

        // If numeric, cast to int (removes .0)
        if (is_numeric($value)) {
            return intval(floatval($value));
        }

        return null;
    }

    /**
     * Helper: Parse date from Excel cell
     */
    private function _parse_date($value)
    {
        if (empty($value))
            return null;

        // Check for invalid dates
        if (strpos($value, '1970-01-01') !== false)
            return null;
        if (strpos($value, '1900-') !== false)
            return null;

        // If numeric (Excel serial date)
        if (is_numeric($value)) {
            $timestamp = strtotime('1899-12-30') + ($value * 86400);
            return date('Y-m-d', $timestamp);
        }

        // Try parsing as date string
        $timestamp = strtotime($value);
        if ($timestamp && $timestamp > 0) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Helper: Parse interest rate from Excel cell
     */
    private function _parse_rate($value)
    {
        if (empty($value))
            return 0;

        // Remove percentage sign
        $value = str_replace(['%', ' '], '', $value);
        $rate = floatval($value);

        // If rate is like 0.007 (0.7%), convert to 0.7
        if ($rate > 0 && $rate < 0.1) {
            $rate = $rate * 100;
        }

        return $rate;
    }

    /**
     * Helper: Parse date from Excel cell with safe handling for #NUM! and invalid dates
     */
    private function _parse_date_safe($value)
    {
        if (empty($value))
            return null;

        // Handle Excel error values like #NUM!, #VALUE!, #REF!, etc
        if (is_string($value) && strpos($value, '#') === 0) {
            return null;
        }

        // Handle dash or minus which Excel sometimes shows for empty dates
        if (trim($value) === '-' || trim($value) === '') {
            return null;
        }

        // Handle invalid dates (1900-based Excel errors or 1970)
        $value_str = (string) $value;
        if (strpos($value_str, '1900') !== false || strpos($value_str, '1899') !== false) {
            return null;
        }
        if (strpos($value_str, '1970-01-01') !== false) {
            return null;
        }

        // If numeric (Excel serial date)
        if (is_numeric($value)) {
            $serial = intval($value);
            // Excel dates are days since 1899-12-30
            // Ignore very small numbers (likely invalid)
            if ($serial < 36526) { // Before year 2000
                return null;
            }
            $timestamp = strtotime('1899-12-30') + ($serial * 86400);
            $year = (int) date('Y', $timestamp);
            // Validate year is reasonable (2000-2100)
            if ($year < 2000 || $year > 2100) {
                return null;
            }
            return date('Y-m-d', $timestamp);
        }

        // Try parsing as date string (e.g., "11/15/2025", "2025-11-15")
        $timestamp = strtotime($value);
        if ($timestamp && $timestamp > 0) {
            $year = (int) date('Y', $timestamp);
            if ($year < 2000 || $year > 2100) {
                return null;
            }
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Helper: Parse interest rate from Excel cell (uses Excel values directly: 0.60%, 0.70%, 0.80%)
     * Stores the rate as displayed in Excel (e.g., 0.6 for 0.6% per month)
     */
    private function _parse_rate_from_excel($value)
    {
        if (empty($value))
            return 0.6; // Default to 0.6% if empty

        // Handle Excel error values
        if (is_string($value) && strpos($value, '#') === 0) {
            return 0.6; // Default on error
        }

        // Remove percentage sign and spaces
        $cleaned = str_replace(['%', ' '], '', (string) $value);
        $rate = floatval($cleaned);

        // Excel rate formats:
        // 0.006 or 0.007 or 0.008 (raw decimal, need *100)
        // 0.60% or 0.70% or 0.80% (percentage string, already good after removing %)
        // 0.6 or 0.7 or 0.8 (already correct)

        // If value is very small like 0.006, convert to 0.6
        if ($rate > 0 && $rate < 0.1) {
            $rate = $rate * 100;
        }

        // If value is like 60 or 70 (percentage as whole number), divide by 100
        if ($rate > 10) {
            $rate = $rate / 100;
        }

        // Ensure rate is within reasonable bounds for monthly rate (0.1% - 5%)
        if ($rate <= 0 || $rate > 5) {
            return 0.6; // Default to 0.6% if out of bounds
        }

        return $rate;
    }

    /**
     * Helper: Detect deposit status from keterangan
     * Patterns from Excel: LUNAS, tarik, ditarik, pinalti, pembaharuan, pokok ditarik, etc.
     */
    private function _detect_status($keterangan, $jumlah)
    {
        // If no keterangan and has amount, it's active
        if (empty($keterangan) && $jumlah > 0)
            return 'aktif';

        // If no amount, it's closed
        if ($jumlah <= 0)
            return 'ditutup';

        $ket_lower = strtolower(trim($keterangan));

        // Check for closed/withdrawn patterns
        $closed_patterns = [
            'lunas',
            'ditarik',
            'pokok ditarik',
            'pokok sudah ditarik',
            'pokok sdh ditarik',
            'tarik tgl',
            'tarik 12/',  // e.g., "tarik 12/6/25"
            'tarik perpanjang'
        ];

        foreach ($closed_patterns as $pattern) {
            if (strpos($ket_lower, $pattern) !== false) {
                return 'ditutup';
            }
        }

        // Pinalti withdrawal means closed
        if (strpos($ket_lower, 'tarik') !== false && strpos($ket_lower, 'pinalti') !== false) {
            return 'ditutup';
        }

        // Pembaharuan (renewal) usually means the deposit was renewed - still active
        // BARU also means new/active
        if (strpos($ket_lower, 'baru') !== false || strpos($ket_lower, 'pembaharuan') !== false) {
            return 'aktif';
        }

        return 'aktif';
    }

    /**
     * Helper: Insert bunga log entry
     */
    private function _insert_bunga_log($deposito_id, $amount, $date, $status, $batch_id, $keterangan = '')
    {
        // Get deposito info for denormalized fields
        $deposito = $this->CI->db->select('no_rekening, nama_nasabah, rate_bunga')
            ->where('id', $deposito_id)
            ->get($this->table)->row();

        $data = [
            'deposito_id' => $deposito_id,
            'no_rekening' => $deposito ? $deposito->no_rekening : null,
            'nama_nasabah' => $deposito ? $deposito->nama_nasabah : null,
            'jumlah_bunga' => $amount,
            'rate_bunga' => $deposito ? $deposito->rate_bunga : 0,
            'tanggal_perhitungan' => $date,
            'status_penarikan' => $status,
            'input_method' => 'import',
            'import_batch_id' => $batch_id,
            'keterangan' => $keterangan
        ];

        return $this->CI->db->insert('tb_bunga_deposito_log', $data);
    }

}
