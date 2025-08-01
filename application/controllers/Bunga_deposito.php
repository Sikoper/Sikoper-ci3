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

        // 🔥 Get full-month total bunga regardless of page
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

        $no_rekening = $this->input->post('no_rekening');
        $list = $this->Nasabah_bunga_model->get_datatables($no_rekening, 'Deposito');
        $data = array();
        $no = $_POST['start'];

        foreach ($list as $field) {
            $no++;
            $row = array();
            $row[] = "<div class=\"text-center\">$no</div>";
            $row[] = $field->tanggal_transaksi . $field->source_id;
            $row[] = "Rp " . number_format($field->jumlah_transaksi, 2, ',', '.');
            $row[] = ((float)$field->rate_bunga) . " %";
            $row[] = "<button class=\"btn btn-danger\" onclick=\"deleteRecordBunga('" . $field->source_id . "', '" . $field->jumlah_transaksi . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";

            $data[] = $row;
        }

        $total_bunga = $this->Nasabah_bunga_model->get_total_bunga_by_rekening($no_rekening, 'Deposito');

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->Nasabah_bunga_model->count_all('Deposito'),
            "recordsFiltered" => $this->Nasabah_bunga_model->count_filtered($no_rekening, 'Deposito'),
            "data" => $data,
            "total_bunga" => number_format($total_bunga, 2, ',', '.')
        );

        echo json_encode($output);
    }

    public function delete()
    {
        // if ($this->input->is_ajax_request()) {
        $id = $this->input->post('id');

        // Get the bunga deposito transaction
        $transaksi = $this->Bunga_deposito_model->get_data_by_id($id);

        if (!$transaksi) {
            echo json_encode(['error' => 'Transaksi tidak ditemukan.']);
            return;
        }

        // Get related deposito record
        $deposito = $this->Deposito_model->get_data_by_id($transaksi->deposito_id);

        if (!$deposito) {
            echo json_encode(['error' => 'Data deposito tidak ditemukan.']);
            return;
        }

        // Recalculate saldo
        $selisih_hutang = $deposito->hutang_bunga - $transaksi->jumlah_transaksi;
        $selisih_total = $deposito->total_bunga - $transaksi->jumlah_transaksi;

        // Delete bunga deposito
        $delete = $this->Bunga_deposito_model->delete_data($id);
        if ($delete) {
            // Update saldo deposito
            $this->Deposito_model->edit_data($transaksi->deposito_id, [
                'hutang_bunga' => $selisih_hutang,
                'total_bunga' => $selisih_total
            ]);

            echo json_encode(['success' => 'Bunga deposito berhasil dihapus.']);
        } else {
            echo json_encode(['error' => 'Gagal menghapus bunga deposito.']);
        }
        // }
    }

    public function run_bunga_deposito()
    {
        if (!$this->input->is_ajax_request()) {
            redirect('unauthorized_403');
        }

        // Langsung panggil fungsi utama untuk memproses bunga
        $processed = $this->Bunga_deposito_model->bunga_proses_deposito();

        // Beri respon berdasarkan hasil dari fungsi tersebut
        if ($processed) {
            $msg = ['success' => 'Bunga deposito berhasil dihitung dan disimpan.'];
        } else {
            // Ini akan muncul jika tidak ada deposito yang jatuh tempo pada hari ini,
            // atau semua yang jatuh tempo hari ini sudah diproses.
            $msg = ['empty' => 'Tidak ada bunga deposito yang perlu diproses hari ini.'];
        }

        header('Content-Type: application/json');
        echo json_encode($msg);
    }
}
