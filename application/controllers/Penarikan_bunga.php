<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penarikan_bunga extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Load model tetap di sini
        $this->load->model('Deposito_model');
        $this->load->model('Pegawai_model');

        // Pindahkan atau ubah pengecekan role dari sini
    }

    // Fungsi untuk MENGAMANKAN akses halaman
    private function _secure_page()
    {
        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        if (!in_array($this->session->userdata('level'), $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    // Panggil fungsi keamanan HANYA di fungsi yang menampilkan halaman
    public function index()
    {
        $this->_secure_page(); // Amankan halaman ini

        $data = [
            'level'   => $this->session->userdata('level'),
            'pegawai' => $this->Pegawai_model->get_data(),
        ];
        $parser = [
            'judul' => "Formulir Penarikan Bunga Deposito",
            // INILAH PENYEBAB MASALAHNYA
            'isi'   => $this->load->view('penarikan_bunga/form', $data, TRUE)
        ];
        // Perintah ini memuat template LENGKAP (header, sidebar, footer)
        $this->parser->parse('templates/main', $parser);
    }

    public function get_combo_rekening_nasabah()
    {
        // Pastikan header adalah JSON
        header('Content-Type: application/json');

        if (!$this->session->userdata('level')) {
            echo json_encode(['status' => 'error', 'message' => 'Session expired']);
            exit;
        }

        $searchTerm = $this->input->get('q');
        $data_from_model = $this->Deposito_model->get_rekening_nasabah_combo($searchTerm);

        $result = [];
        foreach ($data_from_model as $row) {
            $result[] = [
                'id'             => $row->id,
                'text'           => $row->no_rekening . ' - ' . $row->nama_lengkap,
                'nasabah_id'     => $row->nasabah_id,
                'nama_nasabah'   => $row->nama_lengkap,
                'jenis_tabungan' => $row->jenis_tabungan
            ];
        }

        echo json_encode($result);
        exit(); // TAMBAHKAN exit() DI SINI
    }

    public function fetch_detail_rekening()
    {
        // Pastikan header adalah JSON
        header('Content-Type: application/json');

        if (!$this->session->userdata('level')) {
            echo json_encode(['status' => 'error', 'message' => 'Sesi Anda berakhir.']);
            exit(); // TAMBAHKAN exit() DI SINI
        }

        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');
            $rekening = $this->Deposito_model->get_detail_deposito_by_id($id);

            if ($rekening) {
                $response = [
                    'status'         => 'success',
                    'nama_nasabah'   => $rekening->nama_nasabah,
                    'bunga_tersedia' => $rekening->hutang_bunga
                ];
                echo json_encode($response);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Rekening tidak ditemukan.']);
            }
            exit(); // TAMBAHKAN exit() DI SINI
        }
    }

    // Fungsi untuk memproses penarikan bunga
    public function proses_penarikan()
    {
        header('Content-Type: application/json');
        if ($this->input->is_ajax_request()) {
            // Validasi form
            $this->form_validation->set_rules('deposito_id', 'Rekening Deposito', 'required');
            $this->form_validation->set_rules('jumlah_penarikan', 'Jumlah Penarikan', 'required|trim|greater_than[0]');
            if ($this->session->userdata('level') == 'Admin') {
                $this->form_validation->set_rules('pegawai_id', 'Pegawai', 'required');
            }

            if ($this->form_validation->run() == FALSE) {
                echo json_encode(['error_validation' => validation_errors()]);
                // return;
                exit();
            }

            $deposito_id = $this->input->post('deposito_id');
            $jumlah_penarikan = (float) str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_penarikan'));
            $pegawai_id = $this->input->post('pegawai_id') ?? $this->session->userdata('pegawai_id');

            $deposito = $this->Deposito_model->get_detail_deposito_by_id($deposito_id);

            if (!$deposito) {
                echo json_encode(['error_save' => 'Gagal! Data rekening tidak ditemukan.']);
                return;
            }

            // Cek apakah bunga yang tersedia mencukupi
            if ($jumlah_penarikan > (float)$deposito->hutang_bunga) {
                echo json_encode(['error_save' => 'Gagal! Jumlah penarikan melebihi bunga yang tersedia (Rp ' . number_format($deposito->hutang_bunga, 0, ',', '.') . ').']);
                return;
            }

            // Panggil fungsi di model untuk proses transaksi
            $is_success = $this->Deposito_model->tarik_bunga($deposito_id, $jumlah_penarikan, $pegawai_id);

            if ($is_success) {
                echo json_encode(['success' => 'Penarikan bunga berhasil diproses.']);
            } else {
                echo json_encode(['error_save' => 'Terjadi kesalahan pada database saat memproses transaksi.']);
            }
            exit();
        }
    }
}
