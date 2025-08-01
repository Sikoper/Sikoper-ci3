<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga_deposito_model extends CI_Model
{
    var $table = 'tbtransaksi_deposito';
    var $column_order = array(null, 'nama_lengkap', 'no_rekening', 'tanggal_transaksi', 'jumlah_transaksi', 'rate_bunga', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbdeposito.no_rekening', 'tbtransaksi_deposito.tanggal_transaksi');
    var $order = array('no_rekening' => 'ASC');

    private function _get_datatables_query($start_date = null, $end_date = null)
    {
        $this->db->select('tbtransaksi_deposito.*, tbnasabah.nama_lengkap, tbdeposito.no_rekening');
        $this->db->from($this->table);
        $this->db->join('tbdeposito', 'tbdeposito.id = tbtransaksi_deposito.deposito_id');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbdeposito.nasabah_id');
        
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tbtransaksi_deposito.tanggal_transaksi) >=', $start_date);
            $this->db->where('DATE(tbtransaksi_deposito.tanggal_transaksi) <=', $end_date);
        }

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
        $this->db->join('tbdeposito', 'tbdeposito.id = tbtransaksi_deposito.deposito_id');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbdeposito.nasabah_id');

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tbtransaksi_deposito.tanggal_transaksi) >=', $start_date);
            $this->db->where('DATE(tbtransaksi_deposito.tanggal_transaksi) <=', $end_date);
        }

        $query = $this->db->get()->row();
        return $query->jumlah_transaksi ?? 0;
    }

    public function bunga_proses_deposito()
    {
        $today = date('Y-m-d');
        $processedAny = false;

        $this->db->where('status', 'aktif');
        $depositoList = $this->db->get('tbdeposito')->result();

        if (empty($depositoList)) {
            return false;
        }

        $this->db->trans_start();

        foreach ($depositoList as $deposito) {
            if (date('d') != date('d', strtotime($deposito->tanggal_deposito))) {
                continue;
            }

            $bungaExists = $this->db->where('deposito_id', $deposito->id)
                ->where('MONTH(tanggal_bunga)', date('m'))
                ->where('YEAR(tanggal_bunga)', date('Y'))
                ->get('tbdeposito_bunga_log')->num_rows();

            if ($bungaExists > 0) {
                continue;
            }

            $bungaAmount = ($deposito->rate_bunga / 100) * $deposito->jumlah_deposito;

            $this->db->insert('tbtransaksi_deposito', [
                'deposito_id'       => $deposito->id,
                'tanggal_transaksi' => $today,
                'jumlah_transaksi'  => $bungaAmount,
                'rate_bunga'        => $deposito->rate_bunga,
                // 'keterangan'        => 'Pembungaan bulanan otomatis'
            ]);

            $this->db->insert('tbdeposito_bunga_log', [
                'deposito_id'   => $deposito->id,
                'tanggal_bunga' => $today
            ]);
            
            $this->db->set('bunga_tersedia', 'bunga_tersedia + ' . $bungaAmount, false);
            $this->db->where('id', $deposito->id);
            $this->db->update('tbdeposito');

            $processedAny = true;
        }

        $this->db->trans_complete();
        return $processedAny;
    }

    public function get_data_by_id($id)
    {
        return $this->db
            ->select('id, deposito_id, jumlah_transaksi, rate_bunga')
            ->from('tbtransaksi_deposito')
            ->where('id', $id)
            ->get()
            ->row();
    }

    public function delete_data($id)
    {
        return $this->db->delete('tbtransaksi_deposito', ['id' => $id]);
    }

    function round_to_nearest_hundred($value)
    {
        return round($value / 100) * 100;
    }
}