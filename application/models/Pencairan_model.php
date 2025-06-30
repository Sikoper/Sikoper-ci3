<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pencairan_model extends CI_Model
{
    private $_table_penarikan = 'tbpenarikan_deposito';
    private $_table_deposito = 'tbdeposito'; // Added for clarity and easy modification

    /**
     * Get the accumulated sum of withdrawals and penalties for a specific deposit.
     */
    public function get_akumulasi_penarikan_by_deposito($deposito_id)
    {
        $this->db->select_sum('jumlah_penarikan', 'total_akumulasi_penarikan');
        $this->db->select_sum('jumlah_denda', 'total_akumulasi_denda');
        $this->db->from($this->_table_penarikan);
        $this->db->where('deposito_id', $deposito_id);

        $query = $this->db->get();
        $result = $query->row();

        return (object)[
            'total_akumulasi_penarikan' => (float)($result->total_akumulasi_penarikan ?? 0),
            'total_akumulasi_denda'     => (float)($result->total_akumulasi_denda ?? 0)
        ];
    }

    // --- DATATABLES HELPER FUNCTIONS FOR WITHDRAWAL DETAILS ---

    private $_column_order_penarikan_detail = [null, 'p.tanggal_penarikan', 'p.jumlah_penarikan', 'p.jumlah_denda', 'pg.nama_lengkap', null];
    private $_column_search_penarikan_detail = ['p.tanggal_penarikan', 'p.jumlah_denda', 'pg.nama_lengkap'];
    private $_order_penarikan_default = ['p.tanggal_penarikan' => 'desc'];

    private function _get_datatables_query_detail_penarikan($deposito_id)
    {
        if (empty($deposito_id) || !ctype_digit((string)$deposito_id)) {
            $this->db->where('1=0', null, false);
        } else {
            $this->db->where('p.deposito_id', $deposito_id);
        }

        $this->db->select('p.id, p.deposito_id, p.tanggal_penarikan, p.jumlah_penarikan, p.jumlah_denda, pg.nama_lengkap as nama_pegawai');
        $this->db->from($this->_table_penarikan . ' p');
        $this->db->join('tbpegawai pg', 'p.pegawai_id = pg.id', 'left');

        $i = 0;
        if ($this->input->post('search') && $this->input->post('search')['value'] != '') {
            $searchValue = $this->input->post('search')['value'];
            $this->db->group_start();
            foreach ($this->_column_search_penarikan_detail as $item) {
                if ($i === 0) {
                    $this->db->like($item, $searchValue);
                } else {
                    $this->db->or_like($item, $searchValue);
                }
                $i++;
            }
            $this->db->group_end();
        }

        if ($this->input->post('order')) {
            $col_index = $this->input->post('order')['0']['column'];
            $order_dir = $this->input->post('order')['0']['dir'];
            if (isset($this->_column_order_penarikan_detail[$col_index]) && $this->_column_order_penarikan_detail[$col_index] != null) {
                $this->db->order_by($this->_column_order_penarikan_detail[$col_index], $order_dir);
            }
        } else if (isset($this->_order_penarikan_default)) {
            $order = $this->_order_penarikan_default;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    public function get_datatables_detail_penarikan($deposito_id)
    {
        $this->_get_datatables_query_detail_penarikan($deposito_id);
        if ($this->input->post('length') && $this->input->post('length') != -1) {
            $this->db->limit($this->input->post('length'), ($this->input->post('start') ? $this->input->post('start') : 0));
        }
        $query = $this->db->get();
        return $query->result();
    }

    public function count_filtered_detail_penarikan($deposito_id)
    {
        $this->_get_datatables_query_detail_penarikan($deposito_id);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all_detail_penarikan($deposito_id)
    {
        if (empty($deposito_id) || !ctype_digit((string)$deposito_id)) {
            return 0;
        }
        $this->db->from($this->_table_penarikan);
        $this->db->where('deposito_id', $deposito_id);
        return $this->db->count_all_results();
    }

    // --- END DATATABLES ---

    public function simpan_penarikan($data)
    {
        return $this->db->insert($this->_table_penarikan, $data);
    }

    public function get_penarikan_untuk_dihapus($penarikan_id)
    {
        $this->db->where('id', $penarikan_id);
        return $this->db->get($this->_table_penarikan)->row();
    }

    public function hapus_data_penarikan_by_id($id_penarikan)
    {
        $this->db->where('id', $id_penarikan);
        return $this->db->delete($this->_table_penarikan);
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where($this->_table_penarikan, ['id' => $id])->row();
    }

    /**
     * The functions below are related to 'tbdeposito', 'tbjenistabungan' etc.
     * They have been updated from 'simpanan' to 'deposito'.
     */

    public function kurangi_saldo_deposito($id, $jumlah)
    {
        $this->db->set('jumlah_deposito', 'jumlah_deposito - ' . (float)$jumlah, false);
        $this->db->where('id', $id);
        $this->db->update($this->_table_deposito);
        return $this->db->affected_rows() > 0;
    }

    public function tambah_saldo_deposito($deposito_id, $jumlah)
    {
        $this->db->set('jumlah_deposito', 'jumlah_deposito + ' . (float)$jumlah, false);
        $this->db->where('id', $deposito_id);
        return $this->db->update($this->_table_deposito);
    }

    public function get_deposito_by_id($id)
    {
        return $this->db->select('d.*, jt.pengendapan')
            ->from($this->_table_deposito . ' d')
            ->join('tbjenistabungan jt', 'jt.id = d.jenistabungan_id')
            ->where('d.id', $id)
            ->get()
            ->row();
    }

    public function get_rekening_dengan_jenis($nasabah_id)
    {
        $this->db->select('d.id, d.no_rekening, jt.nama as nama_jenis');
        $this->db->from($this->_table_deposito . ' d');
        $this->db->join('tbjenistabungan jt', 'jt.id = d.jenistabungan_id');
        $this->db->where('d.nasabah_id', $nasabah_id);
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

    public function jumlah_penarikan_deposito()
    {
        $this->db->select('MONTH(tanggal_penarikan) as bulan, COUNT(id) as total_penarikan');
        $this->db->from('tbpenarikan_deposito');
        $this->db->group_by('bulan');
        $this->db->order_by('bulan', 'ASC');
        return $this->db->get()->result();
    }
    public function count_new_data($today)
    {
        $this->db->from($this->_table_penarikan);
        $this->db->where('DATE(tanggal_penarikan)', $today);
        return $this->db->count_all_results();
    }

    public function jumlah_penarikan()
    {
        $this->db->select('MONTH(tanggal_penarikan) as bulan, COUNT(id) as total_penarikan');
        $this->db->from($this->_table_penarikan);
        $this->db->group_by('bulan');
        $this->db->order_by('bulan', 'ASC');
        $query = $this->db->get();
        return $query->result();
    }

    public function get_by_date_range($deposito_id, $start_date, $end_date)
    {
        $this->db->where('deposito_id', $deposito_id);
        $this->db->where('tanggal_penarikan >=', $start_date);
        $this->db->where('tanggal_penarikan <=', $end_date);
        $query = $this->db->get($this->_table_penarikan);
        return $query->result();
    }

    public function get_all_by_deposito($deposito_id)
    {
        $this->db->where('deposito_id', $deposito_id);
        $query = $this->db->get($this->_table_penarikan);
        return $query->result();
    }
}
