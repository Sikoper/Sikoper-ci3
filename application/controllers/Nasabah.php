<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Nasabah extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Nasabah_model');
        $this->load->model('Kategori_model');
        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }
    public function index()
    {
        $parser = [
            'judul' => "<i class='fa fa-users'></i> Nasabah",
            'isi'   => $this->load->view('nasabah/index', '', TRUE)
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
                $row[] = $field->nomor_rekening;
                $row[] = $field->nama_lengkap;
                $row[] = $field->telp;
                $row[] = $field->jenistabungan_id;
                $row[] = "<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location='nasabah/edit/" . safe_base64_encode($field->nomor_rekening) . "'\"><i class='fa fa-edit fa-fw'></i></button>
                            <button class=\"btn btn-danger\" onclick=\"deleteItem('" . $field->id . "', '" . $field->nama_lengkap . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>
                            <button class=\"btn btn-secondary\"onclick=\"window.location='nasabah/detail/" . safe_base64_encode($field->nomor_rekening) . "'\"><i class='fa fa-info fa-fw'></i></button>";;
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

}
