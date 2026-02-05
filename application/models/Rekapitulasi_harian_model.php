<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_harian_model extends CI_Model
{
    // Configuration for Deposits (Setoran)
    var $setoran_table = 'tbdetail_simpanan';
    var $setoran_column_order = array(null, 'tanggal_setoran', 'jumlah_setoran', null);
    var $setoran_column_search = array('ts.no_rekening', 'tn.nama_lengkap');
    var $setoran_order = array('tanggal_setoran' => 'desc');

    // Configuration for Withdrawals (Penarikan)
    var $penarikan_table = 'tbdetail_penarikan';
    var $penarikan_column_order = array(null, 'tanggal_penarikan', 'jumlah_penarikan', null);
    var $penarikan_column_search = array('ts.no_rekening', 'tn.nama_lengkap');
    var $penarikan_order = array('tanggal_penarikan' => 'desc');

    // Selected date for filtering
    private $selected_date = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // ====================================================================
    // DEPOSIT (SETORAN) FUNCTIONS
    // ====================================================================

    private function _get_setoran_datatables_query($tanggal = null)
    {
        // Use selected date or default to today
        $tanggal = $tanggal ?? date('Y-m-d');
        $date_start = $tanggal . ' 00:00:00';
        $date_end = $tanggal . ' 23:59:59';

        // UX FIX: JOIN ke nasabah untuk menampilkan nama
        $this->db->select('tds.*, ts.no_rekening, tn.nama_lengkap as nama_nasabah');
        $this->db->from($this->setoran_table . ' tds');
        $this->db->join('tbsimpanan ts', 'ts.id = tds.simpanan_id', 'left');
        $this->db->join('tbnasabah tn', 'tn.id = ts.nasabah_id', 'left');
        $this->db->where('tds.tanggal_setoran >=', $date_start);
        $this->db->where('tds.tanggal_setoran <=', $date_end);

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

    function get_setoran_datatables($tanggal = null)
    {
        $this->selected_date = $tanggal;
        $this->_get_setoran_datatables_query($tanggal);
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        return $this->db->get()->result();
    }

    function count_filtered_setoran($tanggal = null)
    {
        $this->_get_setoran_datatables_query($tanggal);
        return $this->db->get()->num_rows();
    }

    public function count_all_setoran($tanggal = null)
    {
        // Use selected date or default to today
        $tanggal = $tanggal ?? date('Y-m-d');
        $date_start = $tanggal . ' 00:00:00';
        $date_end = $tanggal . ' 23:59:59';

        $this->db->from($this->setoran_table);
        $this->db->where('tanggal_setoran >=', $date_start);
        $this->db->where('tanggal_setoran <=', $date_end);
        return $this->db->count_all_results();
    }

    public function get_setoran_summary($tanggal = null)
    {
        // Use selected date or default to today
        $tanggal = $tanggal ?? date('Y-m-d');
        $date_start = $tanggal . ' 00:00:00';
        $date_end = $tanggal . ' 23:59:59';

        $this->db->select_sum('jumlah_setoran', 'total_setoran');
        $this->db->from($this->setoran_table);
        $this->db->where('tanggal_setoran >=', $date_start);
        $this->db->where('tanggal_setoran <=', $date_end);
        return $this->db->get()->row();
    }

    // ====================================================================
    // WITHDRAWAL (PENARIKAN) FUNCTIONS
    // ====================================================================

    private function _get_penarikan_datatables_query($tanggal = null)
    {
        // Use selected date or default to today
        $tanggal = $tanggal ?? date('Y-m-d');
        $date_start = $tanggal . ' 00:00:00';
        $date_end = $tanggal . ' 23:59:59';

        // UX FIX: JOIN ke nasabah untuk menampilkan nama
        $this->db->select('tdp.*, ts.no_rekening, tn.nama_lengkap as nama_nasabah');
        $this->db->from($this->penarikan_table . ' tdp');
        $this->db->join('tbsimpanan ts', 'ts.id = tdp.simpanan_id', 'left');
        $this->db->join('tbnasabah tn', 'tn.id = ts.nasabah_id', 'left');
        $this->db->where('tdp.tanggal_penarikan >=', $date_start);
        $this->db->where('tdp.tanggal_penarikan <=', $date_end);
        $this->db->where('tdp.status', 'disetujui'); // Only count approved withdrawals

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

    function get_penarikan_datatables($tanggal = null)
    {
        $this->_get_penarikan_datatables_query($tanggal);
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        return $this->db->get()->result();
    }

    function count_filtered_penarikan($tanggal = null)
    {
        $this->_get_penarikan_datatables_query($tanggal);
        return $this->db->get()->num_rows();
    }

    public function count_all_penarikan($tanggal = null)
    {
        // Use selected date or default to today
        $tanggal = $tanggal ?? date('Y-m-d');
        $date_start = $tanggal . ' 00:00:00';
        $date_end = $tanggal . ' 23:59:59';

        $this->db->from($this->penarikan_table);
        $this->db->where('tanggal_penarikan >=', $date_start);
        $this->db->where('tanggal_penarikan <=', $date_end);
        $this->db->where('status', 'disetujui');
        return $this->db->count_all_results();
    }

    public function get_penarikan_summary($tanggal = null)
    {
        // Use selected date or default to today
        $tanggal = $tanggal ?? date('Y-m-d');
        $date_start = $tanggal . ' 00:00:00';
        $date_end = $tanggal . ' 23:59:59';

        $this->db->select_sum('jumlah_penarikan', 'total_penarikan');
        $this->db->from($this->penarikan_table);
        $this->db->where('tanggal_penarikan >=', $date_start);
        $this->db->where('tanggal_penarikan <=', $date_end);
        $this->db->where('status', 'disetujui');
        return $this->db->get()->row();
    }
}
