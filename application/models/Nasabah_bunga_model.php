<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Nasabah_Bunga_model extends CI_Model
{
    var $column_order = array(null, 'tanggal_transaksi', 'jumlah_transaksi', 'tipe', 'nama_lengkap', 'no_rekening', null);
    var $column_search = array('tanggal_transaksi', 'jumlah_transaksi', 'tipe', 'nama_lengkap', 'no_rekening');
    var $order = array('tanggal_transaksi' => 'DESC');

    /**
     * The main query builder for the datatable.
     * Now accepts a $no_rekening to filter by a specific savings or deposit account ID.
     */
    private function _get_base_query($no_rekening = null)
    {
        $whereClause = '';
        if ($no_rekening !== null) {
            $no_rekening = $this->db->escape($no_rekening); // Escape input
            $whereClause = "WHERE bunga.no_rekening = $no_rekening";
        }

        return "
        SELECT * FROM (
            SELECT 
                t1.id,
                t1.tanggal_transaksi,
                t1.jumlah_transaksi,
                'Simpanan' AS tipe,
                n.nama_lengkap,
                s.no_rekening,
                t1.id AS source_id
            FROM tbtransaksi t1
            JOIN tbsimpanan s ON s.id = t1.simpanan_id
            JOIN tbnasabah n ON n.id = s.nasabah_id

            UNION ALL

            SELECT 
                t2.id,
                t2.tanggal_transaksi,
                t2.jumlah_transaksi,
                'Deposito' AS tipe,
                n.nama_lengkap,
                d.no_rekening,
                t2.id AS source_id
            FROM tbtransaksi_deposito t2
            JOIN tbdeposito d ON d.id = t2.deposito_id
            JOIN tbnasabah n ON n.id = d.nasabah_id
        ) AS bunga
        $whereClause
    ";
    }

    private function _get_filtered_query($no_rekening = null)
    {
        $sql = $this->_get_base_query($no_rekening);

        $search_value = $_POST['search']['value'] ?? '';
        if (!empty($search_value)) {
            $search_value = $this->db->escape_like_str($search_value);
            $conditions = [];
            foreach ($this->column_search as $col) {
                $conditions[] = "$col LIKE '%$search_value%'";
            }
            $sql .= " AND (" . implode(' OR ', $conditions) . ")";
        }

        // Order
        if (isset($_POST['order'])) {
            $column_index = $_POST['order'][0]['column'];
            $column_name = $this->column_order[$column_index];
            $dir = $_POST['order'][0]['dir'];
            if ($column_name) {
                $sql .= " ORDER BY $column_name $dir";
            }
        } else {
            $sql .= " ORDER BY tanggal_transaksi DESC";
        }

        return $sql;
    }

    public function get_datatables($no_rekening = null)
    {
        $sql = $this->_get_filtered_query($no_rekening);

        if ($_POST['length'] != -1) {
            $sql .= " LIMIT " . (int)$_POST['start'] . ", " . (int)$_POST['length'];
        }

        return $this->db->query($sql)->result();
    }

    public function count_filtered($no_rekening = null)
    {
        $sql = $this->_get_filtered_query($no_rekening);
        $count_sql = "SELECT COUNT(*) AS filtered FROM ($sql) AS count_table";
        return $this->db->query($count_sql)->row()->filtered;
    }

    public function count_all()
    {
        $sql = "
        SELECT COUNT(*) AS total FROM (
            SELECT id FROM tbtransaksi
            UNION ALL
            SELECT id FROM tbtransaksi_deposito
        ) AS alltrans";
        return $this->db->query($sql)->row()->total;
    }
}
