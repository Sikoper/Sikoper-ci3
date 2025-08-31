<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Deposito_model extends CI_Model
{
    var $table = 'tbdeposito';
    var $column_order = array(null, 'nama_nasabah', 'no_rekening', 'telp_nasabah', 'jumlah_deposito', 'status', null);
    var $column_search = array('tbnasabah.nama_lengkap', 'tbdeposito.no_rekening', 'tbdeposito.status');
    var $order = array('created_at' => 'DESC');

    public $_table_penarikan_deposito = 'tbpenarikan_deposito';
    public $_table_bunga_log = 'tbdeposito_bunga_log';

    private function _get_datatables_query()
    {
        $this->db->select('tbdeposito.*, tbnasabah.nama_lengkap as nama_nasabah, tbnasabah.telp as telp_nasabah');
        $this->db->from($this->table);
        $this->db->join('tbnasabah', 'tbnasabah.id = tbdeposito.nasabah_id');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbdeposito.jenistabungan_id');

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

    function get_datatables()
    {
        $this->_get_datatables_query();
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered()
    {
        $this->_get_datatables_query();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function jumlah_setoran_deposito()
    {
        $this->db->select('MONTH(tanggal_deposito) AS bulan, COUNT(id) AS total_setoran');
        $this->db->from('tbdeposito');
        $this->db->group_by('bulan');
        $this->db->order_by('bulan', 'ASC');
        return $this->db->get()->result();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function insert_data($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function hapus_deposito_lengkap($id_deposito)
    {
        if (empty($id_deposito)) {
            return false;
        }

        $this->db->trans_start();

        $this->db->where('deposito_id', $id_deposito);
        $this->db->delete($this->_table_penarikan_deposito);

        $this->db->where('deposito_id', $id_deposito);
        $this->db->delete($this->_table_bunga_log);

        $this->db->where('id', $id_deposito);
        $this->db->delete($this->table);

        $this->db->trans_complete();


        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Gagal menghapus data deposito lengkap untuk ID: ' . $id_deposito);
            return false;
        }
        return true;
    }

    public function delete_data($id)
    {

        return $this->hapus_deposito_lengkap($id);
    }

    public function edit_data($id, $data)
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function get_data_by_norek($no_rekening)
    {
        return $this->db->get_where($this->table, ['no_rekening' => $no_rekening])->row();
    }

    public function get_rekening_deposito_by_nasabah($nasabah_id)
    {
        $this->db->select('tbdeposito.id, CONCAT(tbdeposito.no_rekening, " - ", tbjenistabungan.nama) as text');
        $this->db->from($this->table);
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbdeposito.jenistabungan_id');
        $this->db->where('tbdeposito.nasabah_id', $nasabah_id);
        $this->db->where('tbjenistabungan.nama', 'Deposito');
        $this->db->where('tbdeposito.status', 'aktif');
        $this->db->where('tbdeposito.jumlah_deposito >', 0);
        $query = $this->db->get();
        return $query->result();
    }

    public function simpan_log_penarikan($data)
    {
        return $this->db->insert('tbpenarikan_deposito', $data);
    }

    public function kurangi_saldo($id, $jumlah)
    {
        $this->db->where('id', $id);
        $this->db->set('jumlah_deposito', 'jumlah_deposito - ' . (float)$jumlah, FALSE);
        return $this->db->update('tbdeposito');
    }

    public function get_nasabah_deposito()
    {
        $this->db->select('
            tbnasabah.nama_lengkap, 
            tbnasabah.nik,
            tbdeposito.no_rekening, 
            tbdeposito.jumlah_deposito, 
            tbdeposito.tanggal_deposito
        ');
        $this->db->from('tbdeposito');
        $this->db->join('tbnasabah', 'tbdeposito.nasabah_id = tbnasabah.id');
        $this->db->order_by('tbnasabah.nama_lengkap', 'ASC');

        $query = $this->db->get();
        return $query->result();
    }

    public function get_detail_for_sertifikat($id)
    {
        $this->db->select('
            tbdeposito.*,
            tbjenistabungan.bunga as suku_bunga,
            tbnasabah.nama_lengkap as nama_nasabah,
            tbnasabah.nik as nik_nasabah,
            tbnasabah.alamat as alamat_nasabah,
            tbnasabah.telp as telp_nasabah,
            tbnasabah.tempat_lahir,
            tbnasabah.tanggal_lahir,
            pegawai.nama_lengkap as nama_pegawai,
            pimpinan.nama_lengkap as nama_pimpinan,
            bendahara.nama_lengkap as nama_bendahara
        ');
        $this->db->from('tbdeposito');
        $this->db->join('tbjenistabungan', 'tbdeposito.jenistabungan_id = tbjenistabungan.id', 'left');
        $this->db->join('tbnasabah', 'tbdeposito.nasabah_id = tbnasabah.id', 'left');
        $this->db->join('tbpegawai as pegawai', 'tbdeposito.pegawai_id = pegawai.id', 'left');
        $this->db->join('tbpegawai as pimpinan', "pimpinan.jabatan = 'KEPALA BAGIAN TATA USAHA'", 'left');
        $this->db->join('tbpegawai as bendahara', "bendahara.jabatan = 'Bendahara'", 'left');
        $this->db->where('tbdeposito.id', $id);
        $this->db->limit(1);

        $query = $this->db->get();
        return $query->row();
    }

    public function ubah_status($deposito_id, $status_baru)
    {
        $this->db->where('id', $deposito_id);
        $this->db->update('tbdeposito', ['status' => $status_baru]);
        return $this->db->affected_rows();
    }

    public function get_rekening_nasabah_combo($searchTerm = null)
    {
        $this->db->select('
        tbdeposito.id,
        tbdeposito.no_rekening,
        tbnasabah.nama_lengkap,
        tbjenistabungan.nama as jenis_tabungan,
        tbnasabah.id as nasabah_id
    ');
        $this->db->from('tbdeposito');
        $this->db->join('tbnasabah', 'tbnasabah.id = tbdeposito.nasabah_id');
        $this->db->join('tbjenistabungan', 'tbjenistabungan.id = tbdeposito.jenistabungan_id');
        $this->db->where('tbjenistabungan.nama', 'Deposito');
        $this->db->group_start()
            ->where('tbdeposito.status', 'aktif')
            ->or_where('tbdeposito.status', 'jatuh tempo')
            ->group_end();
        $this->db->where('tbdeposito.jumlah_deposito >', 0);

        if ($searchTerm) {
            $this->db->group_start();
            $this->db->like('tbdeposito.no_rekening', $searchTerm);
            $this->db->or_like('tbnasabah.nama_lengkap', $searchTerm);
            $this->db->group_end();
        }

        return $this->db->get()->result();
    }

    public function get_by_id($id)
    {
        $this->db->select('
        d.*, 
        n.nama_lengkap
    ');
        $this->db->from('tbdeposito as d');
        $this->db->join('tbnasabah as n', 'n.id = d.nasabah_id', 'left');
        $this->db->where('d.id', $id);
        return $this->db->get()->row();
    }

    public function cari_rekening_deposito_nasabah($search = '')
    {
        $this->db->select('d.id, CONCAT(d.no_rekening, " - ", n.nama_lengkap) as text');
        $this->db->from('tbdeposito d');
        $this->db->join('tbnasabah n', 'd.nasabah_id = n.id');
        $this->db->join('tb_bunga_deposito_log bl', 'd.id = bl.deposito_id');
        $this->db->where('d.status', 'aktif');
        $this->db->where('bl.status_penarikan', 'belum_ditarik');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('d.no_rekening', $search);
            $this->db->or_like('n.nama_lengkap', $search);
            $this->db->group_end();
        }

        $this->db->group_by('d.id');
        $this->db->order_by('d.no_rekening', 'ASC');
        return $this->db->get()->result();
    }

    public function get_detail_deposito_by_id($id)
    {
        $this->db->select('
        d.id, 
        d.no_rekening, 
        n.nama_lengkap as nama_nasabah
    ');
        $this->db->from('tbdeposito d');
        $this->db->join('tbnasabah n', 'd.nasabah_id = n.id');
        $this->db->where('d.id', $id);
        return $this->db->get()->row();
    }

    public function tarik_bunga_deposito($deposito_id, $pegawai_id)
    {
        $this->db->trans_start();

        $this->db->select_sum('jumlah_bunga');
        $this->db->where('deposito_id', $deposito_id);
        $this->db->where('status_penarikan', 'belum_ditarik');
        $total = $this->db->get('tb_bunga_deposito_log')->row()->jumlah_bunga ?? 0;

        if ($total <= 0) {
            $this->db->trans_complete();
            return false;
        }

        $dataP = [
            'deposito_id'            => $deposito_id,
            'pegawai_id'             => $pegawai_id,
            'tanggal_penarikan'      => date('Y-m-d H:i:s'),
            'jumlah_penarikan'       => $total,
            'jumlah_penarikan_pokok' => 0,
            'jumlah_penarikan_bunga' => $total,
            'jumlah_denda'           => 0,
            'total_penarikan'        => $total
        ];
        $this->db->insert('tbpenarikan_deposito', $dataP);
        $penarikan_id = $this->db->insert_id();

        $this->db->set('status_penarikan', 'sudah_ditarik');
        $this->db->set('penarikan_id', $penarikan_id);
        $this->db->where('deposito_id', $deposito_id);
        $this->db->where('status_penarikan', 'belum_ditarik');
        $this->db->update('tb_bunga_deposito_log');

        $this->db->trans_complete();
        return $this->db->trans_status();
    }


    public function get_bunga_tersedia_from_log($deposito_id)
    {
        $this->db->select_sum('jumlah_bunga');
        $this->db->from('tb_bunga_deposito_log');
        $this->db->where('deposito_id', $deposito_id);
        $this->db->where('status_penarikan', 'belum_ditarik');

        $result = $this->db->get()->row();

        return $result->jumlah_bunga ?? 0;
    }


    public function get_bunga_sudah_dibayar_from_log($deposito_id)
    {
        $this->db->select_sum('jumlah_bunga', 'total_bunga');
        $this->db->from('tb_bunga_deposito_log');
        $this->db->where('deposito_id', $deposito_id);
        $this->db->where('status_penarikan', 'sudah_ditarik'); // Hanya hitung yang sudah ditarik
        $result = $this->db->get()->row();
        return (float)($result->total_bunga ?? 0);
    }

    public function get_detail_bunga_by_id($deposito_id)
    {
        if (!$deposito_id) return null;

        // Ambil total bunga dari log
        $this->db->select_sum('jumlah_bunga', 'total_bunga');
        $this->db->where('deposito_id', $deposito_id);
        $this->db->where('status_penarikan', 'belum_ditarik');
        $result = $this->db->get('tb_bunga_deposito_log')->row();

        // Cek kalau memang ada bunga
        $total_bunga = $result && $result->total_bunga ? $result->total_bunga : 0;

        // Ambil nama nasabah
        $nasabah = $this->db->select('n.nama_nasabah')
            ->from('tbdeposito d')
            ->join('tbnasabah n', 'n.id = d.nasabah_id')
            ->where('d.id', $deposito_id)
            ->get()
            ->row();

        return (object)[
            'nama_nasabah' => $nasabah->nama_nasabah ?? 'Tidak ditemukan',
            'bunga_tersedia' => floatval($total_bunga)
        ];
    }

    public function update_status_jatuh_tempo()
    {
        $today = date('Y-m-d');
        $file  = APPPATH . 'cache/last_update_deposito.txt';

        $last_run = file_exists($file) ? file_get_contents($file) : null;

        if ($last_run !== $today) {
            // Jalankan update
            $sql = "
            UPDATE tbdeposito 
            SET status = 'jatuh tempo'
            WHERE status = 'aktif'
            AND DATE_ADD(tanggal_deposito, INTERVAL durasi MONTH) <= ?
        ";
            $this->db->query($sql, [$today]);

            // Simpan tanggal terbaru
            file_put_contents($file, $today);
        }
    }

    public function get_transaksi_by_deposito($id, $tanggal_mulai, $tanggal_akhir, $jenis_laporan = '3')
    {
        $deposito = $this->db->select('jumlah_deposito, tanggal_deposito, pegawai_id')
            ->where('id', $id)->get('tbdeposito')->row();

        if (!$deposito) {
            return [
                'saldo_awal'  => 0,
                'total_setor' => 0,
                'total_tarik' => 0,
                'total_bunga' => 0,
                'saldo_akhir' => 0,
                'transaksi'   => []
            ];
        }

        $tgl_mulai_real  = $tanggal_mulai ?: $deposito->tanggal_deposito;
        $tgl_akhir_query = $tanggal_akhir ?: date('Y-m-d');

        $total_pokok_ditarik = (float)($this->db->select_sum('jumlah_penarikan_pokok', 'total')
            ->where('deposito_id', $id)->get('tbpenarikan_deposito')->row()->total ?? 0);

        $setoran_awal_asli = (float)$deposito->jumlah_deposito + $total_pokok_ditarik;

        $pegawai_awal = $this->db->select('nama_lengkap')->where('id', $deposito->pegawai_id)
            ->get('tbpegawai')->row()->nama_lengkap ?? 'SYSTEM';

        $transaksi_setoran_awal = (object)[
            'tanggal'    => $deposito->tanggal_deposito,
            'keterangan' => 'Setoran Awal Deposito',
            'kredit'     => (float)$setoran_awal_asli,
            'debit'      => 0.0,
            'jenis'      => 'setoran',
            'pegawai'    => $pegawai_awal
        ];

        // Penarikan pokok & bunga dari tbpenarikan_deposito
        $penarikan_sql = "
            SELECT pd.tanggal_penarikan AS tanggal,
                CASE 
                    WHEN pd.jumlah_penarikan_pokok > 0 AND pd.jumlah_penarikan_bunga > 0 THEN 'Penarikan Pokok & Bunga'
                    WHEN pd.jumlah_penarikan_pokok > 0 THEN 'Penarikan Pokok'
                    WHEN pd.jumlah_penarikan_bunga > 0 THEN 'Penarikan Bunga'
                    ELSE 'Penarikan' END AS keterangan,
                0 AS kredit,
                pd.total_penarikan AS debit,
                'penarikan' AS jenis,
                p.nama_lengkap AS pegawai
            FROM tbpenarikan_deposito pd
            LEFT JOIN tbpegawai p ON pd.pegawai_id = p.id
            WHERE pd.deposito_id = ?
            AND DATE(pd.tanggal_penarikan) <= DATE(?)
            ";
        $transaksi_penarikan = $this->db->query($penarikan_sql, [$id, $tgl_akhir_query])->result();

        // Bunga — tampilkan semua status
        // Bunga — tampilkan semua status, bahkan jika sudah_ditarik tampilkan dua baris
        $bunga_sql = "
                SELECT * FROM (
                    -- Baris sebagai Bunga Deposito (selalu ditampilkan)
                    SELECT 
                        DATE(b.tanggal_perhitungan) AS tanggal,
                        'Bunga Deposito' AS keterangan,
                        b.jumlah_bunga AS kredit,
                        0 AS debit,
                        'bunga' AS jenis,
                        'SYSTEM' AS pegawai,
                        b.status_penarikan
                    FROM tb_bunga_deposito_log b
                    WHERE b.deposito_id = ?
                    AND DATE(b.tanggal_perhitungan) <= DATE(?)

                    UNION ALL

                    -- Baris tambahan jika sudah ditarik (Penarikan Bunga)
                    SELECT 
                        DATE(b.tanggal_perhitungan) AS tanggal,
                        'Penarikan Bunga' AS keterangan,
                        0 AS kredit,
                        b.jumlah_bunga AS debit,
                        'penarikan' AS jenis,
                        'SYSTEM' AS pegawai,
                        b.status_penarikan
                    FROM tb_bunga_deposito_log b
                    WHERE b.deposito_id = ?
                    AND DATE(b.tanggal_perhitungan) <= DATE(?)
                    AND b.status_penarikan = 'sudah_ditarik'
                ) AS bunga
                ORDER BY tanggal
            ";

        $transaksi_bunga = $this->db->query($bunga_sql, [$id, $tgl_akhir_query, $id, $tgl_akhir_query])->result();

        // Merge semua transaksi
        $semua_transaksi = array_merge([$transaksi_setoran_awal], $transaksi_bunga, $transaksi_penarikan);
        usort($semua_transaksi, function ($a, $b) {
            return strcmp($a->tanggal, $b->tanggal);
        });

        // Hitung total
        $saldo_awal = 0.0;
        $total_setor = 0.0;
        $total_tarik = 0.0;
        $total_bunga = 0.0;
        $transaksi_periode = [];

        foreach ($semua_transaksi as $t) {
            $t->kredit = (float)$t->kredit;
            $t->debit  = (float)$t->debit;

            if ($t->tanggal < $tgl_mulai_real) {
                $saldo_awal += ($t->kredit - $t->debit);
                continue;
            }

            if ($t->tanggal > $tgl_akhir_query) {
                continue;
            }

            $is_kredit = ($t->jenis === 'setoran' || $t->jenis === 'bunga');
            $is_debit  = ($t->jenis === 'penarikan');

            if (($jenis_laporan === '1' && $is_kredit) ||
                ($jenis_laporan === '2' && $is_debit)  ||
                $jenis_laporan === '3'
            ) {
                $transaksi_periode[] = $t;

                // Semua bunga masuk total_bunga
                if ($t->jenis === 'bunga' || $t->keterangan === 'Penarikan Bunga') {
                    $total_bunga += $t->kredit;
                }

                // Sudah_ditarik tetap masuk sebagai setoran untuk pencatatan
                $total_setor += $t->kredit;
                $total_tarik += $t->debit;
            }
        }

        return [
            'saldo_awal'  => $saldo_awal,
            'total_setor' => $total_setor,
            'total_tarik' => $total_tarik,
            'total_bunga' => $total_bunga,
            'saldo_akhir' => $saldo_awal + $total_setor - $total_tarik,
            'transaksi'   => $transaksi_periode
        ];
    }
}
