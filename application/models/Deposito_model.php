<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Deposito_model extends CI_Model
{
    var $table = 'tbdeposito';
    // DENORMALIZED: Using denormalized columns for faster queries
    var $column_order = array(null, 'nama_nasabah', 'no_rekening', 'telp_nasabah', 'jumlah_deposito', 'status', null);
    var $column_search = array('tbdeposito.nama_nasabah', 'tbnasabah.nama_lengkap', 'tbdeposito.no_rekening', 'tbdeposito.status');
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

    public function save_deposito_with_logs($data, $quick_add_data, $withdrawal_mode, $pegawai_id)
    {
        $this->db->trans_start();

        if ($quick_add_data !== null) {
            $this->db->insert('tbnasabah', $quick_add_data);
            $nasabah_id_final = $this->db->insert_id();

            if (!$nasabah_id_final) {
                $this->db->trans_rollback();
                return false;
            }
            $data['nasabah_id'] = $nasabah_id_final;
        }

        $this->insert_data($data);
        $deposito_id = $this->db->insert_id();

        $tgl_deposito = new DateTime($data['tanggal_deposito']);
        $today = new DateTime();
        $tgl_deposito->setTime(0, 0, 0);
        $today->setTime(0, 0, 0);

        if ($tgl_deposito < $today && $deposito_id) {
            $start_date = clone $tgl_deposito;
            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($start_date, $interval, $today);

            $total_accumulated = 0;
            $bunga_logs = [];

            $nasabah_data = $this->db->get_where('tbnasabah', ['id' => $data['nasabah_id']])->row();
            $nama_nasabah_log = $nasabah_data ? $nasabah_data->nama_lengkap : 'Unknown';

            $amt = floatval($data['jumlah_deposito']);
            $bg_rate = floatval($data['rate_bunga']);

            foreach ($period as $dt) {
                if ($dt == $tgl_deposito) continue;

                $bunga_bulanan = ($amt * $bg_rate / 100) / 12;
                $bunga_bulanan_rounded = round($bunga_bulanan);
                $log_date = $dt->format('Y-m-15');
                $status_penarikan = $withdrawal_mode ? 'sudah_ditarik' : 'belum_ditarik';

                $bunga_logs[] = [
                    'deposito_id' => $deposito_id,
                    'no_rekening' => $data['no_rekening'],
                    'nama_nasabah' => $nama_nasabah_log,
                    'jumlah_bunga' => $bunga_bulanan_rounded,
                    'rate_bunga' => $bg_rate,
                    'tanggal_perhitungan' => $log_date,
                    'status_penarikan' => $status_penarikan,
                    'input_method' => 'auto',
                    'pegawai_id' => $pegawai_id,
                    'keterangan' => 'Bunga otomatis (' . ($withdrawal_mode ? 'Riwayat' : 'Akumulasi') . ') ' . $dt->format('F Y')
                ];

                $total_accumulated += $bunga_bulanan_rounded;

                if ($withdrawal_mode) {
                    $this->db->insert('tbpenarikan', [
                        'simpanan_id' => $deposito_id,
                        'no_rekening' => $data['no_rekening'],
                        'tanggal_penarikan' => $log_date,
                        'jumlah_penarikan' => $bunga_bulanan_rounded,
                        'pegawai_id' => $pegawai_id,
                        'keterangan' => 'Riwayat penarikan bunga ' . $dt->format('F Y'),
                        'jenis_penarikan' => 'Bunga Deposito'
                    ]);
                }
            }

            if (!empty($bunga_logs)) {
                $this->db->insert_batch('tb_bunga_deposito_log', $bunga_logs);
                $bunga_belum_ditarik = $withdrawal_mode ? 0 : $total_accumulated;

                $this->db->where('id', $deposito_id);
                $this->db->update('tbdeposito', [
                    'total_bunga_akumulasi' => $total_accumulated,
                    'bunga_belum_ditarik' => $bunga_belum_ditarik
                ]);
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
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
        return $this->db->get($this->table)->row();
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
        $this->db->join('tbpegawai as pimpinan', "pimpinan.jabatan = 'KEPALA SPBS'", 'left');
        $this->db->join('tbpegawai as bendahara', "bendahara.jabatan = 'KEPALA BAGIAN KEUANGAN'", 'left');
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

        $this->db->limit(50);
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
        $this->db->select('d.id, CONCAT(d.no_rekening, " - ", COALESCE(d.nama_nasabah, n.nama_lengkap)) as text', FALSE);
        $this->db->from('tbdeposito d');
        $this->db->join('tbnasabah n', 'd.nasabah_id = n.id', 'left');

        $this->db->group_start();
        $this->db->where('d.status', 'aktif');
        $this->db->or_where('d.status', 'jatuh tempo');
        $this->db->group_end();

        $this->db->where('d.jumlah_deposito >', 0);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('d.no_rekening', $search);
            $this->db->or_like('d.nama_nasabah', $search);
            $this->db->or_like('n.nama_lengkap', $search);
            $this->db->group_end();
        }

        $this->db->order_by('d.no_rekening', 'ASC');
        $this->db->limit(50);
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

        // Calculate the maximum withdrawable balance based on time elapsed instead of static records
        $total = $this->get_bunga_tersedia_from_log($deposito_id);

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

        // Instead of updating old pending rows (which may not exist if we calculate on the fly), 
        // we create a new log entry recording this payment. 
        // We also mark any existing 'belum_ditarik' rows as 'sudah_ditarik' to clean them up.

        $this->db->set('status_penarikan', 'sudah_ditarik');
        $this->db->set('penarikan_id', $penarikan_id);
        $this->db->where('deposito_id', $deposito_id);
        $this->db->where('status_penarikan', 'belum_ditarik');
        $this->db->update('tb_bunga_deposito_log');

        // Create a definitive record for this withdrawal
        $deposito = $this->get_data_by_id($deposito_id);
        $logData = [
            'deposito_id' => $deposito_id,
            'no_rekening' => $deposito->no_rekening,
            'nama_nasabah' => $deposito->nama_nasabah ?: ($this->db->get_where('tbnasabah', ['id' => $deposito->nasabah_id])->row()->nama_lengkap ?? ''),
            'jumlah_bunga' => $total,
            'rate_bunga' => $deposito->rate_bunga,
            'tanggal_perhitungan' => date('Y-m-d'),
            'status_penarikan' => 'sudah_ditarik',
            'penarikan_id' => $penarikan_id,
            'input_method' => 'manual',
            'keterangan' => 'Penarikan Bunga'
        ];
        $this->db->insert('tb_bunga_deposito_log', $logData);

        $this->db->trans_complete();

        // Return penarikan_id on success for kwitansi printing
        return $this->db->trans_status() ? $penarikan_id : false;
    }

    public function tarik_bunga($deposito_id, $jumlah_penarikan = 0, $pegawai_id = null, $tanggal_penarikan = null)
    {
        return $this->tarik_bunga_deposito($deposito_id, $pegawai_id);
    }


    public function get_bunga_tersedia_from_log($deposito_id)
    {
        $deposito = $this->get_data_by_id($deposito_id);
        if (!$deposito)
            return 0;

        $bunga_sudah_dibayar = $this->get_bunga_sudah_dibayar_from_log($deposito_id);

        $start_date = new DateTime($deposito->tanggal_deposito);
        $now = new DateTime();
        $months_elapsed = ($now->format('Y') - $start_date->format('Y')) * 12
            + ($now->format('n') - $start_date->format('n'));

        if ((int) $now->format('j') < (int) $start_date->format('j')) {
            $months_elapsed--;
        }
        $months_elapsed = max(0, min($months_elapsed, $deposito->durasi));

        $bunga_earned_so_far = $deposito->jumlah_deposito * ($deposito->rate_bunga / 100) * $months_elapsed;

        if ($deposito->status == 'ditutup') {
            $hutang_bunga_saat_ini = 0;
        } else {
            $hutang_bunga_saat_ini = $bunga_earned_so_far - $bunga_sudah_dibayar;
        }

        return max(0, $hutang_bunga_saat_ini);
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

        // Exact match only — no LIKE fallback to avoid wrong matching
        return $result;
    }

    /**
     * Create new nasabah from import data
     */
    public function create_nasabah_from_import($nama, $alamat = '-', $telp = '-', $pegawai_id = null)
    {
        $data = [
            'nik' => '-',
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


	public function import_full_migration($file_path, $pegawai_id, $jenistabungan_id) {
		$this->load->library('deposito_import_service');
		return $this->deposito_import_service->import_full_migration($file_path, $pegawai_id, $jenistabungan_id);
	}
}
