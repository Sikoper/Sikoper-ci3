<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga_model extends CI_Model
{
    var $table = 'tbtransaksi';
    var $column_order = array(null, 'no_rekening', 'nasabah', 'tanggal_transaksi', 'jumlah_transaksi',  null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbsimpanan.no_rekening', 'tbtransaksi.tanggal_transaksi');
    var $order = array('created_at' => 'ASC');

    private function _get_datatables_query($simpanan_id = null)
    {
        $this->db->select('tbtransaksi.*, tbnasabah.nama_lengkap as nasabah, tbsimpanan.no_rekening as no_rekening');
        $this->db->from($this->table);
        $this->db->join('tbsimpanan', 'tbsimpanan.id = tbtransaksi.simpanan_id');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id');
        if ($simpanan_id) {
            $this->db->where('simpanan_id', $simpanan_id);
        }

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

    function get_datatables($simpanan_id = null)
    {
        $this->_get_datatables_query($simpanan_id);
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        return $this->db->get()->result();
    }

    public function count_filtered($simpanan_id = null)
    {
        $this->_get_datatables_query($simpanan_id);
        return $this->db->get()->num_rows();
    }

    public function count_all($simpanan_id = null)
    {
        $this->db->from('tbtransaksi');
        if ($simpanan_id) {
            $this->db->where('simpanan_id', $simpanan_id);
        }
        return $this->db->count_all_results();
    }

    public function count_all_data()
    {
        return $this->db->count_all('tbsimpanan');
    }

    public function checkAndRunBunga()
    {
        $today = date('Y-m-d');
        $exists = $this->db->get_where('systems_log', ['tanggal' => $today])->num_rows();
        if ($exists > 0) return;

        $this->bunga_proses();

        $this->db->insert('systems_log', ['tanggal' => $today]);
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
            $bungaRate = (float) $simpanan->bunga / 12;
            $saldo = (float) $simpanan->jumlah_simpanan;

            if ($saldo <= 0) continue;

            $bungaAmount = ($bungaRate / 100) * $saldo;

            $alreadyGiven = $this->db
                ->where('simpanan_id', $simpanan->id)
                ->where('MONTH(tanggal_transaksi)', date('m'))
                ->where('YEAR(tanggal_transaksi)', date('Y'))
                ->get('tbtransaksi')
                ->num_rows();

            if ($alreadyGiven > 0) {
                continue;
            }

            $this->db->insert('tbtransaksi', [
                'simpanan_id'       => $simpanan->id,
                'tanggal_transaksi' => $today,
                'jumlah_transaksi'  => $bungaAmount
            ]);

            $this->db->set('jumlah_simpanan', 'jumlah_simpanan + ' . $bungaAmount, false);
            $this->db->where('id', $simpanan->id);
            $this->db->update('tbsimpanan');
        }
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where('tbtransaksi', ['id' => $id])->row();
    }
    public function delete_data($id)
    {
        return $this->db->delete('tbtransaksi', ['id' => $id]);
    }
}
