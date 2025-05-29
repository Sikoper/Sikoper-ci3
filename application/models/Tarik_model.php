<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tarik_model extends CI_Model
{
    private $table = 'tbdetail_penarikan';
    var $column_order = array(null, 'tanggal_penarikan', 'jumlah_penarikan', 'pegawai_id', 'status', null);
    var $column_search = array('tanggal_penarikan', 'jumlah_penarikan',);
    var $order = array('tanggal_penarikan' => 'DESC');

    public function __construct()
    {
        parent::__construct();
    }

    public function simpan_detail_penarikan($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function get_detail_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function hapus_detail_by_kriteria($kriteria)
    {
        
        return $this->db->delete($this->table, $kriteria);
    }

    public function update_detail_by_id($id, $data)
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }
}
