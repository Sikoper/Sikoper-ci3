<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penarikan_model extends CI_Model
{

    // Ambil semua nasabah
    public function get_all_nasabah()
    {
        return $this->db->get('tbnasabah')->result();
    }

    public function get_rekening_dengan_jenis($nasabah_id)
    {
        $this->db->select('tbsimpanan.id, tbsimpanan.no_rekening, tbjenistabungan.nama as nama_jenis');
        $this->db->from('tbsimpanan');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbsimpanan.jenistabungan_id');
        $this->db->where('tbsimpanan.nasabah_id', $nasabah_id);
        $query = $this->db->get();

        $result = [];
        foreach ($query->result() as $row) {
            $result[] = [
                'id' => $row->id,
                'text' => $row->no_rekening . ' (' . $row->nama_jenis . ')'
            ];
        }
        return $result;
    }

    public function get_simpanan_by_id($id)
    {
        return $this->db->select('tbsimpanan.*, tbjenistabungan.pengendapan')
            ->from('tbsimpanan')
            ->join('tbjenistabungan', 'tbjenistabungan.id = tbsimpanan.jenistabungan_id')
            ->where('tbsimpanan.id', $id)
            ->get()
            ->row();
    }


    public function kurangi_saldo_simpanan($id, $jumlah)
    {
        $this->db->set('jumlah_simpanan', 'jumlah_simpanan - ' . (int)$jumlah, false);
        $this->db->where('id', $id);
        $this->db->update('tbsimpanan');
    }

    private function _get_datatables_query()
    {
        $this->db->select('tbpenarikan.*, tbsimpanan.no_rekening, tbnasabah.nama_lengkap as nama_nasabah, tbjenistabungan.nama as jenis_tabungan, tbsimpanan.status,');
        $this->db->from('tbpenarikan');
        $this->db->join('tbsimpanan', 'tbpenarikan.simpanan_id = tbsimpanan.id');
        $this->db->join('tbnasabah', 'tbsimpanan.nasabah_id = tbnasabah.id');
        $this->db->join('tbjenistabungan', 'tbsimpanan.jenistabungan_id = tbjenistabungan.id');

        // Searching
        if (isset($_POST['search']['value']) && $_POST['search']['value'] !== '') {
            $this->db->group_start();
            $this->db->like('tbsimpanan.no_rekening', $_POST['search']['value']);
            $this->db->or_like('tbnasabah.nama_lengkap', $_POST['search']['value']);
            $this->db->group_end();
        }

        // Ordering
        if (isset($_POST['order'])) {
            $column_index = $_POST['order'][0]['column'];
            $order_dir = $_POST['order'][0]['dir'];
            $columns = ['tbpenarikan.id', 'tbsimpanan.no_rekening', 'tbnasabah.nama_lengkap', 'tbjenistabungan.nama', 'tbpenarikan.total_penarikan'];

            if (isset($columns[$column_index])) {
                $this->db->order_by($columns[$column_index], $order_dir);
            }
        } else {
            $this->db->order_by('tbpenarikan.id', 'DESC');
        }
    }

    public function get_datatables()
    {
        $this->_get_datatables_query();
        if (isset($_POST['length']) && $_POST['length'] != -1) {
            $this->db->limit($_POST['length'], $_POST['start'] ?? 0);
        }
        return $this->db->get()->result();
    }

    public function count_filtered()
    {
        $this->_get_datatables_query();
        return $this->db->get()->num_rows();
    }

    public function count_all()
    {
        $this->db->from('tbpenarikan');
        return $this->db->count_all_results();
    }

    public function simpan_penarikan($data)
    {
        return $this->db->insert('tbpenarikan', $data);
    }

    public function getById($id)
    {
        $this->db->select('tbpenarikan.id, tbsimpanan.no_rekening, tbnasabah.nama_lengkap as nama_nasabah, tbjenistabungan.nama as jenis_tabungan, tbpenarikan.total_penarikan');
        $this->db->from('tbpenarikan');
        $this->db->join('tbsimpanan', 'tbpenarikan.simpanan_id = tbsimpanan.id');
        $this->db->join('tbnasabah', 'tbsimpanan.nasabah_id = tbnasabah.id');
        $this->db->join('tbjenistabungan', 'tbsimpanan.jenistabungan_id = tbjenistabungan.id');
        $this->db->where('tbpenarikan.id', $id);

        $query = $this->db->get();
        return $query->row_array();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('tbpenarikan', $data);
    }

    public function jumlah_setoran()
    {
        $this->db->select('MONTH(tanggal_penarikan) as bulan, COUNT(id) as total_penarikan');
        $this->db->from('tbdetail_penarikan');
        $this->db->group_by('MONTH(tanggal_penarikan)');
        $this->db->order_by('MONTH(tanggal_penarikan)', 'ASC');

        $query = $this->db->get();
        return $query->result();
    }
}
