<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Deposito_model extends CI_Model
{
    var $table = 'tbdeposito';
    // DENORMALIZED: Using denormalized columns for faster queries
    var $column_order = array(null, 'nama_nasabah', 'no_rekening', 'telp_nasabah', 'jumlah_deposito', 'status', null);
    var $column_search = array('tbdeposito.nama_nasabah', 'tbdeposito.no_rekening', 'tbdeposito.status');
    var $order = array('created_at' => 'DESC');

    public $_table_penarikan_deposito = 'tbpenarikan_deposito';
    public $_table_bunga_log = 'tbdeposito_bunga_log';

    private function _get_datatables_query($status_filter = null)
    {
        // OPTIMIZED: Using denormalized columns - no JOIN needed for basic display
        $this->db->select('tbdeposito.*, 
            COALESCE(tbdeposito.nama_nasabah, tbnasabah.nama_lengkap) as nama_nasabah, 
            COALESCE(tbdeposito.telp_nasabah, tbnasabah.telp) as telp_nasabah');
        $this->db->from($this->table);
        // Keep JOIN as fallback for records missing denormalized data
        $this->db->join('tbnasabah', 'tbnasabah.id = tbdeposito.nasabah_id', 'left');

        // Apply status filter if provided
        if (!empty($status_filter)) {
            $this->db->where('tbdeposito.status', $status_filter);
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

    function get_datatables($status_filter = null)
    {
        $this->_get_datatables_query($status_filter);
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered($status_filter = null)
    {
        $this->_get_datatables_query($status_filter);
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
        // DENORMALIZED: Auto-populate denormalized columns if not provided
        if ((empty($data['nama_nasabah']) || empty($data['telp_nasabah'])) && !empty($data['nasabah_id'])) {
            $nasabah = $this->db->select('nama_lengkap, telp')->where('id', $data['nasabah_id'])->get('tbnasabah')->row();
            if ($nasabah) {
                if (empty($data['nama_nasabah']))
                    $data['nama_nasabah'] = $nasabah->nama_lengkap;
                if (empty($data['telp_nasabah']))
                    $data['telp_nasabah'] = $nasabah->telp;
            }
        }
        if (empty($data['nama_pegawai']) && !empty($data['pegawai_id'])) {
            $pegawai = $this->db->select('nama_lengkap')->where('id', $data['pegawai_id'])->get('tbpegawai')->row();
            if ($pegawai)
                $data['nama_pegawai'] = $pegawai->nama_lengkap;
        }
        if (empty($data['jenis_tabungan']) && !empty($data['jenistabungan_id'])) {
            $jenis = $this->db->select('nama')->where('id', $data['jenistabungan_id'])->get('tbjenistabungan')->row();
            if ($jenis)
                $data['jenis_tabungan'] = $jenis->nama;
        }
        // Calculate maturity date if not provided
        if (empty($data['tanggal_jatuh_tempo']) && !empty($data['tanggal_deposito']) && !empty($data['durasi'])) {
            $data['tanggal_jatuh_tempo'] = date('Y-m-d', strtotime($data['tanggal_deposito'] . ' + ' . $data['durasi'] . ' months'));
        }
        // Initialize totals to 0
        if (!isset($data['total_bunga_akumulasi']))
            $data['total_bunga_akumulasi'] = 0;
        if (!isset($data['bunga_belum_ditarik']))
            $data['bunga_belum_ditarik'] = 0;

        return $this->db->insert($this->table, $data);
    }

    /**
     * DENORMALIZED: Add interest to totals when new bunga log is inserted
     */
    public function add_to_bunga_totals($deposito_id, $amount)
    {
        $this->db->set('total_bunga_akumulasi', 'COALESCE(total_bunga_akumulasi, 0) + ' . (float) $amount, false);
        $this->db->set('bunga_belum_ditarik', 'COALESCE(bunga_belum_ditarik, 0) + ' . (float) $amount, false);
        $this->db->where('id', $deposito_id);
        return $this->db->update($this->table);
    }

    /**
     * DENORMALIZED: Subtract from bunga_belum_ditarik when interest is withdrawn
     */
    public function subtract_bunga_belum_ditarik($deposito_id, $amount)
    {
        $this->db->set('bunga_belum_ditarik', 'GREATEST(COALESCE(bunga_belum_ditarik, 0) - ' . (float) $amount . ', 0)', false);
        $this->db->where('id', $deposito_id);
        return $this->db->update($this->table);
    }

    /**
     * DENORMALIZED: Recalculate all denormalized totals from log table
     */
    public function recalculate_bunga_totals($deposito_id)
    {
        // Calculate total_bunga_akumulasi
        $total = $this->db->select_sum('jumlah_bunga')
            ->where('deposito_id', $deposito_id)
            ->get('tb_bunga_deposito_log')->row();

        // Calculate bunga_belum_ditarik
        $belum_ditarik = $this->db->select_sum('jumlah_bunga')
            ->where('deposito_id', $deposito_id)
            ->where('status_penarikan', 'belum_ditarik')
            ->get('tb_bunga_deposito_log')->row();

        return $this->db->where('id', $deposito_id)->update($this->table, [
            'total_bunga_akumulasi' => $total->jumlah_bunga ?? 0,
            'bunga_belum_ditarik' => $belum_ditarik->jumlah_bunga ?? 0
        ]);
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

    public function get_data_by_norek($rek)
    {
        $this->db->where('no_rekening', $rek);
        $result = $this->db->get($this->table)->row();

        if (!$result && is_numeric($rek)) {
            $this->db->where('id', $rek);
            $result = $this->db->get($this->table)->row();
        }

        return $result;
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
        $this->db->set('jumlah_deposito', 'jumlah_deposito - ' . (float) $jumlah, FALSE);
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
        $this->db->join('tbpegawai as pimpinan', "pimpinan.jabatan = 'KEPALA BAGIAN KEUANGAN'", 'left');
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
            'deposito_id' => $deposito_id,
            'pegawai_id' => $pegawai_id,
            'tanggal_penarikan' => date('Y-m-d H:i:s'),
            'jumlah_penarikan' => $total,
            'jumlah_penarikan_pokok' => 0,
            'jumlah_penarikan_bunga' => $total,
            'jumlah_denda' => 0,
            'total_penarikan' => $total
        ];
        $this->db->insert('tbpenarikan_deposito', $dataP);
        $penarikan_id = $this->db->insert_id();

        $this->db->set('status_penarikan', 'sudah_ditarik');
        $this->db->set('penarikan_id', $penarikan_id);
        $this->db->where('deposito_id', $deposito_id);
        $this->db->where('status_penarikan', 'belum_ditarik');
        $this->db->update('tb_bunga_deposito_log');

        $this->db->trans_complete();

        // Return penarikan_id on success for kwitansi printing
        return $this->db->trans_status() ? $penarikan_id : false;
    }


    public function get_bunga_tersedia_from_log($deposito_id)
    {
        // OPTIMIZED: Try denormalized column first, fallback to SUM query
        $deposito = $this->db->select('bunga_belum_ditarik')
            ->where('id', $deposito_id)
            ->get('tbdeposito')
            ->row();

        if ($deposito && $deposito->bunga_belum_ditarik !== null && $deposito->bunga_belum_ditarik > 0) {
            return (float) $deposito->bunga_belum_ditarik;
        }

        // Fallback to calculated value
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
        return (float) ($result->total_bunga ?? 0);
    }

    public function get_detail_bunga_by_id($deposito_id)
    {
        if (!$deposito_id)
            return null;

        // Ambil total bunga dari log
        $this->db->select_sum('jumlah_bunga', 'total_bunga');
        $this->db->where('deposito_id', $deposito_id);
        $this->db->where('status_penarikan', 'belum_ditarik');
        $result = $this->db->get('tb_bunga_deposito_log')->row();

        // Cek kalau memang ada bunga
        $total_bunga = $result && $result->total_bunga ? $result->total_bunga : 0;

        // OPTIMIZED: Use denormalized nama_nasabah from tbdeposito
        $deposito = $this->db->select('nama_nasabah')
            ->where('id', $deposito_id)
            ->get('tbdeposito')
            ->row();

        return (object) [
            'nama_nasabah' => $deposito->nama_nasabah ?? 'Tidak ditemukan',
            'bunga_tersedia' => floatval($total_bunga)
        ];
    }

    public function update_status_jatuh_tempo()
    {
        $today = date('Y-m-d');
        $file = APPPATH . 'cache/last_update_deposito.txt';

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

    public function perpanjang_otomatis()
    {
        $today = date('Y-m-d');
        $file = APPPATH . 'cache/last_auto_renew_deposito.txt';
        $last_run = file_exists($file) ? file_get_contents($file) : null;

        if ($last_run !== $today) {
            $jenis_query = $this->db->select('bunga')
                ->from('tbjenistabungan')
                ->like('nama', 'deposito', 'both')
                ->get();
            $jenis = $jenis_query->row();

            if (!$jenis) {
                log_message('error', 'Auto-renew failed: Jenis tabungan "Deposito" not found.');
                return;
            }
            $bunga_terbaru = $jenis->bunga;

            // FIX: Gunakan tanggal jatuh tempo sebelumnya + durasi, bukan NOW()
            // Ini agar nasabah tidak rugi hari jika cronjob telat berjalan
            $sql = "
                UPDATE tbdeposito
                SET 
                    status = 'aktif',
                    tanggal_deposito = DATE_ADD(tanggal_deposito, INTERVAL durasi MONTH),
                    rate_bunga = ?
                WHERE
                    status = 'jatuh tempo'
                    AND DATE_ADD(tanggal_deposito, INTERVAL durasi MONTH) <= DATE_SUB(?, INTERVAL 7 DAY)
            ";

            $this->db->query($sql, [$bunga_terbaru, $today]);

            file_put_contents($file, $today);
        }
    }

    public function get_transaksi_by_deposito($id, $tanggal_mulai, $tanggal_akhir, $jenis_laporan = '3')
    {
        $deposito = $this->db->select('jumlah_deposito, tanggal_deposito, pegawai_id')
            ->where('id', $id)->get('tbdeposito')->row();

        if (!$deposito) {
            return [
                'saldo_awal' => 0,
                'total_setor' => 0,
                'total_tarik' => 0,
                'total_bunga' => 0,
                'total_bunga_ditarik' => 0,
                'saldo_akhir' => 0,
                'transaksi' => []
            ];
        }

        $tgl_mulai_real = $tanggal_mulai ?: $deposito->tanggal_deposito;
        $tgl_akhir_query = $tanggal_akhir ?: date('Y-m-d');

        // Get total principal that has been withdrawn
        $total_pokok_ditarik = (float) ($this->db->select_sum('jumlah_penarikan_pokok', 'total')
            ->where('deposito_id', $id)->get('tbpenarikan_deposito')->row()->total ?? 0);

        $setoran_awal_asli = (float) $deposito->jumlah_deposito + $total_pokok_ditarik;

        $pegawai_awal = $this->db->select('nama_lengkap')->where('id', $deposito->pegawai_id)
            ->get('tbpegawai')->row()->nama_lengkap ?? 'SYSTEM';

        $transaksi_setoran_awal = (object) [
            'tanggal' => $deposito->tanggal_deposito,
            'keterangan' => 'Setoran Awal Deposito',
            'kredit' => (float) $setoran_awal_asli,
            'debit' => 0.0,
            'jenis' => 'setoran',
            'pegawai' => $pegawai_awal,
            'is_bunga_only' => false
        ];

        // Penarikan POKOK saja dari tbpenarikan_deposito (jumlah_penarikan_pokok > 0)
        // Bunga withdrawals are tracked separately and don't reduce principal
        $penarikan_pokok_sql = "
            SELECT pd.tanggal_penarikan AS tanggal,
                'Penarikan Pokok' AS keterangan,
                0 AS kredit,
                pd.jumlah_penarikan_pokok AS debit,
                'penarikan' AS jenis,
                p.nama_lengkap AS pegawai,
                0 AS is_bunga_only
            FROM tbpenarikan_deposito pd
            LEFT JOIN tbpegawai p ON pd.pegawai_id = p.id
            WHERE pd.deposito_id = ?
            AND pd.jumlah_penarikan_pokok > 0
            AND DATE(pd.tanggal_penarikan) <= DATE(?)
        ";
        $transaksi_penarikan_pokok = $this->db->query($penarikan_pokok_sql, [$id, $tgl_akhir_query])->result();

        // Penarikan BUNGA dari tbpenarikan_deposito - shown as info but doesn't affect principal saldo
        $penarikan_bunga_sql = "
            SELECT pd.tanggal_penarikan AS tanggal,
                'Penarikan Bunga' AS keterangan,
                0 AS kredit,
                pd.jumlah_penarikan_bunga AS debit,
                'penarikan_bunga' AS jenis,
                p.nama_lengkap AS pegawai,
                1 AS is_bunga_only
            FROM tbpenarikan_deposito pd
            LEFT JOIN tbpegawai p ON pd.pegawai_id = p.id
            WHERE pd.deposito_id = ?
            AND pd.jumlah_penarikan_bunga > 0
            AND DATE(pd.tanggal_penarikan) <= DATE(?)
        ";
        $transaksi_penarikan_bunga = $this->db->query($penarikan_bunga_sql, [$id, $tgl_akhir_query])->result();

        // Bunga diterima (all bunga entries, regardless of status)
        $bunga_sql = "
            SELECT 
                DATE(b.tanggal_perhitungan) AS tanggal,
                'Bunga Deposito' AS keterangan,
                b.jumlah_bunga AS kredit,
                0 AS debit,
                'bunga' AS jenis,
                'SYSTEM' AS pegawai,
                0 AS is_bunga_only
            FROM tb_bunga_deposito_log b
            WHERE b.deposito_id = ?
            AND DATE(b.tanggal_perhitungan) <= DATE(?)
            ORDER BY tanggal
        ";
        $transaksi_bunga = $this->db->query($bunga_sql, [$id, $tgl_akhir_query])->result();

        // Merge semua transaksi
        $semua_transaksi = array_merge(
            [$transaksi_setoran_awal],
            $transaksi_bunga,
            $transaksi_penarikan_pokok,
            $transaksi_penarikan_bunga
        );
        usort($semua_transaksi, function ($a, $b) {
            return strcmp($a->tanggal, $b->tanggal);
        });

        // Hitung total
        $saldo_awal = 0.0;
        $total_setor = 0.0;  // Principal deposits only
        $total_tarik = 0.0;  // Principal withdrawals only
        $total_bunga = 0.0;  // All bunga received
        $total_bunga_ditarik = 0.0;  // Bunga that has been withdrawn
        $transaksi_periode = [];

        // If report starts from deposit date, set initial deposit as opening balance
        $is_from_deposit_date = ($tgl_mulai_real <= $deposito->tanggal_deposito);
        if ($is_from_deposit_date) {
            $saldo_awal = (float) $setoran_awal_asli;
        }

        foreach ($semua_transaksi as $t) {
            $t->kredit = (float) $t->kredit;
            $t->debit = (float) $t->debit;
            $is_bunga_only = isset($t->is_bunga_only) ? (bool) $t->is_bunga_only : false;

            if ($t->tanggal < $tgl_mulai_real) {
                // Before period: calculate opening balance
                // Bunga-only transactions don't affect principal saldo
                if (!$is_bunga_only) {
                    $saldo_awal += ($t->kredit - $t->debit);
                }
                // Add bunga to saldo_awal too (accumulated interest)
                if ($t->jenis === 'bunga') {
                    $saldo_awal += $t->kredit;
                }
                if ($t->jenis === 'penarikan_bunga') {
                    $saldo_awal -= $t->debit;
                }
                continue;
            }

            if ($t->tanggal > $tgl_akhir_query) {
                continue;
            }

            // Skip setoran awal from transaction list when it's already in saldo_awal
            if ($is_from_deposit_date && $t->keterangan === 'Setoran Awal Deposito') {
                continue;
            }

            $is_kredit = ($t->jenis === 'setoran' || $t->jenis === 'bunga');
            $is_debit = ($t->jenis === 'penarikan' || $t->jenis === 'penarikan_bunga');

            if (
                ($jenis_laporan === '1' && $is_kredit) ||
                ($jenis_laporan === '2' && $is_debit) ||
                $jenis_laporan === '3'
            ) {
                $transaksi_periode[] = $t;

                // Track bunga received
                if ($t->jenis === 'bunga') {
                    $total_bunga += $t->kredit;
                }

                // Track bunga withdrawn
                if ($t->jenis === 'penarikan_bunga') {
                    $total_bunga_ditarik += $t->debit;
                }

                // Only count principal setoran
                if ($t->jenis === 'setoran') {
                    $total_setor += $t->kredit;
                }

                // Only count principal tarik
                if ($t->jenis === 'penarikan') {
                    $total_tarik += $t->debit;
                }
            }
        }

        // Saldo akhir = principal + bunga received - bunga withdrawn - principal withdrawn
        $saldo_akhir = $saldo_awal + $total_setor + $total_bunga - $total_tarik - $total_bunga_ditarik;

        return [
            'saldo_awal' => $saldo_awal,
            'total_setor' => $total_setor,
            'total_tarik' => $total_tarik,
            'total_bunga' => $total_bunga,
            'total_bunga_ditarik' => $total_bunga_ditarik,
            'saldo_akhir' => $saldo_akhir,
            'transaksi' => $transaksi_periode
        ];
    }

    // ========== IMPORT METHODS ==========

    /**
     * Find nasabah by name (case-insensitive partial match)
     */
    public function find_nasabah_by_name($nama)
    {
        if (empty($nama))
            return null;

        // Try exact match first
        $result = $this->db->where('LOWER(nama_lengkap)', strtolower(trim($nama)))
            ->get('tbnasabah')->row();

        if ($result)
            return $result;

        // Try LIKE match
        $result = $this->db->like('nama_lengkap', trim($nama), 'both')
            ->limit(1)
            ->get('tbnasabah')->row();

        return $result;
    }

    /**
     * Create new nasabah from import data
     */
    public function create_nasabah_from_import($nama, $alamat = '-', $telp = '-', $pegawai_id = null)
    {
        $data = [
            'nik' => 'IMP' . date('YmdHis') . substr(uniqid(), -4),
            'nama_lengkap' => $nama,
            'jenis_kelamin' => '?',
            'tempat_lahir' => '-',
            'tanggal_lahir' => null,
            'agama' => '-',
            'alamat' => $alamat ?: '-',
            'pekerjaan' => '-',
            'telp' => $telp ?: '-',
            'nama_ibu_kandung' => '-',
            'pegawai_id' => $pegawai_id ?: 1,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('tbnasabah', $data);
        return $this->db->insert_id();
    }

    /**
     * Find existing deposito by nasabah name and deposito date
     */
    public function find_deposito_by_name_date($nama, $tanggal_deposito)
    {
        return $this->db->select('d.*')
            ->from('tbdeposito d')
            ->join('tbnasabah n', 'n.id = d.nasabah_id')
            ->where('LOWER(n.nama_lengkap)', strtolower(trim($nama)))
            ->where('d.tanggal_deposito', $tanggal_deposito)
            ->get()->row();
    }

    /**
     * Find existing deposito by no_seri (serial number from Excel)
     */
    public function find_deposito_by_no_seri($no_seri)
    {
        if (empty($no_seri))
            return null;
        return $this->db->where('no_seri', $no_seri)
            ->get($this->table)->row();
    }

    /**
     * Insert or update deposito from import
     * @param array $data Deposito data
     * @param bool $update_existing Whether to update existing records
     * @return array ['action' => 'insert'|'update'|'skip', 'id' => int, 'message' => string]
     */
    public function insert_or_update_import($data, $update_existing = true)
    {
        // Check for existing by no_seri first
        $existing = null;
        if (!empty($data['no_seri'])) {
            $existing = $this->find_deposito_by_no_seri($data['no_seri']);
        }

        // If not found by no_seri, try by name + date
        if (!$existing && !empty($data['nama_nasabah']) && !empty($data['tanggal_deposito'])) {
            $existing = $this->find_deposito_by_name_date($data['nama_nasabah'], $data['tanggal_deposito']);
        }

        if ($existing) {
            if ($update_existing) {
                // Update existing record
                $update_data = array_filter($data, function ($v) {
                    return $v !== null && $v !== '';
                });
                unset($update_data['nasabah_id']); // Don't update nasabah_id

                $this->db->where('id', $existing->id)->update($this->table, $update_data);
                return [
                    'action' => 'update',
                    'id' => $existing->id,
                    'message' => "Data deposito {$data['nama_nasabah']} berhasil diupdate"
                ];
            } else {
                return [
                    'action' => 'skip',
                    'id' => $existing->id,
                    'message' => "Data deposito {$data['nama_nasabah']} sudah ada, dilewati"
                ];
            }
        }

        // Insert new record
        $this->insert_data($data);
        $new_id = $this->db->insert_id();

        return [
            'action' => 'insert',
            'id' => $new_id,
            'message' => "Data deposito {$data['nama_nasabah']} berhasil ditambahkan"
        ];
    }

    /**
     * Batch import deposito records
     * @param array $rows Array of deposito data
     * @param string $batch_id Import batch ID
     * @param int $pegawai_id ID of employee performing import
     * @param int $jenistabungan_id Deposito type ID
     * @return array Import results
     */
    public function batch_import($rows, $batch_id, $pegawai_id, $jenistabungan_id)
    {
        $results = [
            'total' => count($rows),
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        $this->db->trans_start();

        foreach ($rows as $index => $row) {
            try {
                // Find or create nasabah
                $nasabah = $this->find_nasabah_by_name($row['nama'] ?? '');

                if (!$nasabah) {
                    // Create new nasabah
                    $nasabah_id = $this->create_nasabah_from_import(
                        $row['nama'] ?? 'Unknown',
                        $row['alamat'] ?? '-',
                        $row['telp'] ?? '-',
                        $pegawai_id
                    );
                } else {
                    $nasabah_id = $nasabah->id;
                }

                // Prepare deposito data
                $deposito_data = [
                    'no_seri' => $row['no_seri'] ?? null,
                    'no_rekening' => $row['no_rekening'] ?? $this->generate_next_rekening(),
                    'nasabah_id' => $nasabah_id,
                    'pegawai_id' => $pegawai_id,
                    'jenistabungan_id' => $jenistabungan_id,
                    'nama_nasabah' => $row['nama'] ?? null,
                    'telp_nasabah' => $row['telp'] ?? null,
                    'jumlah_deposito' => floatval($row['jumlah_deposito'] ?? 0),
                    'rate_bunga' => floatval($row['rate_bunga'] ?? 0),
                    'tanggal_deposito' => $row['tanggal_deposito'] ?? date('Y-m-d'),
                    'durasi' => intval($row['durasi'] ?? 12),
                    'import_batch_id' => $batch_id,
                    'import_notes' => $row['keterangan'] ?? null,
                    'status' => $row['status'] ?? 'aktif'
                ];

                // Calculate jatuh tempo if not provided
                if (empty($deposito_data['tanggal_jatuh_tempo']) && !empty($deposito_data['tanggal_deposito']) && !empty($deposito_data['durasi'])) {
                    $deposito_data['tanggal_jatuh_tempo'] = date('Y-m-d', strtotime($deposito_data['tanggal_deposito'] . ' + ' . $deposito_data['durasi'] . ' months'));
                }

                $result = $this->insert_or_update_import($deposito_data, true);

                $results['details'][] = [
                    'row' => $index + 1,
                    'nama' => $row['nama'] ?? 'Unknown',
                    'action' => $result['action'],
                    'message' => $result['message'],
                    'deposito_id' => $result['id']
                ];

                $results[$result['action'] === 'insert' ? 'inserted' : ($result['action'] === 'update' ? 'updated' : 'skipped')]++;

            } catch (Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'row' => $index + 1,
                    'nama' => $row['nama'] ?? 'Unknown',
                    'action' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $results['errors'] = $results['total'];
            $results['inserted'] = 0;
            $results['updated'] = 0;
        }

        return $results;
    }

    /**
     * Generate next rekening number
     */
    public function generate_next_rekening()
    {
        $this->db->select('no_rekening');
        $this->db->from('tbdeposito');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get();

        if ($query && $query->num_rows() > 0) {
            $last = $query->row();
            $lastNumber = (int) substr($last->no_rekening, 1);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'D' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    // ========== FULL MIGRATION IMPORT ==========

    /**
     * Import full migration from Excel file
     * Handles: DAFTAR DEPOSAN, PEMBAYARAN BUNGA, HUTANG BUNGA sheets
     * 
     * @param string $file_path Path to Excel file
     * @param int $pegawai_id Employee ID performing import
     * @param int $jenistabungan_id Deposit type ID
     * @return array Import results with details
     */
    public function import_full_migration($file_path, $pegawai_id, $jenistabungan_id)
    {
        require_once APPPATH . 'third_party/SimpleXLS.php';

        $results = [
            'success' => false,
            'batch_id' => 'IMP' . date('YmdHis'),
            'deposito' => ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
            'bunga_log' => ['inserted' => 0, 'errors' => 0],
            'nasabah' => ['created' => 0, 'found' => 0],
            'details' => [],
            'errors' => []
        ];

        // Parse Excel file
        $xls = \Shuchkin\SimpleXLS::parse($file_path);
        if (!$xls) {
            $results['errors'][] = 'Gagal membaca file Excel: ' . \Shuchkin\SimpleXLS::parseError();
            return $results;
        }

        $sheets = $xls->sheetNames();
        $results['sheets_found'] = $sheets;

        // Find sheet indexes
        $sheet_deposan = array_search('DAFTAR DEPOSAN', $sheets);
        $sheet_bunga = array_search('PEMBAYARAN BUNGA DEPOSITO', $sheets);
        $sheet_hutang = array_search('HUTANG BUNGA', $sheets);

        if ($sheet_deposan === false) {
            $results['errors'][] = 'Sheet "DAFTAR DEPOSAN" tidak ditemukan';
            return $results;
        }

        $this->db->trans_start();

        // Step 1: Import DAFTAR DEPOSAN
        $rows_deposan = $xls->rows($sheet_deposan);
        $no_seri_to_deposito_id = []; // Map no_seri to deposito_id for linking bunga

        // Header is at row 3 (index 2), data starts at row 4 (index 3)
        for ($i = 3; $i < count($rows_deposan); $i++) {
            $row = $rows_deposan[$i];

            // Skip empty rows
            $nama = trim($row[1] ?? '');
            if (empty($nama))
                continue;

            try {
                // Excel Column Mapping for DAFTAR DEPOSAN sheet (0-indexed):
                // 0=NO, 1=NAMA, 2=ALAMAT, 3=NO.SERI, 4=JUMLAH DEPOSITO, 5=TGL DEPOSITO
                // 6=JANGKA WAKTU, 7=JATUH TEMPO, 8=SUKU BUNGA, 9=BUNGA(calc), 10=TELP, 11=KET

                // Parse NO.SERI as integer (handles 1.0 -> 1)
                $no_seri = $this->_parse_int_safe($row[3] ?? 0) ?? 0;

                // Parse amounts with safe handling for #NUM!, -, empty
                $jumlah_deposito = $this->_parse_amount_safe($row[4] ?? 0);

                // Parse dates with safe handling for #NUM! and invalid dates
                $tanggal_deposito = $this->_parse_date_safe($row[5] ?? '');
                $tanggal_jatuh_tempo = $this->_parse_date_safe($row[7] ?? '');

                // Parse duration - default to 12 if invalid
                $durasi = $this->_parse_int_safe($row[6] ?? 12) ?? 12;
                if ($durasi <= 0)
                    $durasi = 12;

                // Parse rate from Excel (0.6, 0.7, 0.8)
                $rate_bunga = $this->_parse_rate_from_excel($row[8] ?? 0);

                // Other fields
                $alamat = trim($row[2] ?? '-');
                $telp = trim($row[10] ?? '-');
                $keterangan = trim($row[11] ?? '');

                // Detect status from keterangan
                $status = $this->_detect_status($keterangan, $jumlah_deposito);

                // Find or create nasabah
                $nasabah = $this->find_nasabah_by_name($nama);
                if (!$nasabah) {
                    $nasabah_id = $this->create_nasabah_from_import($nama, $alamat, $telp, $pegawai_id);
                    $results['nasabah']['created']++;
                } else {
                    $nasabah_id = $nasabah->id;
                    $results['nasabah']['found']++;
                }

                // Check if deposito exists by no_seri
                $existing = $no_seri > 0 ? $this->find_deposito_by_no_seri($no_seri) : null;

                $deposito_data = [
                    'no_seri' => $no_seri > 0 ? $no_seri : null,
                    'nasabah_id' => $nasabah_id,
                    'pegawai_id' => $pegawai_id,
                    'jenistabungan_id' => $jenistabungan_id,
                    'nama_nasabah' => $nama,
                    'telp_nasabah' => $telp,
                    'jumlah_deposito' => $jumlah_deposito,
                    'rate_bunga' => $rate_bunga,
                    'tanggal_deposito' => $tanggal_deposito ?: date('Y-m-d'),
                    'durasi' => $durasi,
                    'tanggal_jatuh_tempo' => $tanggal_jatuh_tempo,
                    'status' => $status,
                    'import_batch_id' => $results['batch_id'],
                    'import_notes' => $keterangan
                ];

                // Calculate jatuh tempo if not provided
                if (empty($deposito_data['tanggal_jatuh_tempo']) && !empty($deposito_data['tanggal_deposito']) && $durasi > 0) {
                    $deposito_data['tanggal_jatuh_tempo'] = date('Y-m-d', strtotime($deposito_data['tanggal_deposito'] . " + $durasi months"));
                }

                if ($existing) {
                    // Update existing
                    unset($deposito_data['nasabah_id']); // Don't change nasabah
                    $this->db->where('id', $existing->id)->update($this->table, $deposito_data);
                    $deposito_id = $existing->id;
                    $results['deposito']['updated']++;
                    $results['details'][] = ['row' => $i + 1, 'nama' => $nama, 'action' => 'updated', 'no_seri' => $no_seri];
                } else {
                    // Insert new
                    $deposito_data['no_rekening'] = $this->generate_next_rekening();
                    $this->insert_data($deposito_data);
                    $deposito_id = $this->db->insert_id();
                    $results['deposito']['inserted']++;
                    $results['details'][] = ['row' => $i + 1, 'nama' => $nama, 'action' => 'inserted', 'no_seri' => $no_seri];
                }

                // Store mapping for bunga import
                if ($no_seri > 0) {
                    $no_seri_to_deposito_id[$no_seri] = $deposito_id;
                }

            } catch (Exception $e) {
                $results['deposito']['errors']++;
                $results['errors'][] = "Row " . ($i + 1) . " ($nama): " . $e->getMessage();
            }
        }

        // Step 2: Import PEMBAYARAN BUNGA DEPOSITO (if sheet exists)
        // This sheet tracks interest payments history
        // Col 6 = SALDO PINDAHAN (outstanding balance from previous year) - belum_ditarik
        // Cols 7-30 = monthly payments (date + amount pairs) - these are sudah_ditarik
        if ($sheet_bunga !== false && count($no_seri_to_deposito_id) > 0) {
            $rows_bunga = $xls->rows($sheet_bunga);

            // Column mapping: Col 7 onwards are monthly payment pairs (TGL, Amount)
            // Jan=7,8 Feb=9,10 Mar=11,12 Apr=13,14 May=15,16 Jun=17,18
            // Jul=19,20 Aug=21,22 Sep=23,24 Oct=25,26 Nov=27,28 Dec=29,30
            $months = [
                7 => '01',
                9 => '02',
                11 => '03',
                13 => '04',
                15 => '05',
                17 => '06',
                19 => '07',
                21 => '08',
                23 => '09',
                25 => '10',
                27 => '11',
                29 => '12'
            ];

            for ($i = 3; $i < count($rows_bunga); $i++) {
                $row = $rows_bunga[$i];
                $nin = intval($row[0] ?? 0); // NIN = No Seri

                if ($nin <= 0 || !isset($no_seri_to_deposito_id[$nin]))
                    continue;

                $deposito_id = $no_seri_to_deposito_id[$nin];

                // Get SALDO PINDAHAN from Col 6
                // Based on Excel KWITANSI BUNGA DEPOSITO, this represents "YG SUDAH DI BAYAR"
                // (interest that has ALREADY been paid in previous periods)
                // So status should be 'sudah_ditarik' NOT 'belum_ditarik'
                $saldo_pindahan = $this->_parse_amount_safe($row[6] ?? 0);

                // Insert previous balance as PAID interest if > 0
                if ($saldo_pindahan > 0) {
                    $this->_insert_bunga_log($deposito_id, $saldo_pindahan, '2024-12-31', 'sudah_ditarik', $results['batch_id'], 'Saldo pindahan (bunga sudah dibayar)');
                    $results['bunga_log']['inserted']++;
                }

                // Insert monthly bunga payments (these ARE payments = sudah_ditarik)
                foreach ($months as $col => $month) {
                    $date_col = $col;
                    $amount_col = $col + 1;

                    $payment_date = $this->_parse_date_safe($row[$date_col] ?? '');
                    $payment_amount = $this->_parse_amount_safe($row[$amount_col] ?? 0);

                    // Only insert if we have valid date AND amount > 0
                    if ($payment_amount > 0 && $payment_date) {
                        $this->_insert_bunga_log($deposito_id, $payment_amount, $payment_date, 'sudah_ditarik', $results['batch_id'], "Pembayaran bunga bulan $month");
                        $results['bunga_log']['inserted']++;
                    }
                }
            }
        }

        // Step 3: Import HUTANG BUNGA (outstanding interest balances)
        // This updates the denormalized bunga_belum_ditarik field
        // #NUM! values should be skipped (return 0 from _parse_amount_safe)
        if ($sheet_hutang !== false && count($no_seri_to_deposito_id) > 0) {
            $rows_hutang = $xls->rows($sheet_hutang);

            // Use DES column (Col 15 = December) as current outstanding balance
            for ($i = 3; $i < count($rows_hutang); $i++) {
                $row = $rows_hutang[$i];
                $nin = intval($row[0] ?? 0);

                if ($nin <= 0 || !isset($no_seri_to_deposito_id[$nin]))
                    continue;

                $deposito_id = $no_seri_to_deposito_id[$nin];

                // Get outstanding balance from DES column (Col 15)
                // Use _parse_amount_safe to handle #NUM! errors
                $hutang_bunga = $this->_parse_amount_safe($row[15] ?? 0);

                // Hutang bunga is negative in Excel, convert to positive
                // If it's 0 or #NUM!, we skip the update
                $bunga_belum_ditarik = abs($hutang_bunga);

                if ($bunga_belum_ditarik > 0) {
                    $this->db->where('id', $deposito_id)
                        ->update($this->table, ['bunga_belum_ditarik' => $bunga_belum_ditarik]);
                }
            }
        }

        $this->db->trans_complete();

        $results['success'] = $this->db->trans_status();

        return $results;
    }

    /**
     * Helper: Parse amount from Excel cell
     */
    private function _parse_amount($value)
    {
        if (empty($value))
            return 0;
        if (is_numeric($value))
            return floatval($value);

        // Remove currency symbols and formatting
        $value = str_replace(['$', 'Rp', ',', ' '], '', $value);
        return floatval($value);
    }

    /**
     * Helper: Parse amount from Excel cell with SAFE handling
     * Handles #NUM!, empty cells, dashes, and other invalid values
     * 
     * @param mixed $value The cell value
     * @return float Returns 0 for invalid values, otherwise the parsed amount
     */
    private function _parse_amount_safe($value)
    {
        // Handle empty, null, 0
        if ($value === null || $value === '' || $value === 0 || $value === '0')
            return 0.0;

        // Convert to string for pattern checking
        $str_value = trim((string) $value);

        // Handle Excel error values: #NUM!, #VALUE!, #REF!, #DIV/0!, etc.
        if (strpos($str_value, '#') === 0) {
            return 0.0;
        }

        // Handle dash (often used for empty/null in Excel)
        if ($str_value === '-') {
            return 0.0;
        }

        // If already numeric, return as float
        if (is_numeric($value)) {
            return floatval($value);
        }

        // Remove currency symbols, thousands separators, and spaces
        $cleaned = str_replace(['$', 'Rp', ',', ' ', '.'], '', $str_value);

        // Handle negative values in parentheses: (1000) => -1000
        if (preg_match('/^\((.+)\)$/', $cleaned, $matches)) {
            $cleaned = '-' . $matches[1];
        }

        // Try to parse as float
        if (is_numeric($cleaned)) {
            return floatval($cleaned);
        }

        // Default to 0 for any other invalid value
        return 0.0;
    }

    /**
     * Helper: Parse integer/ID from Excel cell (NO, NO.SERI, NIN)
     * Handles float values like 1.0 -> 1
     * 
     * @param mixed $value The cell value
     * @return int|null Returns null for invalid values (should skip row)
     */
    private function _parse_int_safe($value)
    {
        if ($value === null || $value === '' || $value === '-')
            return null;

        // Convert to string for pattern checking
        $str_value = trim((string) $value);

        // Handle Excel error values
        if (strpos($str_value, '#') === 0) {
            return null;
        }

        // If numeric, cast to int (removes .0)
        if (is_numeric($value)) {
            return intval(floatval($value));
        }

        return null;
    }

    /**
     * Helper: Parse date from Excel cell
     */
    private function _parse_date($value)
    {
        if (empty($value))
            return null;

        // Check for invalid dates
        if (strpos($value, '1970-01-01') !== false)
            return null;
        if (strpos($value, '1900-') !== false)
            return null;

        // If numeric (Excel serial date)
        if (is_numeric($value)) {
            $timestamp = strtotime('1899-12-30') + ($value * 86400);
            return date('Y-m-d', $timestamp);
        }

        // Try parsing as date string
        $timestamp = strtotime($value);
        if ($timestamp && $timestamp > 0) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Helper: Parse interest rate from Excel cell
     */
    private function _parse_rate($value)
    {
        if (empty($value))
            return 0;

        // Remove percentage sign
        $value = str_replace(['%', ' '], '', $value);
        $rate = floatval($value);

        // If rate is like 0.007 (0.7%), convert to 0.7
        if ($rate > 0 && $rate < 0.1) {
            $rate = $rate * 100;
        }

        return $rate;
    }

    /**
     * Helper: Parse date from Excel cell with safe handling for #NUM! and invalid dates
     */
    private function _parse_date_safe($value)
    {
        if (empty($value))
            return null;

        // Handle Excel error values like #NUM!, #VALUE!, #REF!, etc
        if (is_string($value) && strpos($value, '#') === 0) {
            return null;
        }

        // Handle dash or minus which Excel sometimes shows for empty dates
        if (trim($value) === '-' || trim($value) === '') {
            return null;
        }

        // Handle invalid dates (1900-based Excel errors or 1970)
        $value_str = (string) $value;
        if (strpos($value_str, '1900') !== false || strpos($value_str, '1899') !== false) {
            return null;
        }
        if (strpos($value_str, '1970-01-01') !== false) {
            return null;
        }

        // If numeric (Excel serial date)
        if (is_numeric($value)) {
            $serial = intval($value);
            // Excel dates are days since 1899-12-30
            // Ignore very small numbers (likely invalid)
            if ($serial < 36526) { // Before year 2000
                return null;
            }
            $timestamp = strtotime('1899-12-30') + ($serial * 86400);
            $year = (int) date('Y', $timestamp);
            // Validate year is reasonable (2000-2100)
            if ($year < 2000 || $year > 2100) {
                return null;
            }
            return date('Y-m-d', $timestamp);
        }

        // Try parsing as date string (e.g., "11/15/2025", "2025-11-15")
        $timestamp = strtotime($value);
        if ($timestamp && $timestamp > 0) {
            $year = (int) date('Y', $timestamp);
            if ($year < 2000 || $year > 2100) {
                return null;
            }
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Helper: Parse interest rate from Excel cell (uses Excel values directly: 0.60%, 0.70%, 0.80%)
     * Stores the rate as displayed in Excel (e.g., 0.6 for 0.6% per month)
     */
    private function _parse_rate_from_excel($value)
    {
        if (empty($value))
            return 0.6; // Default to 0.6% if empty

        // Handle Excel error values
        if (is_string($value) && strpos($value, '#') === 0) {
            return 0.6; // Default on error
        }

        // Remove percentage sign and spaces
        $cleaned = str_replace(['%', ' '], '', (string) $value);
        $rate = floatval($cleaned);

        // Excel rate formats:
        // 0.006 or 0.007 or 0.008 (raw decimal, need *100)
        // 0.60% or 0.70% or 0.80% (percentage string, already good after removing %)
        // 0.6 or 0.7 or 0.8 (already correct)

        // If value is very small like 0.006, convert to 0.6
        if ($rate > 0 && $rate < 0.1) {
            $rate = $rate * 100;
        }

        // If value is like 60 or 70 (percentage as whole number), divide by 100
        if ($rate > 10) {
            $rate = $rate / 100;
        }

        // Ensure rate is within reasonable bounds for monthly rate (0.1% - 5%)
        if ($rate <= 0 || $rate > 5) {
            return 0.6; // Default to 0.6% if out of bounds
        }

        return $rate;
    }

    /**
     * Helper: Detect deposit status from keterangan
     * Patterns from Excel: LUNAS, tarik, ditarik, pinalti, pembaharuan, pokok ditarik, etc.
     */
    private function _detect_status($keterangan, $jumlah)
    {
        // If no keterangan and has amount, it's active
        if (empty($keterangan) && $jumlah > 0)
            return 'aktif';

        // If no amount, it's closed
        if ($jumlah <= 0)
            return 'ditutup';

        $ket_lower = strtolower(trim($keterangan));

        // Check for closed/withdrawn patterns
        $closed_patterns = [
            'lunas',
            'ditarik',
            'pokok ditarik',
            'pokok sudah ditarik',
            'pokok sdh ditarik',
            'tarik tgl',
            'tarik 12/',  // e.g., "tarik 12/6/25"
            'tarik perpanjang'
        ];

        foreach ($closed_patterns as $pattern) {
            if (strpos($ket_lower, $pattern) !== false) {
                return 'ditutup';
            }
        }

        // Pinalti withdrawal means closed
        if (strpos($ket_lower, 'tarik') !== false && strpos($ket_lower, 'pinalti') !== false) {
            return 'ditutup';
        }

        // Pembaharuan (renewal) usually means the deposit was renewed - still active
        // BARU also means new/active
        if (strpos($ket_lower, 'baru') !== false || strpos($ket_lower, 'pembaharuan') !== false) {
            return 'aktif';
        }

        return 'aktif';
    }

    /**
     * Helper: Insert bunga log entry
     */
    private function _insert_bunga_log($deposito_id, $amount, $date, $status, $batch_id, $keterangan = '')
    {
        // Get deposito info for denormalized fields
        $deposito = $this->db->select('no_rekening, nama_nasabah, rate_bunga')
            ->where('id', $deposito_id)
            ->get($this->table)->row();

        $data = [
            'deposito_id' => $deposito_id,
            'no_rekening' => $deposito ? $deposito->no_rekening : null,
            'nama_nasabah' => $deposito ? $deposito->nama_nasabah : null,
            'jumlah_bunga' => $amount,
            'rate_bunga' => $deposito ? $deposito->rate_bunga : 0,
            'tanggal_perhitungan' => $date,
            'status_penarikan' => $status,
            'input_method' => 'import',
            'import_batch_id' => $batch_id,
            'keterangan' => $keterangan
        ];

        return $this->db->insert('tb_bunga_deposito_log', $data);
    }
}
