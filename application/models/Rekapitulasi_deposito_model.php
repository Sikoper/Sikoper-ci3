<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_deposito_model extends CI_Model
{
    var $table = 'tbdeposito';
    var $column_order = array(null, 'tbdeposito.no_rekening', 'tbnasabah.nama_lengkap', 'saldo_awal_bulan', 'bunga_bulan_ini', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbdeposito.no_rekening');
    var $order = array('tbdeposito.no_rekening' => 'ASC');

    private function _get_datatables_query($bulan, $tahun)
    {
        // Define the first day of the selected month for date calculations
        $first_day_of_month = date('Y-m-01', strtotime("$tahun-$bulan-01"));
        $end_of_selected_month = date('Y-m-t', strtotime("$tahun-$bulan-01"));

        $this->db->select(
            'tbdeposito.id,
             tbdeposito.no_rekening, 
             tbnasabah.nama_lengkap as nama_nasabah,
             
             -- ✅ Calculate Beginning of Month Balance: Initial deposit + all interest from PREVIOUS months.
             (
                tbdeposito.jumlah_deposito + 
                COALESCE((SELECT SUM(ttd.jumlah_transaksi) 
                 FROM tbtransaksi_deposito ttd 
                 WHERE ttd.deposito_id = tbdeposito.id 
                 AND ttd.tanggal_transaksi < ' . $this->db->escape($first_day_of_month) . '), 0)
             ) as saldo_awal_bulan,

             -- ✅ Calculate Interest for THIS specific month only.
             (SELECT SUM(ttd.jumlah_transaksi) 
              FROM tbtransaksi_deposito ttd 
              WHERE ttd.deposito_id = tbdeposito.id 
              AND MONTH(ttd.tanggal_transaksi) = ' . $this->db->escape($bulan) . '
              AND YEAR(ttd.tanggal_transaksi) = ' . $this->db->escape($tahun) . ') as bunga_bulan_ini'
        );

        $this->db->from($this->table);
        $this->db->join('tbnasabah', 'tbnasabah.id = tbdeposito.nasabah_id');
        $this->db->where('tbdeposito.status', 'aktif');

        // Filter to only show accounts that were created ON OR BEFORE the report date
        $this->db->where('tbdeposito.tanggal_deposito <=', $end_of_selected_month);

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
        // Define date boundaries
        $first_day_of_month = date('Y-m-01', strtotime("$tahun-$bulan-01"));
        $end_of_selected_month = date('Y-m-t', strtotime("$tahun-$bulan-01"));

        // Base query for active deposits relevant to the selected period
        $this->db->from('tbdeposito');
        $this->db->where('status', 'aktif');
        $this->db->where('tanggal_deposito <=', $end_of_selected_month);
        $active_deposits_query = $this->db->get_compiled_select();

        // --- Query for Total Beginning Balance ---
        $this->db->select_sum('jumlah_deposito', 'total_initial_deposits');
        $initial_deposits_result = $this->db->get_where('tbdeposito', [
            'status' => 'aktif',
            'tanggal_deposito <=' => $end_of_selected_month
        ])->row();

        $this->db->select_sum('ttd.jumlah_transaksi', 'total_previous_interest');
        $this->db->from('tbtransaksi_deposito as ttd');
        $this->db->join('tbdeposito as td', 'td.id = ttd.deposito_id');
        $this->db->where('td.status', 'aktif');
        $this->db->where('td.tanggal_deposito <=', $end_of_selected_month);
        $this->db->where('ttd.tanggal_transaksi <', $first_day_of_month);
        $previous_interest_result = $this->db->get()->row();

        $total_saldo_awal = ($initial_deposits_result->total_initial_deposits ?? 0) + ($previous_interest_result->total_previous_interest ?? 0);

        // --- Query for Total Interest THIS Month ---
        $this->db->select_sum('ttd.jumlah_transaksi', 'total_bunga_bulan_ini');
        $this->db->from('tbtransaksi_deposito as ttd');
        $this->db->join('tbdeposito as td', 'td.id = ttd.deposito_id');
        $this->db->where('td.status', 'aktif');
        $this->db->where('td.tanggal_deposito <=', $end_of_selected_month);
        $this->db->where('MONTH(ttd.tanggal_transaksi)', $bulan);
        $this->db->where('YEAR(ttd.tanggal_transaksi)', $tahun);
        $bunga_bulan_ini_result = $this->db->get()->row();

        return [
            'total_saldo_awal' => $total_saldo_awal,
            'total_bunga_bulan_ini'  => $bunga_bulan_ini_result->total_bunga_bulan_ini ?? 0,
        ];
    }
}
