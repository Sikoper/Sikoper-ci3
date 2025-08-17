<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Nasabah_Bunga_model extends CI_Model
{
    // Define table and joins for reuse
    private $table = 'tbtransaksi';
    private $column_order = array(null, 'tbtransaksi.tanggal_transaksi', 'tbtransaksi.jumlah_transaksi', 'tbtransaksi.rate_bunga', 'tbtransaksi.bunga_riil', null);
    private $column_search = array('tbtransaksi.tanggal_transaksi', 'tbtransaksi.rate_bunga');
    private $order = array('tbtransaksi.tanggal_transaksi' => 'DESC');

    /**
     * The main query builder for the datatable.
     * Uses CI Query Builder for better readability and security.
     */
    private function _get_datatables_query($no_rekening = null)
    {
        $this->db->select("
            tbtransaksi.id,
            tbtransaksi.id AS source_id,
            tbtransaksi.tanggal_transaksi,
            tbtransaksi.jumlah_transaksi,
            tbtransaksi.rate_bunga,
            tbtransaksi.bunga_riil,
            'Simpanan' AS tipe, -- Kept for compatibility with the delete function's parameters
            tbnasabah.nama_lengkap,
            tbsimpanan.no_rekening
        ");
        $this->db->from($this->table);
        $this->db->join('tbsimpanan', 'tbsimpanan.id = tbtransaksi.simpanan_id');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id');

        // Filter by specific account number if provided
        if ($no_rekening !== null) {
            $this->db->where('tbsimpanan.no_rekening', $no_rekening);
        }

        // Handle searching
        $search_value = $_POST['search']['value'] ?? '';
        if (!empty($search_value)) {
            $this->db->group_start(); // Open bracket
            foreach ($this->column_search as $i => $item) {
                if ($i === 0) {
                    $this->db->like($item, $search_value);
                } else {
                    $this->db->or_like($item, $search_value);
                }
            }
            $this->db->group_end(); // Close bracket
        }

        // Handle ordering
        if (isset($_POST['order'])) {
            $column_index = $_POST['order'][0]['column'];
            $dir = $_POST['order'][0]['dir'];
            $this->db->order_by($this->column_order[$column_index], $dir);
        } else if (isset($this->order)) {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    /**
     * Fetches data for the datatable.
     */
    public function get_datatables($no_rekening = null)
    {
        $this->_get_datatables_query($no_rekening);
        if ($_POST['length'] != -1) {
            $this->db->limit($_POST['length'], $_POST['start']);
        }
        return $this->db->get()->result();
    }

    /**
     * Counts filtered records.
     */
    public function count_filtered($no_rekening = null)
    {
        $this->_get_datatables_query($no_rekening);
        return $this->db->get()->num_rows();
    }

    /**
     * Counts all records in the table.
     */
    public function count_all($no_rekening = null)
    {
        $this->db->from($this->table);
        if ($no_rekening !== null) {
            $this->db->join('tbsimpanan', 'tbsimpanan.id = tbtransaksi.simpanan_id');
            $this->db->where('tbsimpanan.no_rekening', $no_rekening);
        }
        return $this->db->count_all_results();
    }

    /**
     * Gets the total interest amount for a specific savings account.
     */
    public function get_total_bunga_by_rekening($no_rekening)
    {
        return $this->db
            ->select_sum('jumlah_transaksi')
            ->from($this->table)
            ->join('tbsimpanan', 'tbsimpanan.id = tbtransaksi.simpanan_id')
            ->where('tbsimpanan.no_rekening', $no_rekening)
            ->get()
            ->row()
            ->jumlah_transaksi ?? 0;
    }
}
