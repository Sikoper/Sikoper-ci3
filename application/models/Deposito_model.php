<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Deposito_model extends CI_Model
{
    var $table = 'tbdeposito';
    var $column_order = array(null, 'nama_nasabah', 'no_rekening', 'telp_nasabah', 'jumlah_deposito', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbdeposito.no_rekening', 'tbnasabah.telp', 'tbjenistabungan.nama');
    var $order = array('no_rekening' => 'ASC');

    public $_table_penarikan_deposito = 'tbpenarikan_deposito';
    public $_table_bunga_log = 'tbdeposito_bunga_log';
    public $_table_transaksi_deposito = 'tbtransaksi_deposito';

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

    public function hapus_deposito_lengkap($id_deposito)
    {
        if (empty($id_deposito)) {
            return false;
        }

        $this->db->trans_start();

        $this->db->where('deposito_id', $id_deposito);
        $this->db->delete($this->_table_penarikan_deposito);

        $this->db->where('deposito_id', $id_deposito);
        $this->db->delete($this->_table_bunga_log);

        $this->db->where('deposito_id', $id_deposito);
        $this->db->delete($this->_table_transaksi_deposito);

        $this->db->where('id', $id_deposito);
        $this->db->delete($this->table);

        $this->db->trans_complete();


        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Gagal menghapus data deposito lengkap untuk ID: ' . $id_deposito);
            return false;
        }
        return true;
    }

    public function delete_data($id)
    {

        return $this->hapus_deposito_lengkap($id);
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
        $this->db->join('tbpegawai as pimpinan', "pimpinan.jabatan = 'KEPALA BAGIAN TATA USAHA'", 'left');
        $this->db->join('tbpegawai as bendahara', "bendahara.jabatan = 'Bendahara'", 'left');
        $this->db->where('tbdeposito.id', $id);
        $this->db->limit(1);

        $query = $this->db->get();
        return $query->row();
    }

    public function ubah_status($deposito_id, $status_baru)
    {
        $this->db->where('id', $deposito_id);
        $this->db->update('tbdeposito', ['status' => $status_baru]);
        return $this->db->affected_rows();
    }

    public function get_rekening_nasabah_combo($searchTerm = null)
    {
        $this->db->select('
        tbdeposito.id,
        tbdeposito.no_rekening,
        tbnasabah.nama_lengkap,
        tbjenistabungan.nama as jenis_tabungan,
        tbnasabah.id as nasabah_id
    ');
        $this->db->from('tbdeposito');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbdeposito.nasabah_id');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbdeposito.jenistabungan_id');
        $this->db->where('tbjenistabungan.nama', 'Deposito');
        $this->db->where('tbdeposito.status', 'aktif');
        $this->db->where('tbdeposito.jumlah_deposito >', 0);

        if ($searchTerm) {
            $this->db->group_start();
            $this->db->like('tbdeposito.no_rekening', $searchTerm);
            $this->db->or_like('tbnasabah.nama_lengkap', $searchTerm);
            $this->db->group_end();
        }

        return $this->db->get()->result();
    }

    public function get_by_id($id)
    {
        $this->db->select('
        tbdeposito.*, 
        tbnasabah.nama_lengkap, 
        tbnasabah.id as nasabah_id,
        tbjenistabungan.nama as jenis_tabungan
    ');
        $this->db->from('tbdeposito');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbdeposito.nasabah_id');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbdeposito.jenistabungan_id', 'left');
        $this->db->where('tbdeposito.id', $id);
        return $this->db->get()->row();
    }

    public function cari_rekening_deposito_nasabah($search = '')
    {
        $this->db->select('d.id, CONCAT(d.no_rekening, " - ", n.nama_lengkap) as text');
        $this->db->from('tbdeposito d');
        $this->db->join('tbnasabah n', 'd.nasabah_id = n.id');
        $this->db->where('d.status', 'aktif');
        $this->db->where('d.hutang_bunga >', 0);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('d.no_rekening', $search);
            $this->db->or_like('n.nama_lengkap', $search);
            $this->db->group_end();
        }

        $this->db->order_by('d.no_rekening', 'ASC');
        return $this->db->get()->result();
    }

    public function get_detail_deposito_by_id($id)
    {
        $this->db->select('
            d.id, 
            d.no_rekening, 
            d.hutang_bunga, 
            n.nama_lengkap as nama_nasabah
        ');
        $this->db->from('tbdeposito d');
        $this->db->join('tbnasabah n', 'd.nasabah_id = n.id');
        $this->db->where('d.id', $id);
        return $this->db->get()->row();
    }

    public function tarik_bunga($deposito_id, $jumlah_penarikan, $pegawai_id)
    {
        $this->db->trans_start();

        $this->db->set('hutang_bunga', 'hutang_bunga - ' . (float)$jumlah_penarikan, FALSE);
        $this->db->where('id', $deposito_id);
        $this->db->update('tbdeposito');

        $log_data = [
            'deposito_id'       => $deposito_id,
            'pegawai_id'        => $pegawai_id,
            'tanggal_penarikan' => date('Y-m-d H:i:s'),
            'jumlah_penarikan'  => $jumlah_penarikan, // Jumlah bunga yang ditarik
            'jumlah_denda'      => 0,                // Tidak ada denda untuk penarikan bunga
            'total_penarikan'   => $jumlah_penarikan  // Total sama dengan jumlah penarikan karena denda 0
        ];
        $this->db->insert($this->_table_penarikan_deposito, $log_data);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
