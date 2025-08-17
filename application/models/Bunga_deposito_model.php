<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga_deposito_model extends CI_Model
{
    var $table = 'tb_bunga_deposito_log'; // Sumber data utama adalah tabel log baru
    var $column_order = array(null, 'n.nama_lengkap', 'd.no_rekening', 'log.tanggal_perhitungan', 'log.jumlah_bunga', 'd.rate_bunga', null);
    var $column_search = array('n.nama_lengkap', 'd.no_rekening');
    var $order = array('log.tanggal_perhitungan' => 'DESC');

    public function bunga_proses_deposito()
    {
        $currentDay = date('d');
        $currentMonth = date('m');
        $currentYear = date('Y');
        $processedAny = false;

        $this->db->where('status', 'aktif');
        $depositoList = $this->db->get('tbdeposito')->result();

        if (empty($depositoList)) {
            return false;
        }

        $this->db->trans_start();
        $data_bunga_batch = [];

        foreach ($depositoList as $deposito) {
            $bungaExistsThisMonth = $this->db->where('deposito_id', $deposito->id)
                ->where('MONTH(tanggal_perhitungan)', $currentMonth)
                ->where('YEAR(tanggal_perhitungan)', $currentYear)
                ->get($this->table)
                ->num_rows();

            if ($bungaExistsThisMonth > 0) {
                continue;
            }
            $hariBungaNasabah = date('d', strtotime($deposito->tanggal_deposito));

            if ($hariBungaNasabah <= $currentDay) {
                $bungaAmount = ($deposito->jumlah_deposito * ($deposito->rate_bunga / 100));

                $data_bunga_batch[] = [
                    'deposito_id'         => $deposito->id,
                    'jumlah_bunga'        => $bungaAmount,
                    'tanggal_perhitungan' => $currentYear . '-' . $currentMonth . '-' . $hariBungaNasabah,
                    'status_penarikan'    => 'belum_ditarik'
                ];
                $processedAny = true;
            }
        }

        if (!empty($data_bunga_batch)) {
            $this->db->insert_batch($this->table, $data_bunga_batch);
        }

        $this->db->trans_complete();
        return $processedAny;
    }


    private function _get_datatables_query($start_date = null, $end_date = null)
    {
        $this->db->select('log.id, log.tanggal_perhitungan as tanggal_transaksi, log.jumlah_bunga as jumlah_transaksi, n.nama_lengkap, d.no_rekening, d.rate_bunga');
        $this->db->from('tb_bunga_deposito_log as log');
        $this->db->join('tbdeposito as d', 'd.id = log.deposito_id');
        $this->db->join('tbnasabah as n', 'n.id = d.nasabah_id');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(log.tanggal_perhitungan) >=', $start_date);
            $this->db->where('DATE(log.tanggal_perhitungan) <=', $end_date);
        }

        $i = 0;
        foreach ($this->column_search as $item) {
            if (isset($_POST['search']['value']) && $_POST['search']['value'] != '') {
                if ($i === 0) {
                    $this->db->group_start();
                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }
                if (count($this->column_search) - 1 == $i) $this->db->group_end();
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

    function get_datatables($start_date = null, $end_date = null)
    {
        $this->_get_datatables_query($start_date, $end_date);
        if (isset($_POST['length']) && $_POST['length'] != -1)
            $this->db->limit($_POST['length'], isset($_POST['start']) ? $_POST['start'] : 0);

        $query = $this->db->get();
        if (!$query) {
            return [];
        }
        return $query->result();
    }

    public function count_filtered($start_date = null, $end_date = null)
    {
        $this->_get_datatables_query($start_date, $end_date);
        $query = $this->db->get();
        return $query ? $query->num_rows() : 0;
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function get_total_bunga_filtered($start_date = null, $end_date = null)
    {
        $this->db->select_sum('jumlah_bunga', 'total_bunga');
        $this->db->from($this->table);

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tanggal_perhitungan) >=', $start_date);
            $this->db->where('DATE(tanggal_perhitungan) <=', $end_date);
        }
        $query = $this->db->get();

        if (!$query) {
            return 0;
        }
        return $query->row()->total_bunga ?? 0;
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function delete_data($id)
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    public function get_detail_bunga_by_deposito_id($deposito_id)
    {
        if (empty($deposito_id)) {
            return [];
        }

        $this->db->select('id, tanggal_perhitungan, jumlah_bunga');
        $this->db->from('tb_bunga_deposito_log');
        $this->db->where('deposito_id', $deposito_id);
        $this->db->order_by('tanggal_perhitungan', 'DESC');

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function get_total_detail_bunga($deposito_id)
    {
        if (empty($deposito_id)) {
            return 0;
        }

        $this->db->select_sum('jumlah_bunga', 'total');
        $this->db->from('tb_bunga_deposito_log');
        $this->db->where('deposito_id', $deposito_id);

        $query = $this->db->get();

        // Pengecekan keamanan jika query gagal
        return $query ? ($query->row()->total ?? 0) : 0;
    }
}
