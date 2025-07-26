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

        // filter tanggal
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('DATE(tbtransaksi_deposito.tanggal_transaksi) >=', $start_date);
            $this->db->where('DATE(tbtransaksi_deposito.tanggal_transaksi) <=', $end_date);
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

        $this->db->select('tbdeposito.id, tbdeposito.nasabah_id, tbdeposito.jumlah_deposito, tbdeposito.tanggal_deposito, tbdeposito.rate_bunga');
        $this->db->from('tbdeposito');
        $this->db->where('tbdeposito.status', 'aktif');
        $depositoList = $this->db->get()->result();

        foreach ($depositoList as $deposito) {
            $bungaRate = (float) $deposito->rate_bunga;
            $saldo = (float) $deposito->jumlah_deposito;
            $tanggalDeposito = $deposito->tanggal_deposito;

            if ($saldo < 0) continue;

            $daysDiff = (strtotime($today) - strtotime($tanggalDeposito)) / (60 * 60 * 24);
            if ($daysDiff < 30) continue;

            if (date('d') != date('d', strtotime($tanggalDeposito))) {
                continue;
            }

            // $bungaExists = $this->db->where('deposito_id', $deposito->id)
            //     ->where('MONTH(tanggal_bunga)', date('m'))
            //     ->where('YEAR(tanggal_bunga)', date('Y'))
            //     ->get('tbdeposito_bunga_log')->num_rows();

            // if ($bungaExists > 0) continue;

            // Hitung bunga dan bulatkan ke kelipatan 100 terdekat
            $bungaAmountRaw = ($bungaRate / 100) * $saldo;
            $bungaAmount = round($bungaAmountRaw / 100) * 100;

            $this->db->insert('tbtransaksi_deposito', [
                'deposito_id' => $deposito->id,
                'tanggal_transaksi' => $today,
                'jumlah_transaksi' => $bungaAmount,
                'rate_bunga'        => $bungaRate
            ]);

            $this->db->insert('tbdeposito_bunga_log', [
                'deposito_id' => $deposito->id,
                'tanggal_bunga' => $today
            ]);

            $this->db->set('jumlah_bunga', 'jumlah_bunga + ' . $bungaAmount, false);
            $this->db->set('total_bunga', 'total_bunga + ' . $bungaAmount, false);
            $this->db->where('id', $deposito->id);
            $this->db->update('tbdeposito');

            $processedAny = true;
        }

        return $processedAny;
    }

    // public function is_bunga_deposito_done_today()
    // {
    //     $today = date('Y-m-d');
    //     $tanggalHariIni = date('d');

    //     $this->db->select('tbdeposito.id');
    //     $this->db->from('tbdeposito');
    //     $this->db->where('tbdeposito.status', 'aktif');
    //     $this->db->where('DAY(tbdeposito.tanggal_deposito)', $tanggalHariIni);
    //     $this->db->where('DATEDIFF(?, tbdeposito.tanggal_deposito) >=', 30);
    //     $eligibleDeposito = $this->db->get_compiled_select();

    //     $sql = "
    //     SELECT COUNT(*) AS belum_proses FROM (
    //         {$eligibleDeposito}
    //     ) AS eligible
    //     WHERE NOT EXISTS (
    //         SELECT 1 FROM tbdeposito_bunga_log
    //         WHERE tbdeposito_bunga_log.deposito_id = eligible.id
    //         AND tanggal_bunga = ?
    //     )
    // ";

    //     $query = $this->db->query($sql, [$today, $today]);
    //     return $query->row()->belum_proses == 0;
    // }

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
