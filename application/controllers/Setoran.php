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

                $data_simpanan = $this->Simpanan_model->get_data_by_id($tabungan);
                function safe_base64_encode($string)
                {
                    return strtr(base64_encode($string), '+/=', '-_?');
                }
                // echo '<pre>';
                // print_r($data);
                // exit;

                $inserted = $this->Setoran_model->insert_data($data);
                if ($inserted) {
                    $this->db->set('jumlah_simpanan', 'jumlah_simpanan + ' . $this->db->escape($jumlah_setoran), false);
                    $this->db->where('id', $tabungan);
                    $this->db->update('tbsimpanan');

                    $msg = [
                        'success' => 'Data berhasil ditambahkan.',
                        'redirect' => base_url('simpanan/detail/') . safe_base64_encode($data_simpanan->no_rekening)
                    ];
                } else {
                    $msg = ['error' => 'Gagal menyimpan data.'];
                }
            }

            echo json_encode($msg);
        }
    }

    public function fetchData()
    {
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_?');
        }
        $id = $this->input->post('id');
        if ($this->input->is_ajax_request() == true) {
            $list = $this->Setoran_model->get_datatables($id);
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->tanggal_setoran;
                $row[] = "Rp " . number_format($field->jumlah_setoran, 2, ',', '.');
                $row[] = $field->pegawai;
                $row[] = "<button class=\"btn btn-danger\" onclick=\"deleteSetoran('" . $field->id . "', '" . $field->jumlah_setoran . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";
                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Setoran_model->count_all($id),
                "recordsFiltered" => $this->Setoran_model->count_filtered($id),
                "data" => $data,
            );

            echo json_encode($output);
        } else {
            exit('Maaf data tidak bisa ditampilkan');
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

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');

            $simpanan_detail = $this->Setoran_model->get_data_by_id($id);
            $simpanan = $this->Simpanan_model->get_data_by_id($simpanan_detail->simpanan_id);

            $selisih = $simpanan->jumlah_simpanan - $simpanan_detail->jumlah_setoran;

            $delete = $this->Setoran_model->delete_data($id);
            if ($delete) {
                $data = [
                    'jumlah_simpanan' => $selisih
                ];

                $this->Simpanan_model->edit_data($simpanan_detail->simpanan_id, $data);
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
}
