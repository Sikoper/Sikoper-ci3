<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga_model extends CI_Model
{
    var $table = 'tbtransaksi';
    var $column_order = array(null, 'nama_lengkap', 'no_rekening', 'tanggal_transaksi', 'jumlah_transaksi', 'bunga_riil', 'rate_bunga', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbsimpanan.no_rekening', 'tbtransaksi.tanggal_transaksi');
    var $order = array('created_at' => 'ASC');

    private function _get_datatables_query($start_date = null, $end_date = null)
    {
        // OPTIMIZED: Use denormalized columns when available, fallback to JOIN
        $this->db->select('tbtransaksi.*, 
            COALESCE(tbtransaksi.nama_nasabah, tbnasabah.nama_lengkap) as nama_lengkap, 
            COALESCE(tbtransaksi.no_rekening, tbsimpanan.no_rekening) as no_rekening');
        $this->db->from($this->table);
        $this->db->join('tbsimpanan', 'tbsimpanan.id = tbtransaksi.simpanan_id');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id', 'left');

        // filter tanggal
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) >=', $start_date);
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) <=', $end_date);
        }

        // search
        $i = 0;
        foreach ($this->column_search as $item) {
            if (isset($_POST['search']) && $_POST['search']['value']) {
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

        // order
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

        return $this->db->get()->result();
    }

    public function count_filtered($start_date = null, $end_date = null)
    {
        $this->_get_datatables_query($start_date, $end_date);
        return $this->db->get()->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function get_total_bunga_filtered($start_date = null, $end_date = null)
    {
        $this->db->select_sum('jumlah_transaksi');
        $this->db->from($this->table);
        $this->db->join('tbsimpanan', 'tbsimpanan.id = tbtransaksi.simpanan_id');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) >=', $start_date);
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) <=', $end_date);
        }

        $query = $this->db->get()->row();
        return $query->jumlah_transaksi ?? 0;
    }

    public function checkAndRunBunga()
    {
        $month = date('m');
        $year = date('Y');

        $exists = $this->db
            ->where('MONTH(tanggal)', $month, FALSE)
            ->where('YEAR(tanggal)', $year, FALSE)
            ->get('systems_log')
            ->num_rows();

        if ($exists > 0) {
            return false;
        }

        $this->bunga_proses();

        $this->db->insert('systems_log', ['tanggal' => date('Y-m-d')]);

        return true;
    }

    /**
     * FRAUD PREVENTION: Perhitungan bunga berdasarkan saldo terendah bulan lalu
     * Ini mencegah nasabah melakukan setoran besar di akhir bulan lalu ditarik
     * di awal bulan hanya untuk mengejar bunga.
     */
    public function bunga_proses()
    {
        $today = date('Y-m-d');
        $lastMonth = date('Y-m-d', strtotime('-1 month'));
        $prevMonthStart = date('Y-m-01', strtotime('-1 month'));
        $prevMonthEnd = date('Y-m-t', strtotime('-1 month'));

        // OPTIMIZED: Use denormalized bunga_rate when available, fallback to JOIN
        $this->db->select('tbsimpanan.id, tbsimpanan.nasabah_id, tbsimpanan.jumlah_simpanan, tbsimpanan.tanggal_simpanan, tbsimpanan.no_rekening, tbnasabah.nama_lengkap as nama_nasabah, 
            COALESCE(tbsimpanan.bunga_rate, tbjenistabungan.bunga) as bunga');
        $this->db->from('tbsimpanan');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbsimpanan.jenistabungan_id', 'left');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id', 'left');
        $this->db->where('tbsimpanan.tanggal_simpanan <=', $prevMonthEnd . ' 23:59:59');
        $this->db->where('tbsimpanan.status', 'aktif');
        $this->db->where('(tbsimpanan.bunga_rate > 0 OR tbjenistabungan.bunga > 0)');
        $simpananList = $this->db->get()->result();

        foreach ($simpananList as $simpanan) {
            $bungaRate = (float) $simpanan->bunga;

            // Check if bunga already processed for the target month (prevMonthEnd)
            $targetMonth = date('m', strtotime($prevMonthEnd));
            $targetYear = date('Y', strtotime($prevMonthEnd));
            
            $alreadyGiven = $this->db
                ->where('simpanan_id', $simpanan->id)
                ->where('MONTH(tanggal_transaksi)', $targetMonth)
                ->where('YEAR(tanggal_transaksi)', $targetYear)
                ->get('tbtransaksi')
                ->num_rows();

            if ($alreadyGiven > 0) {
                continue;
            }

            // FRAUD PREVENTION: Hitung saldo terendah bulan lalu
            // Menggunakan query transaksi lengkap sampai akhir bulan lalu untuk mendapatkan saldo yang akurat
            $prevMonthEndFull = $prevMonthEnd . ' 23:59:59';
            $historyQuery = $this->db->query("
                SELECT tipe, jumlah, tanggal
                FROM (
                    SELECT 'setor' as tipe, jumlah_setoran as jumlah, tanggal_setoran as tanggal
                    FROM tbdetail_simpanan 
                    WHERE simpanan_id = ? AND tanggal_setoran <= ?
                    UNION ALL
                    SELECT 'tarik' as tipe, jumlah_penarikan as jumlah, tanggal_penarikan as tanggal
                    FROM tbdetail_penarikan 
                    WHERE simpanan_id = ? AND status = 'disetujui' AND tanggal_penarikan <= ?
                ) transactions
                ORDER BY tanggal ASC
            ", [$simpanan->id, $prevMonthEndFull, $simpanan->id, $prevMonthEndFull]);
            
            $transactions = $historyQuery->result();
            
            $runningBalance = 0;
            $minBalance = null;
            $prevMonthString = date('Y-m', strtotime($prevMonthStart));
            
            foreach ($transactions as $trx) {
                if ($trx->tipe === 'setor') {
                    $runningBalance += (float) $trx->jumlah;
                } else {
                    $runningBalance -= (float) $trx->jumlah;
                }
                
                // Track min balance only during the previous month
                if (date('Y-m', strtotime($trx->tanggal)) === $prevMonthString) {
                    if ($minBalance === null || $runningBalance < $minBalance) {
                        $minBalance = $runningBalance;
                    }
                }
            }
            
            // If no transactions happened last month, the minBalance is simply the ending running balance
            if ($minBalance === null) {
                $minBalance = $runningBalance;
            }
            
            $saldo = $minBalance > 0 ? $minBalance : 0;

            if ($saldo <= 0)
                continue;

            $bungaAmount = ($bungaRate / 100) * $saldo;
            $bungaRiil = $this->round_to_nearest_hundred($bungaAmount);

            if ($bungaAmount <= 0)
                continue;

            // Insert bunga transaction (using previous month's end date)
            $this->db->insert('tbtransaksi', [
                'simpanan_id' => $simpanan->id,
                'no_rekening' => $simpanan->no_rekening,
                'nama_nasabah' => $simpanan->nama_nasabah,
                'tanggal_transaksi' => $prevMonthEnd,
                'jumlah_transaksi' => $bungaRiil, // Use rounded value for transaction
                'bunga_riil' => $bungaAmount, // Store exact value as raw
                'rate_bunga' => $bungaRate
            ]);

            // Update saldo (atomic update)
            $this->db->set('jumlah_simpanan', 'jumlah_simpanan + ' . $bungaAmount, false);
            $this->db->where('id', $simpanan->id);
            $this->db->update('tbsimpanan');
        }
    }

    public function get_data_by_id($id)
    {
        return $this->db
            ->select('id, simpanan_id, jumlah_transaksi, rate_bunga')
            ->from('tbtransaksi')
            ->where('id', $id)
            ->get()
            ->row();
    }
    public function delete_data($id)
    {
        return $this->db->delete('tbtransaksi', ['id' => $id]);
    }

    function round_to_nearest_hundred($value)
    {
        return floor($value / 100) * 100;
    }

    /**
     * Get all interest data for report printing (without pagination)
     */
    public function get_report_data($start_date, $end_date)
    {
        $this->db->select('tbtransaksi.*, 
            COALESCE(tbtransaksi.nama_nasabah, tbnasabah.nama_lengkap) as nama_lengkap, 
            COALESCE(tbtransaksi.no_rekening, tbsimpanan.no_rekening) as no_rekening,
            tbsimpanan.jumlah_simpanan');
        $this->db->from($this->table);
        $this->db->join('tbsimpanan', 'tbsimpanan.id = tbtransaksi.simpanan_id');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id', 'left');
        
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) >=', $start_date);
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) <=', $end_date);
        }
        
        $this->db->order_by('tbtransaksi.tanggal_transaksi', 'ASC');
        return $this->db->get()->result();
    }
}
