<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_tabungan_model extends CI_Model
{
    var $table = 'tbsimpanan';
    var $column_order = array(null, 'tbsimpanan.no_rekening', 'tbnasabah.nama_lengkap', 'tbsimpanan.jumlah_simpanan', 'bunga', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbsimpanan.no_rekening');
    var $order = array('tbsimpanan.no_rekening' => 'ASC');

    private function _get_datatables_query($bulan, $tahun)
    {
        $this->db->select(
            'tbsimpanan.id,
             tbsimpanan.no_rekening, 
             tbnasabah.nama_lengkap as nama_nasabah,
             tbsimpanan.jumlah_simpanan as saldo_pokok,
             (SELECT SUM(tbtransaksi.jumlah_transaksi) 
              FROM tbtransaksi 
              WHERE tbtransaksi.simpanan_id = tbsimpanan.id 
              AND MONTH(tbtransaksi.tanggal_transaksi) = ' . $this->db->escape($bulan) . '
              AND YEAR(tbtransaksi.tanggal_transaksi) = ' . $this->db->escape($tahun) . ') as bunga'
        );

        $this->db->from($this->table);
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id');
        $this->db->where('tbsimpanan.status', 'aktif');

        // ✅ CORRECTED LOGIC: Show accounts that were created ON OR BEFORE the selected month.
        // This creates a "snapshot" of the accounts that existed at that time.
        $end_of_month = date('Y-m-t', strtotime("$tahun-$bulan-01"));
        $this->db->where('tbsimpanan.tanggal_simpanan <=', $end_of_month);

        // --- Standard DataTables search and order logic ---
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
        // ✅ CORRECTED LOGIC: Calculate end date for the filter
        $end_of_month = date('Y-m-t', strtotime("$tahun-$bulan-01"));

        // --- Query for Total Principal Balance ---
        $this->db->select_sum('jumlah_simpanan', 'total_saldo_pokok');
        $this->db->from($this->table);
        $this->db->where('tbsimpanan.status', 'aktif');
        // Filter summary total to only include accounts that existed at that time
        $this->db->where('tbsimpanan.tanggal_simpanan <=', $end_of_month);
        $saldo_pokok_result = $this->db->get()->row();

        // --- Query for Total Interest (This part was already correct) ---
        $this->db->select_sum('jumlah_transaksi', 'total_bunga');
        $this->db->from('tbtransaksi');
        // We only sum interest FROM that specific month
        $this->db->where('MONTH(tanggal_transaksi)', $bulan);
        $this->db->where('YEAR(tanggal_transaksi)', $tahun);
        $bunga_result = $this->db->get()->row();

        return [
            'total_saldo_pokok' => $saldo_pokok_result->total_saldo_pokok ?? 0,
            'total_bunga'       => $bunga_result->total_bunga ?? 0,
        ];
    }
}
