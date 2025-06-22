<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Simpanan_model extends CI_Model
{
    var $table = 'tbsimpanan';
    var $column_order = array(null, 'nama_nasabah', 'no_rekening', 'telp_nasabah', 'jumlah_simpanan',  null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbsimpanan.no_rekening', 'tbnasabah.telp', 'tbjenistabungan.nama');
    var $order = array('no_rekening' => 'ASC');

    private function _get_datatables_query()
    {
        $this->db->select('tbsimpanan.*, tbnasabah.nama_lengkap as nama_nasabah, tbnasabah.telp as telp_nasabah');
        $this->db->from($this->table);
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbsimpanan.jenistabungan_id');

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

    public function count_all_data()
    {
        return $this->db->count_all('tbsimpanan');
    }

    public function insert_data($data)
    {
        return $this->db->insert('tbsimpanan', $data);
    }

    public function delete_data($id)
    {
        return $this->db->delete('tbsimpanan', ['id' => $id]);
    }

    public function edit_data($id, $data)
    {
        return $this->db->where('id', $id)->update('tbsimpanan', $data);
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where('tbsimpanan', ['id' => $id])->row();
    }

    public function get_data_by_norek($no_rekening)
    {
        return $this->db->get_where('tbsimpanan', ['no_rekening' => $no_rekening])->row();
    }

    public function get_data_by_nasabah($id)
    {
        return $this->db->get_where('tbsimpanan', ['nasabah_id' => $id])->result();
    }

    public function search_nasabah($keyword)
    {
        $this->db->like('nama_lengkap', $keyword);
        $this->db->select('id, nama_lengkap');
        $this->db->from('tbnasabah');
        $query = $this->db->get();
        return $query->result();
    }

    // public function checkAndRunBunga()
    // {
    //     if (date('d') != '25') {
    //         return;
    //     }

    //     $today = date('Y-m-d');

    //     $exists = $this->db->get_where('system_log', ['tanggal' => $today])->num_rows();
    //     if ($exists > 0) {
    //         return;
    //     }

    //     $this->add_bunga();

    //     $this->db->insert('system_log', ['tanggal' => $today]);
    // }

    public function get_akumulasi_penarikan_dan_denda($simpanan_id)
    {
        $this->db->select_sum(
            "CASE WHEN jenis_transaksi = 'PENARIKAN' THEN jumlah ELSE 0 END",
            'total_penarikan'
        );
        $this->db->select_sum(
            "CASE WHEN jenis_transaksi = 'DENDA' THEN jumlah ELSE 0 END",
            'total_denda'
        );
        $this->db->from('tbdetail_simpanan');
        $this->db->where('simpanan_id', $simpanan_id);

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            $result = $query->row();
            return (object) [
                'total_penarikan' => $result->total_penarikan ?? 0,
                'total_denda'     => $result->total_denda ?? 0,
            ];
        }

        return (object) [
            'total_penarikan' => 0,
            'total_denda'     => 0,
        ];
    }
}
