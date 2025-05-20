<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penarikan_model extends CI_Model
{

    // Ambil semua nasabah
    public function get_all_nasabah()
    {
        return $this->db->get('tbnasabah')->result();
    }

    // Ambil simpanan berdasarkan nasabah
    public function get_simpanan_by_nasabah($nasabah_id)
    {
        $this->db->select('s.id, s.no_rekening, j.nama_jenis as jenis');
        $this->db->from('tbsimpanan s');
        $this->db->join('tbjenistabungan j', 'j.id = s.jenistabungan_id');
        $this->db->where('s.nasabah_id', $nasabah_id);
        return $this->db->get()->result();
    }

    // Ambil detail simpanan (termasuk saldo)
    public function get_saldo_simpanan($simpanan_id)
    {
        return $this->db->get_where('tbsimpanan', ['id' => $simpanan_id])->row();
    }
}
