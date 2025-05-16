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
                $jenistabungan = $this->Kategori_model->get_data_by_id($field->jenistabungan_id);
                $jenis_tabungan = $jenistabungan ? $jenistabungan->nama : 'Tidak Diketahui';

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->no_rekening;
                $row[] = $field->nama_lengkap;
                $row[] = $field->telp;
                $row[] = $jenis_tabungan;
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
        $allowed_roles = ['Admin', 'Pegawai',];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }

        $getProv = file_get_contents("https://wilayah.id/api/provinces.json");
        $response = json_decode($getProv, true);

        $datajenis = $this->Kategori_model->get_data();
        $pegawai = $this->Pegawai_model->get_data();

        $data = [
            'provinces' => $response['data'],
            'jenistabungan' => $datajenis,
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
            $provinsi           = $input->post('provinsi');
            $kabupaten          = $input->post('kabupaten');
            $kecamatan          = $input->post('kecamatan');
            $desa               = $input->post('desa');
            $rt                 = $input->post('rt');
            $rw                 = $input->post('rw');
            $alamat             = $input->post('alamat');
            $telp               = $input->post('telp');
            $jenis_tabungan     = $input->post('jenistabungan_id');
            $no_rekening        = $input->post('nomor_rekening');
            $pegawai_id         = $input->post('pegawai_id');

            // Validasi
            $this->form_validation->set_rules('nik', 'NIK', 'required|is_unique[tbnasabah.nik]|numeric', [
                'required'   => 'NIK wajib diisi.',
                'is_unique'  => 'NIK sudah terdaftar.',
                'numeric'    => 'NIK harus berupa angka.'
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
            $this->form_validation->set_rules('provinsi', 'Provinsi', 'required', [
                'required'   => 'Provinsi tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('kabupaten', 'Kabupaten', 'required', [
                'required'   => 'Kabupaten tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('kecamatan', 'Kecamatan', 'required', [
                'required'   => 'Kecamatan tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('desa', 'Desa', 'required', [
                'required'   => 'Desa tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('alamat', 'Alamat', 'required', [
                'required'   => 'Alamat tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('telp', 'No Telepon', 'required|numeric|min_length[10]', [
                'required'   => 'No Telepon tidak boleh kosong.',
                'numeric'    => 'No Telepon harus berupa angka.',
                'min_length' => 'No Telepon minimal 10 karakter.'
            ]);
            $this->form_validation->set_rules('jenistabungan_id', 'Jenis Tabungan', 'required', [
                'required'   => 'Jenis Tabungan tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('nomor_rekening', 'Nomor Rekening', 'required', [
                'required'   => 'Nomor Rekening tidak boleh kosong.'
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
                        'errornomor_rekening'   => form_error('nomor_rekening'),
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
                    'provinsi'          => $provinsi,
                    'kabupaten'         => $kabupaten,
                    'kecamatan'         => $kecamatan,
                    'desa'              => $desa,
                    'rt'                => $rt ?: '000',
                    'rw'                => $rw ?: '000',
                    'alamat'            => $alamat,
                    'telp'              => $telp,
                    'jenistabungan_id'  => $jenis_tabungan,
                    'no_rekening'       => $no_rekening,
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

    public function generate_norek()
    {
        $this->load->model('Nasabah_model');

        // 1. Kode kantor - bisa juga ambil dari session user jika login per kantor
        $kode_kantor = '01'; // misalnya Kantor Pusat

        // 2. Tanggal dibuat dalam format: ddmmyy (misal: 140525 untuk 14 Mei 2025)
        $tanggal = date('dm');   // 2 digit hari + 2 digit bulan (14 + 05)
        $tahun   = date('y');    // 2 digit tahun (25)
        $tanggal_lengkap = $tanggal . $tahun; // Jadi: 140525

        // 3. Urutan nasabah saat ini (ditambah 1)
        $jumlah_nasabah = $this->Nasabah_model->count_all_nasabah();
        $urutan = str_pad($jumlah_nasabah + 1, 6, '0', STR_PAD_LEFT); // ex: 000001

        // 4. Gabungkan semua jadi nomor rekening
        $norek = $kode_kantor . $tanggal_lengkap . $urutan; // contoh: 01140525000001

        // 5. Return sebagai JSON
        echo json_encode(['norek' => $norek]);
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

        $getProv = file_get_contents("https://wilayah.id/api/provinces.json");
        $responseProv = json_decode($getProv, true);
        $getKab = file_get_contents("https://wilayah.id/api/regencies/" . $nasabah->provinsi . ".json");
        $responseKab = json_decode($getKab, true);
        $getKec = file_get_contents("https://wilayah.id/api/districts/" . $nasabah->kabupaten . ".json");
        $responseKec = json_decode($getKec, true);
        $getKel = file_get_contents("https://wilayah.id/api/villages/" . $nasabah->kecamatan . ".json");
        $responseKel = json_decode($getKel, true);

        $kategori = $this->Kategori_model->get_data();
        $pegawai = $this->Pegawai_model->get_data();

        $data = [
            'nasabah' => $nasabah,
            'desa' => $nasabah->desa,
            'kecamatan' => $nasabah->kecamatan,
            'kabupaten' => $nasabah->kabupaten,
            'provinsi' => $nasabah->provinsi,
            'provList' => $responseProv['data'],
            'kabList' => $responseKab['data'],
            'kecList' => $responseKec['data'],
            'kelList' => $responseKel['data'],
            'jenistabungan' => $kategori,
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
            $provinsi           = $input->post('provinsi');
            $kabupaten          = $input->post('kabupaten');
            $kecamatan          = $input->post('kecamatan');
            $desa               = $input->post('desa');
            $rt                 = $input->post('rt');
            $rw                 = $input->post('rw');
            $alamat             = $input->post('alamat');
            $telp               = $input->post('telp');
            $jenis_tabungan     = $input->post('jenistabungan_id');
            $no_rekening        = $input->post('nomor_rekening');
            $pegawai_id         = $input->post('pegawai_id');

            // Validasi
            $nasabah = $this->Nasabah_model->get_data_by_id($id);
            if ($nasabah->nik == $nik) {
                $this->form_validation->set_rules('nik', 'NIK', 'required', [
                    'required'   => 'NIK wajib diisi.',
                ]);
            } else {
                $this->form_validation->set_rules('nik', 'NIK', 'required|is_unique[tbnasabah.nik]', [
                    'required'   => 'NIK wajib diisi.',
                    'is_unique'  => 'NIK sudah terdaftar.'
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
            $this->form_validation->set_rules('provinsi', 'Provinsi', 'required', [
                'required'   => 'Provinsi tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('kabupaten', 'Kabupaten', 'required', [
                'required'   => 'Kabupaten tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('kecamatan', 'Kecamatan', 'required', [
                'required'   => 'Kecamatan tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('desa', 'Desa', 'required', [
                'required'   => 'Desa tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('alamat', 'Alamat', 'required', [
                'required'   => 'Alamat tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('telp', 'No Telepon', 'required|numeric|min_length[10]', [
                'required'   => 'No Telepon tidak boleh kosong.',
                'numeric'    => 'No Telepon harus berupa angka.',
                'min_length' => 'No Telepon minimal 10 karakter.'
            ]);
            $this->form_validation->set_rules('jenistabungan_id', 'Jenis Tabungan', 'required', [
                'required'   => 'Jenis Tabungan tidak boleh kosong.'
            ]);
            $this->form_validation->set_rules('nomor_rekening', 'Nomor Rekening', 'required', [
                'required'   => 'Nomor Rekening tidak boleh kosong.'
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
                        'errornomor_rekening'   => form_error('nomor_rekening'),
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
                    'provinsi'          => $provinsi,
                    'kabupaten'         => $kabupaten,
                    'kecamatan'         => $kecamatan,
                    'desa'              => $desa,
                    'rt'                => $rt ?: '000',
                    'rw'                => $rw ?: '000',
                    'alamat'            => $alamat,
                    'telp'              => $telp,
                    'jenistabungan_id'  => $jenis_tabungan,
                    'no_rekening'       => $no_rekening,
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

        $getProv = json_decode(file_get_contents("https://wilayah.id/api/provinces.json"), true);
        $nama_provinsi = array_column($getProv['data'], 'name', 'code')[$nasabah->provinsi] ?? '-';

        $getKab = json_decode(file_get_contents("https://wilayah.id/api/regencies/" . $nasabah->provinsi . ".json"), true);
        $nama_kabupaten = array_column($getKab['data'], 'name', 'code')[$nasabah->kabupaten] ?? '-';

        $getKec = json_decode(file_get_contents("https://wilayah.id/api/districts/" . $nasabah->kabupaten . ".json"), true);
        $nama_kecamatan = array_column($getKec['data'], 'name', 'code')[$nasabah->kecamatan] ?? '-';

        $getKel = json_decode(file_get_contents("https://wilayah.id/api/villages/" . $nasabah->kecamatan . ".json"), true);
        $nama_desa = array_column($getKel['data'], 'name', 'code')[$nasabah->desa] ?? '-';


        $kategori = $this->Kategori_model->get_data_by_id($nasabah->jenistabungan_id);


        $data = [
            'nasabah'   => $nasabah,
            'desa'      => $nasabah->desa,
            'kecamatan' => $nasabah->kecamatan,
            'kabupaten' => $nasabah->kabupaten,
            'provinsi' => $nasabah->provinsi,
            'nama_provinsi' => $nama_provinsi,
            'nama_kabupaten' => $nama_kabupaten,
            'nama_kecamatan' => $nama_kecamatan,
            'nama_desa' => $nama_desa,
            'jenis_tabungan' => $kategori,
            'level' => $this->session->userdata('level'),
        ];
        $parser = [
            'judul' => "<i class='fa fa-user'></i> Nasabah",
            'isi'   => $this->load->view('nasabah/detail', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }
}
