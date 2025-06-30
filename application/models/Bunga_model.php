<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga_model extends CI_Model
{
    var $column_order = array(null, 'tanggal_transaksi', 'nama_lengkap', 'no_rekening', 'jumlah_transaksi', 'tipe', null);
    var $column_search = array('tanggal_transaksi', 'nama_lengkap', 'no_rekening', 'tipe');
    var $order = array('tanggal_transaksi' => 'DESC');

    private function _get_base_query()
    {
        return "
        SELECT * FROM (
            SELECT 
                t1.id,
                t1.tanggal_transaksi,
                t1.jumlah_transaksi,
                'Simpanan' AS tipe,
                n.nama_lengkap,
                s.no_rekening,
                t1.rate_bunga
            FROM tbtransaksi t1
            JOIN tbsimpanan s ON s.id = t1.simpanan_id
            JOIN tbnasabah n ON n.id = s.nasabah_id
            UNION ALL
            SELECT 
                t2.id,
                t2.tanggal_transaksi,
                t2.jumlah_transaksi,
                'Deposito' AS tipe,
                n.nama_lengkap,
                d.no_rekening,
                t2.rate_bunga
            FROM tbtransaksi_deposito t2
            JOIN tbdeposito d ON d.id = t2.deposito_id
            JOIN tbnasabah n ON n.id = d.nasabah_id
        ) AS bunga";
    }

    private function _get_datatables_query($start_date = null, $end_date = null)
    {
        $sql = $this->_get_base_query();
        $conditions = [];

        if ($start_date && $end_date) {
            $conditions[] = "tanggal_transaksi BETWEEN '$start_date' AND '$end_date'";
        }

        if (!empty($_POST['search']['value'])) {
            $search = $this->db->escape_like_str($_POST['search']['value']);
            $search_conditions = [];
            foreach ($this->column_search as $item) {
                $search_conditions[] = "$item LIKE '%$search%'";
            }
            $conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if (isset($_POST['order'])) {
            $col_idx = $_POST['order']['0']['column'];
            $col_name = $this->column_order[$col_idx];
            $dir = $_POST['order']['0']['dir'];
            if ($col_name) {
                $sql .= " ORDER BY $col_name $dir";
            }
        } else {
            $order = $this->order;
            $sql .= " ORDER BY " . key($order) . " " . $order[key($order)];
        }

        return $sql;
    }

    function get_datatables($start_date = null, $end_date = null)
    {
        $sql = $this->_get_datatables_query($start_date, $end_date);

        if (isset($_POST['length']) && $_POST['length'] != -1) {
            $limit = (int)$_POST['length'];
            $offset = (int)$_POST['start'];
            $sql .= " LIMIT $offset, $limit";
        }

        return $this->db->query($sql)->result();
    }

    function count_filtered($start_date = null, $end_date = null)
    {
        $sql = $this->_get_datatables_query($start_date, $end_date);
        $count_sql = "SELECT COUNT(*) as filtered FROM ($sql) as count_table";
        return $this->db->query($count_sql)->row()->filtered;
    }

    function count_all()
    {
        $sql = "
        SELECT COUNT(*) AS total FROM (
            SELECT id FROM tbtransaksi
            UNION ALL
            SELECT id FROM tbtransaksi_deposito
        ) AS trans";
        $query = $this->db->query($sql);
        return $query->row()->total;
    }

    public function get_total_bunga_filtered($start_date = null, $end_date = null)
    {
        $sql = $this->_get_datatables_query($start_date, $end_date);
        $sum_sql = "SELECT SUM(jumlah_transaksi) AS total_bunga FROM ($sql) AS subquery";
        $query = $this->db->query($sum_sql);
        return $query->row()->total_bunga ?? 0;
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

            $bungaAmountRaw = ($bungaRate / 100) * $saldo;

            // Round up to nearest 100
            $bungaAmount = ceil($bungaAmountRaw / 100) * 100;

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
                'rate_bunga'        => $bungaRate
            ]);

            // Update saldo
            $this->db->set('jumlah_simpanan', 'jumlah_simpanan + ' . $bungaAmount, false);
            $this->db->where('id', $simpanan->id);
            $this->db->update('tbsimpanan');
        }
    }


    public function bunga_proses_deposito()
    {
        $today = date('Y-m-d');
        $processedAny = false;

        $this->db->select('tbdeposito.id, tbdeposito.nasabah_id, tbdeposito.jumlah_deposito, tbdeposito.tanggal_deposito, tbjenistabungan.bunga');
        $this->db->from('tbdeposito');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbdeposito.jenistabungan_id');
        $this->db->where('tbdeposito.status', 'aktif');
        $depositoList = $this->db->get()->result();

        foreach ($depositoList as $deposito) {
            $bungaRate = (float) $deposito->bunga;
            $saldo = (float) $deposito->jumlah_deposito;
            $tanggalDeposito = $deposito->tanggal_deposito;

            if ($saldo <= 0) continue;

            $daysDiff = (strtotime($today) - strtotime($tanggalDeposito)) / (60 * 60 * 24);
            if ($daysDiff < 30) continue;

            if (date('d') != date('d', strtotime($tanggalDeposito))) {
                continue;
            }

            $bungaExists = $this->db->where('deposito_id', $deposito->id)
                ->where('MONTH(tanggal_bunga)', date('m'))
                ->where('YEAR(tanggal_bunga)', date('Y'))
                ->get('tbdeposito_bunga_log')->num_rows();

            if ($bungaExists > 0) continue;

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

            $processedAny = true;
        }

        return $processedAny;
    }

    public function is_bunga_deposito_done_today()
    {
        $today = date('Y-m-d');
        $tanggalHariIni = date('d');

        $this->db->select('tbdeposito.id');
        $this->db->from('tbdeposito');
        $this->db->where('tbdeposito.status', 'aktif');
        $this->db->where('DAY(tbdeposito.tanggal_deposito)', $tanggalHariIni);
        $this->db->where('DATEDIFF(?, tbdeposito.tanggal_deposito) >=', 30);
        $eligibleDeposito = $this->db->get_compiled_select();

        $sql = "
        SELECT COUNT(*) AS belum_proses FROM (
            {$eligibleDeposito}
        ) AS eligible
        WHERE NOT EXISTS (
            SELECT 1 FROM tbdeposito_bunga_log
            WHERE tbdeposito_bunga_log.deposito_id = eligible.id
            AND tanggal_bunga = ?
        )
    ";

        $query = $this->db->query($sql, [$today, $today]);
        return $query->row()->belum_proses == 0;
    }

    public function get_data_by_id($id)
    {
        $sql = "
        SELECT 
            t1.id,
            t1.simpanan_id,
            NULL AS deposito_id,
            t1.jumlah_transaksi,
            t1.rate_bunga,
            'Simpanan' AS tipe
        FROM tbtransaksi t1
        WHERE t1.id = ?

        UNION ALL

        SELECT 
            t2.id,
            NULL AS simpanan_id,
            t2.deposito_id,
            t2.jumlah_transaksi,
            t2.rate_bunga,
            'Deposito' AS tipe
        FROM tbtransaksi_deposito t2
        WHERE t2.id = ?
        LIMIT 1
    ";

        return $this->db->query($sql, [$id, $id])->row();
    }
    public function delete_data($id, $tipe = 'Simpanan')
    {
        if ($tipe === 'Simpanan') {
            return $this->db->delete('tbtransaksi', ['id' => $id]);
        } elseif ($tipe === 'Deposito') {
            return $this->db->delete('tbtransaksi_deposito', ['id' => $id]);
        }
        return false;
    }

    function round_to_nearest_hundred($value)
    {
        return round($value / 100) * 100;
    }
}
