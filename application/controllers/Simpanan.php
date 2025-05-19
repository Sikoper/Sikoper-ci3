<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Simpanan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
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
            'judul' => "<i class='fa fa-money-check'></i> Simpanan",
            'isi'   => $this->load->view('simpanan/index', '', TRUE)
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
            $list = $this->Simpanan_model->get_datatables();
            $data = array();
            $no = $_POST['start'];



            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->nama_nasabah;
                $row[] = $field->no_rekening;
                $row[] = $field->telp_nasabah;
                $row[] = $field->jenis_tabungan;
                $row[] = "<button type=\"button\" class=\"btn btn-success\" onclick=\"window.location='simpanan/edit/" . safe_base64_encode($field->no_rekening) . "'\"><i class='fa fa-edit fa-fw'></i></button>
                            <button class=\"btn btn-danger\" onclick=\"deleteItem('" . $field->id . "', '" . $field->no_rekening . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>
                            <button class=\"btn btn-secondary\"onclick=\"window.location='simpanan/detail/" . safe_base64_encode($field->no_rekening) . "'\"><i class='fa fa-info fa-fw'></i></button>";;
                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Simpanan_model->count_all(),
                "recordsFiltered" => $this->Simpanan_model->count_filtered(),
                "data" => $data,
            );

            echo json_encode($output);
        } else {
            exit('Maaf data tidak bisa ditampilkan');
        }
    }

    public function add()
    {
        $data = [
            'jenis' => $this->Kategori_model->get_data(),
            'pegawai' => $this->Pegawai_model->get_data(),
            'level' => $this->session->userData('level')
        ];

        $parser = [
            'judul' => "<i class='fa fa-money-check'></i> Simpanan",
            'isi'   => $this->load->view('simpanan/addForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function simpanData()
    {
        if ($this->input->is_ajax_request()) {
            $allowed_roles = ['Admin', 'Direktur', 'Pegawai'];
            $level = $this->session->userdata('level');

            if (!in_array($level, $allowed_roles)) {
                $msg = [
                    'error' => 'Unauthorized 403'
                ];
                echo json_encode($msg);
                return;
            }

            $tanggal_simpanan = $this->input->post('tanggal_simpanan');
            $nasabah = $this->input->post('nasabah');
            $jenis_tabungan = $this->input->post('jenis_tabungan');
            $pegawai = $this->input->post('pegawai_id');
            $jumlah_simpanan = str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_simpanan'));
            $durasi = $this->input->post('durasi');
            $nama_ahli_waris = $this->input->post('nama_ahli_waris');
            $kontak_ahli_waris = $this->input->post('kontak_ahli_waris');
            $hubungan_ahli_waris = $this->input->post('hubungan_ahli_waris');
            $signature_input = $this->input->post('signature_input');
            $no_rekening = $this->input->post('nomor_rekening');

            $this->form_validation->set_rules('tanggal_simpanan', 'Tanggal Simpanan', 'required', [
                'required'   => 'Tanggal simpanan wajib diisi.'
            ]);

            $this->form_validation->set_rules('nasabah', 'Nasabah', 'required', [
                'required'     => 'Nasabah tidak boleh kosong.'
            ]);

            $this->form_validation->set_rules('jenis_tabungan', 'Jenis Tabungan', 'required', [
                'required'     => 'Jenis tabungan harus diisi.',
            ]);

            $this->form_validation->set_rules('bunga', 'Bunga', 'required', [
                'required' => 'Bunga harus diisi.'
            ]);

            $this->form_validation->set_rules('biaya_registrasi', 'Biaya Registrasi', 'required', [
                'required' => 'Biaya Registrasi wajib diisi'
            ]);

            $jenis_data = $this->Kategori_model->get_data_by_id($jenis_tabungan);

            if (!empty($jenis_data)) {
                $validasi_deposito = $jenis_data->nama;

                if ($validasi_deposito == 'Deposito') {
                    $this->form_validation->set_rules('durasi', 'Jangka waktu', 'required', [
                        'required' => 'Jangka waktu deposito wajib diisi'
                    ]);
                }

                $minimum_jumlah = $jenis_data->simpanan_awal;

                $this->form_validation->set_rules('jumlah_simpanan', 'Jumlah Simpanan', 'required|callback_check_minimum[' . $minimum_jumlah . ']', [
                    'required' => 'Jumlah simpanan harus diisi.',
                ]);
            }

            $this->form_validation->set_rules('jumlah_simpanan', 'Jumlah Simpanan', 'required', [
                'required' => 'Jumlah simpanan harus diisi.',
            ]);

            $this->form_validation->set_rules('simpanan_awal', 'Simpanan awal', 'required', [
                'required' => 'Simpanan awal harus diisi.'
            ]);

            $this->form_validation->set_rules('pengendapan', 'Pengendapan', 'required', [
                'required' => 'Pengendapan harus diisi.'
            ]);

            $this->form_validation->set_rules('jenis_denda', 'Jenis Denda', 'required', [
                'required' => 'Jenis denda harus diisi.'
            ]);

            $this->form_validation->set_rules('jumlah_denda', 'Jumlah Denda', 'required', [
                'required' => 'Jumlah denda harus diisi.'
            ]);

            $this->form_validation->set_rules('signature_input', 'Tanda tangan', 'required', [
                'required' => 'Tanda tangan harus diisi.'
            ]);

            $this->form_validation->set_rules('nomor_rekening', 'Nomer Rekening', 'required|is_unique[tbsimpanan.no_rekening]', [
                'required' => 'Nomer rekening harus diisi.',
                'is_unique' => 'Nomer rekening sudah terdaftar.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorTanggalSimpanan'  => form_error('tanggal_simpanan'),
                        'errorNasabah'          => form_error('nasabah'),
                        'errorJenisTabungan'    => form_error('jenis_tabungan'),
                        'errorBunga'            => form_error('bunga'),
                        'errorBiayaRegistrasi'  => form_error('biaya_registrasi'),
                        'errorSimpananAwal'     => form_error('simpanan_awal'),
                        'errorPengendapan'      => form_error('pengendapan'),
                        'errorJenisDenda'       => form_error('jenis_denda'),
                        'errorJumlahDenda'      => form_error('jumlah_denda'),
                        'errorJumlahSimpanan'   => form_error('jumlah_simpanan'),
                        'errorTandaTangan'      => form_error('signature_input'),
                        'errorNoRekening'       => form_error('nomor_rekening'),
                        'errorDurasi'           => form_error('durasi'),
                    ]
                ];
            } else {
                $upload_path = FCPATH . 'assets/uploads/nasabah/tanda-tangan/';
                $data_nasabah = $this->Nasabah_model->get_data_by_id($nasabah);
                $file_name = $no_rekening . '-' . $data_nasabah->nik . '-ttd.png';

                if ($signature_input) {
                    $imgData = explode(',', $signature_input);
                    $imageDecoded = base64_decode($imgData[1]);
                    file_put_contents($upload_path . $file_name, $imageDecoded);
                    $path_ttd = 'assets/uploads/nasabah/tanda-tangan/' . $file_name;
                }

                $data = [
                    'tanggal_simpanan' => $tanggal_simpanan,
                    'no_rekening' => $no_rekening,
                    'nasabah_id' => $nasabah,
                    'pegawai_id' => $pegawai,
                    'jenistabungan_id' => $jenis_tabungan,
                    'jumlah_simpanan' => $jumlah_simpanan,
                    'durasi' => $durasi,
                    'nama_ahli_waris' => $nama_ahli_waris,
                    'telp_ahli_waris' => $kontak_ahli_waris,
                    'hubungan_ahli_waris' => $hubungan_ahli_waris,
                    'tanda_tangan' => $path_ttd,
                ];

                // echo '<pre>';
                // print_r($data);
                // exit;

                $inserted = $this->Simpanan_model->insert_data($data);
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
            $id = $this->input->post('id');
            $data = $this->Simpanan_model->get_data_by_id($id);

            if (!empty($data->tanda_tangan)) {
                $foto = $data->tanda_tangan;
                if (file_exists($foto)) {
                    unlink(FCPATH . $foto);
                }
            }

            $this->Simpanan_model->delete_data($id);

            $msg = [
                'success' => 'Data berhasil dihapus'
            ];

            echo json_encode($msg);
        }
    }

    public function edit($encoded_rek = null)
    {
        $allowed_roles = ['Admin', 'Direktur', 'Pegawai'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }

        function safe_base64_decode($string)
        {
            return base64_decode(strtr($string, '-_?', '+/='));
        }

        if ($encoded_rek === null) {
            show_custom_404();
            return;
        }

        $no_rekening = safe_base64_decode($encoded_rek);;
        $simpanan = $this->Simpanan_model->get_data_by_norek($no_rekening);
        $nasabah = $this->Nasabah_model->get_data_by_id($simpanan->nasabah_id);   

        if (!$simpanan) {
            show_custom_404();
            return;
        }

        $data = [
            'simpanan' => $simpanan,
            'nasabah' => $nasabah,
            'jenis' => $this->Kategori_model->get_data(),
            'pegawai' => $this->Pegawai_model->get_data(),
            'level' => $this->session->userData('level'),
        ];

        $parser = [
            'judul' => "<i class='fa fa-money-check'></i> Simpanan",
            'isi'   => $this->load->view('simpanan/editForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function getJenisData()
    {
        if ($this->input->is_ajax_request() == true) {
            $id = $this->input->post('id');

            $kategori = $this->Kategori_model->get_data_by_id($id);

            $data = [
                'kategori' => $kategori
            ];

            echo json_encode($data);
        } else {
            show_custom_404();
        }
    }

    public function generate_norek()
    {
        if ($this->input->is_ajax_request() == true) {
            $kategori = $this->input->post('kategori');

            $kode_kantor = '01';

            $tanggal = date('d');
            $bulan = date('m');
            $tahun   = date('y');
            $tanggal_lengkap = $tanggal . $bulan . $tahun;

            $jumlah_nasabah = $this->Simpanan_model->count_all_data();
            $urutan = str_pad($jumlah_nasabah + 1, 4, '0', STR_PAD_LEFT);

            $norek = $kode_kantor . '0' . $kategori . $tanggal_lengkap . $urutan;

            echo json_encode(['norek' => $norek]);
        }
    }

    public function check_minimum($jumlah_simpanan, $minimum_jumlah)
    {
        $jumlah_simpanan = str_replace('.', '', $jumlah_simpanan);
        $jumlah_simpanan = floatval($jumlah_simpanan);

        if ($jumlah_simpanan < $minimum_jumlah) {
            $this->form_validation->set_message('check_minimum', 'Jumlah simpanan minimum adalah Rp ' . number_format($minimum_jumlah, 0, ',', '.'));
            return FALSE;
        }
        return TRUE;
    }
}
