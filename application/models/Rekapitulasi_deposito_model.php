<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_deposito_model extends CI_Model
{
    var $table = 'tbdeposito';
    var $column_order = array(null, 'd.no_rekening', 'n.nama_lengkap', 'saldo_awal_bulan', 'bunga_bulan_ini', null);
    var $column_search = array('n.nama_lengkap', 'd.no_rekening');
    var $order = array('d.no_rekening' => 'ASC');

    private function _get_datatables_query($bulan, $tahun)
    {
        $first_day_of_month = date('Y-m-01', strtotime("$tahun-$bulan-01"));
        $end_of_selected_month = date('Y-m-t', strtotime("$tahun-$bulan-01"));

        $this->db->select(
            'd.id,
         d.no_rekening, 
         n.nama_lengkap as nama_nasabah,
         (
             d.jumlah_deposito + 
             COALESCE((SELECT SUM(bl.jumlah_bunga) 
                       FROM tb_bunga_deposito_log bl 
                       WHERE bl.deposito_id = d.id 
                       AND bl.tanggal_perhitungan < ' . $this->db->escape($first_day_of_month) . '), 0)
         ) as saldo_awal_bulan,
         (SELECT SUM(bl.jumlah_bunga) 
          FROM tb_bunga_deposito_log bl 
          WHERE bl.deposito_id = d.id 
          AND MONTH(bl.tanggal_perhitungan) = ' . $this->db->escape($bulan) . '
          AND YEAR(bl.tanggal_perhitungan) = ' . $this->db->escape($tahun) . ') as bunga_bulan_ini'
        );

        $this->db->from('tbdeposito as d');
        $this->db->join('tbnasabah as n', 'n.id = d.nasabah_id');
        $this->db->where('d.status', 'aktif');
        $this->db->where('d.tanggal_deposito <=', $end_of_selected_month);

        $i = 0;
        // Perbaikan kecil: sesuaikan nama kolom search dengan alias tabel (d dan n)
        $this->column_search = array('n.nama_lengkap', 'd.no_rekening');
        foreach ($this->column_search as $item) {
            if (isset($_POST['search']['value']) && $_POST['search']['value'] != '') {
                if ($i === 0) {
                    $this->db->group_start();
                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }
                if (count($this->column_search) - 1 == $i) $this->db->group_end();
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

    // GANTI SELURUH FUNGSI get_datatables() DENGAN INI
    function get_datatables($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);

        if (isset($_POST['length']) && $_POST['length'] != -1) {
            $this->db->limit($_POST['length'], (isset($_POST['start']) ? $_POST['start'] : 0));
        }

        $query = $this->db->get();

        // Tambahkan pengecekan jika query gagal
        if (!$query) {
            return []; // Kembalikan array kosong jika query error
        }

        return $query->result(); // Kembalikan hasil query
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

// GANTI SELURUH FUNGSI get_summary_data() DENGAN INI
public function get_summary_data($bulan, $tahun)
{
    $first_day_of_month = date('Y-m-01', strtotime("$tahun-$bulan-01"));
    $end_of_selected_month = date('Y-m-t', strtotime("$tahun-$bulan-01"));

    // Query untuk Total Saldo Awal (Pokok + Bunga Sebelumnya)
    $this->db->select_sum('d.jumlah_deposito', 'total_pokok');
    $this->db->from('tbdeposito d');
    $this->db->where('d.status', 'aktif');
    $this->db->where('d.tanggal_deposito <=', $end_of_selected_month);
    $query1 = $this->db->get();
    $total_pokok = $query1 ? ($query1->row()->total_pokok ?? 0) : 0;

    $this->db->select_sum('bl.jumlah_bunga', 'total_bunga_sebelumnya');
    $this->db->from('tb_bunga_deposito_log bl');
    $this->db->join('tbdeposito d', 'd.id = bl.deposito_id');
    $this->db->where('d.status', 'aktif');
    $this->db->where('d.tanggal_deposito <=', $end_of_selected_month);
    $this->db->where('bl.tanggal_perhitungan <', $first_day_of_month);
    $query2 = $this->db->get();
    $total_bunga_sebelumnya = $query2 ? ($query2->row()->total_bunga_sebelumnya ?? 0) : 0;

    $total_saldo_awal = $total_pokok + $total_bunga_sebelumnya;

    // Query untuk Total Bunga Bulan Ini
    $this->db->select_sum('bl.jumlah_bunga', 'total_bunga_bulan_ini');
    $this->db->from('tb_bunga_deposito_log bl');
    $this->db->join('tbdeposito d', 'd.id = bl.deposito_id');
    $this->db->where('d.status', 'aktif');
    $this->db->where('d.tanggal_deposito <=', $end_of_selected_month);
    $this->db->where('MONTH(bl.tanggal_perhitungan)', $bulan);
    $this->db->where('YEAR(bl.tanggal_perhitungan)', $tahun);
    $query3 = $this->db->get();
    $total_bunga_bulan_ini = $query3 ? ($query3->row()->total_bunga_bulan_ini ?? 0) : 0;

    return [
        'total_saldo_awal'      => $total_saldo_awal,
        'total_bunga_bulan_ini' => $total_bunga_bulan_ini,
    ];
}
}
