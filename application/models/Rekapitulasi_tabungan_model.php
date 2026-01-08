<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_tabungan_model extends CI_Model
{
    var $table = 'tbsimpanan';
    var $column_order = array(null, 'tbsimpanan.no_rekening', 'nama_nasabah', 'saldo_pokok', 'bunga', null);
    var $column_search = array('tbsimpanan.nama_nasabah', 'tbsimpanan.no_rekening');
    var $order = array('tbsimpanan.no_rekening' => 'ASC');

    private function _get_datatables_query($bulan, $tahun)
    {
        // Set date range for queries
        if ($bulan == 'all') {
            $start_of_period = date('Y-01-01', strtotime("$tahun-01-01"));
            $end_of_period = date('Y-12-31', strtotime("$tahun-12-31"));
        } else {
            $start_of_period = date('Y-m-01', strtotime("$tahun-$bulan-01"));
            $end_of_period = date('Y-m-t', strtotime($start_of_period));
        }

        // SALDO POKOK: Simply use jumlah_simpanan from tbsimpanan
        // This is the balance that was imported or calculated
        $saldo_pokok_subquery = "COALESCE(tbsimpanan.jumlah_simpanan, 0)";

        // BUNGA: Sum of interest transactions for the selected period
        if ($bulan == 'all') {
            $bunga_subquery = "
                (SELECT SUM(tbtransaksi.jumlah_transaksi) 
                 FROM tbtransaksi 
                 WHERE tbtransaksi.simpanan_id = tbsimpanan.id 
                 AND YEAR(tbtransaksi.tanggal_transaksi) = " . $this->db->escape($tahun) . ")
            ";
        } else {
            $bunga_subquery = "
                (SELECT SUM(tbtransaksi.jumlah_transaksi) 
                 FROM tbtransaksi 
                 WHERE tbtransaksi.simpanan_id = tbsimpanan.id 
                 AND MONTH(tbtransaksi.tanggal_transaksi) = " . $this->db->escape($bulan) . "
                 AND YEAR(tbtransaksi.tanggal_transaksi) = " . $this->db->escape($tahun) . ")
            ";
        }

        $this->db->select(
            'tbsimpanan.id,
             tbsimpanan.no_rekening, 
             COALESCE(tbsimpanan.nama_nasabah, tbnasabah.nama_lengkap) as nama_nasabah,
             (' . $saldo_pokok_subquery . ') as saldo_pokok,
             ' . $bunga_subquery . ' as bunga'
        );

        $this->db->from($this->table);
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id', 'left');

        // NO DATE FILTER - Show all accounts regardless of when they were opened
        // This ensures imported data from previous year carries forward

        // Search logic
        $i = 0;
        foreach ($this->column_search as $item) {
            if (isset($_POST['search']['value']) && $_POST['search']['value']) {
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

        // Order logic
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
        if (isset($_POST['length']) && $_POST['length'] != -1)
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
        return $this->db->count_all_results();
    }

    public function get_summary_data($bulan, $tahun)
    {
        // SIMPLIFIED: Just sum jumlah_simpanan from all accounts
        // This gives us the total balance regardless of date filtering

        $this->db->select_sum('jumlah_simpanan', 'total_saldo');
        $this->db->from('tbsimpanan');
        $total_saldo_result = $this->db->get()->row();
        $total_saldo_pokok = $total_saldo_result->total_saldo ?? 0;

        // Sum bunga for the period
        $this->db->select_sum('jumlah_transaksi', 'total_bunga');
        $this->db->from('tbtransaksi');

        if ($bulan == 'all') {
            $this->db->where('YEAR(tanggal_transaksi)', $tahun);
        } else {
            $this->db->where('MONTH(tanggal_transaksi)', $bulan);
            $this->db->where('YEAR(tanggal_transaksi)', $tahun);
        }
        $bunga_result = $this->db->get()->row();

        return [
            'total_saldo_pokok' => $total_saldo_pokok,
            'total_bunga' => $bunga_result->total_bunga ?? 0,
        ];
    }
}