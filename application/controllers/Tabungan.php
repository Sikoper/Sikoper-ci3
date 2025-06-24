<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tabungan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Tabungan_model');

        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function fetchTabungan()
    {
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_?');
        }
        $id = $this->input->post('id');
        if ($this->input->is_ajax_request() == true) {
            $list = $this->Tabungan_model->get_datatables($id);
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->tanggal;
                $row[] = "Rp " . number_format($field->jumlah_uang, 2, ',', '.');
                $badgeClass = ($field->keterangan == 'Setor') ? 'bg-success' : 'bg-danger';
                $row[] = "<span class=\"badge $badgeClass\">{$field->keterangan}</span>";
                $row[] = $field->pegawai;
                $row[] = "<button class=\"btn btn-danger\" onclick=\"deleteSetoran('" . $field->detail_id . "', '" . $field->jumlah_uang . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";
                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Tabungan_model->count_all($id),
                "recordsFiltered" => $this->Tabungan_model->count_filtered($id),
                "data" => $data,
            );

            echo json_encode($output);
        } else {
            exit('Maaf data tidak bisa ditampilkan');
        }
    }
}
