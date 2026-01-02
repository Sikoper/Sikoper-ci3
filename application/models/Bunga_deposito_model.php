<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga_deposito_model extends CI_Model
{
    var $table = 'tb_bunga_deposito_log'; // Sumber data utama adalah tabel log baru
    var $column_order = array(null, 'n.nama_lengkap', 'd.no_rekening', 'log.tanggal_perhitungan', 'log.jumlah_bunga', 'd.rate_bunga', null);
    var $column_search = array('n.nama_lengkap', 'd.no_rekening');
    var $order = array('log.tanggal_perhitungan' => 'DESC');

    public function bunga_proses_deposito()
    {
        $currentDay = date('d');
        $currentMonth = date('m');
        $currentYear = date('Y');
        $processedAny = false;

        $this->db->where('status', 'aktif');
        $depositoList = $this->db->get('tbdeposito')->result();

        if (empty($depositoList)) {
            return false;
        }

        $this->db->trans_start();
        $data_bunga_batch = [];

        foreach ($depositoList as $deposito) {
            // cek umur deposito
            $tanggal_deposito = new DateTime($deposito->tanggal_deposito);
            $tanggal_mulai_bunga = clone $tanggal_deposito;
            $tanggal_mulai_bunga->modify('+30 days'); // bunga mulai dihitung 30 hari setelah registrasi
            $today = new DateTime();

            if ($today < $tanggal_mulai_bunga) {
                // skip kalau belum 30 hari
                continue;
            }

            // cek apakah bunga bulan ini sudah ada
            $bungaExistsThisMonth = $this->db->where('deposito_id', $deposito->id)
                ->where('MONTH(tanggal_perhitungan)', $currentMonth)
                ->where('YEAR(tanggal_perhitungan)', $currentYear)
                ->get($this->table)
                ->num_rows();

            if ($bungaExistsThisMonth > 0) {
                continue;
            }

            $hariBungaNasabah = (int) date('d', strtotime($deposito->tanggal_deposito));
            $lastDayOfMonth = (int) date('t'); // Tanggal terakhir bulan ini
            $isLastDayOfMonth = ($currentDay == $lastDayOfMonth);

            // Proses jika:
            // 1. Hari bunga nasabah <= hari ini, ATAU
            // 2. Hari ini adalah tanggal terakhir bulan dan hari bunga nasabah > hari terakhir
            //    (untuk menangani kasus Februari dimana tanggal 29/30/31 tidak ada)
            $shouldProcess = ($hariBungaNasabah <= $currentDay) ||
                ($isLastDayOfMonth && $hariBungaNasabah > $lastDayOfMonth);

            if ($shouldProcess) {
                $bungaAmount = ($deposito->jumlah_deposito * ($deposito->rate_bunga / 100));

                // Gunakan tanggal perhitungan yang sesuai
                $tanggalPerhitungan = $currentYear . '-' . $currentMonth . '-';
                if ($hariBungaNasabah > $lastDayOfMonth) {
                    // Jika tanggal daftar > tanggal terakhir bulan, gunakan tanggal terakhir
                    $tanggalPerhitungan .= str_pad($lastDayOfMonth, 2, '0', STR_PAD_LEFT);
                } else {
                    $tanggalPerhitungan .= str_pad($hariBungaNasabah, 2, '0', STR_PAD_LEFT);
                }

                $data_bunga_batch[] = [
                    'deposito_id' => $deposito->id,
                    'no_rekening' => $deposito->no_rekening ?? null,
                    'nama_nasabah' => $deposito->nama_nasabah ?? null,
                    'jumlah_bunga' => $bungaAmount,
                    'rate_bunga' => $deposito->rate_bunga,
                    'tanggal_perhitungan' => $tanggalPerhitungan,
                    'status_penarikan' => 'belum_ditarik'
                ];
                $processedAny = true;
            }
        }

        if (!empty($data_bunga_batch)) {
            $this->db->insert_batch($this->table, $data_bunga_batch);
        }

        $this->db->trans_complete();
        return $processedAny;
    }

    private function _get_datatables_query($start_date = null, $end_date = null)
    {
        // OPTIMIZED: Use denormalized columns when available, fallback to JOIN
        $this->db->select('log.id, log.tanggal_perhitungan as tanggal_transaksi, log.jumlah_bunga as jumlah_transaksi, 
            COALESCE(log.nama_nasabah, n.nama_lengkap) as nama_lengkap, 
            COALESCE(log.no_rekening, d.no_rekening) as no_rekening, 
            d.rate_bunga');
        $this->db->from('tb_bunga_deposito_log as log');
        $this->db->join('tbdeposito as d', 'd.id = log.deposito_id');
        $this->db->join('tbnasabah as n', 'n.id = d.nasabah_id', 'left');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(log.tanggal_perhitungan) >=', $start_date);
            $this->db->where('DATE(log.tanggal_perhitungan) <=', $end_date);
        }

        $i = 0;
        foreach ($this->column_search as $item) {
            if (isset($_POST['search']['value']) && $_POST['search']['value'] != '') {
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

    function get_datatables($start_date = null, $end_date = null)
    {
        $this->_get_datatables_query($start_date, $end_date);
        if (isset($_POST['length']) && $_POST['length'] != -1)
            $this->db->limit($_POST['length'], isset($_POST['start']) ? $_POST['start'] : 0);

        $query = $this->db->get();
        if (!$query) {
            return [];
        }
        return $query->result();
    }

    public function count_filtered($start_date = null, $end_date = null)
    {
        $this->_get_datatables_query($start_date, $end_date);
        $query = $this->db->get();
        return $query ? $query->num_rows() : 0;
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function get_total_bunga_filtered($start_date = null, $end_date = null)
    {
        $this->db->select_sum('jumlah_bunga', 'total_bunga');
        $this->db->from($this->table);

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tanggal_perhitungan) >=', $start_date);
            $this->db->where('DATE(tanggal_perhitungan) <=', $end_date);
        }
        $query = $this->db->get();

        if (!$query) {
            return 0;
        }
        return $query->row()->total_bunga ?? 0;
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function delete_data($id)
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    public function get_detail_bunga_by_deposito_id($deposito_id)
    {
        if (empty($deposito_id)) {
            return [];
        }

        $this->db->select('id, tanggal_perhitungan, jumlah_bunga, rate_bunga');
        $this->db->from('tb_bunga_deposito_log');
        $this->db->where('deposito_id', $deposito_id);
        $this->db->order_by('tanggal_perhitungan', 'DESC');

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function get_total_detail_bunga($deposito_id)
    {
        if (empty($deposito_id)) {
            return 0;
        }

        $this->db->select_sum('jumlah_bunga', 'total');
        $this->db->from('tb_bunga_deposito_log');
        $this->db->where('deposito_id', $deposito_id);

        $query = $this->db->get();

        // Pengecekan keamanan jika query gagal
        return $query ? ($query->row()->total ?? 0) : 0;
    }

    /**
     * Get all interest data for report printing (without pagination)
     */
    public function get_report_data($start_date, $end_date)
    {
        $this->db->select('log.id, log.tanggal_perhitungan as tanggal_transaksi, log.jumlah_bunga as jumlah_transaksi, 
            COALESCE(log.nama_nasabah, n.nama_lengkap) as nama_lengkap, 
            COALESCE(log.no_rekening, d.no_rekening) as no_rekening, 
            d.rate_bunga, d.jumlah_deposito');
        $this->db->from('tb_bunga_deposito_log as log');
        $this->db->join('tbdeposito as d', 'd.id = log.deposito_id');
        $this->db->join('tbnasabah as n', 'n.id = d.nasabah_id', 'left');
        
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(log.tanggal_perhitungan) >=', $start_date);
            $this->db->where('DATE(log.tanggal_perhitungan) <=', $end_date);
        }
        
        $this->db->order_by('log.tanggal_perhitungan', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Check if bunga entry already exists for deposito in the same month
     */
    public function check_duplicate($deposito_id, $tanggal)
    {
        $month = date('m', strtotime($tanggal));
        $year = date('Y', strtotime($tanggal));
        
        return $this->db->where('deposito_id', $deposito_id)
            ->where('MONTH(tanggal_perhitungan)', $month)
            ->where('YEAR(tanggal_perhitungan)', $year)
            ->count_all_results($this->table) > 0;
    }

    /**
     * Insert manual bunga entry
     */
    public function insert_bunga_manual($data)
    {
        $deposito = $this->db->select('no_rekening, nama_nasabah, rate_bunga')
            ->where('id', $data['deposito_id'])
            ->get('tbdeposito')->row();

        $insert_data = [
            'deposito_id' => $data['deposito_id'],
            'no_rekening' => $deposito->no_rekening ?? null,
            'nama_nasabah' => $deposito->nama_nasabah ?? null,
            'jumlah_bunga' => $data['jumlah_bunga'],
            'rate_bunga' => $deposito->rate_bunga ?? null,
            'tanggal_perhitungan' => $data['tanggal_perhitungan'],
            'status_penarikan' => 'belum_ditarik'
        ];

        // Add optional columns if they exist in the table
        if ($this->db->field_exists('input_method', $this->table)) {
            $insert_data['input_method'] = $data['input_method'] ?? 'manual';
        }
        if ($this->db->field_exists('pegawai_id', $this->table)) {
            $insert_data['pegawai_id'] = $data['pegawai_id'] ?? null;
        }
        if ($this->db->field_exists('keterangan', $this->table)) {
            $insert_data['keterangan'] = $data['keterangan'] ?? null;
        }

        return $this->db->insert($this->table, $insert_data);
    }

    /**
     * Get active deposito list for dropdown (excluding those with bunga this month)
     */
    public function get_deposito_dropdown($search = '', $target_date = null)
    {
        $target_date = $target_date ?: date('Y-m-d');
        $month = date('m', strtotime($target_date));
        $year = date('Y', strtotime($target_date));
        
        // Subquery: get deposito IDs that already have bunga for this month
        $subquery = $this->db->select('deposito_id')
            ->where('MONTH(tanggal_perhitungan)', $month)
            ->where('YEAR(tanggal_perhitungan)', $year)
            ->get_compiled_select($this->table);
        
        $this->db->select('d.id, d.no_rekening, d.jumlah_deposito, d.rate_bunga, 
            COALESCE(d.nama_nasabah, n.nama_lengkap) as nama_nasabah');
        $this->db->from('tbdeposito d');
        $this->db->join('tbnasabah n', 'n.id = d.nasabah_id', 'left');
        $this->db->where('d.status', 'aktif');
        $this->db->where("d.id NOT IN ($subquery)", null, false);
        
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('d.no_rekening', $search);
            $this->db->or_like('d.nama_nasabah', $search);
            $this->db->or_like('n.nama_lengkap', $search);
            $this->db->group_end();
        }
        
        $this->db->order_by('d.no_rekening', 'ASC');
        $this->db->limit(20);
        
        return $this->db->get()->result();
    }

    /**
     * Calculate bunga for single deposito
     */
    public function calculate_bunga_single($deposito_id)
    {
        $deposito = $this->db->select('jumlah_deposito, rate_bunga')
            ->where('id', $deposito_id)
            ->get('tbdeposito')->row();

        if (!$deposito) return 0;

        return $deposito->jumlah_deposito * ($deposito->rate_bunga / 100);
    }

    // ========== IMPORT METHODS ==========
    
    /**
     * Insert bunga from import (with duplicate check)
     * @param array $data Bunga data
     * @param bool $skip_duplicate If true, skip duplicates. If false, allow duplicates.
     * @return array ['action' => 'insert'|'skip', 'message' => string]
     */
    public function insert_bunga_import($data, $skip_duplicate = true)
    {
        // Check for duplicate if required
        if ($skip_duplicate) {
            $exists = $this->check_duplicate($data['deposito_id'], $data['tanggal_perhitungan']);
            if ($exists) {
                $bulan = date('F Y', strtotime($data['tanggal_perhitungan']));
                return [
                    'action' => 'skip',
                    'message' => "Bunga untuk bulan $bulan sudah ada"
                ];
            }
        }
        
        // Get deposito info for denormalized fields
        $deposito = $this->db->select('no_rekening, nama_nasabah, rate_bunga')
            ->where('id', $data['deposito_id'])
            ->get('tbdeposito')->row();
        
        $insert_data = [
            'deposito_id' => $data['deposito_id'],
            'no_rekening' => $data['no_rekening'] ?? ($deposito->no_rekening ?? null),
            'nama_nasabah' => $data['nama_nasabah'] ?? ($deposito->nama_nasabah ?? null),
            'jumlah_bunga' => $data['jumlah_bunga'],
            'rate_bunga' => $data['rate_bunga'] ?? ($deposito->rate_bunga ?? null),
            'tanggal_perhitungan' => $data['tanggal_perhitungan'],
            'status_penarikan' => $data['status_penarikan'] ?? 'belum_ditarik',
            'input_method' => 'import',
            'pegawai_id' => $data['pegawai_id'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'import_batch_id' => $data['import_batch_id'] ?? null
        ];
        
        $this->db->insert($this->table, $insert_data);
        
        return [
            'action' => 'insert',
            'id' => $this->db->insert_id(),
            'message' => "Bunga berhasil diimport"
        ];
    }
    
    /**
     * Batch import bunga records from Excel
     * @param array $rows Array of bunga data
     * @param string $batch_id Import batch ID
     * @param int $pegawai_id ID of employee performing import
     * @return array Import results
     */
    public function batch_import_bunga($rows, $batch_id, $pegawai_id)
    {
        $results = [
            'total' => count($rows),
            'inserted' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];
        
        $this->db->trans_start();
        
        foreach ($rows as $index => $row) {
            try {
                // Find deposito by name or no_seri
                $deposito = null;
                if (!empty($row['deposito_id'])) {
                    $deposito = $this->db->where('id', $row['deposito_id'])
                        ->get('tbdeposito')->row();
                } elseif (!empty($row['no_seri'])) {
                    $deposito = $this->db->where('no_seri', $row['no_seri'])
                        ->get('tbdeposito')->row();
                } elseif (!empty($row['nama'])) {
                    // First try exact match on nama_nasabah in tbdeposito (for imported records)
                    $deposito = $this->db->where('LOWER(nama_nasabah)', strtolower(trim($row['nama'])))
                        ->get('tbdeposito')->row();
                    
                    // If not found, try via tbnasabah
                    if (!$deposito) {
                        $deposito = $this->db->select('d.*')
                            ->from('tbdeposito d')
                            ->join('tbnasabah n', 'n.id = d.nasabah_id', 'left')
                            ->where('LOWER(n.nama_lengkap)', strtolower(trim($row['nama'])))
                            ->get()->row();
                    }
                    
                    // Try partial match if still not found
                    if (!$deposito) {
                        $deposito = $this->db->like('LOWER(nama_nasabah)', strtolower(trim($row['nama'])))
                            ->get('tbdeposito')->row();
                    }
                }
                
                if (!$deposito) {
                    $results['errors']++;
                    $results['details'][] = [
                        'row' => $index + 1,
                        'nama' => $row['nama'] ?? 'Unknown',
                        'action' => 'error',
                        'message' => 'Deposito tidak ditemukan untuk: ' . ($row['nama'] ?? 'Unknown')
                    ];
                    continue;
                }
                
                // Prepare bunga data
                $bunga_data = [
                    'deposito_id' => $deposito->id,
                    'no_rekening' => $deposito->no_rekening,
                    'nama_nasabah' => $deposito->nama_nasabah,
                    'jumlah_bunga' => floatval($row['jumlah_bunga'] ?? 0),
                    'rate_bunga' => $deposito->rate_bunga,
                    'tanggal_perhitungan' => $row['tanggal_perhitungan'] ?? date('Y-m-d'),
                    'status_penarikan' => $row['status_penarikan'] ?? 'sudah_ditarik', // Imported payments are already paid
                    'pegawai_id' => $pegawai_id,
                    'keterangan' => $row['keterangan'] ?? 'Import dari Excel',
                    'import_batch_id' => $batch_id
                ];
                
                $result = $this->insert_bunga_import($bunga_data, true);
                
                $results['details'][] = [
                    'row' => $index + 1,
                    'nama' => $row['nama'] ?? 'Unknown',
                    'bulan' => date('F Y', strtotime($bunga_data['tanggal_perhitungan'])),
                    'action' => $result['action'],
                    'message' => $result['message']
                ];
                
                $results[$result['action'] === 'insert' ? 'inserted' : 'skipped']++;
                
            } catch (Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'row' => $index + 1,
                    'nama' => $row['nama'] ?? 'Unknown',
                    'action' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            $results['errors'] = $results['total'];
            $results['inserted'] = 0;
        }
        
        return $results;
    }
    
    /**
     * Parse interest payments from Sheet 2 format
     * Converts the monthly payment columns to individual records
     * @param array $row Row data from Excel
     * @param array $month_columns Mapping of month columns (e.g., ['JAN' => 1, 'FEB' => 2, ...])
     * @return array Array of individual payment records
     */
    public function parse_sheet2_row($row, $month_columns, $year = null)
    {
        $year = $year ?: date('Y');
        $payments = [];
        
        foreach ($month_columns as $col_name => $month_num) {
            // Check if there's a payment for this month
            // Format from Excel: TGL BYR | Amount in paired columns
            $tgl_col = $col_name . '_TGL';
            $amt_col = $col_name;
            
            $amount = isset($row[$amt_col]) ? floatval($row[$amt_col]) : 0;
            $tgl = isset($row[$tgl_col]) ? $row[$tgl_col] : null;
            
            if ($amount > 0) {
                // Determine the date
                if ($tgl && is_numeric($tgl)) {
                    // Excel serial date
                    $tanggal = $this->excel_date_to_mysql($tgl);
                } elseif ($tgl) {
                    $tanggal = date('Y-m-d', strtotime($tgl));
                } else {
                    // Use last day of the month
                    $tanggal = date('Y-m-t', strtotime("$year-$month_num-01"));
                }
                
                $payments[] = [
                    'tanggal_perhitungan' => $tanggal,
                    'jumlah_bunga' => $amount,
                    'status_penarikan' => 'sudah_ditarik'
                ];
            }
        }
        
        return $payments;
    }
    
    /**
     * Convert Excel serial date to MySQL date
     */
    protected function excel_date_to_mysql($excel_date, $datemode = 0)
    {
        if (empty($excel_date) || !is_numeric($excel_date)) {
            return date('Y-m-d');
        }
        
        $base_date = ($datemode == 0) ? strtotime('1899-12-30') : strtotime('1904-01-01');
        $timestamp = $base_date + ($excel_date * 86400);
        
        return date('Y-m-d', $timestamp);
    }
}
