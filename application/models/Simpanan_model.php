<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Simpanan_model extends CI_Model
{
    var $table = 'tbsimpanan';
    // DENORMALIZED: Using denormalized columns for faster queries
    var $column_order = array(null, 'nama_nasabah', 'no_rekening', 'telp_nasabah', 'jumlah_simpanan',  null);
    var $column_search = array('tbsimpanan.nama_nasabah', 'tbsimpanan.no_rekening', 'tbsimpanan.jenis_tabungan');
    var $order = array('created_at' => 'DESC');

    public $_table_detail_simpanan = 'tbdetail_simpanan';
    public $_table_transaksi = 'tbtransaksi';

    private function _get_datatables_query()
    {
        // OPTIMIZED: Using denormalized columns - no JOIN needed for basic display
        $this->db->select('tbsimpanan.*, 
            COALESCE(tbsimpanan.nama_nasabah, tbnasabah.nama_lengkap) as nama_nasabah, 
            COALESCE(tbnasabah.telp, "") as telp_nasabah');
        $this->db->from($this->table);
        // Keep JOIN as fallback for records missing denormalized data
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id', 'left');
        $this->db->group_by('tbsimpanan.id');

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
        // DENORMALIZED: Auto-populate denormalized columns if not provided
        if (empty($data['nama_nasabah']) && !empty($data['nasabah_id'])) {
            $nasabah = $this->db->select('nama_lengkap')->where('id', $data['nasabah_id'])->get('tbnasabah')->row();
            if ($nasabah) $data['nama_nasabah'] = $nasabah->nama_lengkap;
        }
        if (empty($data['nama_pegawai']) && !empty($data['pegawai_id'])) {
            $pegawai = $this->db->select('nama_lengkap')->where('id', $data['pegawai_id'])->get('tbpegawai')->row();
            if ($pegawai) $data['nama_pegawai'] = $pegawai->nama_lengkap;
        }
        if ((empty($data['jenis_tabungan']) || empty($data['bunga_rate'])) && !empty($data['jenistabungan_id'])) {
            $jenis = $this->db->select('nama, bunga')->where('id', $data['jenistabungan_id'])->get('tbjenistabungan')->row();
            if ($jenis) {
                if (empty($data['jenis_tabungan'])) $data['jenis_tabungan'] = $jenis->nama;
                if (empty($data['bunga_rate'])) $data['bunga_rate'] = $jenis->bunga;
            }
        }
        // Initialize totals to 0
        if (!isset($data['total_setoran'])) $data['total_setoran'] = 0;
        if (!isset($data['total_penarikan'])) $data['total_penarikan'] = 0;
        if (!isset($data['total_bunga_akumulasi'])) $data['total_bunga_akumulasi'] = 0;
        
        return $this->db->insert('tbsimpanan', $data);
    }

    /**
     * DENORMALIZED: Update total_setoran after a deposit is made
     */
    public function add_to_total_setoran($simpanan_id, $amount)
    {
        $this->db->set('total_setoran', 'COALESCE(total_setoran, 0) + ' . (float)$amount, false);
        $this->db->where('id', $simpanan_id);
        return $this->db->update('tbsimpanan');
    }

    /**
     * DENORMALIZED: Update total_penarikan after a withdrawal is approved
     */
    public function add_to_total_penarikan($simpanan_id, $amount)
    {
        $this->db->set('total_penarikan', 'COALESCE(total_penarikan, 0) + ' . (float)$amount, false);
        $this->db->where('id', $simpanan_id);
        return $this->db->update('tbsimpanan');
    }

    /**
     * DENORMALIZED: Update total_bunga_akumulasi after interest is credited
     */
    public function add_to_total_bunga($simpanan_id, $amount)
    {
        $this->db->set('total_bunga_akumulasi', 'COALESCE(total_bunga_akumulasi, 0) + ' . (float)$amount, false);
        $this->db->where('id', $simpanan_id);
        return $this->db->update('tbsimpanan');
    }

    /**
     * DENORMALIZED: Recalculate all denormalized totals from detail tables
     */
    public function recalculate_totals($simpanan_id)
    {
        // Calculate total_setoran
        $setoran = $this->db->select_sum('jumlah_setoran')
            ->where('simpanan_id', $simpanan_id)
            ->get('tbdetail_simpanan')->row();
        
        // Calculate total_penarikan
        $penarikan = $this->db->select_sum('jumlah_penarikan')
            ->where('simpanan_id', $simpanan_id)
            ->where('status', 'disetujui')
            ->get('tbdetail_penarikan')->row();
        
        // Calculate total_bunga
        $bunga = $this->db->select_sum('jumlah_transaksi')
            ->where('simpanan_id', $simpanan_id)
            ->get('tbtransaksi')->row();
        
        return $this->db->where('id', $simpanan_id)->update('tbsimpanan', [
            'total_setoran' => $setoran->jumlah_setoran ?? 0,
            'total_penarikan' => $penarikan->jumlah_penarikan ?? 0,
            'total_bunga_akumulasi' => $bunga->jumlah_transaksi ?? 0
        ]);
    }

    public function hapus_simpanan_lengkap($id_simpanan)
    {
        if (empty($id_simpanan)) {
            return false;
        }
        $this->db->trans_start();

        $this->db->where('simpanan_id', $id_simpanan);
        $this->db->delete($this->_table_detail_simpanan);

        $this->db->where('simpanan_id', $id_simpanan);
        $this->db->delete($this->_table_transaksi);

        $this->db->where('id', $id_simpanan);
        $this->db->delete($this->table);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Gagal menghapus data simpanan lengkap untuk ID: ' . $id_simpanan);
            return false;
        }
        return true;
    }

    public function delete_data($id)
    {
        return $this->hapus_simpanan_lengkap($id);
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

    public function sync_total_simpanan($simpanan_id)
    {
        $total = $this->db->select_sum('jumlah_setoran')
            ->where('simpanan_id', $simpanan_id)
            ->get('tbdetail_simpanan')
            ->row()
            ->jumlah_setoran;

        return $this->db->where('id', $simpanan_id)
            ->update('tbsimpanan', ['jumlah_simpanan' => $total]);
    }

    public function get_data_tabungan_full_by_norek($no_rekening)
    {
        return $this->db
            ->select('tbsimpanan.*, tbnasabah.nama_lengkap, tbnasabah.id as nasabah_id, tbjenistabungan.nama as jenis_tabungan')
            ->from('tbsimpanan')
            ->join('tbnasabah', 'tbsimpanan.nasabah_id = tbnasabah.id')
            ->join('tbjenistabungan', 'tbsimpanan.jenistabungan_id = tbjenistabungan.id')
            ->where('tbsimpanan.no_rekening', $no_rekening)
            ->get()
            ->row();
    }

    public function cari_rekening_nasabah($search = '')
    {
        $this->db->select('s.id, s.no_rekening, n.nama_lengkap, k.nama AS jenis_tabungan');
        $this->db->from('tbsimpanan s');
        $this->db->join('tbnasabah n', 's.nasabah_id = n.id');
        $this->db->join('tbjenistabungan k', 's.jenistabungan_id = k.id');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('s.no_rekening', $search);
            $this->db->or_like('n.nama_lengkap', $search);
            $this->db->or_like('k.nama', $search);
            $this->db->group_end();
        }

        $this->db->order_by('s.no_rekening', 'ASC');
        return $this->db->get()->result();
    }

    public function get_detail_tabungan_by_id($id)
    {
        $this->db->select('s.id, s.no_rekening, s.nasabah_id, s.jumlah_simpanan, 
                n.nama_lengkap, n.nik, n.alamat,
                k.nama AS jenis_tabungan');
        $this->db->from('tbsimpanan s');
        $this->db->join('tbnasabah n', 's.nasabah_id = n.id');
        $this->db->join('tbjenistabungan k', 's.jenistabungan_id = k.id');
        $this->db->where('s.id', $id);
        return $this->db->get()->row();
    }
}
