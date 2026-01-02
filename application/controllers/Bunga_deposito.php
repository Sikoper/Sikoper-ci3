<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga_deposito extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Deposito_model');
        $this->load->model('Bunga_deposito_model');
        $this->load->model('Nasabah_bunga_model');
        $this->load->model('Kategori_model');
        $this->load->model('Pegawai_model');

        // echo '<pre>';
        // print_r($this->session->userdata());
        // exit;

        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function index()
    {
        $pegawai = $this->Pegawai_model->get_data();
        $data = [
            'pegawai' => $pegawai,
            'level' => $this->session->userdata('level'),
        ];

        $parser = [
            'judul' => "Daftar Bunga Deposito",
            'isi'   => $this->load->view('bunga-deposito/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetchBungaDeposito()
    {
        $start = $this->input->post('start_date');
        $end = $this->input->post('end_date');

        $level = $this->session->userdata('level');

        $list = $this->Bunga_deposito_model->get_datatables($start, $end);
        $data = [];
        $no = isset($_POST['start']) ? $_POST['start'] : 0;

        foreach ($list as $field) {
            $no++;
            $row = [];
            $row[] = $no;
            $row[] = $field->nama_lengkap;
            $row[] = $field->no_rekening;
            $row[] = date('d-m-Y', strtotime($field->tanggal_transaksi));
            $row[] = number_format($field->jumlah_transaksi, 0, ',', '.');
            $row[] = $field->rate_bunga . " %";

            if ($level == 'Admin') {
                $row[] = "<button class='btn btn-sm btn-danger' onclick=\"deleteItem('$field->id', '$field->no_rekening')\"><i class=\"fa fa-trash\"></i></button>";
            } else {
                $row[] = "";
            }

            $data[] = $row;
        }

        $total_bunga = $this->Bunga_deposito_model->get_total_bunga_filtered($start, $end);

        $output = [
            "draw" => isset($_POST['draw']) ? $_POST['draw'] : 1,
            "recordsTotal" => $this->Bunga_deposito_model->count_all(),
            "recordsFiltered" => $this->Bunga_deposito_model->count_filtered($start, $end),
            "data" => $data,
            "total_bunga" => number_format($total_bunga, 0, ',', '.')
        ];
        echo json_encode($output);
    }

    public function fetchNasabahDepositoBunga()
    {
        if (!$this->input->is_ajax_request()) {
            exit('Maaf data tidak bisa ditampilkan');
        }

        $deposito_id = $this->input->post('deposito_id');
        $deposito = $this->Deposito_model->get_by_id($deposito_id);
        $list = $this->Bunga_deposito_model->get_detail_bunga_by_deposito_id($deposito_id);

        $data = array();
        $no = 0;

        if (!empty($list)) {
            foreach ($list as $field) {
                $no++;
                $row = array();
                $row[] = "<div class=\"text-center\">{$no}</div>";
                $row[] = date('d-m-Y', strtotime($field->tanggal_perhitungan));
                $row[] = "Rp " . number_format($field->jumlah_bunga, 2, ',', '.');
                $row[] = ($field->rate_bunga ?? ($deposito ? $deposito->rate_bunga : '0')) . " %";
                $row[] = "<button class=\"btn btn-sm btn-danger\" onclick=\"deleteRecordBunga('{$field->id}')\"><i class=\"fa fa-trash fa-fw\"></i></button>";

                $data[] = $row;
            }
        }

        $total_bunga = $this->Bunga_deposito_model->get_total_detail_bunga($deposito_id);

        $output = array(
            "draw" => $this->input->post('draw') ? (int)$this->input->post('draw') : 1,
            "recordsTotal" => count($list),
            "recordsFiltered" => count($list),
            "data" => $data,
            "total_bunga" => number_format($total_bunga, 2, ',', '.')
        );

        echo json_encode($output);
    }

    public function delete()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        header('Content-Type: application/json');

        $id_log_bunga = $this->input->post('id');

        if (empty($id_log_bunga)) {
            echo json_encode(['error' => 'ID bunga tidak valid.']);
            return;
        }

        $log_bunga = $this->Bunga_deposito_model->get_data_by_id($id_log_bunga);

        if (!$log_bunga) {
            echo json_encode(['error' => 'Data bunga tidak ditemukan.']);
            return;
        }

        if ($log_bunga->status_penarikan == 'sudah_ditarik') {
            echo json_encode(['error' => 'Gagal! Data bunga ini tidak bisa dihapus karena sudah pernah ditarik oleh nasabah.']);
            return;
        }

        $is_deleted = $this->Bunga_deposito_model->delete_data($id_log_bunga);

        if ($is_deleted) {
            echo json_encode(['success' => 'Data bunga berhasil dihapus.']);
        } else {
            echo json_encode(['error' => 'Gagal menghapus data bunga dari database.']);
        }
    }

    public function run_bunga_deposito()
    {
        if (!$this->input->is_ajax_request()) {
            redirect('unauthorized_403');
        }

        $processed = $this->Bunga_deposito_model->bunga_proses_deposito();

        if ($processed) {
            $msg = ['success' => 'Bunga deposito berhasil dihitung dan disimpan.'];
        } else {
            $msg = ['empty' => 'Tidak ada bunga deposito yang perlu diproses hari ini.'];
        }

        header('Content-Type: application/json');
        echo json_encode($msg);
    }

    /**
     * Print monthly interest report for Deposito
     */
    public function print_laporan_bulanan()
    {
        $start_date = $this->input->get('start_date') ?? date('Y-m-01');
        $end_date = $this->input->get('end_date') ?? date('Y-m-t');
        
        // Get data for the report
        $list = $this->Bunga_deposito_model->get_report_data($start_date, $end_date);
        $total_bunga = $this->Bunga_deposito_model->get_total_bunga_filtered($start_date, $end_date);
        
        // Format dates for display
        $formatter = new \IntlDateFormatter('id_ID', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
        $formatter->setPattern('MMMM yyyy');
        $periode = $formatter->format(new DateTime($start_date));
        
        $formatter->setPattern('d MMMM yyyy');
        $tanggal_cetak = $formatter->format(new DateTime());
        
        $data = [
            'list' => $list,
            'total_bunga' => $total_bunga,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'periode' => $periode,
            'tanggal_cetak' => $tanggal_cetak,
            'tipe' => 'Deposito'
        ];

        $html = $this->load->view('bunga/cetak_laporan', $data, TRUE);

        $this->load->library('dompdf_lib');
        $this->dompdf_lib->loadHtml($html);
        $this->dompdf_lib->setPaper('A4', 'portrait');
        $this->dompdf_lib->render();

        $filename = "Laporan_Bunga_Deposito_" . date('Y-m', strtotime($start_date)) . ".pdf";
        $this->dompdf_lib->stream($filename, false);
    }

    /**
     * Search deposito for Select2 dropdown (AJAX)
     */
    public function search_deposito()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $search = $this->input->get('q') ?? '';
        $target_date = $this->input->get('target_date') ?? date('Y-m-d');
        $results = $this->Bunga_deposito_model->get_deposito_dropdown($search, $target_date);

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'id' => $row->id,
                'text' => $row->no_rekening . ' - ' . $row->nama_nasabah,
                'jumlah_deposito' => $row->jumlah_deposito,
                'rate_bunga' => $row->rate_bunga,
                'nama_nasabah' => $row->nama_nasabah
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['results' => $data]);
    }

    /**
     * Calculate bunga for single deposito (AJAX)
     */
    public function hitung_bunga()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $deposito_id = $this->input->post('deposito_id');
        $bunga = $this->Bunga_deposito_model->calculate_bunga_single($deposito_id);

        header('Content-Type: application/json');
        echo json_encode(['bunga' => $bunga, 'formatted' => number_format($bunga, 0, ',', '.')]);
    }

    /**
     * Save manual bunga entry (AJAX)
     */
    public function simpan_bunga_manual()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        // Validation
        $deposito_id = $this->input->post('deposito_id');
        $tanggal = $this->input->post('tanggal_perhitungan');
        $jumlah_bunga = str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_bunga'));
        $keterangan = $this->input->post('keterangan');

        if (empty($deposito_id) || empty($tanggal) || empty($jumlah_bunga)) {
            echo json_encode(['error' => 'Deposito, tanggal, dan jumlah bunga wajib diisi.']);
            return;
        }

        // Check for duplicate (only 1 per month allowed)
        if ($this->Bunga_deposito_model->check_duplicate($deposito_id, $tanggal)) {
            $bulan = date('F Y', strtotime($tanggal));
            echo json_encode(['error' => "Bunga untuk deposito ini pada bulan $bulan sudah ada."]);
            return;
        }

        // Prepare data
        $data = [
            'deposito_id' => $deposito_id,
            'tanggal_perhitungan' => $tanggal,
            'jumlah_bunga' => $jumlah_bunga,
            'input_method' => 'manual',
            'pegawai_id' => $this->session->userdata('pegawai_id'),
            'keterangan' => $keterangan
        ];

        // Insert
        $inserted = $this->Bunga_deposito_model->insert_bunga_manual($data);

        if ($inserted) {
            echo json_encode(['success' => 'Bunga manual berhasil disimpan.']);
        } else {
            echo json_encode(['error' => 'Gagal menyimpan bunga manual.']);
        }
    }
}
