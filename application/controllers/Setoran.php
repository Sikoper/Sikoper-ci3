<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Setoran extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Nasabah_model');
        $this->load->model('Kategori_model');
        $this->load->model('Pegawai_model');
        $this->load->model('Setoran_model');

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
            'judul' => "<i class='fa fa-money-check'></i> Setoran",
            'isi'   => $this->load->view('setoran/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function simpanData()
    {
        if ($this->input->is_ajax_request()) {
            $tanggal_setoran = $this->input->post('tanggal_setoran');
            $tabungan = $this->input->post('tabungan');
            $jumlah_setoran = str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_setoran'));
            $pegawai_id = $this->input->post('pegawai_id');

            $this->form_validation->set_rules('tanggal_setoran', 'Tanggal Setoran', 'required', [
                'required'   => 'Tanggal setoran wajib diisi.'
            ]);

            $this->form_validation->set_rules('nasabah', 'Nasabah', 'required', [
                'required'   => 'Nasabah wajib diisi.'
            ]);

            $this->form_validation->set_rules('tabungan', 'Tabungan', 'required', [
                'required'   => 'Tabungan wajib diisi.'
            ]);

            $this->form_validation->set_rules('jumlah_setoran', 'Jumlah Setoran', 'required', [
                'required'   => 'Jumlah setoran wajib diisi.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorTanggalSetoran'   => form_error('tanggal_setoran'),
                        'errorNasabah'          => form_error('nasabah'),
                        'errorTabungan'         => form_error('tabungan'),
                        'errorJumlahSetoran'    => form_error('jumlah_setoran'),
                    ]
                ];
            } else {

                $data = [
                    'simpanan_id' => $tabungan,
                    'tanggal_setoran' => $tanggal_setoran,
                    'jumlah_setoran' => $jumlah_setoran,
                    'pegawai_id' => $pegawai_id
                ];

                // echo '<pre>';
                // print_r($data);
                // exit;

                $inserted = $this->Setoran_model->insert_data($data);
                if ($inserted) {
                    $msg = ['success' => 'Data berhasil ditambahkan.'];
                } else {
                    $msg = ['error' => 'Gagal menyimpan data.'];
                }
            }

            echo json_encode($msg);
        }
    }

    public function get_no_rekening()
    {
        if ($this->input->is_ajax_request()) {
            $nasabah = $this->input->post('nasabah');
            $data_simpanan = $this->Simpanan_model->get_data_by_nasabah($nasabah);
            $Value = "<option value='' selected> --- Pilih tabungan --- </option>";

            foreach ($data_simpanan as $row) {
                $kategori = $this->Kategori_model->get_data_by_id($row->jenistabungan_id);
                $kategori_nama = $kategori ? $kategori->nama : 'Unknown';
                $Value .= '<option value="' . $row->id . '">' . $row->no_rekening . ' (' . $kategori_nama . ')</option>';
            }

            echo json_encode(['data' => $Value]);
        }
    }
}
