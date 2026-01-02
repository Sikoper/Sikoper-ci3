<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_deposito_model extends CI_Model
{
    var $table = 'tbdeposito';
    // ✅ MODIFIED: Renamed aliases for clarity
    var $column_order = array(null, 'd.no_rekening', 'n.nama_lengkap', 'saldo_awal', 'bunga_periode', null);
    var $column_search = array('n.nama_lengkap', 'd.no_rekening');
    var $order = array('d.no_rekening' => 'ASC');

    private function _get_datatables_query($bulan, $tahun)
    {
        // ✅ NEW: Dynamic date range logic
        if ($bulan == 'all') {
            $start_of_period = date('Y-01-01', strtotime("$tahun-01-01"));
            if ($tahun == date('Y')) {
                // For the current year, use today's date
                $end_of_period = date('Y-m-d');
            } else {
                // For past years, use the end of that year
                $end_of_period = date('Y-12-31', strtotime("$tahun-12-31"));
            }
        } else {
            $start_of_period = date('Y-m-01', strtotime("$tahun-$bulan-01"));
            $end_of_period = date('Y-m-t', strtotime($start_of_period));
        }

        // Subquery for starting balance. The logic works for both month/year views.
        $saldo_awal_subquery = "
            d.jumlah_deposito + 
            COALESCE((SELECT SUM(bl.jumlah_bunga) 
                      FROM tb_bunga_deposito_log bl 
                      WHERE bl.deposito_id = d.id 
                      AND bl.tanggal_perhitungan < " . $this->db->escape($start_of_period) . "), 0)
        ";

        // ✅ MODIFIED: Subquery for interest gained during the period
        if ($bulan == 'all') {
            $bunga_periode_subquery = "
                (SELECT SUM(bl.jumlah_bunga) 
                 FROM tb_bunga_deposito_log bl 
                 WHERE bl.deposito_id = d.id 
                 AND YEAR(bl.tanggal_perhitungan) = " . $this->db->escape($tahun) . "
                 AND bl.tanggal_perhitungan <= " . $this->db->escape($end_of_period) . ")
            ";
        } else {
            $bunga_periode_subquery = "
                (SELECT SUM(bl.jumlah_bunga) 
                 FROM tb_bunga_deposito_log bl 
                 WHERE bl.deposito_id = d.id 
                 AND MONTH(bl.tanggal_perhitungan) = " . $this->db->escape($bulan) . "
                 AND YEAR(bl.tanggal_perhitungan) = " . $this->db->escape($tahun) . ")
            ";
        }

        $this->db->select(
            'd.id,
             d.no_rekening, 
             COALESCE(d.nama_nasabah, n.nama_lengkap) as nama_nasabah,
             (' . $saldo_awal_subquery . ') as saldo_awal,
             (' . $bunga_periode_subquery . ') as bunga_periode'
        );

        $this->db->from('tbdeposito as d');
        // OPTIMIZED: Use LEFT JOIN as fallback for records missing denormalized data
        $this->db->join('tbnasabah as n', 'n.id = d.nasabah_id', 'left');
        // FIXED: Removed status filter to include closed accounts in historical reports
        $this->db->where('d.tanggal_deposito <=', $end_of_period);

        // Search and Order logic remains the same
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

    function get_datatables($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);
        if (isset($_POST['length']) && $_POST['length'] != -1) {
            $this->db->limit($_POST['length'], (isset($_POST['start']) ? $_POST['start'] : 0));
        }
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    function count_filtered($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);
        $query = $this->db->get();
        return $query ? $query->num_rows() : 0;
    }

    public function count_all()
    {
        // FIXED: Removed status filter for count_all to be consistent
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function get_summary_data($bulan, $tahun)
    {
        // ✅ NEW: Dynamic date range logic, same as above
        if ($bulan == 'all') {
            $start_of_period = date('Y-01-01', strtotime("$tahun-01-01"));
            if ($tahun == date('Y')) {
                $end_of_period = date('Y-m-d');
            } else {
                $end_of_period = date('Y-12-31', strtotime("$tahun-12-31"));
            }
        } else {
            $start_of_period = date('Y-m-01', strtotime("$tahun-$bulan-01"));
            $end_of_period = date('Y-m-t', strtotime($start_of_period));
        }

        // Total Initial Deposits
        $this->db->select_sum('d.jumlah_deposito', 'total_pokok');
        // FIXED: Removed status filter
        $this->db->from('tbdeposito d')->where('d.tanggal_deposito <=', $end_of_period);
        $total_pokok = ($this->db->get()->row()->total_pokok ?? 0);

        // Total Prior Interest
        $this->db->select_sum('bl.jumlah_bunga', 'total_bunga_sebelumnya');
        $this->db->from('tb_bunga_deposito_log bl')->join('tbdeposito d', 'd.id = bl.deposito_id');
        // FIXED: Removed status filter
        $this->db->where('d.tanggal_deposito <=', $end_of_period)->where('bl.tanggal_perhitungan <', $start_of_period);
        $total_bunga_sebelumnya = ($this->db->get()->row()->total_bunga_sebelumnya ?? 0);

        $total_saldo_awal = $total_pokok + $total_bunga_sebelumnya;

        // ✅ MODIFIED: Total Interest for the Current Period
        $this->db->select_sum('bl.jumlah_bunga', 'total_bunga_bulan_ini');
        $this->db->from('tb_bunga_deposito_log bl')->join('tbdeposito d', 'd.id = bl.deposito_id');
        // FIXED: Removed status filter
        $this->db->where('d.tanggal_deposito <=', $end_of_period);

        if ($bulan == 'all') {
            $this->db->where('YEAR(bl.tanggal_perhitungan)', $tahun)->where('bl.tanggal_perhitungan <=', $end_of_period);
        } else {
            $this->db->where('MONTH(bl.tanggal_perhitungan)', $bulan)->where('YEAR(bl.tanggal_perhitungan)', $tahun);
        }
        $total_bunga_bulan_ini = ($this->db->get()->row()->total_bunga_bulan_ini ?? 0);

        return [
            'total_saldo_awal' => $total_saldo_awal,
            'total_bunga_bulan_ini' => $total_bunga_bulan_ini,
        ];
    }
}
