<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_deposito extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // The model name should match the file name
        $this->load->model('Rekapitulasi_deposito_model');

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
        $this->db->select_min('YEAR(tanggal_deposito)', 'start_year');
        $query = $this->db->get('tbdeposito');
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
            'judul' => "Rekapitulasi Deposito",
            'isi'   => $this->load->view('rekapitulasi_deposito/index', $data_view, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

public function fetch_rekapitulasi()
{
    // if (!$this->input->is_ajax_request()) {
    //     exit('No direct script access allowed');
    // }

    // Beri nilai default jika POST kosong (penting untuk debugging dan data awal)
    $bulan = $this->input->post('bulan') ? $this->input->post('bulan') : date('n');
    $tahun = $this->input->post('tahun') ? $this->input->post('tahun') : date('Y');

    $list = $this->Rekapitulasi_deposito_model->get_datatables($bulan, $tahun);
    
    // Jika query gagal (karena model mengembalikan false), kirim response error
    if ($list === false) {
        echo json_encode(["error" => "Terjadi kesalahan pada query database."]);
        return;
    }

    $data = array();
    $no = isset($_POST['start']) ? $_POST['start'] : 0;

    foreach ($list as $field) {
        $no++;
        $row = array();

        $saldo_awal_bulan = $field->saldo_awal_bulan ?? 0;
        $bunga_bulan_ini = $field->bunga_bulan_ini ?? 0;
        $total_diterima = $saldo_awal_bulan + $bunga_bulan_ini;

        $row[] = "<div class='text-center'>$no</div>";
        $row[] = $field->no_rekening;
        $row[] = $field->nama_nasabah;
        $row[] = "<div class='text-end'>Rp " . number_format($saldo_awal_bulan, 0, ',', '.') . "</div>";
        $row[] = "<div class='text-end'>Rp " . number_format($bunga_bulan_ini, 0, ',', '.') . "</div>";
        $row[] = "<div class='text-end fw-bold'>Rp " . number_format($total_diterima, 0, ',', '.') . "</div>";

        $data[] = $row;
    }

    $summary = $this->Rekapitulasi_deposito_model->get_summary_data($bulan, $tahun);
    $recordsFiltered = $this->Rekapitulasi_deposito_model->count_filtered($bulan, $tahun);

    $output = array(
        "draw"                  => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        "recordsTotal"          => $this->Rekapitulasi_deposito_model->count_all(),
        "recordsFiltered"       => $recordsFiltered,
        "data"                  => $data,
        "total_saldo_awal"      => "Rp " . number_format($summary['total_saldo_awal'], 0, ',', '.'),
        "total_bunga_bulan_ini" => "Rp " . number_format($summary['total_bunga_bulan_ini'], 0, ',', '.'),
    );

    header('Content-Type: application/json');
    echo json_encode($output);
}
}
