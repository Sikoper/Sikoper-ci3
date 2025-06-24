<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tabungan_model extends CI_Model
{
    var $table = 'tbsimpanan';
    var $column_order = array(null, 'tanggal', 'jumlah_uang', 'keterangan', 'pegawai', null);
    var $column_search = array('trans.tanggal', 'trans.keterangan', 'tbpegawai.nama_lengkap');
    var $order = array('tanggal' => 'ASC');

    private function _get_datatables_query($id = null)
    {
        $subquery = "
        (
            SELECT 
                ds.id AS detail_id,
                tbs.id AS simpanan_id,
                tbs.pegawai_id,
                ds.tanggal_setoran AS tanggal,
                ds.jumlah_setoran AS jumlah_uang,
                'Setor' AS keterangan
            FROM tbdetail_simpanan ds
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id

            UNION ALL

            SELECT 
                dp.id AS detail_id,
                tbs.id AS simpanan_id,
                tbs.pegawai_id,
                dp.tanggal_penarikan AS tanggal,
                dp.jumlah_penarikan AS jumlah_uang,
                'Tarik' AS keterangan
            FROM tbdetail_penarikan dp
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
        ) AS trans
        ";

        $this->db->select('trans.*, tbpegawai.nama_lengkap AS pegawai');
        $this->db->from($subquery);
        $this->db->join('tbpegawai', 'tbpegawai.id = trans.pegawai_id');

        if ($id !== null) {
            $this->db->where('trans.simpanan_id', $id);
        }

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

    function get_datatables($id = null)
    {
        $this->_get_datatables_query($id);
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered($id = null)
    {
        $this->_get_datatables_query($id);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all($id = null)
    {
        $where = "";
        $bind = [];

        if ($id !== null) {
            $where = "WHERE simpanan_id = ?";
            $bind[] = $id;
        }

        $sql = "
        SELECT COUNT(*) AS total FROM (
            SELECT 
                tbs.id AS simpanan_id
            FROM tbdetail_simpanan ds
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id

            UNION ALL

            SELECT 
                tbs.id AS simpanan_id
            FROM tbdetail_penarikan dp
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
        ) AS trans
        $where
    ";

        $query = $this->db->query($sql, $bind);
        return $query->row()->total;
    }
}
