<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tabungan_model extends CI_Model
{
    var $table = 'tbsimpanan';
    var $column_order = array(null, 'tanggal', 'jumlah_uang', 'keterangan', 'pegawai', null);
    var $column_search = array('trans.tanggal', 'trans.keterangan', 'tbpegawai.nama_lengkap');
    var $order = array('tanggal' => 'ASC');

    private function _get_datatables_query($id = null)
    {
        $subquery = "
        (
            SELECT 
                ds.id AS detail_id,
                tbs.id AS simpanan_id,
                tbs.pegawai_id,
                ds.tanggal_setoran AS tanggal,
                ds.jumlah_setoran AS jumlah_uang,
                tp.nama_lengkap as pegawai,
                'Setor' AS keterangan
            FROM tbdetail_simpanan ds
            JOIN tbpegawai tp ON tp.id = ds.pegawai_id
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id

            UNION ALL

            SELECT 
                dp.id AS detail_id,
                tbs.id AS simpanan_id,
                tbs.pegawai_id,
                dp.tanggal_penarikan AS tanggal,
                dp.jumlah_penarikan AS jumlah_uang,
                tp.nama_lengkap as pegawai,
                'Tarik' AS keterangan
            FROM tbdetail_penarikan dp
            JOIN tbpegawai tp ON tp.id = dp.pegawai_id
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
        ) AS trans
        ";

        $this->db->from($subquery);
        $this->db->join('tbpegawai', 'tbpegawai.id = trans.pegawai_id');

        if ($id !== null) {
            $this->db->where('trans.simpanan_id', $id);
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

    function get_datatables($id = null)
    {
        $this->_get_datatables_query($id);
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered($id = null)
    {
        $this->_get_datatables_query($id);
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all($id = null)
    {
        $where = "";
        $bind = [];

        if ($id !== null) {
            $where = "WHERE simpanan_id = ?";
            $bind[] = $id;
        }

        $sql = "
        SELECT COUNT(*) AS total FROM (
            SELECT 
                tbs.id AS simpanan_id
            FROM tbdetail_simpanan ds
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id

            UNION ALL

            SELECT 
                tbs.id AS simpanan_id
            FROM tbdetail_penarikan dp
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
        ) AS trans
        $where
    ";

        $query = $this->db->query($sql, $bind);
        return $query->row()->total;
    }

    public function get_data_by_id($id)
    {
        $sql = "
        SELECT tbs.id, tbs.no_rekening, tbs.nasabah_id, tbnasabah.nama_lengkap
        FROM (
            SELECT tbs.id, tbs.no_rekening, tbs.nasabah_id
            FROM tbdetail_simpanan ds
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id
            WHERE tbs.id = ?

            UNION ALL

            SELECT tbs.id, tbs.no_rekening, tbs.nasabah_id
            FROM tbdetail_penarikan dp
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
            WHERE tbs.id = ?
        ) AS tbs
        JOIN tbnasabah ON tbnasabah.id = tbs.nasabah_id
        LIMIT 1
    ";

        $query = $this->db->query($sql, [$id, $id]);
        return $query->row();
    }

    public function get_transaksi_rekening_koran($source_id, $tanggal_mulai = null, $tanggal_akhir = null, $jenis_laporan = 3, $jenis_tabungan = 'simpanan')
    {
        $queries = [];
        $saldo_awal = 0;
        $total_setor_periode = 0;
        $total_tarik_periode = 0;
        $used_first_setor = false;
        $first_setor_date = null;

        if ($jenis_tabungan === 'simpanan') {
            // --- Hitung saldo awal Simpanan ---
            $this->db->select_sum('jumlah_setoran', 'total');
            $this->db->where('simpanan_id', $source_id);
            if ($tanggal_mulai) $this->db->where('tanggal_setoran <', $tanggal_mulai);
            $q1 = $this->db->get('tbdetail_simpanan');
            $setor_awal = ($q1->num_rows() > 0 && $q1->row()->total !== null) ? (float) $q1->row()->total : 0;

            $this->db->select_sum('jumlah_penarikan', 'total');
            $this->db->where('simpanan_id', $source_id);
            if ($tanggal_mulai) $this->db->where('tanggal_penarikan <', $tanggal_mulai);
            $q2 = $this->db->get('tbdetail_penarikan');
            $tarik_awal = ($q2->num_rows() > 0 && $q2->row()->total !== null) ? (float) $q2->row()->total : 0;

            $this->db->select_sum('jumlah_transaksi', 'total');
            $this->db->where('simpanan_id', $source_id);
            if ($tanggal_mulai) $this->db->where('tanggal_transaksi <', $tanggal_mulai);
            $q3 = $this->db->get('tbtransaksi');
            $bunga_awal = ($q3->num_rows() > 0 && $q3->row()->total !== null) ? (float) $q3->row()->total : 0;

            $saldo_awal = ($setor_awal + $bunga_awal) - $tarik_awal;

            // Jika saldo_awal masih 0, cek setoran pertama dalam periode
            if ($saldo_awal == 0 && $tanggal_mulai && $tanggal_akhir) {
                $this->db->select('tanggal_setoran, jumlah_setoran');
                $this->db->where('simpanan_id', $source_id);
                $this->db->where('tanggal_setoran >=', $tanggal_mulai);
                $this->db->where('tanggal_setoran <=', $tanggal_akhir);
                $this->db->order_by('tanggal_setoran', 'ASC');
                $this->db->limit(1);
                $first_setor = $this->db->get('tbdetail_simpanan')->row();

                if ($first_setor && $first_setor->jumlah_setoran > 0) {
                    $saldo_awal = (float) $first_setor->jumlah_setoran;
                    $first_setor_date = $first_setor->tanggal_setoran;
                    $used_first_setor = true;
                }
            }

            // --- Transaksi Setoran ---
            if ($jenis_laporan == 1 || $jenis_laporan == 3) {
                $this->db->select("
                ds.tanggal_setoran AS tanggal,
                0 AS debit,
                ds.jumlah_setoran AS kredit,
                'Setoran Tunai' AS keterangan,
                p.nama_lengkap AS pegawai
            ");
                $this->db->from('tbdetail_simpanan ds');
                $this->db->join('tbpegawai p', 'p.id = ds.pegawai_id', 'left');
                $this->db->where('ds.simpanan_id', $source_id);
                if ($tanggal_mulai && $tanggal_akhir) {
                    $this->db->where('ds.tanggal_setoran >=', $tanggal_mulai);
                    $this->db->where('ds.tanggal_setoran <=', $tanggal_akhir);
                    if ($used_first_setor && $first_setor_date) {
                        $this->db->where('ds.tanggal_setoran !=', $first_setor_date);
                    }
                }
                $queries[] = $this->db->get_compiled_select();
            }

            // --- Transaksi Penarikan ---
            if ($jenis_laporan == 2 || $jenis_laporan == 3) {
                $this->db->select("
                dp.tanggal_penarikan AS tanggal,
                dp.jumlah_penarikan AS debit,
                0 AS kredit,
                'Penarikan Tunai' AS keterangan,
                p.nama_lengkap AS pegawai
            ");
                $this->db->from('tbdetail_penarikan dp');
                $this->db->join('tbpegawai p', 'p.id = dp.pegawai_id', 'left');
                $this->db->where('dp.simpanan_id', $source_id);
                if ($tanggal_mulai && $tanggal_akhir) {
                    $this->db->where('dp.tanggal_penarikan >=', $tanggal_mulai);
                    $this->db->where('dp.tanggal_penarikan <=', $tanggal_akhir);
                }
                $queries[] = $this->db->get_compiled_select();
            }

            // --- Transaksi Bunga Simpanan ---
            $this->db->select("
            tanggal_transaksi AS tanggal,
            0 AS debit,
            jumlah_transaksi AS kredit,
            'Bunga Simpanan' AS keterangan,
            'SYSTEM' AS pegawai
        ");
            $this->db->from('tbtransaksi');
            $this->db->where('simpanan_id', $source_id);
            if ($tanggal_mulai && $tanggal_akhir) {
                $this->db->where('tanggal_transaksi >=', $tanggal_mulai);
                $this->db->where('tanggal_transaksi <=', $tanggal_akhir);
            }
            $queries[] = $this->db->get_compiled_select();
        } else if ($jenis_tabungan === 'deposito') {
            // --- Hitung saldo awal Deposito ---
            $this->db->select_sum('jumlah_transaksi', 'total');
            $this->db->where('deposito_id', $source_id);
            if ($tanggal_mulai) $this->db->where('tanggal_transaksi <', $tanggal_mulai);
            $q4 = $this->db->get('tbtransaksi_deposito');
            $bunga_awal = ($q4->num_rows() > 0 && $q4->row()->total !== null) ? (float) $q4->row()->total : 0;

            $saldo_awal = $bunga_awal;

            // --- Transaksi Bunga Deposito ---
            $this->db->select("
            tanggal_transaksi AS tanggal,
            0 AS debit,
            jumlah_transaksi AS kredit,
            'Bunga Deposito' AS keterangan,
            'SYSTEM' AS pegawai
        ");
            $this->db->from('tbtransaksi_deposito');
            $this->db->where('deposito_id', $source_id);
            if ($tanggal_mulai && $tanggal_akhir) {
                $this->db->where('tanggal_transaksi >=', $tanggal_mulai);
                $this->db->where('tanggal_transaksi <=', $tanggal_akhir);
            }
            $queries[] = $this->db->get_compiled_select();
        }

        // --- Final Execution ---
        if (empty($queries)) {
            return [
                'saldo_awal' => $saldo_awal,
                'total_setor' => 0,
                'total_tarik' => 0,
                'saldo_akhir' => $saldo_awal,
                'transaksi' => []
            ];
        }

        $final_query = implode(" UNION ALL ", $queries) . " ORDER BY tanggal ASC";
        $transaksi = $this->db->query($final_query)->result();

        $total_setor_periode = array_sum(array_column($transaksi, 'kredit'));
        $total_tarik_periode = array_sum(array_column($transaksi, 'debit'));
        $saldo_akhir = $saldo_awal + $total_setor_periode - $total_tarik_periode;

        return [
            'saldo_awal' => $saldo_awal,
            'total_setor' => $total_setor_periode,
            'total_tarik' => $total_tarik_periode,
            'saldo_akhir' => $saldo_akhir,
            'transaksi' => $transaksi
        ];
    }
}
