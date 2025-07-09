<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penarikan_model extends CI_Model
{
    private $_table_penarikan_header = 'tbpenarikan';
    private $_table_penarikan_items = 'tbdetail_penarikan';

    public function get_akumulasi_penarikan_by_simpanan($simpanan_id)
    {
        $total_penarikan = 0;
        $total_denda = 0;

        $this->db->select_sum('jumlah_penarikan', 'sum_jumlah_penarikan');
        $this->db->from($this->_table_penarikan_items);
        $this->db->where('simpanan_id', $simpanan_id);
        $this->db->where('status', 'disetujui');
        $query_penarikan = $this->db->get();

        if ($query_penarikan->num_rows() > 0) {
            $result_penarikan = $query_penarikan->row();
            $total_penarikan = $result_penarikan->sum_jumlah_penarikan ?? 0;
        }

        $this->db->select_sum('jumlah_denda', 'sum_jumlah_denda');
        $this->db->from($this->_table_penarikan_header);
        $this->db->where('simpanan_id', $simpanan_id);
        $query_denda = $this->db->get();

        if ($query_denda->num_rows() > 0) {
            $result_denda = $query_denda->row();
            $total_denda = $result_denda->sum_jumlah_denda ?? 0;
        }

        return (object)[
            'total_akumulasi_penarikan' => $total_penarikan,
            'total_akumulasi_denda'     => $total_denda
        ];
    }

    private $_column_order_penarikan_detail = [null, 'p.tanggal_penarikan', 'p.total_penarikan', 'p.jumlah_denda', 'pg.nama_lengkap', null];
    private $_column_search_penarikan_detail = ['p.tanggal_penarikan', 'p.total_penarikan', 'p.jumlah_denda', 'pg.nama_lengkap'];
    private $_order_penarikan_default = ['p.tanggal_penarikan' => 'desc'];


    private function _get_datatables_query_detail_penarikan($simpanan_id)
    {
        if (empty($simpanan_id) || !ctype_digit((string)$simpanan_id)) {
            $this->db->where('1=0', null, false);
        } else {
            $this->db->where('p.simpanan_id', $simpanan_id);
        }

        $this->db->select('p.id, p.simpanan_id, p.tanggal_penarikan, p.total_penarikan, p.jumlah_denda, pg.nama_lengkap as nama_pegawai');
        $this->db->from($this->_table_penarikan_header . ' p');
        $this->db->join('tbpegawai pg', 'p.pegawai_id = pg.id', 'left');

        $i = 0;
        if ($this->input->post('search') && $this->input->post('search')['value'] != '') {
            foreach ($this->_column_search_penarikan_detail as $item) {
                if ($i === 0) {
                    $this->db->group_start();
                    $this->db->like($item, $this->input->post('search')['value']);
                } else {
                    $this->db->or_like($item, $this->input->post('search')['value']);
                }
                if (count($this->_column_search_penarikan_detail) - 1 == $i)
                    $this->db->group_end();
                $i++;
            }
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


    public function get_datatables_detail_penarikan($simpanan_id)
    {
        $this->_get_datatables_query_detail_penarikan($simpanan_id);
        if ($this->input->post('length') && $this->input->post('length') != -1) {
            $this->db->limit($this->input->post('length'), ($this->input->post('start') ? $this->input->post('start') : 0));
        }
        $query = $this->db->get();
        return $query->result();
    }

    public function count_filtered_detail_penarikan($simpanan_id)
    {
        $this->_get_datatables_query_detail_penarikan($simpanan_id);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all_detail_penarikan($simpanan_id)
    {
        $this->db->from($this->_table_penarikan_header);
        if (!empty($simpanan_id) && ctype_digit((string)$simpanan_id)) {
            $this->db->where('simpanan_id', $simpanan_id);
        } else {
            return 0;
        }
        return $this->db->count_all_results();
    }

    public function simpan_penarikan($data)
    {
        return $this->db->insert('tbpenarikan', $data);
    }

    public function kurangi_saldo_simpanan($id, $jumlah)
    {
        $this->db->set('jumlah_simpanan', 'jumlah_simpanan - ' . (float)$jumlah, false);
        $this->db->where('id', $id);
        $this->db->update('tbsimpanan');
        return $this->db->affected_rows() > 0;
    }

    public function get_penarikan_untuk_dihapus($penarikan_id)
    {
        $this->db->where('id', $penarikan_id);
        return $this->db->get($this->_table_penarikan_header)->row();
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

    public function jumlah_penarikan_bulanan()
    {
        $sql = "
        SELECT MONTH(tanggal_penarikan) AS bulan, COUNT(*) AS jumlah
        FROM tbpenarikan
        GROUP BY bulan

        UNION ALL

        SELECT MONTH(tanggal_penarikan) AS bulan, COUNT(*) AS jumlah
        FROM tbpenarikan_deposito
        GROUP BY bulan
    ";

        $query = $this->db->query("
        SELECT bulan, SUM(jumlah) AS total
        FROM ($sql) AS combined
        GROUP BY bulan
        ORDER BY bulan
    ");

        return $query->result();
    }


    public function hapus_data_penarikan_by_id($id_penarikan)
    {
        $this->db->where('id', $id_penarikan);
        return $this->db->delete($this->_table_penarikan_header);
    }

    public function tambah_saldo_simpanan($simpanan_id, $jumlah)
    {
        $this->db->set('jumlah_simpanan', 'jumlah_simpanan + ' . (float)$jumlah, false);
        $this->db->where('id', $simpanan_id);
        return $this->db->update('tbsimpanan');
    }

    public function count_new_data($today)
    {
        $this->db->from('tbdetail_penarikan');
        $this->db->where('tanggal_penarikan', $today);
        return $this->db->count_all_results();
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where('tbdetail_penarikan', ['id' => $id])->row();
    }

    public function jumlah_penarikan()
    {
        $this->db->select('MONTH(tanggal_penarikan) as bulan, COUNT(id) as total_penarikan');
        $this->db->from('tbdetail_penarikan');
        $this->db->group_by('MONTH(tanggal_penarikan)');
        $this->db->order_by('MONTH(tanggal_penarikan)', 'ASC');

        $query = $this->db->get();
        return $query->result();
    }

    public function get_by_date_range($simpanan_id, $start_date, $end_date)
    {
        $this->db->where('simpanan_id', $simpanan_id);
        $this->db->where('tanggal_penarikan >=', $start_date);
        $this->db->where('tanggal_penarikan <=', $end_date);
        $query = $this->db->get('tbdetail_penarikan');
        return $query->result();
    }
    public function get_all_by_simpanan($simpanan_id)
    {
        $this->db->where('simpanan_id', $simpanan_id);
        $query = $this->db->get('tbdetail_penarikan');
        return $query->result();
    }

    public function get_combo_rekening_nasabah($search = null)
    {
        $this->db->select('tbsimpanan.id, tbsimpanan.no_rekening');
        $this->db->from('tbsimpanan');
        $this->db->join('tbnasabah', 'tbsimpanan.nasabah_id = tbnasabah.id');
        $this->db->join('tbjenistabungan', 'tbsimpanan.jenistabungan_id = tbjenistabungan.id');

        if ($search) {
            $this->db->group_start();
            $this->db->like('tbsimpanan.no_rekening', $search);
            $this->db->or_like('tbnasabah.nama_lengkap', $search);
            $this->db->or_like('tbjenistabungan.nama', $search);
            $this->db->group_end();
        }

        $this->db->limit(20);
        $query = $this->db->get();

        $result = [];
        foreach ($query->result() as $row) {
            $result[] = [
                'id' => $row->id,
                'text' => $row->no_rekening
            ];
        }
        return $result;
    }

    public function get_rekening_nasabah_combo()
    {
        $this->db->select('ts.id as id_tabungan, ts.no_rekening, tn.id as id_nasabah, tn.nama_lengkap, jt.nama as jenis_tabungan');
        $this->db->from('tbsimpanan ts');
        $this->db->join('tbnasabah tn', 'ts.nasabah_id = tn.id');
        $this->db->join('tbjenistabungan jt', 'ts.jenistabungan_id = jt.id');
        return $this->db->get()->result();
    }

    public function get_simpanan_detail_by_id($id)
{
    $this->db->select('ts.jumlah_simpanan, tn.nama_lengkap, jt.nama as jenis_tabungan');
    $this->db->from('tbsimpanan ts');
    $this->db->join('tbnasabah tn', 'ts.nasabah_id = tn.id');
    $this->db->join('tbjenistabungan jt', 'ts.jenistabungan_id = jt.id');
    $this->db->where('ts.id', $id);
    return $this->db->get()->row();
}

}
