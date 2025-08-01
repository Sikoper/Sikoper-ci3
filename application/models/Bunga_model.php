<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga_model extends CI_Model
{
    var $table = 'tbtransaksi';
    var $column_order = array(null, 'nama_lengkap', 'no_rekening', 'tanggal_transaksi', 'jumlah_transaksi', 'bunga_riil','rate_bunga', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbsimpanan.no_rekening', 'tbtransaksi.tanggal_transaksi');
    var $order = array('created_at' => 'ASC');

    private function _get_datatables_query($start_date = null, $end_date = null)
    {
        $this->db->select('tbtransaksi.*, tbnasabah.nama_lengkap, tbsimpanan.no_rekening');
        $this->db->from($this->table);
        $this->db->join('tbsimpanan', 'tbsimpanan.id = tbtransaksi.simpanan_id');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id');

        // filter tanggal
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) >=', $start_date);
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) <=', $end_date);
        }

        // search
        $i = 0;
        foreach ($this->column_search as $item) {
            if (isset($_POST['search']) && $_POST['search']['value']) {
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

        // order
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

        return $this->db->get()->result();
    }

    public function count_filtered($start_date = null, $end_date = null)
    {
        $this->_get_datatables_query($start_date, $end_date);
        return $this->db->get()->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function get_total_bunga_filtered($start_date = null, $end_date = null)
    {
        $this->db->select_sum('jumlah_transaksi');
        $this->db->from($this->table);
        $this->db->join('tbsimpanan', 'tbsimpanan.id = tbtransaksi.simpanan_id');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) >=', $start_date);
            $this->db->where('DATE(tbtransaksi.tanggal_transaksi) <=', $end_date);
        }

        $query = $this->db->get()->row();
        return $query->jumlah_transaksi ?? 0;
    }

    public function checkAndRunBunga()
    {
        $month = date('m');
        $year = date('Y');

        $exists = $this->db
            ->where('MONTH(tanggal)', $month)
            ->where('YEAR(tanggal)', $year)
            ->get('systems_log')
            ->num_rows();

        if ($exists > 0) {
            return false;
        }

        $this->bunga_proses();

        $this->db->insert('systems_log', ['tanggal' => date('Y-m-d')]);

        return true;
    }

    public function bunga_proses()
    {
        $today = date('Y-m-d');
        $lastMonth = date('Y-m-d', strtotime('-1 month'));

        $this->db->select('tbsimpanan.id, tbsimpanan.nasabah_id, tbsimpanan.jumlah_simpanan, tbsimpanan.tanggal_simpanan, tbjenistabungan.bunga as bunga');
        $this->db->from('tbsimpanan');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbsimpanan.jenistabungan_id');
        $this->db->where('tbsimpanan.tanggal_simpanan <=', $lastMonth);
        $this->db->where('tbsimpanan.status', 'aktif');
        $this->db->where('tbjenistabungan.bunga >', '0');
        $simpananList = $this->db->get()->result();

        foreach ($simpananList as $simpanan) {
            $bungaRate = (float) $simpanan->bunga;
            $saldo = (float) $simpanan->jumlah_simpanan;

            if ($saldo <= 0) continue;

            $bungaAmount = ($bungaRate / 100) * $saldo;
            $bungaRiil = $this->round_to_nearest_hundred($bungaAmount);

            // Check if bunga already processed this month
            $alreadyGiven = $this->db
                ->where('simpanan_id', $simpanan->id)
                ->where('MONTH(tanggal_transaksi)', date('m'))
                ->where('YEAR(tanggal_transaksi)', date('Y'))
                ->get('tbtransaksi')
                ->num_rows();

            if ($alreadyGiven > 0) {
                continue;
            }

            // Insert bunga transaction
            $this->db->insert('tbtransaksi', [
                'simpanan_id'       => $simpanan->id,
                'tanggal_transaksi' => $today,
                'jumlah_transaksi'  => $bungaAmount,
                'bunga_riil'        => $bungaRiil,
                'rate_bunga'        => $bungaRate
            ]);

            // Update saldo
            $this->db->set('jumlah_simpanan', 'jumlah_simpanan + ' . $bungaAmount, false);
            $this->db->where('id', $simpanan->id);
            $this->db->update('tbsimpanan');
        }
    }

    public function get_data_by_id($id)
    {
        return $this->db
            ->select('id, simpanan_id, jumlah_transaksi, rate_bunga')
            ->from('tbtransaksi')
            ->where('id', $id)
            ->get()
            ->row();
    }
    public function delete_data($id)
    {
        return $this->db->delete('tbtransaksi', ['id' => $id]);
    }

    function round_to_nearest_hundred($value)
    {
        return floor($value / 100) * 100;
    }
}
