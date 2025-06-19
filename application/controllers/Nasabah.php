<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Nasabah extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
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
        $allowed_roles = ['Admin', 'Pegawai',];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }

        $pegawai = $this->Pegawai_model->get_data();

        $data = [
            'pegawai' => $pegawai,
            'level' => $this->session->userdata('level'),
        ];

        $parser = [
            'judul' => "<i class='fa fa-user-plus'></i> Nasabah",
            'isi'   => $this->load->view('nasabah/addForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function simpanData()
    {
        if ($this->input->is_ajax_request()) {
            $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
            $level = $this->session->userdata('level');

            if (!in_array($level, $allowed_roles)) {
                echo json_encode(['error' => 'Unauthorized 403']);
                return;
            }

            $input = $this->input;

            // Ambil input dari form
            $nik                = $input->post('nik');
            $nama_lengkap       = $input->post('nama_lengkap');
            $kelamin            = $input->post('jenis_kelamin');
            $tempat_lahir       = $input->post('tempat_lahir');
            $tanggal_lahir      = $input->post('tgl_lahir');
            $agama              = $input->post('agama');
            $pekerjaan          = $input->post('pekerjaan');
            $nama_ibu_kandung   = $input->post('nama_ibu_kandung');
            $email              = $input->post('email');
            $alamat             = $input->post('alamat');
            $telp               = $input->post('telp');
            $pegawai_id         = $input->post('pegawai_id');

            // Validasi
            $this->form_validation->set_rules('nik', 'NIK', 'required|is_unique[tbnasabah.nik]|numeric|min_length[16]|max_length[16]', [
                'required'     => 'NIK wajib diisi.',
                'is_unique'    => 'NIK sudah terdaftar.',
                'numeric'      => 'NIK harus berupa angka.',
                'min_length'   => 'NIK harus terdiri dari 16 digit.',
                'max_length'   => 'NIK harus terdiri dari 16 digit.'
            ]);

            $this->form_validation->set_rules('nama_lengkap', 'Nama Lengkap', 'required|min_length[4]|max_length[100]', [
                'required'   => 'Nama tidak boleh kosong.',
                'min_length' => 'Nama Lengkap minimal 4 karakter.',
                'max_length' => 'Nama Lengkap maksimal 100 karakter.'
            ]);
            $this->form_validation->set_rules('jenis_kelamin', 'Jenis Kelamin', 'required', [
                'required'   => 'Jenis Kelamin tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('tempat_lahir', 'Tempat Lahir', 'required|max_length[30]', [
                'required'   => 'Tempat Lahir tidak boleh kosong.',
                'max_length' => 'Tempat Lahir maksimal 30 karakter.'
            ]);
            $this->form_validation->set_rules('tgl_lahir', 'Tanggal Lahir', 'required', [
                'required'   => 'Tanggal Lahir tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('agama', 'Agama', 'required', [
                'required'   => 'Agama tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('pekerjaan', 'Pekerjaan', 'required', [
                'required'   => 'Pekerjaan tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('nama_ibu_kandung', 'Nama Ibu Kandung', 'required', [
                'required'   => 'Nama Ibu Kandung tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email', [
                'required'   => 'Email tidak boleh kosong.',
                'valid_email' => 'Format email tidak valid.'
            ]);
            $this->form_validation->set_rules('alamat', 'Alamat', 'required', [
                'required'   => 'Alamat tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('telp', 'No Telepon', 'required|numeric|min_length[11]', [
                'required'   => 'No Telepon tidak boleh kosong.',
                'numeric'    => 'No Telepon harus berupa angka.',
                'min_length' => 'No Telepon minimal 11 karakter.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                echo json_encode([
                    'error' => [
                        'errorNik'              => form_error('nik'),
                        'errorNamaLengkap'      => form_error('nama_lengkap'),
                        'errorJenisKelamin'     => form_error('jenis_kelamin'),
                        'errorTempatLahir'      => form_error('tempat_lahir'),
                        'errorTanggalLahir'     => form_error('tgl_lahir'),
                        'errorAgama'            => form_error('agama'),
                        'errorPekerjaan'        => form_error('pekerjaan'),
                        'errorNama_ibu_kandung' => form_error('nama_ibu_kandung'),
                        'errorEmail'            => form_error('email'),
                        'errorProvinsi'         => form_error('provinsi'),
                        'errorKabupaten'        => form_error('kabupaten'),
                        'errorKecamatan'        => form_error('kecamatan'),
                        'errorDesa'             => form_error('desa'),
                        'errorAlamat'           => form_error('alamat'),
                        'errorRt'               => form_error('rt'),
                        'errorRw'               => form_error('rw'),
                        'errorTelp'             => form_error('telp'),
                        'errorJabatan'          => form_error('jenistabungan_id'),
                    ]
                ]);
            } else {
                $data = [
                    'nik'               => $nik,
                    'nama_lengkap'      => $nama_lengkap,
                    'jenis_kelamin'     => $kelamin,
                    'tempat_lahir'      => $tempat_lahir,
                    'tanggal_lahir'     => $tanggal_lahir,
                    'agama'             => $agama,
                    'pekerjaan'         => $pekerjaan,
                    'nama_ibu_kandung'  => $nama_ibu_kandung,
                    'email'             => $email,
                    'alamat'            => $alamat,
                    'telp'              => $telp,
                    'pegawai_id'        => $pegawai_id
                ];

                // echo '<pre>';
                // print_r($data);
                // exit;
                $inserted = $this->Nasabah_model->insert_data($data);

                if ($inserted) {
                    echo json_encode(['success' => 'Data berhasil disimpan.']);
                } else {
                    echo json_encode(['error' => 'Gagal menyimpan data ke database.']);
                }
            }
        }
    }

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');

            $this->Nasabah_model->delete_data($id);

            $msg = [
                'success' => 'Data berhasil dihapus'
            ];

            echo json_encode($msg);
        }
    }

    public function edit($encoded_nik = null)
    {
        $allowed_roles = ['Admin', 'Pegawai',];
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
        $nasabah = $this->Nasabah_model->get_data_by_nik($nik);

        if (!$nasabah) {
            show_custom_404();
            return;
        }

        $pegawai = $this->Pegawai_model->get_data();

        $data = [
            'nasabah' => $nasabah,
            'level' => $this->session->userdata('level'),
            'pegawai' => $pegawai,
        ];
        $parser = [
            'judul' => "<i class='fa fa-user-edit'></i> Nasabah",
            'isi'   => $this->load->view('nasabah/editForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function updateData()
    {
        if ($this->input->is_ajax_request()) {
            $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
            $level = $this->session->userdata('level');

            if (!in_array($level, $allowed_roles)) {
                echo json_encode(['error' => 'Unauthorized 403']);
                return;
            }

            $input = $this->input;

            // Ambil input dari form
            $id                 = $input->post('id');
            $nik                = $input->post('nik');
            $nama_lengkap       = $input->post('nama_lengkap');
            $kelamin            = $input->post('jenis_kelamin');
            $tempat_lahir       = $input->post('tempat_lahir');
            $tanggal_lahir      = $input->post('tgl_lahir');
            $agama              = $input->post('agama');
            $pekerjaan          = $input->post('pekerjaan');
            $nama_ibu_kandung   = $input->post('nama_ibu_kandung');
            $email              = $input->post('email');
            $alamat             = $input->post('alamat');
            $telp               = $input->post('telp');
            $pegawai_id         = $input->post('pegawai_id');

            // Validasi
            $nasabah = $this->Nasabah_model->get_data_by_id($id);
            if ($nasabah->nik == $nik) {
                $this->form_validation->set_rules('nik', 'NIK', 'required|numeric|min_length[16]|max_length[16]', [
                    'required'     => 'NIK wajib diisi.',
                    'numeric'      => 'NIK harus berupa angka.',
                    'min_length'   => 'NIK harus terdiri dari 16 digit.',
                    'max_length'   => 'NIK harus terdiri dari 16 digit.'
                ]);
            } else {
                $this->form_validation->set_rules('nik', 'NIK', 'required|is_unique[tbnasabah.nik]|numeric|min_length[16]|max_length[16]', [
                    'required'     => 'NIK wajib diisi.',
                    'is_unique'    => 'NIK sudah terdaftar.',
                    'numeric'      => 'NIK harus berupa angka.',
                    'min_length'   => 'NIK harus terdiri dari 16 digit.',
                    'max_length'   => 'NIK harus terdiri dari 16 digit.'
                ]);
            };

            $this->form_validation->set_rules('nama_lengkap', 'Nama Lengkap', 'required|min_length[4]|max_length[100]', [
                'required'   => 'Nama tidak boleh kosong.',
                'min_length' => 'Nama Lengkap minimal 4 karakter.',
                'max_length' => 'Nama Lengkap maksimal 100 karakter.'
            ]);
            $this->form_validation->set_rules('jenis_kelamin', 'Jenis Kelamin', 'required', [
                'required'   => 'Jenis Kelamin tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('tempat_lahir', 'Tempat Lahir', 'required|max_length[30]', [
                'required'   => 'Tempat Lahir tidak boleh kosong.',
                'max_length' => 'Tempat Lahir maksimal 30 karakter.'
            ]);
            $this->form_validation->set_rules('tgl_lahir', 'Tanggal Lahir', 'required', [
                'required'   => 'Tanggal Lahir tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('agama', 'Agama', 'required', [
                'required'   => 'Agama tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('pekerjaan', 'Pekerjaan', 'required', [
                'required'   => 'Pekerjaan tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('nama_ibu_kandung', 'Nama Ibu Kandung', 'required', [
                'required'   => 'Nama Ibu Kandung tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email', [
                'required'   => 'Email tidak boleh kosong.',
                'valid_email' => 'Format email tidak valid.'
            ]);
            $this->form_validation->set_rules('alamat', 'Alamat', 'required', [
                'required'   => 'Alamat tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('telp', 'No Telepon', 'required|numeric|min_length[10]', [
                'required'   => 'No Telepon tidak boleh kosong.',
                'numeric'    => 'No Telepon harus berupa angka.',
                'min_length' => 'No Telepon minimal 11 karakter.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                echo json_encode([
                    'error' => [
                        'errorNik'              => form_error('nik'),
                        'errorNamaLengkap'      => form_error('nama_lengkap'),
                        'errorJenisKelamin'     => form_error('jenis_kelamin'),
                        'errorTempatLahir'      => form_error('tempat_lahir'),
                        'errorTanggalLahir'     => form_error('tgl_lahir'),
                        'errorAgama'            => form_error('agama'),
                        'errorPekerjaan'        => form_error('pekerjaan'),
                        'errorNama_ibu_kandung' => form_error('nama_ibu_kandung'),
                        'errorEmail'            => form_error('email'),
                        'errorProvinsi'         => form_error('provinsi'),
                        'errorKabupaten'        => form_error('kabupaten'),
                        'errorKecamatan'        => form_error('kecamatan'),
                        'errorDesa'             => form_error('desa'),
                        'errorAlamat'           => form_error('alamat'),
                        'errorRt'               => form_error('rt'),
                        'errorRw'               => form_error('rw'),
                        'errorTelp'             => form_error('telp'),
                        'errorJabatan'          => form_error('jenistabungan_id'),
                    ]
                ]);
            } else {
                $data = [
                    'nik'               => $nik,
                    'nama_lengkap'      => $nama_lengkap,
                    'jenis_kelamin'     => $kelamin,
                    'tempat_lahir'      => $tempat_lahir,
                    'tanggal_lahir'     => $tanggal_lahir,
                    'agama'             => $agama,
                    'pekerjaan'         => $pekerjaan,
                    'nama_ibu_kandung'  => $nama_ibu_kandung,
                    'email'             => $email,
                    'alamat'            => $alamat,
                    'telp'              => $telp,
                    'pegawai_id'        => $pegawai_id
                ];

                // echo '<pre>';
                // print_r($data);
                // exit;
                $inserted = $this->Nasabah_model->edit_data($id, $data);

                if ($inserted) {
                    echo json_encode(['success' => 'Data berhasil disimpan.']);
                } else {
                    echo json_encode(['error' => 'Gagal menyimpan data ke database.']);
                }
            }
        }
    }

    public function detail($encoded_nik = null)
    {
        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
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
        $nasabah = $this->Nasabah_model->get_data_by_nik($nik);

        if (!$nasabah) {
            show_custom_404();
            return;
        }


        $data = [
            'level' => $this->session->userdata('level'),
        ];
        $parser = [
            'judul' => "<i class='fa fa-user'></i> Nasabah",
            'isi'   => $this->load->view('nasabah/detail', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function cari_nasabah()
    {
        $keyword = $this->input->get('q');

        if (!empty($keyword)) {
            $nasabah = $this->Nasabah_model->search_nasabah($keyword);
        } else {
            $this->db->select('id, nama_lengkap');
            $this->db->from('tbnasabah');
            $this->db->limit(100);
            $nasabah = $this->db->get()->result();
        }

        $data = [];
        foreach ($nasabah as $row) {
            $data[] = [
                'id' => $row->id,
                'text' => $row->nama_lengkap
            ];
        }

        echo json_encode($data);
    }
}
