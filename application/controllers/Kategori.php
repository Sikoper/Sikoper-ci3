<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kategori extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Kategori_model');
        $allowed_roles = ['Admin', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }
    public function index()
    {
        $parser = [
            'judul' => "<i class='fa fa-list'></i> Jenis Tabungan",
            'isi'   => $this->load->view('kategori/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetchData()
    {
        if ($this->input->is_ajax_request() == true) {
            $list = $this->Kategori_model->get_datatables();
            $data = array();
            $no = $_POST['start'];
            $level = $this->session->userdata('level');
            function safe_base64_encode($string)
            {
                return strtr(base64_encode($string), '+/=', '-_.');
            }

            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->nama;
                $row[] = number_format($field->bunga, 2, '.', '') . ' %';
                $row[] = 'Rp ' . number_format($field->biaya_registrasi, 0, ',', '.');
                $row[] = 'Rp ' . number_format($field->simpanan_awal, 0, ',', '.');
                $encodedId = safe_base64_encode($field->id);
                $buttons = "<button class=\"btn btn-secondary\" onclick=\"window.location='jenis_tabungan/detail/$encodedId'\"><i class='fa fa-info fa-fw'></i></button>";

                if ($level !== 'Direktur') {
                    $buttons = "<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location='jenis_tabungan/edit/$encodedId'\"><i class='fa fa-edit fa-fw'></i></button>
                            <button class=\"btn btn-danger\" onclick=\"deleteItem('{$field->id}', '{$field->nama}')\"><i class=\"fa fa-trash fa-fw\"></i></button> " . $buttons;
                }

                $row[] = $buttons;
                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Kategori_model->count_all(),
                "recordsFiltered" => $this->Kategori_model->count_filtered(),
                "data" => $data,
            );

            echo json_encode($output);
        } else {
            exit('Maaf data tidak bisa ditampilkan');
        }
    }

    public function add()
    {
        // $allowed_roles = ['Admin'];
        // $level = $this->session->userdata('level');
        // if (!in_array($level, $allowed_roles)) {
        //     redirect('unauthorized_403');
        // }
        $parser = [
            'judul' => "<i class='fa fa-list'></i> Jenis Tabungan",
            'isi'   => $this->load->view('kategori/addForm', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function simpanData()
    {
        if ($this->input->is_ajax_request()) {
            $allowed_roles = ['Admin'];
            $level = $this->session->userdata('level');

            if (!in_array($level, $allowed_roles)) {
                $msg = [
                    'error' => 'Unauthorized 403'
                ];
                echo json_encode($msg);
                return;
            }

            $nama = $this->input->post('nama');
            $bunga = str_replace(',', '.', $this->input->post('bunga'));
            $biaya_registrasi = str_replace(['.', ','], ['', '.'], $this->input->post('biaya_registrasi'));
            $simpanan_awal    = str_replace(['.', ','], ['', '.'], $this->input->post('simpanan_awal'));
            $pengendapan      = str_replace(['.', ','], ['', '.'], $this->input->post('pengendapan'));
            $keterangan = $this->input->post('keterangan');

            $this->form_validation->set_rules('nama', 'Nama', 'required', [
                'required'     => 'Nama wajib diisi.'
            ]);
            $this->form_validation->set_rules('bunga', 'Bunga', 'required', [
                'required'     => 'Bunga wajib diisi.'
            ]);
            $this->form_validation->set_rules('biaya_registrasi', 'Biaya Registrasi', 'required', [
                'required'     => 'Biaya Registrasi wajib diisi.',
            ]);
            $this->form_validation->set_rules('simpanan_awal', 'Simpanan Awal', 'required', [
                'required'     => 'Simpanan awal wajib diisi.',
            ]);
            $this->form_validation->set_rules('pengendapan', 'Pengendapan', 'required', [
                'required'     => 'Pengendapan wajib diisi.',
            ]);
            $this->form_validation->set_rules('keterangan', 'Keterangan', 'required', [
                'required'     => 'Keterangan wajib diisi.',
            ]);

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorNama' => form_error('nama'),
                        'errorBunga' => form_error('bunga'),
                        'errorBiayaRegistrasi' => form_error('biaya_registrasi'),
                        'errorSimpananAwal' => form_error('simpanan_awal'),
                        'errorPengendapan' => form_error('pengendapan'),
                        'errorKeterangan' => form_error('keterangan'),
                    ]
                ];
            } else {
                $data = [
                    'nama' => $nama,
                    'bunga' => $bunga,
                    'biaya_registrasi' => $biaya_registrasi,
                    'simpanan_awal' => $simpanan_awal,
                    'pengendapan' => $pengendapan,
                    'keterangan' => $keterangan,
                ];
                $this->Kategori_model->insert_data($data);
                $msg = ['success' => 'Data berhasil ditambahkan.'];
            }

            echo json_encode($msg);
        } else {
            show_custom_404();
            return;
        }
    }

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $allowed_roles = ['Admin'];
            $level = $this->session->userdata('level');

            if (!in_array($level, $allowed_roles)) {
                $msg = [
                    'error' => 'Unauthorized 403'
                ];
                echo json_encode($msg);
                return;
            }

            $id = $this->input->post('id');

            $this->Kategori_model->delete_data($id);

            $msg = [
                'success' => 'Data berhasil dihapus'
            ];
            echo json_encode($msg);
        } else {
            show_custom_404();
            return;
        }
    }

    public function edit($encoded_id = null)
    {
        $allowed_roles = ['Admin'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }

        function safe_base64_decode($string)
        {
            return base64_decode(strtr($string, '-_.', '+/='));
        }

        if ($encoded_id === null) {
            show_custom_404();
            return;
        }

        $id = safe_base64_decode($encoded_id);
        $kategori = $this->Kategori_model->get_data_by_id($id);

        if (!$kategori) {
            show_custom_404();
            return;
        }

        $data = [
            'kategori' => $kategori,
        ];
        $parser = [
            'judul' => "<i class='fa fa-list'></i> Jenis Tabungan",
            'isi'   => $this->load->view('kategori/editForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function updateData()
    {
        if ($this->input->is_ajax_request()) {
            $allowed_roles = ['Admin'];
            $level = $this->session->userdata('level');

            if (!in_array($level, $allowed_roles)) {
                $msg = [
                    'error' => 'Unauthorized 403'
                ];
                echo json_encode($msg);
                return;
            }

            $id = $this->input->post('id');
            $nama = $this->input->post('nama');
            $bunga = str_replace(',', '.', $this->input->post('bunga'));
            $biaya_registrasi = str_replace(['.', ','], ['', '.'], $this->input->post('biaya_registrasi'));
            $simpanan_awal    = str_replace(['.', ','], ['', '.'], $this->input->post('simpanan_awal'));
            $pengendapan      = str_replace(['.', ','], ['', '.'], $this->input->post('pengendapan'));
            $keterangan = $this->input->post('keterangan');

            $this->form_validation->set_rules('nama', 'Nama', 'required', [
                'required'     => 'Nama wajib diisi.'
            ]);
            $this->form_validation->set_rules('bunga', 'Bunga', 'required', [
                'required'     => 'Bunga wajib diisi.'
            ]);
            $this->form_validation->set_rules('biaya_registrasi', 'Biaya Registrasi', 'required', [
                'required'     => 'Biaya Registrasi wajib diisi.',
            ]);
            $this->form_validation->set_rules('simpanan_awal', 'Simpanan Awal', 'required', [
                'required'     => 'Simpanan awal wajib diisi.',
            ]);
            $this->form_validation->set_rules('pengendapan', 'Pengendapan', 'required', [
                'required'     => 'Pengendapan wajib diisi.',
            ]);
            $this->form_validation->set_rules('keterangan', 'Keterangan', 'required', [
                'required'     => 'Keterangan wajib diisi.',
            ]);

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorNama' => form_error('nama'),
                        'errorBunga' => form_error('bunga'),
                        'errorBiayaRegistrasi' => form_error('biaya_registrasi'),
                        'errorSimpananAwal' => form_error('simpanan_awal'),
                        'errorPengendapan' => form_error('pengendapan'),
                        'errorKeterangan' => form_error('keterangan'),
                    ]
                ];
            } else {
                $data = [
                    'nama' => $nama,
                    'bunga' => $bunga,
                    'biaya_registrasi' => $biaya_registrasi,
                    'simpanan_awal' => $simpanan_awal,
                    'pengendapan' => $pengendapan,
                    'keterangan' => $keterangan,
                ];
                $inserted = $this->Kategori_model->edit_data($id, $data);
                if ($inserted) {
                    $msg = ['success' => 'Data berhasil diubah.'];
                } else {
                    $msg = ['error' => 'Gagal menyimpan perubahan data.'];
                }
            }

            echo json_encode($msg);
        } else {
            show_custom_404();
            return;
        }
    }

    public function detail($encoded_id = null)
    {
        function safe_base64_decode($string)
        {
            return base64_decode(strtr($string, '-_.', '+/='));
        }

        if ($encoded_id === null) {
            show_custom_404();
            return;
        }

        $id = safe_base64_decode($encoded_id);;
        $kategori = $this->Kategori_model->get_data_by_id($id);

        if (!$kategori) {
            show_custom_404();
            return;
        }

        $data = [
            'kategori' => $kategori,
        ];
        $parser = [
            'judul' => "<i class='fa fa-list'></i> Jenis Tabungan",
            'isi'   => $this->load->view('kategori/detail', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }
}
