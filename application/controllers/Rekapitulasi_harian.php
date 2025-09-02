<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_harian extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        // The model name should match the file name
        $this->load->model('Rekapitulasi_harian_model');

        // Your role-based access control
        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        if (!in_array($this->session->userdata('level'), $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function index()
    {
        $data = [
            'title' => 'Rekapitulasi Transaksi Harian',
        ];

        $parser = [
            'judul' => "Rekapitulasi Harian",
            'isi'   => $this->load->view('rekapitulasi_harian/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    /**
     * Fetch data for Deposit (Setoran) DataTable.
     */
    public function fetch_setoran_data()
    {
        $list = $this->Rekapitulasi_harian_model->get_setoran_datatables();
        $summary = $this->Rekapitulasi_harian_model->get_setoran_summary();

        $data = array();
        $no = $this->input->post('start');
        foreach ($list as $item) {
            $no++;
            $row = array();
            $row[] = '<div class="text-center">' . $no . '</div>';
            $row[] = date('H:i:s', strtotime($item->tanggal_setoran));
            $row[] = '<div class="text-end fw-bold">Rp ' . number_format($item->jumlah_setoran, 0, ',', '.') . '</div>';
            $data[] = $row;
        }

        $total_setoran = $summary->total_setoran ?? 0;

        $output = array(
            "draw" => (int)$this->input->post('draw'),
            "recordsTotal" => $this->Rekapitulasi_harian_model->count_all_setoran(),
            "recordsFiltered" => $this->Rekapitulasi_harian_model->count_filtered_setoran(),
            "data" => $data,
            "total_setoran" => 'Rp ' . number_format($total_setoran, 0, ',', '.'),
        );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    /**
     * Fetch data for Withdrawal (Penarikan) DataTable.
     */
    public function fetch_penarikan_data()
    {
        $list = $this->Rekapitulasi_harian_model->get_penarikan_datatables();
        $summary = $this->Rekapitulasi_harian_model->get_penarikan_summary();

        $data = array();
        $no = $this->input->post('start');
        foreach ($list as $item) {
            $no++;
            $row = array();
            $row[] = '<div class="text-center">' . $no . '</div>';
            $row[] = date('H:i:s', strtotime($item->tanggal_penarikan));
            $row[] = '<div class="text-end fw-bold">Rp ' . number_format($item->jumlah_penarikan, 0, ',', '.') . '</div>';
            $data[] = $row;
        }

        $total_penarikan = $summary->total_penarikan ?? 0;

        $output = array(
            "draw" => (int)$this->input->post('draw'),
            "recordsTotal" => $this->Rekapitulasi_harian_model->count_all_penarikan(),
            "recordsFiltered" => $this->Rekapitulasi_harian_model->count_filtered_penarikan(),
            "data" => $data,
            "total_penarikan" => 'Rp ' . number_format($total_penarikan, 0, ',', '.'),
        );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }
}
