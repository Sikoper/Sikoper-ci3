<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_harian_model extends CI_Model
{
    // Configuration for Deposits (Setoran)
    var $setoran_table = 'tbdetail_simpanan';
    var $setoran_column_order = array(null, 'tanggal_setoran', 'jumlah_setoran');
    var $setoran_column_search = array('TIME(tanggal_setoran)', 'jumlah_setoran');
    var $setoran_order = array('tanggal_setoran' => 'desc');

    // Configuration for Withdrawals (Penarikan)
    var $penarikan_table = 'tbdetail_penarikan';
    var $penarikan_column_order = array(null, 'tanggal_penarikan', 'jumlah_penarikan');
    var $penarikan_column_search = array('TIME(tanggal_penarikan)', 'jumlah_penarikan');
    var $penarikan_order = array('tanggal_penarikan' => 'desc');

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // ====================================================================
    // DEPOSIT (SETORAN) FUNCTIONS
    // ====================================================================

    private function _get_setoran_datatables_query()
    {
        $this->db->from($this->setoran_table);
        $this->db->where('DATE(tanggal_setoran) = CURDATE()');

        if (isset($_POST['search']['value']) && !empty($_POST['search']['value'])) {
            $search_value = $_POST['search']['value'];
            $this->db->group_start();
            foreach ($this->setoran_column_search as $i => $item) {
                ($i === 0) ? $this->db->like($item, $search_value) : $this->db->or_like($item, $search_value);
            }
            $this->db->group_end();
        }

        if (isset($_POST['order'])) {
            $col_index = $_POST['order']['0']['column'];
            $dir = $_POST['order']['0']['dir'];
            if (isset($this->setoran_column_order[$col_index])) {
                $this->db->order_by($this->setoran_column_order[$col_index], $dir);
            }
        } else if (isset($this->setoran_order)) {
            $order = $this->setoran_order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_setoran_datatables()
    {
        $this->_get_setoran_datatables_query();
        if ($_POST['length'] != -1) $this->db->limit($_POST['length'], $_POST['start']);
        return $this->db->get()->result();
    }

    function count_filtered_setoran()
    {
        $this->_get_setoran_datatables_query();
        return $this->db->get()->num_rows();
    }

    public function count_all_setoran()
    {
        $this->db->from($this->setoran_table)->where('DATE(tanggal_setoran) = CURDATE()');
        return $this->db->count_all_results();
    }

    public function get_setoran_summary()
    {
        $this->db->select_sum('jumlah_setoran', 'total_setoran');
        $this->db->from($this->setoran_table)->where('DATE(tanggal_setoran) = CURDATE()');
        return $this->db->get()->row();
    }

    // ====================================================================
    // WITHDRAWAL (PENARIKAN) FUNCTIONS
    // ====================================================================

    private function _get_penarikan_datatables_query()
    {
        $this->db->from($this->penarikan_table);
        $this->db->where('DATE(tanggal_penarikan) = CURDATE()');
        $this->db->where('status', 'disetujui'); // Only count approved withdrawals

        if (isset($_POST['search']['value']) && !empty($_POST['search']['value'])) {
            $search_value = $_POST['search']['value'];
            $this->db->group_start();
            foreach ($this->penarikan_column_search as $i => $item) {
                ($i === 0) ? $this->db->like($item, $search_value) : $this->db->or_like($item, $search_value);
            }
            $this->db->group_end();
        }

        if (isset($_POST['order'])) {
            $col_index = $_POST['order']['0']['column'];
            $dir = $_POST['order']['0']['dir'];
            if (isset($this->penarikan_column_order[$col_index])) {
                $this->db->order_by($this->penarikan_column_order[$col_index], $dir);
            }
        } else if (isset($this->penarikan_order)) {
            $order = $this->penarikan_order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_penarikan_datatables()
    {
        $this->_get_penarikan_datatables_query();
        if ($_POST['length'] != -1) $this->db->limit($_POST['length'], $_POST['start']);
        return $this->db->get()->result();
    }

    function count_filtered_penarikan()
    {
        $this->_get_penarikan_datatables_query();
        return $this->db->get()->num_rows();
    }

    public function count_all_penarikan()
    {
        $this->db->from($this->penarikan_table);
        $this->db->where('DATE(tanggal_penarikan) = CURDATE()');
        $this->db->where('status', 'disetujui');
        return $this->db->count_all_results();
    }

    public function get_penarikan_summary()
    {
        $this->db->select_sum('jumlah_penarikan', 'total_penarikan');
        $this->db->from($this->penarikan_table);
        $this->db->where('DATE(tanggal_penarikan) = CURDATE()');
        $this->db->where('status', 'disetujui');
        return $this->db->get()->row();
    }
}
