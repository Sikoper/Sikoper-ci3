<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pencairan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Deposito_model');
        $this->load->model('Nasabah_model');
        $this->load->model('Kategori_model');
        $this->load->model('Pegawai_model');
        $this->load->model('Pencairan_model');

        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function index()
    {
        function safe_base64_decode_pencairan($string)
        {
            return base64_decode(strtr($string, '-_?', '+/='));
        }

        $encoded_rek = $this->input->get('id');
        $tabungan = null;
        if (!empty($encoded_rek)) {
            $no_rekening = safe_base64_decode_pencairan($encoded_rek);
            $tabungan = $this->Deposito_model->get_data_by_norek($no_rekening);
        }

        $data = [
            'tabungan' => $tabungan,
            'selected_nasabah' => $tabungan->nasabah_id ?? null,
            'selected_rekening' => $tabungan->id ?? null,
            'disabled' => !empty($tabungan),
            'jenis' => $this->Kategori_model->get_data(),
            'pegawai' => $this->Pegawai_model->get_data(),
            'nasabah' => $this->Nasabah_model->get_data(),
            'level' => $this->session->userData('level')
        ];

        $parser = [
            'judul' => "Formulir Pencairan Deposito",
            'isi'   => $this->load->view('pencairan/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function get_rekening_by_nasabah()
    {
        $nasabah_id = $this->input->post('nasabah_id');
        $data = $this->Deposito_model->get_rekening_deposito_by_nasabah($nasabah_id);
        echo json_encode($data);
    }

    public function fetchRekening()
    {
        if ($this->input->is_ajax_request()) {
            $simpanan_id = $this->input->post('id');
            $simpanan = $this->Deposito_model->get_data_by_id($simpanan_id);
            if (!$simpanan) {
                echo json_encode(['error' => 'Data simpanan tidak ditemukan.']);
                return;
            }

            $jenis_tabungan = $this->Kategori_model->get_data_by_id($simpanan->jenistabungan_id);
            if (!$jenis_tabungan) {
                echo json_encode(['error' => 'Data kategori tabungan tidak ditemukan.']);
                return;
            }

            $original_deposit_date_dt = new DateTime($simpanan->tanggal_deposito);
            $duration_months = (int) $simpanan->durasi;
            $grace_period_days = 7;
            $current_date_dt = new DateTime(date('Y-m-d'));

            $penalty_rate = (float) $jenis_tabungan->jumlah_denda;
            $final_calculated_penalty_rp = 0;
            $display_penalty_rate_config = $penalty_rate;

            $effective_tenor_to_display_dt = clone $original_deposit_date_dt;

            if ($jenis_tabungan->nama === 'Deposito') {
                $current_eval_deposit_date_dt = clone $original_deposit_date_dt;
                while (true) {
                    $current_eval_tenor_date_dt = (clone $current_eval_deposit_date_dt)->modify("+{$duration_months} months");
                    $current_eval_grace_end_dt = (clone $current_eval_tenor_date_dt)->modify("+{$grace_period_days} days");

                    $effective_tenor_to_display_dt = clone $current_eval_tenor_date_dt;

                    if ($current_date_dt < $current_eval_tenor_date_dt) {
                        $final_calculated_penalty_rp = round(($penalty_rate / 100) * $simpanan->jumlah_deposito);
                        break;
                    } else if ($current_date_dt >= $current_eval_tenor_date_dt && $current_date_dt <= $current_eval_grace_end_dt) {
                        $final_calculated_penalty_rp = 0;
                        $display_penalty_rate_config = 0;
                        break;
                    } else {
                        $current_eval_deposit_date_dt = (clone $current_eval_grace_end_dt)->modify('+1 day');
                    }
                }
            } else {
                $final_calculated_penalty_rp = 0;
                $display_penalty_rate_config = 0;
            }

            $msg = [
                'saldo' => $simpanan->jumlah_deposito,
                'durasi' => $simpanan->durasi,
                'tenor' => $effective_tenor_to_display_dt->format('Y-m-d'),
                'jumlah_denda' => $display_penalty_rate_config,
                'jenis_denda' => ($display_penalty_rate_config > 0) ? $jenis_tabungan->jenis_denda : '-',
                'kategori' => $jenis_tabungan,
                'calculated_penalty_rp' => $final_calculated_penalty_rp
            ];
            echo json_encode($msg);
        } else {
            show_404();
        }
    }

    public function proses()
    {
        $this->form_validation->set_rules('nasabah', 'Nasabah', 'required', ['required' => 'Nasabah wajib dipilih.']);
        $this->form_validation->set_rules('simpanan_id', 'Rekening Deposito', 'required', ['required' => 'Rekening Deposito wajib dipilih.']);
        $this->form_validation->set_rules('jumlah_penarikan', 'Jumlah Penarikan', 'required', ['required' => 'Jumlah penarikan wajib diisi.']);
        $this->form_validation->set_rules('tanggal_penarikan', 'Tanggal Penarikan', 'required', ['required' => 'Tanggal penarikan wajib diisi.']);

        if ($this->session->userdata('level') == 'Admin') {
            $this->form_validation->set_rules('pegawai_id', 'Pegawai', 'required', ['required' => 'Pegawai wajib dipilih oleh Admin.']);
        }

        if ($this->form_validation->run() == FALSE) {
            $errors = [
                'errorNasabah'   => form_error('nasabah'),
                'errorSimpanan'  => form_error('simpanan_id'),
                'errorJumlah'    => form_error('jumlah_penarikan'),
                'errorPegawai'   => form_error('pegawai_id') ?? '',
                'errorTanggal'   => form_error('tanggal_penarikan')
            ];
            echo json_encode(['error' => $errors]);
            return;
        }
        $simpanan_id = $this->input->post('simpanan_id');
        $simpanan_data = $this->Deposito_model->get_data_by_id($simpanan_id);

        if (!$simpanan_data) {
            echo json_encode(['error_save' => 'Data simpanan deposito tidak ditemukan. Mohon muat ulang halaman.']);
            return;
        }

        $jenis_tabungan_data = $this->Kategori_model->get_data_by_id($simpanan_data->jenistabungan_id);
        if (!$jenis_tabungan_data) {
            echo json_encode(['error_save' => 'Data kategori untuk deposito ini tidak ditemukan.']);
            return;
        }

        $jumlah_penarikan_diminta = (float) str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_penarikan') ?? '');

        $penalty_rp_final = 0;
        if ($jenis_tabungan_data->nama === 'Deposito') {
            $original_deposit_date_dt = new DateTime($simpanan_data->tanggal_deposito);
            $duration_months = (int) $simpanan_data->durasi;
            $grace_period_days = 7;
            $penalty_rate = (float) $jenis_tabungan_data->jumlah_denda;
            $date_of_withdrawal_dt = new DateTime($this->input->post('tanggal_penarikan'));

            $current_eval_deposit_date_dt = clone $original_deposit_date_dt;
            while (true) {
                $current_eval_tenor_date_dt = (clone $current_eval_deposit_date_dt)->modify("+{$duration_months} months");
                $current_eval_grace_end_dt = (clone $current_eval_tenor_date_dt)->modify("+{$grace_period_days} days");

                if ($date_of_withdrawal_dt < $current_eval_tenor_date_dt) {
                    $penalty_rp_final = round(($penalty_rate / 100) * $simpanan_data->jumlah_deposito);
                    break;
                } elseif ($date_of_withdrawal_dt >= $current_eval_tenor_date_dt && $date_of_withdrawal_dt <= $current_eval_grace_end_dt) {
                    $penalty_rp_final = 0;
                    break;
                } else {
                    $current_eval_deposit_date_dt = (clone $current_eval_grace_end_dt)->modify('+1 day');
                }
            }
        }
        $saldo_saat_ini = (float) $simpanan_data->jumlah_deposito;
        $total_pengurangan = $jumlah_penarikan_diminta + $penalty_rp_final;

        if (round($total_pengurangan, 2) > round($saldo_saat_ini, 2)) {
            $pesan_error_saldo = 'Penarikan gagal. Jumlah penarikan dan denda (Rp ' . number_format($penalty_rp_final, 0, ',', '.') . ') melebihi saldo deposito yang tersedia (Rp ' . number_format($saldo_saat_ini, 0, ',', '.') . ').';
            echo json_encode(['error' => ['errorJumlah' => $pesan_error_saldo]]);
            return;
        }
        $this->db->trans_start();
        $data_log = [
            'deposito_id'       => $simpanan_id,
            'pegawai_id'        => ($this->session->userdata('level') == 'Admin') ? $this->input->post('pegawai_id') : $this->session->userdata('pegawai_id'),
            'tanggal_penarikan' => $this->input->post('tanggal_penarikan') . ' ' . date('H:i:s'),
            'jumlah_penarikan'  => $jumlah_penarikan_diminta,
            'jumlah_denda'      => $penalty_rp_final
        ];
        $this->Deposito_model->simpan_log_penarikan($data_log);

        $this->Deposito_model->kurangi_saldo($simpanan_id, $total_pengurangan);
        if (($saldo_saat_ini - $total_pengurangan) == 0) {
            $this->Deposito_model->ubah_status($simpanan_id, 'nonaktif');
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $msg = ['error_save' => 'Terjadi kesalahan teknis saat menyimpan transaksi. Transaksi dibatalkan.'];
        } else {
            $this->db->trans_commit();
            $encoded_rek = $this->_safe_base64_encode($simpanan_data->no_rekening);
            $redirect_url_final = site_url('penarikan/detail/' . $encoded_rek);
            $pesan_sukses = 'Pencairan deposito berhasil diproses. Saldo telah diperbarui.';
            if (($saldo_saat_ini - $total_pengurangan) == 0) {
                $pesan_sukses .= ' Status rekening kini nonaktif.';
            }
            $msg = [
                'success' => $pesan_sukses,
                'redirect' => $redirect_url_final
            ];
        }

        echo json_encode($msg);
    }

    private function _safe_base64_encode($string)
    {
        return strtr(base64_encode($string), '+/=', '-_?');
    }

    public function fetch_detail_penarikan_by_deposito()
    {
        if (!$this->input->is_ajax_request()) {
            redirect('unauthorized_403');
        }

        $deposito_id = $this->input->post('deposito_id');

        if (empty($deposito_id) || !ctype_digit((string)$deposito_id)) {
            echo json_encode([
                "draw"            => $this->input->post('draw') ? intval($this->input->post('draw')) : 0,
                "recordsTotal"    => 0,
                "recordsFiltered" => 0,
                "data"            => [],
                "error"           => "ID Deposito tidak valid."
            ]);
            return;
        }

        $this->load->model('Pencairan_model');

        $list = $this->Pencairan_model->get_datatables_detail_penarikan($deposito_id);
        $akumulasi = $this->Pencairan_model->get_akumulasi_penarikan_by_deposito($deposito_id);

        $data = [];
        $no = $this->input->post('start') ? intval($this->input->post('start')) : 0;

        foreach ($list as $item) {
            $no++;
            $row = [];

            $row[] = '<div class="text-center">' . $no . '</div>';
            $row[] = date('d-m-Y H:i', strtotime($item->tanggal_penarikan));
            $row[] = '<div class="text-end">Rp ' . number_format($item->jumlah_penarikan, 2, ',', '.') . '</div>';
            $row[] = '<div class="text-end">Rp ' . number_format($item->jumlah_denda, 2, ',', '.') . '</div>';
            $row[] = $item->nama_pegawai ? htmlspecialchars($item->nama_pegawai, ENT_QUOTES, 'UTF-8') : '-';

            $row[] = '<div class="text-center">
                    <button class="btn btn-danger btn-sm" title="Hapus Penarikan"
                        onclick="deleteDetailPenarikan(' . $item->id . ', \'' . htmlspecialchars(number_format($item->jumlah_penarikan, 2, ',', '.'), ENT_QUOTES, 'UTF-8') . '\')">
                        <i class="fa fa-trash"></i>
                    </button>
                    </div>';

            $data[] = $row;
        }

        $output = [
            "draw"            => $this->input->post('draw') ? intval($this->input->post('draw')) : 0,
            "recordsTotal"    => $this->Pencairan_model->count_all_detail_penarikan($deposito_id),
            "recordsFiltered" => $this->Pencairan_model->count_filtered_detail_penarikan($deposito_id),
            "data"            => $data,
            "akumulasi"       => [
                "jumlah_penarikan" => $akumulasi->total_akumulasi_penarikan,
                "jumlah_denda"     => $akumulasi->total_akumulasi_denda
            ]
        ];

        header('Content-Type: application/json');
        echo json_encode($output);
    }
}
