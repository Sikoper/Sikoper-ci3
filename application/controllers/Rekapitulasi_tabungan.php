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
        // --- ✅ DYNAMIC YEAR GENERATION ---

        // 1. Query the database to find the earliest year from deposit records.
        $this->db->select_min('YEAR(tanggal_simpanan)', 'start_year');
        $query = $this->db->get('tbsimpanan');
        $result = $query->row();

        // 2. Determine the start year. Default to the current year if no records exist.
        $current_year = date('Y');
        $start_year = $result && $result->start_year ? $result->start_year : $current_year;

        // 3. Generate the array of years from the start year to the current year.
        $years = [];
        for ($i = $current_year; $i >= $start_year; $i--) {
            $years[] = $i;
        }

        // --- Prepare data for the view ---
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

        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');

        $list = $this->Rekapitulasi_tabungan_model->get_datatables($bulan, $tahun);
        $data = array();
        $no = $_POST['start'];

        foreach ($list as $field) {
            $no++;
            $row = array();

            // Ensure saldo_pokok is not null before calculations
            $saldo_pokok = $field->saldo_pokok ?? 0;
            $bunga = $field->bunga ?? 0;
            $total_diterima = $saldo_pokok + $bunga;

            $row[] = "<div class='text-center'>$no</div>";
            $row[] = $field->no_rekening;
            $row[] = $field->nama_nasabah;
            $row[] = "<div class='text-end'>Rp " . number_format($saldo_pokok, 0, ',', '.') . "</div>";
            $row[] = "<div class='text-end'>Rp " . number_format($bunga, 0, ',', '.') . "</div>";
            $row[] = "<div class='text-end fw-bold'>Rp " . number_format($total_diterima, 0, ',', '.') . "</div>";

            $data[] = $row;
        }

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
