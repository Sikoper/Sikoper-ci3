<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Nasabah_model extends CI_Model
{
    var $table = 'tbnasabah';
    var $column_order = array(null, 'nik', 'nama_lengkap', 'telp', 'email',  null);
    var $column_search = array('nik', 'nama_lengkap', 'email');
    var $order = array('created_at' => 'DESC');

    private function _get_datatables_query()
    {

        $this->db->from($this->table);

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

    function get_datatables()
    {
        $this->_get_datatables_query();
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered()
    {
        $this->_get_datatables_query();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function count_all_nasabah()
    {
        return $this->db->count_all('tbnasabah');
    }

        public function insert_data($data)
    {
        return $this->db->insert('tbnasabah', $data);
    }

        public function delete_data($id)
    {
        return $this->db->delete('tbnasabah', ['id' => $id]);
    }

    public function get_data_by_nik($nik)
    {
        return $this->db->get_where('tbnasabah', ['nik' => $nik])->row();
    }

    public function edit_data($id, $data)
    {
        return $this->db->where('id', $id)->update('tbnasabah', $data);
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where('tbnasabah', ['id' => $id])->row();
    }

    public function get_data()
    {
        return $this->db->get('tbnasabah')->result();
    }

    public function search_nasabah($keyword)
    {
        $this->db->like('nama_lengkap', $keyword);
        $this->db->select('id, nama_lengkap');
        $this->db->from('tbnasabah');
        $query = $this->db->get();
        return $query->result();
    }
}
