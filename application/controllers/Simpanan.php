<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Simpanan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Tabungan_model');
        $this->load->model('Nasabah_model');
        $this->load->model('Kategori_model');
        $this->load->model('Pegawai_model');
        $this->load->model('Penarikan_model');
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
        $parser = [
            'judul' => "Data Tabungan",
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
            $level = $this->session->userdata('level');

            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->nama_nasabah;
                $row[] = $field->no_rekening;
                $row[] = $field->telp_nasabah;
                $row[] = number_format($field->jumlah_simpanan, 0, ',', '.');
                if ($level == 'Admin') {
                    $row[] = '<button type="button" class="btn btn-success" onclick="window.location=\'simpanan/edit/' . safe_base64_encode($field->no_rekening) . '\'">
                                    <i class="fa fa-edit fa-fw"></i>
                                </button>
                                <button type="button" class="btn btn-danger" onclick="deleteItem(\'' . $field->id . '\', \'' . $field->no_rekening . '\')">
                                    <i class="fa fa-trash fa-fw"></i>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="window.location=\'simpanan/detail/' . safe_base64_encode($field->no_rekening) . '\'">
                                    <i class="fa fa-info fa-fw"></i>
                                </button>
                                <button type="button" class="btn btn-primary" onclick="printNasabah(\'' . $field->id . '\', \'' . $field->nama_nasabah . '\')">
                                    <i class="fa fa-file"></i>
                                </button>';
                } else {
                    $row[] = '
                            <button type="button" class="btn btn-secondary" onclick="window.location=\'simpanan/detail/' . safe_base64_encode($field->no_rekening) . '\'">
                                <i class="fa fa-info fa-fw"></i>
                            </button>
                            <button type="button" class="btn btn-primary" onclick="printNasabah(\'' . $field->id . '\', \'' . $field->nama_nasabah . '\')">
                                <i class="fa fa-file"></i>
                            </button>';
                }
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
        $query_jenis = $this->db
            ->select('nama, id')
            ->from('tbjenistabungan')
            ->like('nama', 'tabungan')
            ->get();

        $jenistabungan = $query_jenis->row();

        $data = [
            'jenis' => $jenistabungan,
            'pegawai' => $this->Pegawai_model->get_data(),
            'level' => $this->session->userData('level')
        ];

        $parser = [
            'judul' => " Form Buka Tabungan Baru ",
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
            // $durasi = $this->input->post('durasi');
            // $nama_ahli_waris = $this->input->post('nama_ahli_waris');
            // $kontak_ahli_waris = $this->input->post('kontak_ahli_waris');
            // $hubungan_ahli_waris = $this->input->post('hubungan_ahli_waris');
            $no_rekening = $this->session->userdata('temp_no_rekening');

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
            } else if (empty($jenis_data)) {
                $this->form_validation->set_rules('jumlah_simpanan', 'Jumlah Simpanan', 'required', [
                    'required' => 'Jumlah simpanan harus diisi.',
                ]);
            }

            $this->form_validation->set_rules('simpanan_awal', 'Simpanan awal', 'required', [
                'required' => 'Simpanan awal harus diisi.'
            ]);

            $this->form_validation->set_rules('pengendapan', 'Pengendapan', 'required', [
                'required' => 'Pengendapan harus diisi.'
            ]);

            // $this->form_validation->set_rules('jenis_denda', 'Jenis Denda', 'required', [
            //     'required' => 'Jenis denda harus diisi.'
            // ]);

            // $this->form_validation->set_rules('jumlah_denda', 'Jumlah Denda', 'required', [
            //     'required' => 'Jumlah denda harus diisi.'
            // ]);

            $this->form_validation->set_rules('pegawai_id', 'Pegawai', 'required', [
                'required' => 'Pegawai sebagai penanggung jawab wajib dipilih.',
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
                        'errorPegawai'          => form_error('pegawai_id'),
                        // 'errorJenisDenda'       => form_error('jenis_denda'),
                        // 'errorJumlahDenda'      => form_error('jumlah_denda'),
                        'errorJumlahSimpanan'   => form_error('jumlah_simpanan'),
                        'errorNoRekening'       => form_error('nomor_rekening'),
                        'errorDurasi'           => form_error('durasi'),
                    ]
                ];
            } else {

                $data = [
                    'tanggal_simpanan' => $tanggal_simpanan,
                    'no_rekening' => $no_rekening,
                    'nasabah_id' => $nasabah,
                    'pegawai_id' => $pegawai,
                    'jenistabungan_id' => $jenis_tabungan,
                    'jumlah_simpanan' => $jumlah_simpanan,
                    // 'durasi' => $durasi,
                    // 'nama_ahli_waris' => $nama_ahli_waris,
                    // 'telp_ahli_waris' => $kontak_ahli_waris,
                    // 'hubungan_ahli_waris' => $hubungan_ahli_waris,
                ];

                // echo '<pre>';
                // print_r($data);
                // exit;

                $this->db->trans_start();

                // Insert main simpanan
                $inserted = $this->Simpanan_model->insert_data($data);

                if ($inserted) {
                    $simpanan_id = $this->db->insert_id();

                    $detail_setoran = [
                        'simpanan_id'      => $simpanan_id,
                        'tanggal_setoran'  => $tanggal_simpanan,
                        'jumlah_setoran'   => $jumlah_simpanan,
                        'pegawai_id'       => $pegawai,
                    ];

                    $this->db->insert('tbdetail_simpanan', $detail_setoran);
                }

                $this->db->trans_complete();
                $this->session->unset_userdata('temp_no_rekening');

                if ($this->db->trans_status() === FALSE) {
                    $msg = ['error' => 'Gagal menyimpan data tabungan simpanan dan detail.'];
                } else {
                    $msg = ['success' => 'Data tabungan berhasil ditambahkan.'];
                    push_event('simpanan-channel', 'simpanan-event', ['message' => 'Simpanan baru ditambahkan!']);
                }
            }

            echo json_encode($msg);
        } else {
            redirect('unauthorized_403');
        }
    }

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');
            $hasil = $this->Simpanan_model->delete_data($id);

            if ($hasil) {
                // Jika model mengembalikan true (berhasil)
                $msg = [
                    'success' => 'Data simpanan dan seluruh riwayatnya berhasil dihapus.'
                ];
            } else {
                // Jika model mengembalikan false (gagal)
                $msg = [
                    'error' => 'Gagal menghapus data. Terjadi kesalahan pada database.'
                ];
            }

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

        $query_jenis = $this->db
            ->select('nama, id')
            ->from('tbjenistabungan')
            ->like('nama', 'tabungan')
            ->get();

        $jenistabungan = $query_jenis->row();

        $data = [
            'simpanan' => $simpanan,
            'nasabah' => $nasabah,
            'jenis' => $jenistabungan,
            'pegawai' => $this->Pegawai_model->get_data(),
            'level' => $this->session->userData('level'),
        ];

        $parser = [
            'judul' => "Form Edit Simpanan",
            'isi'   => $this->load->view('simpanan/editForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function updateData()
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

            $id = $this->input->post('id');
            $tanggal_simpanan = $this->input->post('tanggal_simpanan');
            $nasabah = $this->input->post('nasabah');
            $jenis_tabungan = $this->input->post('jenis_tabungan');
            $pegawai = $this->input->post('pegawai_id');
            $jumlah_simpanan = str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_simpanan'));
            // $durasi = $this->input->post('durasi');
            // $nama_ahli_waris = $this->input->post('nama_ahli_waris');
            // $kontak_ahli_waris = $this->input->post('kontak_ahli_waris');
            // $hubungan_ahli_waris = $this->input->post('hubungan_ahli_waris');
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
            } else if (empty($jenis_data)) {
                $this->form_validation->set_rules('jumlah_simpanan', 'Jumlah Simpanan', 'required', [
                    'required' => 'Jumlah simpanan harus diisi.',
                ]);
            }

            $this->form_validation->set_rules('simpanan_awal', 'Simpanan awal', 'required', [
                'required' => 'Simpanan awal harus diisi.'
            ]);

            $this->form_validation->set_rules('pengendapan', 'Pengendapan', 'required', [
                'required' => 'Pengendapan harus diisi.'
            ]);

            // $this->form_validation->set_rules('jenis_denda', 'Jenis Denda', 'required', [
            //     'required' => 'Jenis denda harus diisi.'
            // ]);

            // $this->form_validation->set_rules('jumlah_denda', 'Jumlah Denda', 'required', [
            //     'required' => 'Jumlah denda harus diisi.'
            // ]);

            $Simpanan = $this->Simpanan_model->get_data_by_id($id);
            if ($Simpanan->no_rekening == $no_rekening) {
                $this->form_validation->set_rules('nomor_rekening', 'Nomer Rekening', 'required', [
                    'required' => 'Nomer rekening harus diisi.',
                ]);
            } else {
                $this->form_validation->set_rules('nomor_rekening', 'Nomer Rekening', 'required|is_unique[tbsimpanan.no_rekening]', [
                    'required' => 'Nomer rekening harus diisi.',
                    'is_unique' => 'Nomer rekening sudah terdaftar.'
                ]);
            }

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
                        // 'errorJenisDenda'       => form_error('jenis_denda'),
                        // 'errorJumlahDenda'      => form_error('jumlah_denda'),
                        'errorJumlahSimpanan'   => form_error('jumlah_simpanan'),
                        'errorNoRekening'       => form_error('nomor_rekening'),
                        // 'errorDurasi'           => form_error('durasi'),
                    ]
                ];
            } else {
                $data = [
                    'tanggal_simpanan' => $tanggal_simpanan,
                    'no_rekening' => $no_rekening,
                    'nasabah_id' => $nasabah,
                    'pegawai_id' => $pegawai,
                    'jenistabungan_id' => $jenis_tabungan,
                    'jumlah_simpanan' => $jumlah_simpanan,
                    // 'durasi' => $durasi,
                    // 'nama_ahli_waris' => $nama_ahli_waris,
                    // 'telp_ahli_waris' => $kontak_ahli_waris,
                    // 'hubungan_ahli_waris' => $hubungan_ahli_waris,
                ];

                // echo '<pre>';
                // print_r($data);
                // exit;
                $this->db->trans_start();
                $updated = $this->Simpanan_model->edit_data($id, $data);

                $first_setoran = $this->Setoran_model->get_first_by_simpanan_id($id);
                if ($first_setoran) {
                    $this->Setoran_model->edit_data($first_setoran->id, [
                        'jumlah_setoran' => $jumlah_simpanan
                    ]);
                }
                $this->Simpanan_model->sync_total_simpanan($id);
                $this->db->trans_complete();


                if ($updated) {
                    $msg = ['success' => 'Data tabungan berhasil dirubah.'];
                    push_event('simpanan-channel', 'simpanan-event', ['message' => 'Simpanan berhasil diubah!']);
                } else {
                    $msg = ['error' => 'Gagal menyimpan perubahan data tabungan.'];
                }
            }

            echo json_encode($msg);
        }
    }

    public function detail($encoded_rek = null)
    {
        $allowed_roles = ['Admin', 'Direktur', 'Pegawai'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }

        if (!function_exists('safe_base64_decode_detail_simpanan')) {
            function safe_base64_decode_detail_simpanan($string)
            {
                $data = strtr($string, '-_?', '+/=');
                $mod4 = strlen($data) % 4;
                if ($mod4) {
                    $data .= substr('====', $mod4);
                }
                return base64_decode($data);
            }
        }

        if ($encoded_rek === null) {
            show_404();
            return;
        }

        $no_rekening = safe_base64_decode_detail_simpanan($encoded_rek);

        if ($no_rekening === false || empty(trim($no_rekening))) {
            show_404("Nomor rekening tidak valid.");
            return;
        }

        $simpanan = $this->Simpanan_model->get_data_by_norek($no_rekening);

        if (!$simpanan) {
            show_404("Data simpanan tidak ditemukan untuk nomor rekening: " . html_escape($no_rekening));
            return;
        }

        $nasabah = $this->Nasabah_model->get_data_by_id($simpanan->nasabah_id);
        $jenis_tabungan = $this->Kategori_model->get_data_by_id($simpanan->jenistabungan_id);
        $pegawai = $this->Pegawai_model->get_data_by_id($simpanan->pegawai_id);

        $this->load->model('Penarikan_model');
        $akumulasi_data_penarikan = $this->Penarikan_model->get_akumulasi_penarikan_by_simpanan($simpanan->id);

        if (!function_exists('format_durasi')) {
            function format_durasi($bulan)
            {
                if ($bulan === null || !is_numeric($bulan) || $bulan <= 0) {
                    return '-';
                }
                $bulan_int = intval($bulan);
                $tahun = floor($bulan_int / 12);
                $sisa_bulan = $bulan_int % 12;

                $output_parts = [];
                if ($tahun > 0) {
                    $output_parts[] = "{$tahun} tahun";
                }
                if ($sisa_bulan > 0) {
                    $output_parts[] = "{$sisa_bulan} bulan";
                }

                if (empty($output_parts)) {
                    return "{$bulan_int} bulan";
                }

                $output_str = implode(' ', $output_parts);
                if ($bulan_int >= 12) {
                    $output_str .= " (Total: {$bulan_int} bulan)";
                }
                return $output_str;
            }
        }

        $data = [
            'simpanan'        => $simpanan,
            'nasabah'         => $nasabah,
            'jenis'           => $jenis_tabungan,
            'pegawai'         => $pegawai,
            'level'           => $this->session->userdata('level'),
            'formatted_durasi' => format_durasi($simpanan->durasi ?? null),
            'total_akumulasi_penarikan' => $akumulasi_data_penarikan ? ($akumulasi_data_penarikan->total_akumulasi_penarikan ?? 0) : 0,
            'total_akumulasi_denda'     => $akumulasi_data_penarikan ? ($akumulasi_data_penarikan->total_akumulasi_denda ?? 0) : 0,
        ];

        $parser = [
            'judul' => "<a href=\"" . base_url('simpanan') . "\" class=\"btn btn-warning\">
                        <i class=\"fa fa-backward\"></i> Kembali
                    </a> 
                    ",
            'isi'   => $this->load->view('simpanan/detail', $data, TRUE)
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

    public function create_nomer_rekening()
    {
        if ($this->input->is_ajax_request()) {
            if (!$this->session->userdata('temp_no_rekening')) {
                $this->db->set(null, false)->insert('tbrekening_tabungan');
                $no_rekening = $this->db->insert_id();

                $this->db->where('id <', $no_rekening)->delete('tbrekening_tabungan');

                $this->session->set_userdata('temp_no_rekening', $no_rekening);
            }

            echo json_encode(['no_rekening' => $this->session->userdata('temp_no_rekening')]);
        } else {
            redirect('unauthorized_403');
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

    public function print_nasabah()
    {
        $id = $this->input->get('id');
        $simpanan = $this->Simpanan_model->get_data_by_id($id);
        $nasabah = $this->Nasabah_model->get_data_by_id($simpanan->nasabah_id);
        $jenis = $this->Kategori_model->get_data_by_id($simpanan->jenistabungan_id);

        $data = [
            'nama_nasabah' => $nasabah->nama_lengkap,
            'no_rekening' => $simpanan->no_rekening,
            'jenis_tabungan' => $jenis->nama,
            'tanggal_simpanan' => $simpanan->tanggal_simpanan
        ];

        $html = $this->load->view('simpanan/cetak_nasabah', $data, true);

        $this->load->library('dompdf_lib');
        $this->dompdf_lib->loadHtml($html);
        $this->dompdf_lib->setPaper([0, 0, 396.85, 283.46], 'landscape');
        $this->dompdf_lib->render();

        $filename = "nasabah_" . $nasabah->nama_lengkap . "_" . $simpanan->no_rekening . ".pdf";
        $this->dompdf_lib->stream($filename, false);
    }

    public function laporan()
    {
        function safe_base64_decode($string)
        {
            return base64_decode(strtr($string, '-_?', '+/='));
        }

        $id = $this->input->get('id');
        $no_rek = safe_base64_decode($id);
        $simpanan = $this->Simpanan_model->get_data_by_norek($no_rek);

        $data = [
            'simpanan' => $simpanan
        ];

        $parser = [
            'judul' => "<i class='fa fa-money-check'></i> Laporan simpanan",
            'isi'   => $this->load->view('simpanan/laporan', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function print_laporan()
    {
        // 1. Get parameters from URL
        $id = $this->input->get('id');
        $tanggal_mulai = $this->input->get('tanggal_mulai');
        $tanggal_akhir = $this->input->get('tanggal_akhir');
        $jenis_laporan = $this->input->get('jenis_laporan');

        // Default jenis_laporan ke '3' (Semua Transaksi) jika kosong
        if (empty($jenis_laporan)) {
            $jenis_laporan = '3';
        }

        // 2. Fetch main account and customer data
        $tabungan = $this->Tabungan_model->get_data_by_id($id);
        if (!$tabungan) {
            show_error("Error: Data tabungan tidak ditemukan.", 404);
            return;
        }

        // You can fetch the full nasabah object if needed,
        // but this works if get_data_by_id already joins the nasabah table.
        $nasabah = $this->Nasabah_model->get_data_by_id($tabungan->nasabah_id);
        if (!$nasabah) {
            show_error("Error: Data nasabah tidak ditemukan.", 404);
            return;
        }

        // 3. Call the model function. It correctly handles null dates for the query.
        $rekening_data = $this->Tabungan_model->get_transaksi_rekening_koran($tabungan->id, $tanggal_mulai, $tanggal_akhir, $jenis_laporan);

        // 4. Prepare the complete data array for the view
        // FIX: Pass the original date variables directly. The view will handle the display logic.
        $data = [
            'tabungan'      => $tabungan,
            'nasabah'       => $nasabah,
            'rekening'      => $rekening_data, // Pass the entire result array
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_akhir' => $tanggal_akhir,
        ];

        // 5. Load view and generate PDF
        $html = $this->load->view('simpanan/cetak_laporan', $data, true);

        $this->load->library('dompdf_lib');
        $this->dompdf_lib->loadHtml($html);
        $this->dompdf_lib->setPaper('A4', 'portrait');
        $this->dompdf_lib->render();

        $filename = "Rekening_Koran_" . str_replace(' ', '_', $nasabah->nama_lengkap) . "_" . $tabungan->no_rekening . ".pdf";

        // The default behavior is to prompt for download.
        $this->dompdf_lib->stream($filename);
    }
}
