<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Deposito_model');
        $this->load->model('Bunga_model');
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
            'judul' => "Daftar Bunga Tabungan",
            'isi'   => $this->load->view('bunga/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetchBungaTabungan()
    {
        $start = $this->input->post('start_date');
        $end = $this->input->post('end_date');

        $level = $this->session->userdata('level');

        $list = $this->Bunga_model->get_datatables($start, $end);
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
            $row[] = number_format($field->bunga_riil, 0, ',', '.');
            $row[] = $field->rate_bunga . " %";

            if ($level == 'Admin') {
                $row[] = "<button class='btn btn-sm btn-danger' onclick=\"deleteItem('$field->id', '$field->no_rekening')\"><i class=\"fa fa-trash\"></i></button>";
            } else {
                $row[] = "";
            }

            $data[] = $row;
        }

        $total_bunga = $this->Bunga_model->get_total_bunga_filtered($start, $end);

        $output = [
            "draw" => isset($_POST['draw']) ? $_POST['draw'] : 1,
            "recordsTotal" => $this->Bunga_model->count_all(),
            "recordsFiltered" => $this->Bunga_model->count_filtered($start, $end),
            "data" => $data,
            "total_bunga" => number_format($total_bunga, 0, ',', '.')
        ];
        echo json_encode($output);
    }

    public function fetchNasabahTabunganBunga()
    {
        if (!$this->input->is_ajax_request()) {
            exit('Maaf data tidak bisa ditampilkan');
        }

        $no_rekening = $this->input->post('no_rekening');
        $list = $this->Nasabah_bunga_model->get_datatables($no_rekening, 'Simpanan');
        $data = array();
        $no = $_POST['start'];

        foreach ($list as $field) {
            $no++;
            $row = array();
            $row[] = "<div class=\"text-center\">$no</div>";
            $row[] = $field->tanggal_transaksi;
            $row[] = "Rp " . number_format($field->jumlah_transaksi, 2, ',', '.');
            $row[] = "Rp " . number_format($field->bunga_riil, 2, ',', '.');
            $row[] = ((float)$field->rate_bunga) . " %";
            $row[] = "<button class=\"btn btn-danger\" onclick=\"deleteRecordBunga('" . $field->source_id . "', '" . $field->jumlah_transaksi . "','" . $field->tipe . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";

            $data[] = $row;
        }

        $total_bunga = $this->Nasabah_bunga_model->get_total_bunga_by_rekening($no_rekening, 'Simpanan');

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->Nasabah_bunga_model->count_all('Simpanan'),
            "recordsFiltered" => $this->Nasabah_bunga_model->count_filtered($no_rekening, 'Simpanan'),
            "data" => $data,
            "total_bunga" => number_format($total_bunga, 2, ',', '.')
        );

        echo json_encode($output);
    }

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');

            // Get the transaksi bunga simpanan
            $transaksi = $this->Bunga_model->get_data_by_id($id);

            if (!$transaksi) {
                echo json_encode(['error' => 'Transaksi tidak ditemukan.']);
                return;
            }

            // Get related simpanan
            $simpanan = $this->Simpanan_model->get_data_by_id($transaksi->simpanan_id);

            if (!$simpanan) {
                echo json_encode(['error' => 'Data simpanan tidak ditemukan.']);
                return;
            }

            // Recalculate total simpanan
            $selisih = $simpanan->jumlah_simpanan - $transaksi->jumlah_transaksi;

            // Delete transaksi
            $delete = $this->Bunga_model->delete_data($id);
            if ($delete) {
                // Update jumlah simpanan
                $this->Simpanan_model->edit_data($transaksi->simpanan_id, [
                    'jumlah_simpanan' => $selisih
                ]);
                echo json_encode(['success' => 'Bunga tabungan berhasil dihapus.']);
            } else {
                echo json_encode(['error' => 'Gagal menghapus bunga tabungan.']);
            }
        }
    }

    public function run_bunga()
    {
        if (!$this->input->is_ajax_request()) {
            redirect('unauthorized_403');
        }

        $processed = $this->Bunga_model->checkAndRunBunga();

        if ($processed) {
            $msg = ['success' => 'Bunga tabungan berhasil dihitung'];
        } else {
            $msg = ['error' => 'Bunga tabungan sudah diperbarui bulan ini.'];
        }

        header('Content-Type: application/json');
        echo json_encode($msg);
    }
}
