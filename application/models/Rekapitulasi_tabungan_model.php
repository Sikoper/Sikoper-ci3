<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_tabungan_model extends CI_Model
{
    var $table = 'tbsimpanan';
    var $column_order = array(null, 'tbsimpanan.no_rekening', 'tbnasabah.nama_lengkap', 'saldo_pokok', 'bunga', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbsimpanan.no_rekening');
    var $order = array('tbsimpanan.no_rekening' => 'ASC');

    private function _get_datatables_query($bulan, $tahun)
    {
        // Define the date range for the selected reporting period
        $start_of_month = date('Y-m-01', strtotime("$tahun-$bulan-01"));
        $end_of_month = date('Y-m-t', strtotime($start_of_month));

        // ✅ MODIFIED: Subquery to calculate the final principal balance.
        // This includes all deposits up to the end of the month PLUS all interest from BEFORE the current month.
        $saldo_pokok_subquery = "
            COALESCE((SELECT SUM(tds.jumlah_setoran) 
                       FROM tbdetail_simpanan tds 
                       WHERE tds.simpanan_id = tbsimpanan.id 
                       AND tds.tanggal_setoran <= " . $this->db->escape($end_of_month) . "), 0)
            + 
            COALESCE((SELECT SUM(tbt.jumlah_transaksi) 
                       FROM tbtransaksi tbt 
                       WHERE tbt.simpanan_id = tbsimpanan.id 
                       AND tbt.tanggal_transaksi < " . $this->db->escape($start_of_month) . "), 0)
        ";

        $this->db->select(
            'tbsimpanan.id,
             tbsimpanan.no_rekening, 
             tbnasabah.nama_lengkap as nama_nasabah,
             (' . $saldo_pokok_subquery . ') as saldo_pokok,
             
             -- Interest calculation for ONLY the specific reporting month (unchanged)
             (SELECT SUM(tbtransaksi.jumlah_transaksi) 
              FROM tbtransaksi 
              WHERE tbtransaksi.simpanan_id = tbsimpanan.id 
              AND MONTH(tbtransaksi.tanggal_transaksi) = ' . $this->db->escape($bulan) . '
              AND YEAR(tbtransaksi.tanggal_transaksi) = ' . $this->db->escape($tahun) . ') as bunga'
        );

        $this->db->from($this->table);
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id');
        $this->db->where('tbsimpanan.status', 'aktif');
        $this->db->where('tbsimpanan.tanggal_simpanan <=', $end_of_month);

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

    function get_datatables($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        $this->db->where('status', 'aktif');
        return $this->db->count_all_results();
    }

    public function get_summary_data($bulan, $tahun)
    {
        // Define the date range for consistent filtering
        $start_of_month = date('Y-m-01', strtotime("$tahun-$bulan-01"));
        $end_of_month = date('Y-m-t', strtotime($start_of_month));

        // --- Query for Total Deposits ---
        $this->db->select_sum('tds.jumlah_setoran', 'total_deposits');
        $this->db->from('tbdetail_simpanan as tds');
        $this->db->join('tbsimpanan as ts', 'ts.id = tds.simpanan_id');
        $this->db->where('ts.status', 'aktif');
        $this->db->where('tds.tanggal_setoran <=', $end_of_month);
        $this->db->where('ts.tanggal_simpanan <=', $end_of_month);
        $total_deposits_result = $this->db->get()->row();
        $total_deposits = $total_deposits_result->total_deposits ?? 0;

        // --- ✅ MODIFIED: Query for Total Interest from PRIOR months ---
        $this->db->select_sum('tbt.jumlah_transaksi', 'total_prior_interest');
        $this->db->from('tbtransaksi as tbt');
        $this->db->join('tbsimpanan as ts', 'ts.id = tbt.simpanan_id');
        $this->db->where('ts.status', 'aktif');
        $this->db->where('ts.tanggal_simpanan <=', $end_of_month);
        $this->db->where('tbt.tanggal_transaksi <', $start_of_month); // Only interest BEFORE this month
        $prior_interest_result = $this->db->get()->row();
        $total_prior_interest = $prior_interest_result->total_prior_interest ?? 0;

        // --- Query for Total Interest for the CURRENT month (unchanged) ---
        $this->db->select_sum('jumlah_transaksi', 'total_bunga');
        $this->db->from('tbtransaksi');
        $this->db->join('tbsimpanan as ts', 'ts.id = simpanan_id');
        $this->db->where('ts.status', 'aktif');
        $this->db->where('ts.tanggal_simpanan <=', $end_of_month);
        $this->db->where('MONTH(tanggal_transaksi)', $bulan);
        $this->db->where('YEAR(tanggal_transaksi)', $tahun);
        $bunga_result = $this->db->get()->row();
        
        // Final calculation combines deposits and prior interest
        $total_saldo_pokok = $total_deposits + $total_prior_interest;

        return [
            'total_saldo_pokok' => $total_saldo_pokok,
            'total_bunga'       => $bunga_result->total_bunga ?? 0,
        ];
    }
}