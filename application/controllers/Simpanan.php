<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Simpanan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Nasabah_model');
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
        $parser = [
            'judul' => "<i class='fa fa-money-check'></i> Simpanan",
            'isi'   => $this->load->view('simpanan/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetchData()
    {
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_?');
        }

        if ($this->input->is_ajax_request() == true) {
            $list = $this->Nasabah_model->get_datatables();
            $data = array();
            $no = $_POST['start'];



            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->nik;
                $row[] = $field->nama_lengkap;
                $row[] = $field->telp;
                $row[] = $field->email;
                $row[] = "<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location='nasabah/edit/" . safe_base64_encode($field->nik) . "'\"><i class='fa fa-edit fa-fw'></i></button>
                            <button class=\"btn btn-danger\" onclick=\"deleteItem('" . $field->id . "', '" . $field->nama_lengkap . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>
                            <button class=\"btn btn-secondary\"onclick=\"window.location='nasabah/detail/" . safe_base64_encode($field->nik) . "'\"><i class='fa fa-info fa-fw'></i></button>";;
                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Nasabah_model->count_all(),
                "recordsFiltered" => $this->Nasabah_model->count_filtered(),
                "data" => $data,
            );

            echo json_encode($output);
        } else {
            exit('Maaf data tidak bisa ditampilkan');
        }
    }

    public function add()
    {
        $data = [
            'jenis' => $this->Kategori_model->get_data(),
            'pegawai' => $this->Pegawai_model->get_data(),
            'level' => $this->session->userData('level')
        ];

        $parser = [
            'judul' => "<i class='fa fa-money-check'></i> Simpanan",
            'isi'   => $this->load->view('simpanan/addForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function getJenisData()
    {
        if ($this->input->is_ajax_request() == true) {
            $id = $this->input->post('id');

            $kategori = $this->Kategori_model->get_data_by_id($id);

            $data = [
                'kategori' => $kategori
            ];

            echo json_encode($data);
        } else {
            show_custom_404();
        }
    }

    public function generate_norek()
    {
        $kode_kantor = '01';

        $tanggal = date('d');
        $bulan = date('m');
        $tahun   = date('y');
        $tanggal_lengkap = $tanggal . $bulan . $tahun;

        $jumlah_nasabah = $this->Simpanan_model->count_all_data();
        $urutan = str_pad($jumlah_nasabah + 1, 6, '0', STR_PAD_LEFT);

        $norek = $kode_kantor . $tanggal_lengkap . $urutan;

        echo json_encode(['norek' => $norek]);
    }
}
