<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_tabungan_model extends CI_Model
{
    var $table = 'tbsimpanan';
    var $column_order = array(null, 'tbsimpanan.no_rekening', 'tbnasabah.nama_lengkap', 'saldo_pokok', 'bunga', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbsimpanan.no_rekening');
    var $order = array('tbsimpanan.no_rekening' => 'ASC');

    private function _get_datatables_query($bulan, $tahun)
    {
        // ✅ CORRECTED: Date range logic now handles the current year correctly
        if ($bulan == 'all') {
            $start_of_period = date('Y-01-01', strtotime("$tahun-01-01"));
            if ($tahun == date('Y')) {
                // For the current year, use today's date as the end date
                $end_of_period = date('Y-m-d');
            } else {
                // For past years, use the end of the year
                $end_of_period = date('Y-12-31', strtotime("$tahun-12-31"));
            }
        } else {
            $start_of_period = date('Y-m-01', strtotime("$tahun-$bulan-01"));
            $end_of_period = date('Y-m-t', strtotime($start_of_period));
        }

        // Subquery for SALDO POKOK: This logic is now correct because the dates are right
        $saldo_pokok_subquery = "
            COALESCE((SELECT SUM(tds.jumlah_setoran) 
                        FROM tbdetail_simpanan tds 
                        WHERE tds.simpanan_id = tbsimpanan.id 
                        AND tds.tanggal_setoran <= " . $this->db->escape($end_of_period) . "), 0)
            + 
            COALESCE((SELECT SUM(tbt.jumlah_transaksi) 
                        FROM tbtransaksi tbt 
                        WHERE tbt.simpanan_id = tbsimpanan.id 
                        AND tbt.tanggal_transaksi < " . $this->db->escape($start_of_period) . "), 0)
        ";

        // Subquery for BUNGA: This logic was already correct and adapts to the dates
        if ($bulan == 'all') {
            $bunga_subquery = "
                (SELECT SUM(tbtransaksi.jumlah_transaksi) 
                 FROM tbtransaksi 
                 WHERE tbtransaksi.simpanan_id = tbsimpanan.id 
                 AND YEAR(tbtransaksi.tanggal_transaksi) = " . $this->db->escape($tahun) . "
                 AND tbtransaksi.tanggal_transaksi <= " . $this->db->escape($end_of_period) . ")
            ";
        } else {
            $bunga_subquery = "
                (SELECT SUM(tbtransaksi.jumlah_transaksi) 
                 FROM tbtransaksi 
                 WHERE tbtransaksi.simpanan_id = tbsimpanan.id 
                 AND MONTH(tbtransaksi.tanggal_transaksi) = " . $this->db->escape($bulan) . "
                 AND YEAR(tbtransaksi.tanggal_transaksi) = " . $this->db->escape($tahun) . ")
            ";
        }

        $this->db->select(
            'tbsimpanan.id,
             tbsimpanan.no_rekening, 
             tbnasabah.nama_lengkap as nama_nasabah,
             (' . $saldo_pokok_subquery . ') as saldo_pokok,
             ' . $bunga_subquery . ' as bunga'
        );

        $this->db->from($this->table);
        $this->db->join('tbnasabah', 'tbnasabah.id = tbsimpanan.nasabah_id');
        $this->db->where('tbsimpanan.status', 'aktif');
        $this->db->where('tbsimpanan.tanggal_simpanan <=', $end_of_period);

        // --- Search and Order logic remains unchanged ---
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

    // No changes needed in these functions
    function get_datatables($bulan, $tahun)
    {
        $this->_get_datatables_query($bulan, $tahun);
        if ($_POST['length'] != -1)
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
        $this->db->where('status', 'aktif');
        return $this->db->count_all_results();
    }

    public function get_summary_data($bulan, $tahun)
    {
        // ✅ CORRECTED: Date range logic now handles the current year correctly
        if ($bulan == 'all') {
            $start_of_period = date('Y-01-01', strtotime("$tahun-01-01"));
            if ($tahun == date('Y')) {
                // For the current year, use today's date as the end date
                $end_of_period = date('Y-m-d');
            } else {
                // For past years, use the end of the year
                $end_of_period = date('Y-12-31', strtotime("$tahun-12-31"));
            }
        } else {
            $start_of_period = date('Y-m-01', strtotime("$tahun-$bulan-01"));
            $end_of_period = date('Y-m-t', strtotime($start_of_period));
        }

        // --- All queries below now use the correct dates ---

        $this->db->select_sum('tds.jumlah_setoran', 'total_deposits');
        $this->db->from('tbdetail_simpanan as tds');
        $this->db->join('tbsimpanan as ts', 'ts.id = tds.simpanan_id');
        $this->db->where('ts.status', 'aktif');
        $this->db->where('tds.tanggal_setoran <=', $end_of_period);
        $this->db->where('ts.tanggal_simpanan <=', $end_of_period);
        $total_deposits_result = $this->db->get()->row();
        $total_deposits = $total_deposits_result->total_deposits ?? 0;

        $this->db->select_sum('tbt.jumlah_transaksi', 'total_prior_interest');
        $this->db->from('tbtransaksi as tbt');
        $this->db->join('tbsimpanan as ts', 'ts.id = tbt.simpanan_id');
        $this->db->where('ts.status', 'aktif');
        $this->db->where('ts.tanggal_simpanan <=', $end_of_period);
        $this->db->where('tbt.tanggal_transaksi <', $start_of_period);
        $prior_interest_result = $this->db->get()->row();
        $total_prior_interest = $prior_interest_result->total_prior_interest ?? 0;

        $this->db->select_sum('jumlah_transaksi', 'total_bunga');
        $this->db->from('tbtransaksi');
        $this->db->join('tbsimpanan as ts', 'ts.id = simpanan_id');
        $this->db->where('ts.status', 'aktif');
        $this->db->where('ts.tanggal_simpanan <=', $end_of_period);
        
        if ($bulan == 'all') {
            $this->db->where('YEAR(tanggal_transaksi)', $tahun);
            // Also ensure we don't count future interest
            $this->db->where('tanggal_transaksi <=', $end_of_period);
        } else {
            $this->db->where('MONTH(tanggal_transaksi)', $bulan);
            $this->db->where('YEAR(tanggal_transaksi)', $tahun);
        }
        $bunga_result = $this->db->get()->row();
        
        $total_saldo_pokok = $total_deposits + $total_prior_interest;

        return [
            'total_saldo_pokok' => $total_saldo_pokok,
            'total_bunga'       => $bunga_result->total_bunga ?? 0,
        ];
    }
}