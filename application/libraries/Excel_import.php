<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'third_party/SimpleXLSX.php';
require_once APPPATH . 'third_party/SimpleXLS.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLS;

/**
 * Excel Import Library
 * Handles parsing of XLS/XLSX files for data import
 */
class Excel_import
{
    protected $CI;
    protected $errors = [];
    protected $warnings = [];
    
    public function __construct()
    {
        $this->CI =& get_instance();
    }
    
    /**
     * Parse Excel file and return data array
     */
    public function parse_file($file_path, $sheet_index = 0, $header_row = 1)
    {
        $this->errors = [];
        
        if (!file_exists($file_path)) {
            return ['success' => false, 'errors' => ['File tidak ditemukan.']];
        }
        
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        try {
            if ($extension === 'xlsx') {
                return $this->parse_xlsx($file_path, $sheet_index, $header_row);
            } elseif ($extension === 'xls') {
                return $this->parse_xls($file_path, $sheet_index, $header_row);
            } else {
                return ['success' => false, 'errors' => ['Format file tidak didukung. Gunakan .xls atau .xlsx']];
            }
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Parse XLSX file
     */
    protected function parse_xlsx($file_path, $sheet_index, $header_row)
    {
        $xlsx = SimpleXLSX::parse($file_path);
        
        if (!$xlsx) {
            return ['success' => false, 'errors' => [SimpleXLSX::parseError()]];
        }
        
        $sheet_names = $xlsx->sheetNames();
        $sheet_name = $sheet_names[$sheet_index] ?? 'Sheet ' . ($sheet_index + 1);
        
        $rows = $xlsx->rows($sheet_index);
        
        return $this->process_rows($rows, $header_row, $sheet_name);
    }
    
    /**
     * Parse XLS file
     */
    protected function parse_xls($file_path, $sheet_index, $header_row)
    {
        $xls = SimpleXLS::parse($file_path);
        
        if (!$xls) {
            return ['success' => false, 'errors' => [SimpleXLS::parseError()]];
        }
        
        $sheet_names = $xls->sheetNames();
        $sheet_name = $sheet_names[$sheet_index] ?? 'Sheet ' . ($sheet_index + 1);
        
        $rows = $xls->rows($sheet_index);
        
        return $this->process_rows($rows, $header_row, $sheet_name);
    }
    
    /**
     * Process rows into structured data
     * Auto-detects actual header row by looking for rows with typical header keywords
     */
    protected function process_rows($rows, $header_row, $sheet_name)
    {
        if (empty($rows)) {
            return ['success' => false, 'errors' => ['Sheet kosong atau tidak ada data']];
        }
        
        $header_index = $header_row - 1; // Convert to 0-indexed
        
        // Known header keywords to identify the real header row
        $header_keywords = ['NO', 'NAMA', 'ALAMAT', 'JUMLAH', 'TGL', 'TANGGAL', 'JANGKA', 'TEMPO', 'BUNGA', 'KET', 'SERI', 'DEPOSITO', 'WAKTU'];
        
        // Check if the specified header row is actually a header row (has multiple keywords)
        // If not, scan downward to find the real header row
        $actual_header_index = $header_index;
        for ($scan_row = $header_index; $scan_row < min($header_index + 5, count($rows)); $scan_row++) {
            if (!isset($rows[$scan_row])) continue;
            
            $keyword_count = 0;
            foreach ($rows[$scan_row] as $cell) {
                $cell_upper = strtoupper(trim((string)$cell));
                foreach ($header_keywords as $kw) {
                    if (strpos($cell_upper, $kw) !== false) {
                        $keyword_count++;
                        break;
                    }
                }
            }
            
            // If we found at least 3 header keywords, this is likely the real header row
            if ($keyword_count >= 3) {
                $actual_header_index = $scan_row;
                break;
            }
        }
        
        if (!isset($rows[$actual_header_index])) {
            return ['success' => false, 'errors' => ['Baris header tidak ditemukan']];
        }
        
        // Generate proper column names from the detected header row
        $headers = [];
        $alphabet = range('A', 'Z');
        foreach ($rows[$actual_header_index] as $col_index => $h) {
            $val = trim((string)$h);
            if ($val !== '' && $val !== null) {
                // Clean up the header name
                $val = preg_replace('/\s+/', ' ', $val); // normalize whitespace
                $headers[] = $val;
            } else {
                // Use column letter for empty headers
                $col_letter = $col_index < 26 ? $alphabet[$col_index] : 'Col_' . ($col_index + 1);
                $headers[] = $col_letter;
            }
        }
        
        $data = [];
        // Start reading data from the row AFTER the actual header row
        for ($i = $actual_header_index + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $row_data = [];
            $is_empty = true;
            $is_header_row = false;
            
            foreach ($headers as $col_index => $header) {
                $value = isset($row[$col_index]) ? $row[$col_index] : '';
                
                // Check if row has any meaningful data
                if ($value !== '' && $value !== null && $value !== 0 && $value !== '0') {
                    $is_empty = false;
                }
                
                // Check if this looks like another header row (sometimes there are sub-headers)
                if (is_string($value)) {
                    $val_upper = strtoupper(trim($value));
                    if (in_array($val_upper, $header_keywords) || $val_upper === strtoupper($header)) {
                        $is_header_row = true;
                    }
                }
                
                $row_data[$header] = $value;
            }
            
            // Only add rows that have data and aren't header rows
            if (!$is_empty && !$is_header_row) {
                $row_data['_row_number'] = $i + 1;
                $data[] = $row_data;
            }
        }
        
        return [
            'success' => true,
            'headers' => $headers,
            'data' => $data,
            'total_rows' => count($data),
            'sheet_name' => $sheet_name,
            'detected_header_row' => $actual_header_index + 1 // 1-indexed for display
        ];
    }
    
    /**
     * Get list of sheets in Excel file
     */
    public function get_sheets($file_path)
    {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        try {
            if ($extension === 'xlsx') {
                $xlsx = SimpleXLSX::parse($file_path);
                if (!$xlsx) {
                    return ['success' => false, 'error' => SimpleXLSX::parseError()];
                }
                $sheet_names = $xlsx->sheetNames();
                $sheets = [];
                foreach ($sheet_names as $index => $name) {
                    $rows = $xlsx->rows($index);
                    $sheets[] = [
                        'index' => $index,
                        'name' => $name,
                        'rows' => count($rows),
                        'cols' => count($rows[0] ?? [])
                    ];
                }
                return ['success' => true, 'sheets' => $sheets];
                
            } elseif ($extension === 'xls') {
                $xls = SimpleXLS::parse($file_path);
                if (!$xls) {
                    return ['success' => false, 'error' => SimpleXLS::parseError()];
                }
                $sheet_names = $xls->sheetNames();
                $sheets = [];
                foreach ($sheet_names as $index => $name) {
                    $rows = $xls->rows($index);
                    $sheets[] = [
                        'index' => $index,
                        'name' => $name,
                        'rows' => count($rows),
                        'cols' => count($rows[0] ?? [])
                    ];
                }
                return ['success' => true, 'sheets' => $sheets];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
        
        return ['success' => false, 'error' => 'Format tidak didukung'];
    }
    
    /**
     * Convert Excel serial date to MySQL date format
     */
    public function excel_date_to_mysql($excel_date, $datemode = 0)
    {
        if (empty($excel_date)) {
            return null;
        }
        
        // If already looks like a date string
        if (!is_numeric($excel_date)) {
            $timestamp = strtotime($excel_date);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
            return null;
        }
        
        // Excel dates are stored as days since 1899-12-30 (datemode 0)
        $base_date = ($datemode == 0) ? strtotime('1899-12-30') : strtotime('1904-01-01');
        $timestamp = $base_date + ($excel_date * 86400);
        
        return date('Y-m-d', $timestamp);
    }
    
    /**
     * Convert decimal interest rate to percentage
     */
    public function normalize_interest_rate($rate)
    {
        if (empty($rate)) return 0;
        
        $rate = floatval($rate);
        
        // If rate is already a percentage (e.g., 7 or 0.7)
        if ($rate >= 1) {
            return $rate;
        }
        
        // If rate is decimal like 0.007 (0.7%) or 0.07 (7%)
        if ($rate < 0.1) {
            return $rate * 100; // 0.007 -> 0.7
        }
        
        return $rate;
    }
    
    /**
     * Detect deposito status from KET (notes) column
     */
    public function detect_status_from_notes($notes)
    {
        if (empty($notes)) {
            return 'aktif';
        }
        
        $notes_lower = strtolower($notes);
        
        if (strpos($notes_lower, 'lunas') !== false) {
            return 'ditutup';
        }
        if (strpos($notes_lower, 'tarik') !== false) {
            return 'ditutup';
        }
        if (strpos($notes_lower, 'ditarik') !== false) {
            return 'ditutup';
        }
        if (strpos($notes_lower, 'pinalti') !== false) {
            return 'ditutup';
        }
        
        return 'aktif';
    }
    
    /**
     * Suggest column mappings based on header names
     */
    public function suggest_column_mapping($headers)
    {
        $mappings = [];
        $patterns = [
            'no_seri' => ['no.seri', 'no seri', 'noseri', 'serial', 'no'],
            'nama' => ['nama', 'name', 'nasabah'],
            'alamat' => ['alamat', 'address', 'addr'],
            'jumlah_deposito' => ['jumlah deposito', 'jumlah', 'amount', 'deposito', 'saldo'],
            'tanggal_deposito' => ['tgl deposito', 'tanggal deposito', 'tgl', 'date', 'tanggal'],
            'durasi' => ['jangka waktu', 'durasi', 'term', 'waktu', 'bulan'],
            'tanggal_jatuh_tempo' => ['jatuh tempo', 'maturity', 'tempo'],
            'rate_bunga' => ['sukubunga', 'suku bunga', 'bunga', 'rate', 'interest'],
            'bunga_bulanan' => ['bunga per bulan', 'bunga/bulan', 'monthly interest'],
            'telp' => ['telp', 'phone', 'hp', 'kontak', 'telepon', 'no hp'],
            'keterangan' => ['ket', 'keterangan', 'notes', 'catatan', 'status']
        ];
        
        foreach ($headers as $header) {
            $header_lower = strtolower(trim($header));
            
            foreach ($patterns as $field => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($header_lower, $keyword) !== false) {
                        $mappings[$header] = $field;
                        break 2;
                    }
                }
            }
        }
        
        return $mappings;
    }
    
    /**
     * Generate import batch ID
     */
    public function generate_batch_id()
    {
        return 'IMP' . date('YmdHis') . substr(uniqid(), -4);
    }
}
