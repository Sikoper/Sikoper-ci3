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
        // Menggunakan helper function dari secure_helper.php
        $data_pencairan_awal = null;
        $encoded_id = $this->input->get('id');

        if (!empty($encoded_id)) {
            $deposito_id = safe_base64_decode($encoded_id);
            if ($deposito_id) {
                $data_pencairan_awal = $this->Deposito_model->get_by_id($deposito_id);
            }
        }

        $data = [
            'pegawai' => $this->Pegawai_model->get_data(),
            'level' => $this->session->userdata('level'),
            'data_pencairan_awal' => $data_pencairan_awal
        ];

        $parser = [
            'judul' => "Formulir Pencairan Deposito",
            'isi' => $this->load->view('pencairan/index', $data, TRUE)
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
        header('Content-Type: application/json');
        $id = $this->input->post('id');

        $deposito = $this->Deposito_model->get_by_id($id);
        if (!$deposito) {
            echo json_encode(['error' => 'Data deposito tidak ditemukan.']);
            return;
        }

        $bunga_tersedia = $this->Deposito_model->get_bunga_tersedia_from_log($id);
        $jenis_tabungan = $this->Kategori_model->get_data_by_id($deposito->jenistabungan_id);

        $denda = 0;
        $tanggal_jatuh_tempo = new DateTime($deposito->tanggal_deposito);
        $tanggal_jatuh_tempo->add(new DateInterval('P' . $deposito->durasi . 'M'));

        if (new DateTime() < $tanggal_jatuh_tempo) {
            $penalty_rate = (float) ($jenis_tabungan->jumlah_denda ?? 0);
            $denda = round(($penalty_rate / 100) * $deposito->jumlah_deposito);
        }

        $response = [
            'saldo' => (float) $deposito->jumlah_deposito,
            'bunga_tersedia' => (float) $bunga_tersedia,
            'calculated_penalty_rp' => (float) $denda,
            'tenor' => $tanggal_jatuh_tempo->format('d-m-Y'),
        ];
        echo json_encode($response);
    }

    public function proses()
    {
        header('Content-Type: application/json');
        $this->load->library('form_validation');

        $this->form_validation->set_error_delimiters('', '');

        $this->form_validation->set_rules('deposito_id', 'Rekening Deposito', 'required|numeric', [
            'required' => '%s harus dipilih.',
            'numeric' => 'Format %s tidak valid.'
        ]);

        if ($this->session->userdata('level') == 'Admin') {
            $this->form_validation->set_rules('pegawai_id', 'Pegawai', 'required|numeric', [
                'required' => 'Sebagai Admin, Anda wajib memilih pegawai yang memproses.',
                'numeric' => 'Format %s tidak valid.'
            ]);
        }

        if ($this->form_validation->run() == FALSE) {
            $errors = [
                'deposito_id' => form_error('deposito_id'),
                'pegawai_id' => form_error('pegawai_id'),
            ];
            echo json_encode(['status' => 'validation_error', 'errors' => $errors]);
            return;
        }
        $deposito_id = $this->input->post('deposito_id');
        $pegawai_id = $this->input->post('pegawai_id') ?? $this->session->userdata('pegawai_id');

        if (empty($pegawai_id)) {
            $pegawai_fallback = $this->Pegawai_model->get_data();
            $pegawai_id = !empty($pegawai_fallback) ? $pegawai_fallback[0]->id : 1;
        }

        $result = $this->Pencairan_model->proses_pencairan_penuh($deposito_id, $pegawai_id);

        if ($result['status']) {
            echo json_encode([
                'success' => $result['message'],
                'redirect' => base_url('deposito')
            ]);
        } else {
            echo json_encode(['error_save' => $result['message']]);
        }
    }

    private function _safe_base64_encode($string)
    {
        return strtr(base64_encode($string), '+/=', '-_?');
    }

    public function fetch_detail_penarikan_by_deposito()
    {
        // if (!$this->input->is_ajax_request()) {
        //     redirect('unauthorized_403');
        // }

        $deposito_id = $this->input->post('deposito_id');

        if (empty($deposito_id) || !ctype_digit((string) $deposito_id)) {
            echo json_encode([
                "draw" => $this->input->post('draw') ? intval($this->input->post('draw')) : 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "error" => "ID Deposito tidak valid."
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
                      <button class="btn btn-primary btn-sm" title="Cetak Kwitansi"
                          onclick="window.open(\'' . base_url('deposito/print_kwitansi_bunga/' . $item->id) . '\', \'_blank\')">
                          <i class="fa fa-print"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" title="Hapus Penarikan"
                          onclick="deleteDetailPenarikan(' . $item->id . ', \'' . htmlspecialchars(number_format($item->jumlah_penarikan, 2, ',', '.'), ENT_QUOTES, 'UTF-8') . '\')">
                          <i class="fa fa-trash"></i>
                      </button>
                      </div>';
            $data[] = $row;
        }

        $output = [
            "draw" => $this->input->post('draw') ? intval($this->input->post('draw')) : 0,
            "recordsTotal" => $this->Pencairan_model->count_all_detail_penarikan($deposito_id),
            "recordsFiltered" => $this->Pencairan_model->count_filtered_detail_penarikan($deposito_id),
            "data" => $data,
            "akumulasi" => [
                "jumlah_penarikan" => $akumulasi->total_akumulasi_penarikan,
                "jumlah_denda" => $akumulasi->total_akumulasi_denda
            ]
        ];

        header('Content-Type: application/json');
        echo json_encode($output);
    }

    public function get_combo_rekening_nasabah()
    {
        header('Content-Type: application/json');
        $searchTerm = $this->input->get('q');
        $data = $this->Deposito_model->get_rekening_nasabah_combo($searchTerm);

        $result = [];
        foreach ($data as $row) {
            $result[] = [
                'id' => $row->id,
                'text' => $row->no_rekening . ' - ' . $row->nama_lengkap,
                'nama_nasabah' => $row->nama_lengkap
            ];
        }
        echo json_encode(['results' => $result]);
    }

    public function get_detail_deposito_by_id()
    {
        $id = $this->input->post('deposito_id');
        $this->load->model('Deposito_model');
        $data = $this->Deposito_model->get_data_by_id($id);

        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
}
