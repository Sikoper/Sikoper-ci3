<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penarikan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Penarikan_model');
        $this->load->model('Nasabah_model');
        $this->load->model('Kategori_model');
        $this->load->model('Pegawai_model');

        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function index()
    {
        $parser = [
            'judul' => "<i class='fa fa-user-lock'></i> Penarikan",
            'isi'   => $this->load->view('penarikan/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function add()
    {
        $data = [
            'jenis' => $this->Kategori_model->get_data(),
            'pegawai' => $this->Pegawai_model->get_data(),
            'nasabah' => $this->Nasabah_model->get_data(), // Tambahkan jika ingin dropdown nasabah
            'level' => $this->session->userData('level')
        ];

        $parser = [
            'judul' => "<i class='fa fa-money-check'></i> Penarikan",
            'isi'   => $this->load->view('penarikan/addForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function proses()
    {
        $simpanan_id = $this->input->post('simpanan_id', TRUE);
        $jumlah = $this->input->post('jumlah', TRUE);

        if (empty($simpanan_id) || empty($jumlah)) {
            $this->session->set_flashdata('error', 'Data tidak lengkap.');
            redirect('penarikan/add');
            return;
        }

        $simpanan = $this->Penarikan_model->get_simpanan_by_id($simpanan_id);

        if (!$simpanan) {
            $this->session->set_flashdata('error', 'Data simpanan tidak ditemukan.');
            redirect('penarikan/add');
            return;
        }

        if ($simpanan->status !== 'aktif') {
            $this->session->set_flashdata('error', 'Status simpanan tidak aktif.');
            redirect('penarikan/add');
            return;
        }

        if ($jumlah > $simpanan->jumlah_simpanan) {
            $this->session->set_flashdata('error', 'Saldo tidak mencukupi!');
            redirect('penarikan/add');
            return;
        }

        $pegawai_id = $this->session->userdata('pegawai_id');
        if (!$pegawai_id) {
            $this->session->set_flashdata('error', 'Pegawai tidak terdeteksi. Silakan login ulang.');
            redirect('penarikan/add');
            return;
        }

        $data = [
            'simpanan_id' => $simpanan_id,
            'pegawai_id' => $pegawai_id,
            'tanggal_penarikan' => date('Y-m-d H:i:s'),
            'total_penarikan' => $jumlah
        ];

        $this->db->trans_start();
        $this->Penarikan_model->simpan_penarikan($data);
        $this->Penarikan_model->kurangi_saldo_simpanan($simpanan_id, $jumlah);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Terjadi kesalahan saat melakukan penarikan.');
            redirect('penarikan/add');
        } else {
            $this->session->set_flashdata('success', 'Penarikan berhasil!');
            redirect('penarikan');
        }
    }

    // Tambahan opsional: AJAX get saldo berdasarkan simpanan_id
    public function get_saldo($simpanan_id)
    {
        $simpanan = $this->Penarikan_model->get_simpanan_by_id($simpanan_id);
        if ($simpanan) {
            echo json_encode(['saldo' => $simpanan->jumlah_simpanan]);
        } else {
            echo json_encode(['error' => 'Data tidak ditemukan']);
        }
    }
}
