<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pegawai extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Pegawai_model');
        // $this->load->model('Users_model');
        // $allowed_roles = ['Admin'];
        // $level = $this->session->userdata('level');
        // if (!in_array($level, $allowed_roles)) {
        //     show_404();
        // }
    }
    public function index()
    {
        $parser = [
            'judul' => "<i class='fa fa-users'></i> Pegawai",
            'isi'   => $this->load->view('pegawai/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetchData()
    {
        if ($this->input->is_ajax_request() == true) {
            $list = $this->Pegawai_model->get_datatables();
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->nik;
                $row[] = $field->nama_lengkap;
                $row[] = $field->telp;
                $row[] = $field->jabatan;
                $row[] = "<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location='penghuni/edit/$field->nik'\"><i class='fa fa-edit fa-fw'></i></button>
                            <button class=\"btn btn-danger\" onclick=\"deleteItem('" . $field->id . "', '" . $field->nama_lengkap . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>
                            <button class=\"btn btn-secondary\"><i class='fa fa-info fa-fw'></i></button>";;
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

    public function simpanData()
    {
        if ($this->input->is_ajax_request()) {
            $nik = $this->input->post('nik');
            $nama_lengkap = $this->input->post('nama_lengkap');
            $tempat_lahir = $this->input->post('tempat_lahir');
            $tanggal_lahir = $this->input->post('tanggal_lahir');
            $kelamin = $this->input->post('jenis_kelamin');
            $provinsi = $this->input->post('provinsi');
            $kabupaten = $this->input->post('kabupaten');
            $kecamatan = $this->input->post('kecamatan');
            $desa = $this->input->post('desa');
            $rt = $this->input->post('rt');
            $rw = $this->input->post('rw');
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

            $this->form_validation->set_rules('provinsi', 'Provinsi', 'required', [
                'required' => 'Provinsi harus dipilih.'
            ]);

            $this->form_validation->set_rules('kabupaten', 'Kabupaten', 'required', [
                'required' => 'Kabupaten harus dipilih.'
            ]);

            $this->form_validation->set_rules('kecamatan', 'Kecamatan', 'required', [
                'required' => 'Kecamatan harus dipilih.'
            ]);

            $this->form_validation->set_rules('desa', 'Kelurahan/Desa', 'required', [
                'required' => 'Kelurahan/Desa harus dipilih.'
            ]);

            $this->form_validation->set_rules('rt', 'RT', 'required', [
                'required' => 'RT harus diisi.'
            ]);

            $this->form_validation->set_rules('rw', 'RW', 'required', [
                'required' => 'RW harus diisi.'
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
                        'errorProvinsi'         => form_error('provinsi'),
                        'errorKabupaten'        => form_error('kabupaten'),
                        'errorKecamatan'        => form_error('kecamatan'),
                        'errorDesa'             => form_error('desa'),
                        'errorRt'               => form_error('rt'),
                        'errorRw'               => form_error('rw'),
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
                    'rt'                => $rt,
                    'rw'                => $rw,
                    'desa'              => $desa,
                    'kecamatan'         => $kecamatan,
                    'kabupaten'         => $kabupaten,
                    'provinsi'          => $provinsi,
                    'tempat_lahir'      => $tempat_lahir,
                    'tanggal_lahir'     => $tanggal_lahir,
                    'jenis_kelamin'     => $kelamin,
                    'agama'             => $agama,
                    'telp'              => $telp,
                    'jabatan'           => $jabatan
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
}
