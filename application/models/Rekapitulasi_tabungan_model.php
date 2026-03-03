<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_tabungan_model extends CI_Model
{
    var $table = 'tbsimpanan';
    var $column_order = array(null, 'tbsimpanan.no_rekening', 'nama_nasabah', 'setoran_bulan', 'penarikan_bulan', 'saldo_pokok', 'bunga');
    var $column_search = array('tbsimpanan.nama_nasabah', 'tbsimpanan.no_rekening');
    var $order = array('CAST(SUBSTRING(tbsimpanan.no_rekening, 2) AS UNSIGNED)' => 'ASC');

    private function _get_datatables_query($bulan, $tahun)
    {
        // Set date range for queries
        if ($bulan == 'all') {
            $start_of_period = date('Y-01-01', strtotime("$tahun-01-01"));
            $end_of_period = date('Y-12-31', strtotime("$tahun-12-31"));
        } else {
            $start_of_period = date('Y-m-01', strtotime("$tahun-$bulan-01"));
            $end_of_period = date('Y-m-t', strtotime($start_of_period));
        }

        $escaped_start = $this->db->escape($start_of_period);
        $escaped_end = $this->db->escape($end_of_period);

        // SETORAN: Sum of deposits in the selected period
        $setoran_subquery = "(SELECT COALESCE(SUM(ds.jumlah_setoran), 0)
             FROM tbdetail_simpanan ds
             WHERE ds.simpanan_id = tbsimpanan.id
             AND DATE(ds.tanggal_setoran) >= $escaped_start
             AND DATE(ds.tanggal_setoran) <= $escaped_end)";

        // PENARIKAN: Sum of approved withdrawals in the selected period
        $penarikan_subquery = "(SELECT COALESCE(SUM(dp.jumlah_penarikan), 0)
             FROM tbdetail_penarikan dp
             WHERE dp.simpanan_id = tbsimpanan.id
             AND dp.status = 'disetujui'
             AND DATE(dp.tanggal_penarikan) >= $escaped_start
             AND DATE(dp.tanggal_penarikan) <= $escaped_end)";

        // SALDO POKOK: Calculate as-of end of selected period
        // Formula: current_balance - setoran_after - bunga_after + penarikan_after
        $setoran_after = "(SELECT COALESCE(SUM(ds2.jumlah_setoran), 0)
             FROM tbdetail_simpanan ds2
             WHERE ds2.simpanan_id = tbsimpanan.id
             AND DATE(ds2.tanggal_setoran) > $escaped_end)";

        $penarikan_after = "(SELECT COALESCE(SUM(dp2.jumlah_penarikan), 0)
             FROM tbdetail_penarikan dp2
             WHERE dp2.simpanan_id = tbsimpanan.id
             AND dp2.status = 'disetujui'
             AND DATE(dp2.tanggal_penarikan) > $escaped_end)";

        $bunga_after = "(SELECT COALESCE(SUM(t2.jumlah_transaksi), 0)
             FROM tbtransaksi t2
             WHERE t2.simpanan_id = tbsimpanan.id
             AND t2.tanggal_transaksi > $escaped_end)";

        $saldo_pokok_subquery = "(COALESCE(tbsimpanan.jumlah_simpanan, 0) - $setoran_after + $penarikan_after - $bunga_after)";

        // BUNGA: Sum of interest transactions for the selected period
        if ($bulan == 'all') {
            $bunga_subquery = "
                (SELECT COALESCE(SUM(tbtransaksi.jumlah_transaksi), 0)
                 FROM tbtransaksi 
                 WHERE tbtransaksi.simpanan_id = tbsimpanan.id 
                 AND YEAR(tbtransaksi.tanggal_transaksi) = " . $this->db->escape($tahun) . ")
            ";
        } else {
            $bunga_subquery = "
                (SELECT COALESCE(SUM(tbtransaksi.jumlah_transaksi), 0)
                 FROM tbtransaksi 
                 WHERE tbtransaksi.simpanan_id = tbsimpanan.id 
                 AND MONTH(tbtransaksi.tanggal_transaksi) = " . $this->db->escape($bulan) . "
                 AND YEAR(tbtransaksi.tanggal_transaksi) = " . $this->db->escape($tahun) . ")
            ";
        }

        $this->db->select(
            'tbsimpanan.id,
             tbsimpanan.no_rekening, 
             COALESCE(tbsimpanan.nama_nasabah, tbnasabah.nama_lengkap) as nama_nasabah,
             ' . $setoran_subquery . ' as setoran_bulan,
             ' . $penarikan_subquery . ' as penarikan_bulan,
             ' . $saldo_pokok_subquery . ' as saldo_pokok,
             ' . $bunga_subquery . ' as bunga',
            FALSE
        );

        $this->db->from($this->table);
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id', 'left');

        // Search logic
        $i = 0;
        foreach ($this->column_search as $item) {
            if (isset($_POST['search']['value']) && $_POST['search']['value']) {
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

        // Order logic
        if (isset($_POST['order'])) {
            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } else if (isset($this->order)) {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)], FALSE);
        }
    }

    function get_datatables($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);
        if (isset($_POST['length']) && $_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function get_summary_data($bulan, $tahun)
    {
        // Calculate period
        if ($bulan == 'all') {
            $start_of_period = date('Y-01-01', strtotime("$tahun-01-01"));
            $end_of_period = date('Y-12-31', strtotime("$tahun-12-31"));
        } else {
            $start_of_period = date('Y-m-01', strtotime("$tahun-$bulan-01"));
            $end_of_period = date('Y-m-t', strtotime($start_of_period));
        }

        $escaped_start = $this->db->escape($start_of_period);
        $escaped_end = $this->db->escape($end_of_period);

        // Total SETORAN in the period
        $setoran_query = $this->db->query("
            SELECT COALESCE(SUM(ds.jumlah_setoran), 0) as total
            FROM tbdetail_simpanan ds
            JOIN tbsimpanan ts ON ts.id = ds.simpanan_id
            WHERE DATE(ds.tanggal_setoran) >= $escaped_start
            AND DATE(ds.tanggal_setoran) <= $escaped_end
        ");
        $total_setoran = $setoran_query->row()->total ?? 0;

        // Total PENARIKAN in the period
        $penarikan_query = $this->db->query("
            SELECT COALESCE(SUM(dp.jumlah_penarikan), 0) as total
            FROM tbdetail_penarikan dp
            JOIN tbsimpanan ts ON ts.id = dp.simpanan_id
            WHERE dp.status = 'disetujui'
            AND DATE(dp.tanggal_penarikan) >= $escaped_start
            AND DATE(dp.tanggal_penarikan) <= $escaped_end
        ");
        $total_penarikan = $penarikan_query->row()->total ?? 0;

        // Total SALDO as-of end of period
        $saldo_query = $this->db->query("
            SELECT 
                (SELECT COALESCE(SUM(jumlah_simpanan), 0) FROM tbsimpanan) 
                - (SELECT COALESCE(SUM(ds.jumlah_setoran), 0) FROM tbdetail_simpanan ds 
                   JOIN tbsimpanan ts ON ts.id = ds.simpanan_id 
                   WHERE DATE(ds.tanggal_setoran) > $escaped_end)
                + (SELECT COALESCE(SUM(dp.jumlah_penarikan), 0) FROM tbdetail_penarikan dp 
                   JOIN tbsimpanan ts ON ts.id = dp.simpanan_id 
                   WHERE dp.status = 'disetujui' 
                   AND DATE(dp.tanggal_penarikan) > $escaped_end)
                - (SELECT COALESCE(SUM(t.jumlah_transaksi), 0) FROM tbtransaksi t 
                   JOIN tbsimpanan ts ON ts.id = t.simpanan_id 
                   WHERE t.tanggal_transaksi > $escaped_end)
                as total_saldo
        ");
        $total_saldo_pokok = $saldo_query->row()->total_saldo ?? 0;

        // Total BUNGA for the period
        $this->db->select_sum('jumlah_transaksi', 'total_bunga');
        $this->db->from('tbtransaksi');
        if ($bulan == 'all') {
            $this->db->where('YEAR(tanggal_transaksi)', $tahun);
        } else {
            $this->db->where('MONTH(tanggal_transaksi)', $bulan);
            $this->db->where('YEAR(tanggal_transaksi)', $tahun);
        }
        $bunga_result = $this->db->get()->row();

        return [
            'total_setoran' => $total_setoran,
            'total_penarikan' => $total_penarikan,
            'total_saldo_pokok' => $total_saldo_pokok,
            'total_bunga' => $bunga_result->total_bunga ?? 0,
        ];
    }
}
