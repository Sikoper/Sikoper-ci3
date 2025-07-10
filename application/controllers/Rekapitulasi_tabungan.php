<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_tabungan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // The model name should match the file name
        $this->load->model('Rekapitulasi_tabungan_model');

        // Your role-based access control
        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        if (!in_array($this->session->userdata('level'), $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function index()
    {
        // --- Dynamic Year and Month Generation ---
        $years = [];
        $start_year = 2023; // Or fetch the earliest year from your records
        $current_year = date('Y');
        for ($i = $current_year; $i >= $start_year; $i--) {
            $years[] = $i;
        }

        $data_view = [
            'selected_month' => date('n'), // 'n' for month number without leading zeros (1-12)
            'selected_year'  => $current_year,
            'years'          => $years,
        ];

        $parser = [
            'judul' => "Rekapitulasi Tabungan",
            'isi'   => $this->load->view('rekapitulasi_tabungan/index', $data_view, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetch_rekapitulasi()
    {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }

        // --- CRITICAL STEP 1: Receive the filter data from the AJAX request ---
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');

        // --- CRITICAL STEP 2: Pass the filter data to the model ---
        $list = $this->Rekapitulasi_tabungan_model->get_datatables($bulan, $tahun);

        $data = array();
        $no = $_POST['start'];

        foreach ($list as $field) {
            $no++;
            $row = array();

            $bunga = $field->bunga ?? 0;
            $total_diterima = $field->saldo_pokok + $bunga;

            $row[] = "<div class='text-center'>$no</div>";
            $row[] = $field->no_rekening;
            $row[] = $field->nama_nasabah;
            $row[] = "<div class='text-end'>Rp " . number_format($field->saldo_pokok, 0, ',', '.') . "</div>";
            $row[] = "<div class='text-end'>Rp " . number_format($bunga, 0, ',', '.') . "</div>";
            $row[] = "<div class='text-end fw-bold'>Rp " . number_format($total_diterima, 0, ',', '.') . "</div>";

            $data[] = $row;
        }

        // --- CRITICAL STEP 3: Also pass filter data for the summary and counts ---
        $summary = $this->Rekapitulasi_tabungan_model->get_summary_data($bulan, $tahun);
        $recordsFiltered = $this->Rekapitulasi_tabungan_model->count_filtered($bulan, $tahun);

        $output = array(
            "draw"              => $_POST['draw'],
            "recordsTotal"      => $this->Rekapitulasi_tabungan_model->count_all(),
            "recordsFiltered"   => $recordsFiltered,
            "data"              => $data,
            "total_saldo_pokok" => "Rp " . number_format($summary['total_saldo_pokok'], 0, ',', '.'),
            "total_bunga"       => "Rp " . number_format($summary['total_bunga'], 0, ',', '.'),
        );

        header('Content-Type: application/json');
        echo json_encode($output);
    }
}
