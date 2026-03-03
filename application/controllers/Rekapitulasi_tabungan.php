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

        // 2. Determine the start year. Minimum is 2025 to ensure imported historical data is accessible.
        $current_year = date('Y');
        $db_start_year = $result && $result->start_year ? $result->start_year : $current_year;
        $start_year = min($db_start_year, 2025); // Always include 2025 as minimum

        // 3. Generate the array of years from the start year to the current year.
        $years = [];
        for ($i = $current_year; $i >= $start_year; $i--) {
            $years[] = $i;
        }

        // --- Prepare data for the view ---
        $data_view = [
            'selected_month' => date('n'), // 'n' for month number without leading zeros (1-12)
            'selected_year' => $current_year,
            'years' => $years,
        ];

        $parser = [
            'judul' => "Rekapitulasi Tabungan",
            'isi' => $this->load->view('rekapitulasi_tabungan/index', $data_view, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetch_rekapitulasi()
    {
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');

        $list = $this->Rekapitulasi_tabungan_model->get_datatables($bulan, $tahun);
        $data = array();
        $no = $_POST['start'];

        foreach ($list as $field) {
            $no++;
            $row = array();

            $setoran = $field->setoran_bulan ?? 0;
            $penarikan = $field->penarikan_bulan ?? 0;
            $saldo_pokok = $field->saldo_pokok ?? 0;
            $bunga = $field->bunga ?? 0;

            // Setoran includes bunga (matching Excel)
            $setoran_total = $setoran + $bunga;

            $row[] = "<div class='text-center'>$no</div>";
            $row[] = $field->no_rekening;
            $row[] = $field->nama_nasabah;
            $row[] = "<div class='text-end'>Rp " . number_format($setoran_total, 0, ',', '.') . "</div>";
            $row[] = "<div class='text-end'>Rp " . number_format($penarikan, 0, ',', '.') . "</div>";
            $row[] = "<div class='text-end'>Rp " . number_format($saldo_pokok, 0, ',', '.') . "</div>";
            $row[] = "<div class='text-end'>Rp " . number_format($bunga, 0, ',', '.') . "</div>";

            $data[] = $row;
        }

        $summary = $this->Rekapitulasi_tabungan_model->get_summary_data($bulan, $tahun);
        $recordsFiltered = $this->Rekapitulasi_tabungan_model->count_filtered($bulan, $tahun);
        $recordsTotal = $this->Rekapitulasi_tabungan_model->count_all();

        // Total Setoran includes bunga (matching Excel)
        $total_setoran_combined = ($summary['total_setoran'] ?? 0) + ($summary['total_bunga'] ?? 0);

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data,
            "total_setoran" => "Rp " . number_format($total_setoran_combined, 0, ',', '.'),
            "total_penarikan" => "Rp " . number_format($summary['total_penarikan'] ?? 0, 0, ',', '.'),
            "total_saldo_pokok" => "Rp " . number_format($summary['total_saldo_pokok'] ?? 0, 0, ',', '.'),
            "total_bunga" => "Rp " . number_format($summary['total_bunga'] ?? 0, 0, ',', '.'),
        );

        header('Content-Type: application/json');
        echo json_encode($output);
    }
}
