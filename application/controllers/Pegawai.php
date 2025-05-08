<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pegawai extends CI_Controller
{
    public function index()
    {
        $parser = [
            'judul' => "<i class='fa fa-users'></i> Pegawai",
            'isi'   => $this->load->view('pegawai/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function getKab()
    {
        if ($this->input->is_ajax_request()) {
            $provinsi = $this->input->post('provinsi');
            $getKab = file_get_contents('https://wilayah.id/api/regencies/' . $provinsi . '.json');
            $response = json_decode($getKab, true);
            $Kab = $response['data'];
            $Value = "<option value='' selected> -- Pilih Kabupaten Asal -- </option>";

            foreach ($Kab as $row) :
                $Value .= '<option value="' . $row['code'] . '">' . $row['name'] . '</option>';
            endforeach;

            $msg = [
                'data' => $Value
            ];

            echo json_encode($msg);
        } else {
            show_404();
        }
    }

    public function getKec()
    {
        if ($this->input->is_ajax_request()) {
            $kabupaten = $this->input->post('kabupaten');
            $getKab = file_get_contents('https://wilayah.id/api/districts/' . $kabupaten . '.json');
            $response = json_decode($getKab, true);
            $Kab = $response['data'];
            $Value = "<option value='' selected> -- Pilih Kecamatan Asal -- </option>";

            foreach ($Kab as $row) :
                $Value .= '<option value="' . $row['code'] . '">' . $row['name'] . '</option>';
            endforeach;

            $msg = [
                'data' => $Value
            ];

            echo json_encode($msg);
        } else {
            show_404();
        }
    }

    public function getKel()
    {
        if ($this->input->is_ajax_request()) {
            $kecamatan = $this->input->post('kecamatan');
            $getKab = file_get_contents('https://wilayah.id/api/villages/' . $kecamatan . '.json');
            $response = json_decode($getKab, true);
            $Kab = $response['data'];
            $Value = "<option value='' selected> -- Pilih Desa Asal -- </option>";

            foreach ($Kab as $row) :
                $Value .= '<option value="' . $row['code'] . '">' . $row['name'] . '</option>';
            endforeach;

            $msg = [
                'data' => $Value
            ];

            echo json_encode($msg);
        } else {
            show_404();
        }
    }

    public function add()
    {
        $getProv = file_get_contents("https://wilayah.id/api/provinces.json");
        $response = json_decode($getProv, true);
        $data['provinces'] = $response['data'];

        $parser = [
            'judul' => "<i class='fa fa-user-plus'></i> Pegawai",
            'isi'   => $this->load->view('pegawai/addForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }
}
