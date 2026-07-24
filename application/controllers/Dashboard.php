<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Bunga_model');
        $this->load->model('Setoran_model');
        $this->load->model('Deposito_model');
        $this->load->model('Penarikan_model');
        $this->load->model('Pencairan_model');
        $this->load->model('Pegawai_model');
        $this->load->model('Nasabah_model');
    }
    public function index()
    {
        $level = $this->session->userdata('level');
        
        // Auto-run tabungan interest process once a month
        $this->Bunga_model->checkAndRunBunga();

        if ($level === 'Admin') {
            $content = $this->load->view('home/index', '', TRUE);
        } else {
            $content = $this->load->view('home/pegawai', '', TRUE);
        }

        $parser = [
            'judul' => 'Selamat Datang!',
            'isi'   => $content
        ];

        $this->parser->parse('templates/main', $parser);
    }

    public function fetchData()
    {
        $today = date('Y-m-d');

        // Counts
        $jumlah_pegawai = $this->Pegawai_model->count_all();
        $jumlah_nasabah = $this->Nasabah_model->count_all();

        // Setoran bulan ini
        $setoran_raw = $this->Setoran_model->jumlah_setoran(); // Simpanan
        $setoran_deposito_raw = $this->Deposito_model->jumlah_setoran_deposito(); // You must create this method
        $setoran = array_fill(0, 12, 0);

        foreach ($setoran_raw as $item) {
            $monthIndex = $item->bulan - 1;
            $setoran[$monthIndex] += (int) $item->total_setoran;
        }
        foreach ($setoran_deposito_raw as $item) {
            $monthIndex = $item->bulan - 1;
            $setoran[$monthIndex] += (int) $item->total_setoran;
        }

        // Penarikan bulan ini (Simpanan + Deposito)
        $penarikan_bulanan = $this->Penarikan_model->jumlah_penarikan_bulanan();
        $penarikan = array_fill(0, 12, 0);

        foreach ($penarikan_bulanan as $item) {
            $monthIndex = $item->bulan - 1;
            $penarikan[$monthIndex] = (int) $item->total;
        }


        // Hari ini
        $setoran_baru = $this->Setoran_model->count_new_data($today);
        $penarikan_baru_simpanan = $this->Penarikan_model->count_new_data($today);
        $penarikan_baru_deposito = $this->Pencairan_model->count_new_data($today);
        $penarikan_baru = $penarikan_baru_simpanan + $penarikan_baru_deposito;
        
        // Total deposito (jumlah rekening deposito)
        $jumlah_deposito = $this->Deposito_model->count_all();

        $data = [
            'jumlah_pegawai' => $jumlah_pegawai,
            'jumlah_nasabah' => $jumlah_nasabah,
            'jumlah_deposito' => $jumlah_deposito,
            'setoran_baru' => $setoran_baru,
            'penarikan_baru' => $penarikan_baru,
            'setoran' => $setoran, // merged simpanan + deposito
            'penarikan' => $penarikan
        ];

        echo json_encode($data);
    }
}
