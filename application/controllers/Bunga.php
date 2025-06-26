<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Bunga_model');
        $this->load->model('Nasabah_Bunga_model');
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
            'judul' => "Daftar Bunga Nasabah",
            'isi'   => $this->load->view('bunga/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    // public function add_bunga()
    // {
    //     if (date('d') != '28') return;

    //     $today = date('Y-m-d');
    //     $exists = $this->db->get_where('systems_log', ['tanggal' => $today])->num_rows();
    //     if ($exists > 0) return;

    //     $this->Bunga_model->bunga_proses();

    //     $this->db->insert('systems_log', ['tanggal' => $today]);

    //     echo json_encode(['success' => 'Bunga bulanan berhasil diberikan']);
    // }

    public function fetchData()
    {
        if ($this->input->is_ajax_request()) {
            $list = $this->Bunga_model->get_datatables();
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->nama_lengkap;
                $row[] = $field->no_rekening;
                $row[] = $field->tanggal_transaksi;
                $row[] = "Rp " . number_format($field->jumlah_transaksi, 2, ',', '.');
                $row[] = $field->tipe;
                $row[] = "<button class=\"btn btn-danger\" onclick=\"deleteItem('" . $field->id . "', '" . $field->no_rekening . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";

                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Bunga_model->count_all(),
                "recordsFiltered" => $this->Bunga_model->count_filtered(),
                "data" => $data,
            );

            echo json_encode($output);
        } else {
            exit('Maaf data tidak bisa ditampilkan');
        }
    }

    public function fetchNasabahTabunganBunga()
    {
        if ($this->input->is_ajax_request()) {
            $no_rekening = $this->input->post('no_rekening');
            $list = $this->Nasabah_Bunga_model->get_datatables($no_rekening);
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $field) {
                $no++;
                $row = array();
                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->tanggal_transaksi;
                $row[] = "Rp " . number_format($field->jumlah_transaksi, 2, ',', '.');
                $row[] = "<button class=\"btn btn-danger\" onclick=\"deleteRecord('" . $field->source_id . "', '" . $field->jumlah_transaksi . "','" . $field->tipe . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";

                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Nasabah_Bunga_model->count_all($no_rekening),
                "recordsFiltered" => $this->Nasabah_Bunga_model->count_filtered($no_rekening),
                "data" => $data,
            );

            echo json_encode($output);
        // } else {
        //     exit('Maaf data tidak bisa ditampilkan');
        // }
        }
    }
    // public function fetchNasabahBunga()
    // {
    //     $result = $this->Bunga_model->get_datatables();
    //     $data = [];
    //     $no = $_POST['start'];
    //     foreach ($result as $row) {
    //         $no++;
    //         $data[] = [
    //             'tanggal_transaksi' => date('d-m-Y', strtotime($row->tanggal_transaksi)),
    //             'no_rekening' => $row->no_rekening,
    //             'jumlah_transaksi' => 'Rp ' . number_format($row->jumlah_transaksi, 0, ',', '.'),
    //             'tipe' => $row->tipe,
    //             'bunga_khusus' => $row->bunga_khusus . '%'
    //         ];
    //     }

    //     $output = [
    //         "draw" => $_POST['draw'],
    //         "recordsTotal" => $this->Bunga_model->count_all(),
    //         "recordsFiltered" => $this->Bunga_model->count_filtered(),
    //         "data" => $data,
    //     ];

    //     echo json_encode($output);
    // }

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');

            $transaksi = $this->Bunga_model->get_data_by_id($id);
            $simpanan = $this->Simpanan_model->get_data_by_id($transaksi->simpanan_id);

            $selisih = $simpanan->jumlah_simpanan - $transaksi->jumlah_transaksi;

            $delete = $this->Bunga_model->delete_data($id);
            if ($delete) {
                $data = [
                    'jumlah_simpanan' => $selisih
                ];

                $this->Simpanan_model->edit_data($transaksi->simpanan_id, $data);
                $msg = [
                    'success' => 'Bunga berhasil dihapus.'
                ];
            } else {
                $msg = [
                    'error' => 'Bunga gagal dihapus.'
                ];
            }

            echo json_encode($msg);
        }
    }

    public function run_bunga()
    {
        $processed = $this->Bunga_model->checkAndRunBunga();

        if ($processed) {
            $msg = ['success' => 'Bunga tabungan berhasil dihitung'];
        } else {
            $msg = ['error' => 'Bunga tabungan sudah diperbarui bulan ini.'];
        }

        header('Content-Type: application/json');
        echo json_encode($msg);
    }

    public function run_bunga_deposito()
    {
        if ($this->Bunga_model->is_bunga_deposito_done_today()) {
            $msg = ['error' => 'Semua bunga deposito sudah diproses hari ini.'];
        } else {
            $processed = $this->Bunga_model->bunga_proses_deposito();

            if ($processed) {
                $msg = ['success' => 'Bunga deposito berhasil dihitung dan disimpan.'];
            } else {
                $msg = ['error' => 'Tidak ada bunga deposito yang valid untuk diproses hari ini.'];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($msg);
    }
}
