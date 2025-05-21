<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Bunga_model');
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
            'judul' => "<i class='fa fa-money-check'></i> Bunga",
            'isi'   => $this->load->view('bunga/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function add_bunga()
    {
        if (date('d') != '25') return;

        $today = date('Y-m-d');
        $exists = $this->db->get_where('systems_log', ['tanggal' => $today])->num_rows();
        if ($exists > 0) return;

        $this->Bunga_model->bunga_proses();

        $this->db->insert('systems_log', ['tanggal' => $today]);

        echo json_encode(['success' => 'Bunga bulanan berhasil diberikan']);
    }

    public function fetchData()
    {
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_?');
        }

        if ($this->input->is_ajax_request() == true) {
            $list = $this->Bunga_model->get_datatables();
            $data = array();
            $no = $_POST['start'];



            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->no_rekening;
                $row[] = $field->nasabah;
                $row[] = $field->tanggal_transaksi;
                $row[] = "Rp " . number_format($field->jumlah_transaksi, 2, ',', '.');
                $row[] = "<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location='nasabah/edit/" . safe_base64_encode($field->id) . "'\"><i class='fa fa-edit fa-fw'></i></button>
                            <button class=\"btn btn-danger\" onclick=\"deleteItem('" . $field->id . "', '" . $field->no_rekening . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";
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

    public function fetchNasabahBunga()
    {
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_?');
        }

        if ($this->input->is_ajax_request() == true) {
            $simpanan_id = $this->input->post('simpanan_id');
            $list = $this->Bunga_model->get_datatables($simpanan_id);
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->tanggal_transaksi;
                $row[] = "Rp " . number_format($field->jumlah_transaksi, 2, ',', '.');
                $row[] = "<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location='nasabah/edit/" . safe_base64_encode($field->id) . "'\"><i class='fa fa-edit fa-fw'></i></button>
                            <button class=\"btn btn-danger\" onclick=\"deleteItem('" . $field->id . "', '" . $field->no_rekening . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";
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
}
