<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Deposito_model extends CI_Model
{
    var $table = 'tbdeposito';
    var $column_order = array(null, 'nama_nasabah', 'no_rekening', 'telp_nasabah', 'jumlah_deposito', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbdeposito.no_rekening', 'tbnasabah.telp', 'tbjenistabungan.nama');
    var $order = array('no_rekening' => 'ASC');

    private function _get_datatables_query()
    {
        $this->db->select('tbdeposito.*, tbnasabah.nama_lengkap as nama_nasabah, tbnasabah.telp as telp_nasabah');
        $this->db->from($this->table);
        $this->db->join('tbnasabah', 'tbnasabah.id = tbdeposito.nasabah_id');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbdeposito.jenistabungan_id');

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

    public function jumlah_setoran_deposito()
    {
        $this->db->select('MONTH(tanggal_deposito) AS bulan, COUNT(id) AS total_setoran');
        $this->db->from('tbdeposito');
        $this->db->group_by('bulan');
        $this->db->order_by('bulan', 'ASC');
        return $this->db->get()->result();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function insert_data($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function delete_data($id)
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    public function edit_data($id, $data)
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function get_data_by_norek($no_rekening)
    {
        return $this->db->get_where($this->table, ['no_rekening' => $no_rekening])->row();
    }

    public function get_rekening_deposito_by_nasabah($nasabah_id)
    {
        $this->db->select('tbdeposito.id, CONCAT(tbdeposito.no_rekening, " - ", tbjenistabungan.nama) as text');
        $this->db->from($this->table);
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbdeposito.jenistabungan_id');
        $this->db->where('tbdeposito.nasabah_id', $nasabah_id);
        $this->db->where('tbjenistabungan.nama', 'Deposito');
        $this->db->where('tbdeposito.status', 'aktif');
        $this->db->where('tbdeposito.jumlah_deposito >', 0);
        $query = $this->db->get();
        return $query->result();
    }

    public function simpan_log_penarikan($data)
    {
        return $this->db->insert('tbpenarikan_deposito', $data);
    }

    public function kurangi_saldo($id, $jumlah)
    {
        $this->db->where('id', $id);
        $this->db->set('jumlah_deposito', 'jumlah_deposito - ' . (float)$jumlah, FALSE);
        return $this->db->update('tbdeposito');
    }

    public function get_nasabah_deposito()
    {
        $this->db->select('
            tbnasabah.nama_lengkap, 
            tbnasabah.nik,
            tbdeposito.no_rekening, 
            tbdeposito.jumlah_deposito, 
            tbdeposito.tanggal_deposito
        ');
        $this->db->from('tbdeposito');
        $this->db->join('tbnasabah', 'tbdeposito.nasabah_id = tbnasabah.id');
        $this->db->order_by('tbnasabah.nama_lengkap', 'ASC');

        $query = $this->db->get();
        return $query->result();
    }

    public function get_detail_for_sertifikat($id)
    {
        $this->db->select('
            tbdeposito.*,
            tbjenistabungan.bunga as suku_bunga,
            tbnasabah.nama_lengkap as nama_nasabah,
            tbnasabah.nik as nik_nasabah,
            tbnasabah.alamat as alamat_nasabah,
            tbnasabah.telp as telp_nasabah,
            tbnasabah.tempat_lahir,
            tbnasabah.tanggal_lahir,
            pegawai.nama_lengkap as nama_pegawai,
            pimpinan.nama_lengkap as nama_pimpinan,
            bendahara.nama_lengkap as nama_bendahara
        ');
        $this->db->from('tbdeposito');
        $this->db->join('tbjenistabungan', 'tbdeposito.jenistabungan_id = tbjenistabungan.id', 'left');
        $this->db->join('tbnasabah', 'tbdeposito.nasabah_id = tbnasabah.id', 'left');
        $this->db->join('tbpegawai as pegawai', 'tbdeposito.pegawai_id = pegawai.id', 'left');

        // Asumsi untuk mendapatkan nama Pimpinan/Kepala dan Bendahara dari tabel pegawai
        $this->db->join('tbpegawai as pimpinan', "pimpinan.jabatan = 'KEPALA BAGIAN TATA USAHA'", 'left');
        $this->db->join('tbpegawai as bendahara', "bendahara.jabatan = 'Bendahara'", 'left');

        $this->db->where('tbdeposito.id', $id);
        $this->db->limit(1); // Pastikan hanya satu baris yang diambil

        $query = $this->db->get();
        return $query->row();
    }

    public function ubah_status($deposito_id, $status_baru)
    {
        $this->db->where('id', $deposito_id);
        $this->db->update('tbdeposito', ['status' => $status_baru]);
        return $this->db->affected_rows();
    }
}
