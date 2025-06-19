<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Bunga_model');
        $this->load->model('Setoran_model');
        $this->load->model('Penarikan_model');
        $this->load->model('Pegawai_model');
        $this->load->model('Nasabah_model');

        if (date('d') == '19') {
            $this->Bunga_model->checkAndRunBunga();
        }
    }
    public function index()
    {
        $parser = [
            'judul' => 'Selamat Datang!',
            'isi'   => $this->load->view('home/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetchData()
    {
        $today = date('Y-m-d');
        $jumlah_pegawai = $this->Pegawai_model->count_all();
        $jumlah_nasabah = $this->Nasabah_model->count_all();
        
        $setoran_raw = $this->Setoran_model->jumlah_setoran();
        $setoran = array_fill(0, 12, 0);
        foreach ($setoran_raw as $item) {
            $monthIndex = $item->bulan - 1;
            $setoran[$monthIndex] = (int) $item->total_setoran;
        }

        $penarikan_raw = $this->Penarikan_model->jumlah_setoran();
        $penarikan = array_fill(0, 12, 0);
        foreach ($penarikan_raw as $item) {
            $monthIndex = $item->bulan - 1;
            $setoran[$monthIndex] = (int) $item->total_penarikan;
        }

        $setoran_baru = $this->Setoran_model->count_new_data($today);
        $penarikan_baru = $this->Setoran_model->count_new_data($today);

        $data = [
            'jumlah_pegawai' => $jumlah_pegawai,
            'jumlah_nasabah' => $jumlah_nasabah,
            'setoran_baru' => $setoran_baru,
            'penarikan_baru' => $penarikan_baru,
            'setoran' => $setoran,
            'penarikan' => $penarikan
        ];

        echo json_encode($data);
    }
}
