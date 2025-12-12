<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Setoran_model extends CI_Model
{
    var $table = 'tbdetail_simpanan';
    var $column_order = array(null, 'tanggal_setoran', 'jumlah_setoran', 'pegawai', null);
    var $column_search = array('tbdetail_simpanan.tanggal_setoran', 'tbpegawai.nama_lengkap');
    var $order = array('created_at' => 'ASC');

    private function _get_datatables_query($id)
    {
        $this->db->select('tbdetail_simpanan.*, tbpegawai.nama_lengkap as pegawai');
        $this->db->from($this->table);
        $this->db->where('tbdetail_simpanan.simpanan_id', $id);
        $this->db->join('tbpegawai', 'tbpegawai.id = tbdetail_simpanan.pegawai_id', 'left');

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

    function get_datatables($id)
    {
        $this->_get_datatables_query($id);
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered($id)
    {
        $this->_get_datatables_query($id);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all($id)
    {
        $this->db->from($this->table);
        $this->db->where('simpanan_id', $id);
        return $this->db->count_all_results();
    }

    public function count_all_data()
    {
        return $this->db->count_all('tbdetail_simpanan');
    }

    public function insert_data($data)
    {
        return $this->db->insert('tbdetail_simpanan', $data);
    }

    public function delete_data($id)
    {
        return $this->db->delete('tbdetail_simpanan', ['id' => $id]);
    }

    public function edit_data($id, $data)
    {
        return $this->db->where('id', $id)->update('tbdetail_simpanan', $data);
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where('tbdetail_simpanan', ['id' => $id])->row();
    }

    public function count_new_data($today)
    {
        $date = $today ?? date('Y-m-d');
        $start_of_month = date('Y-m-01', strtotime($date));
        $end_of_month = date('Y-m-t', strtotime($date));

        // Count simpanan
        $this->db->from('tbdetail_simpanan');
        $this->db->where('tanggal_setoran >=', $start_of_month . ' 00:00:00');
        $this->db->where('tanggal_setoran <=', $end_of_month . ' 23:59:59');
        $simpanan = $this->db->count_all_results();

        // Count deposito
        $this->db->from('tbdeposito');
        $this->db->where('tanggal_deposito >=', $start_of_month);
        $this->db->where('tanggal_deposito <=', $end_of_month);
        $deposito = $this->db->count_all_results();

        return $simpanan + $deposito;
    }

    public function jumlah_setoran()
    {
        $this->db->select('MONTH(tanggal_setoran) as bulan, COUNT(id) as total_setoran');
        $this->db->from('tbdetail_simpanan');
        $this->db->group_by('MONTH(tanggal_setoran)');
        $this->db->order_by('MONTH(tanggal_setoran)', 'ASC');

        $query = $this->db->get();
        return $query->result();
    }

    public function get_by_date_range($simpanan_id, $start_date, $end_date)
    {
        $this->db->where('simpanan_id', $simpanan_id);
        $this->db->where('tanggal_setoran >=', $start_date);
        $this->db->where('tanggal_setoran <=', $end_date);
        $query = $this->db->get('tbdetail_simpanan');
        return $query->result();
    }

    public function get_all_by_simpanan($simpanan_id)
    {
        $this->db->where('simpanan_id', $simpanan_id);
        $query = $this->db->get('tbdetail_simpanan');
        return $query->result();
    }

    public function get_first_by_simpanan_id($simpanan_id)
    {
        return $this->db->where('simpanan_id', $simpanan_id)
            ->order_by('tanggal_setoran', 'ASC')
            ->limit(1)
            ->get('tbdetail_simpanan')
            ->row();
    }
}
