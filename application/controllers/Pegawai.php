<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pegawai extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Pegawai_model');
        $this->load->model('Users_model');
        $allowed_roles = ['Admin', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }
    public function index()
    {
        $parser = [
            'judul' => "Data Pegawai",
            'isi'   => $this->load->view('pegawai/index', '', TRUE)
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
            $list = $this->Pegawai_model->get_datatables();
            $data = array();
            $no = $_POST['start'];
            $level = $this->session->userdata('level');

            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->nik;
                $row[] = $field->nama_lengkap;
                $row[] = $field->telp;
                $row[] = $field->jabatan;
                $encodedNik = safe_base64_encode($field->nik);
                $buttons = "<button class=\"btn btn-secondary\" onclick=\"window.location='pegawai/detail/$encodedNik'\"><i class='fa fa-info fa-fw'></i></button>";

                if ($level !== 'Direktur') {
                    $buttons = "<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location='pegawai/edit/$encodedNik'\"><i class='fa fa-edit fa-fw'></i></button>
                            <button class=\"btn btn-danger\" onclick=\"deleteItem('{$field->id}', '{$field->nama_lengkap}')\"><i class=\"fa fa-trash fa-fw\"></i></button> " . $buttons;
                }

                $row[] = $buttons;
                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Pegawai_model->count_all(),
                "recordsFiltered" => $this->Pegawai_model->count_filtered(),
                "data" => $data,
            );

            echo json_encode($output);
        } else {
            exit('Maaf data tidak bisa ditampilkan');
        }
    }

    public function add()
    {
        $allowed_roles = ['Admin'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }

        $parser = [
            'judul' => "<i class='fa fa-user-plus'></i> Pegawai",
            'isi'   => $this->load->view('pegawai/addForm', '', TRUE)
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
            
            $nik = $this->input->post('nik');
            $nama_lengkap = $this->input->post('nama_lengkap');
            $tempat_lahir = $this->input->post('tempat_lahir');
            $tanggal_lahir = $this->input->post('tanggal_lahir');
            $kelamin = $this->input->post('jenis_kelamin');
            $alamat = $this->input->post('alamat');
            $agama = $this->input->post('agama');
            $telp = $this->input->post('telp');
            $jabatan = $this->input->post('jabatan');

            $this->form_validation->set_rules('nik', 'NIK', 'required|is_unique[tbpegawai.nik]', [
                'required'   => 'NIK wajib diisi.',
                'is_unique'  => 'NIK sudah terdaftar.'
            ]);

            $this->form_validation->set_rules('nama_lengkap', 'Nama Lengkap', 'required|min_length[4]|max_length[100]', [
                'required'     => 'Nama tidak boleh kosong.',
                'min_length'   => 'Nama minimal 4 karakter.',
                'max_length'   => 'Nama maksimal 100 karakter.'
            ]);

            $this->form_validation->set_rules('tempat_lahir', 'Tempat Lahir', 'required|max_length[30]', [
                'required'     => 'Tempat lahir harus diisi.',
                'max_length'   => 'Tempat lahir maksimal 30 karakter.'
            ]);

            $this->form_validation->set_rules('tanggal_lahir', 'Tanggal Lahir', 'required', [
                'required' => 'Tanggal lahir wajib diisi.'
            ]);

            $this->form_validation->set_rules('jenis_kelamin', 'Kelamin', 'required', [
                'required' => 'Jenis kelamin harus dipilih.'
            ]);

            $this->form_validation->set_rules('alamat', 'Jalan', 'required', [
                'required' => 'Alamat harus diisi.'
            ]);

            $this->form_validation->set_rules('agama', 'Agama', 'required', [
                'required' => 'Agama harus diisi.'
            ]);

            $this->form_validation->set_rules('telp', 'Nomer Telpon', 'required|numeric|min_length[10]', [
                'required' => 'Nomer telepon harus diisi.'
            ]);

            $this->form_validation->set_rules('jabatan', 'Jabatan', 'required', [
                'required' => 'Jabatan harus diisi.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorNik'              => form_error('nik'),
                        'errorNamaLengkap'      => form_error('nama_lengkap'),
                        'errorTempatLahir'      => form_error('tempat_lahir'),
                        'errorTanggalLahir'     => form_error('tanggal_lahir'),
                        'errorJenisKelamin'     => form_error('jenis_kelamin'),
                        'errorAlamat'           => form_error('alamat'),
                        'errorTelp'             => form_error('telp'),
                        'errorAgama'            => form_error('agama'),
                        'errorJabatan'          => form_error('jabatan'),
                    ]
                ];
            } else {

                $data = [
                    'nik'               => $nik,
                    'nama_lengkap'      => $nama_lengkap,
                    'alamat'            => $alamat,
                    'tempat_lahir'      => $tempat_lahir,
                    'tanggal_lahir'     => $tanggal_lahir,
                    'jenis_kelamin'     => $kelamin,
                    'agama'             => $agama,
                    'telp'              => $telp,
                    'jabatan'           => $jabatan,
                    'user_token'        => '1'
                ];

                $inserted = $this->Pegawai_model->insert_data($data);
                if ($inserted) {
                    $msg = ['success' => 'Data berhasil ditambahkan.'];
                } else {
                    $msg = ['error' => 'Gagal menyimpan data.'];
                }
            }

            echo json_encode($msg);
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

            $this->Pegawai_model->delete_data($id);

            $msg = [
                'success' => 'Data berhasil dihapus'
            ];
            echo json_encode($msg);
        } else {
            redirect('unauthorized_403');
        }
    }

    public function edit($encoded_nik = null)
    {
        $allowed_roles = ['Admin'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }

        function safe_base64_decode($string)
        {
            return base64_decode(strtr($string, '-_?', '+/='));
        }

        if ($encoded_nik === null) {
            show_custom_404();
            return;
        }

        $nik = safe_base64_decode($encoded_nik);;
        $pegawai = $this->Pegawai_model->get_data_by_nik($nik);

        if (!$pegawai) {
            show_custom_404();
            return;
        }

        $data = [
            'pegawai' => $pegawai,
        ];
        $parser = [
            'judul' => "Edit Data Pegawai",
            'isi'   => $this->load->view('pegawai/editForm', $data, TRUE)
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
            $nik = $this->input->post('nik');
            $nama_lengkap = $this->input->post('nama_lengkap');
            $tempat_lahir = $this->input->post('tempat_lahir');
            $tanggal_lahir = $this->input->post('tanggal_lahir');
            $kelamin = $this->input->post('jenis_kelamin');
            $alamat = $this->input->post('alamat');
            $agama = $this->input->post('agama');
            $telp = $this->input->post('telp');
            $jabatan = $this->input->post('jabatan');

            $pegawai = $this->Pegawai_model->get_data_by_id($id);
            if ($pegawai->nik == $nik) {
                $this->form_validation->set_rules('nik', 'NIK', 'required', [
                    'required'   => 'NIK wajib diisi.',
                ]);
            } else {
                $this->form_validation->set_rules('nik', 'NIK', 'required|is_unique[tbpegawai.nik]', [
                    'required'   => 'NIK wajib diisi.',
                    'is_unique'  => 'NIK sudah terdaftar.'
                ]);
            };

            $this->form_validation->set_rules('nama_lengkap', 'Nama Lengkap', 'required|min_length[4]|max_length[100]', [
                'required'     => 'Nama tidak boleh kosong.',
                'min_length'   => 'Nama minimal 4 karakter.',
                'max_length'   => 'Nama maksimal 100 karakter.'
            ]);

            $this->form_validation->set_rules('tempat_lahir', 'Tempat Lahir', 'required|max_length[30]', [
                'required'     => 'Tempat lahir harus diisi.',
                'max_length'   => 'Tempat lahir maksimal 30 karakter.'
            ]);

            $this->form_validation->set_rules('tanggal_lahir', 'Tanggal Lahir', 'required', [
                'required' => 'Tanggal lahir wajib diisi.'
            ]);

            $this->form_validation->set_rules('jenis_kelamin', 'Kelamin', 'required', [
                'required' => 'Jenis kelamin harus dipilih.'
            ]);

            $this->form_validation->set_rules('alamat', 'Jalan', 'required', [
                'required' => 'Alamat harus diisi.'
            ]);

            $this->form_validation->set_rules('agama', 'Agama', 'required', [
                'required' => 'Agama harus diisi.'
            ]);

            $this->form_validation->set_rules('telp', 'Nomer Telpon', 'required|numeric|min_length[10]', [
                'required' => 'Nomer telepon harus diisi.'
            ]);

            $this->form_validation->set_rules('jabatan', 'Jabatan', 'required', [
                'required' => 'Jabatan harus diisi.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorNik'              => form_error('nik'),
                        'errorNamaLengkap'      => form_error('nama_lengkap'),
                        'errorTempatLahir'      => form_error('tempat_lahir'),
                        'errorTanggalLahir'     => form_error('tanggal_lahir'),
                        'errorJenisKelamin'     => form_error('jenis_kelamin'),
                        'errorAlamat'           => form_error('alamat'),
                        'errorTelp'             => form_error('telp'),
                        'errorAgama'            => form_error('agama'),
                        'errorJabatan'          => form_error('jabatan'),
                    ]
                ];
            } else {

                $data = [
                    'nik'               => $nik,
                    'nama_lengkap'      => $nama_lengkap,
                    'alamat'            => $alamat,
                    'tempat_lahir'      => $tempat_lahir,
                    'tanggal_lahir'     => $tanggal_lahir,
                    'jenis_kelamin'     => $kelamin,
                    'agama'             => $agama,
                    'telp'              => $telp,
                    'jabatan'           => $jabatan
                ];

                $inserted = $this->Pegawai_model->edit_data($id, $data);
                if ($inserted) {
                    $msg = ['success' => 'Data berhasil diubah.'];
                } else {
                    $msg = ['error' => 'Gagal menyimpan perubahan data.'];
                }
            }

            echo json_encode($msg);
        }
    }

    public function detail($encoded_nik = null)
    {
        function safe_base64_decode($string)
        {
            return base64_decode(strtr($string, '-_?', '+/='));
        }

        if ($encoded_nik === null) {
            show_custom_404();
            return;
        }

        $nik = safe_base64_decode($encoded_nik);

        $this->load->model('Pegawai_model');
        $pegawai = $this->Pegawai_model->get_data_by_nik($nik);

        if (!$pegawai) {
            show_custom_404();
            return;
        }

        $data = [
            'pegawai' => $pegawai,
        ];
        $parser = [
            'judul' => "Detail Data Pegawai",
            'isi'   => $this->load->view('pegawai/detail', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function cari_pegawai()
    {
        $keyword = $this->input->get('q');

        if (!empty($keyword)) {
            $pegawai = $this->Pegawai_model->search_pegawai($keyword);
        } else {
            $this->db->select('id, nama_lengkap');
            $this->db->where('user_token','1');
            $this->db->from('tbpegawai');
            $this->db->limit(100);
            $pegawai = $this->db->get()->result();
        }

        $data = [];
        foreach ($pegawai as $row) {
            $data[] = [
                'id' => $row->id,
                'text' => $row->nama_lengkap
            ];
        }

        echo json_encode($data);
    }
}
