<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Deposito extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Deposito_model');
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
            'judul' => "Data Deposito",
            'isi'   => $this->load->view('deposito/index', '', TRUE)
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
            $list = $this->Deposito_model->get_datatables();
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
                $row[] = number_format($field->jumlah_deposito, 0, ',', '.');
                if ($level == 'Admin') {
                    $row[] = '<button type="button" class="btn btn-success" onclick="window.location=\'deposito/edit/' . safe_base64_encode($field->no_rekening) . '\'">
                                    <i class="fa fa-edit fa-fw"></i>
                                </button>
                                <button type="button" class="btn btn-danger" onclick="deleteItem(\'' . $field->id . '\', \'' . $field->no_rekening . '\')">
                                    <i class="fa fa-trash fa-fw"></i>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="window.location=\'deposito/detail/' . safe_base64_encode($field->no_rekening) . '\'">
                                    <i class="fa fa-info fa-fw"></i>
                                </button>
                                <button type="button" class="btn btn-primary" onclick="printSertifikat(\'' . $field->id . '\', \'' . $field->nama_nasabah . '\')">
                                    <i class="fa fa-file"></i>
                                </button>';
                } else {
                    $row[] = '
                            <button type="button" class="btn btn-secondary" onclick="window.location=\'deposito/detail/' . safe_base64_encode($field->no_rekening) . '\'">
                                <i class="fa fa-info fa-fw"></i>
                            </button>
                            <button type="button" class="btn btn-primary" onclick="printSertifikat(\'' . $field->id . '\', \'' . $field->nama_nasabah . '\')">
                                <i class="fa fa-file"></i>
                            </button>';
                }
                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Deposito_model->count_all(),
                "recordsFiltered" => $this->Deposito_model->count_filtered(),
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
            ->like('nama', 'deposito')
            ->get();

        $jenistabungan = $query_jenis->row();

        $data = [
            'jenis' => $jenistabungan,
            'pegawai' => $this->Pegawai_model->get_data(),
            'level' => $this->session->userData('level')
        ];

        $parser = [
            'judul' => " Form Buka Deposito Baru ",
            'isi'   => $this->load->view('deposito/addForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function simpanData()
    {
        // if ($this->input->is_ajax_request()) {
            $allowed_roles = ['Admin', 'Direktur', 'Pegawai'];
            $level = $this->session->userdata('level');

            if (!in_array($level, $allowed_roles)) {
                $msg = [
                    'error' => 'Unauthorized 403'
                ];
                echo json_encode($msg);
                return;
            }

            $tanggal_deposito = $this->input->post('tanggal_deposito');
            $nasabah = $this->input->post('nasabah');
            $jenis_tabungan = $this->input->post('jenis_tabungan');
            $pegawai = $this->input->post('pegawai_id');
            $jumlah_deposito = str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_deposito'));
            $durasi = $this->input->post('durasi');
            $rate_bunga = $this->input->post('bunga');
            $nama_ahli_waris = $this->input->post('nama_ahli_waris');
            $kontak_ahli_waris = $this->input->post('kontak_ahli_waris');
            $hubungan_ahli_waris = $this->input->post('hubungan_ahli_waris');
            $no_rekening = $this->input->post('nomor_rekening');

            $this->form_validation->set_rules('tanggal_deposito', 'Tanggal Deposito', 'required', [
                'required'   => 'Tanggal deposito wajib diisi.'
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

                $this->form_validation->set_rules('jumlah_deposito', 'Jumlah Deposito', 'required|callback_check_minimum[' . $minimum_jumlah . ']', [
                    'required' => 'Jumlah deposito harus diisi.',
                ]);
            } else if (empty($jenis_data)) {
                $this->form_validation->set_rules('jumlah_deposito', 'Jumlah Deposito', 'required', [
                    'required' => 'Jumlah deposito harus diisi.',
                ]);
            }

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

            $this->form_validation->set_rules('pegawai_id', 'Pegawai', 'required', [
                'required' => 'Pegawai sebagai penanggung jawab wajib dipilih.',
            ]);

            $this->form_validation->set_rules('nomor_rekening', 'Nomer Rekening', 'required|is_unique[tbdeposito.no_rekening]', [
                'required' => 'Nomer rekening harus diisi.',
                'is_unique' => 'Nomer rekening sudah terdaftar.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorTanggalSimpanan'  => form_error('tanggal_deposito'),
                        'errorNasabah'          => form_error('nasabah'),
                        'errorJenisTabungan'    => form_error('jenis_tabungan'),
                        'errorBunga'            => form_error('bunga'),
                        'errorBiayaRegistrasi'  => form_error('biaya_registrasi'),
                        'errorSimpananAwal'     => form_error('simpanan_awal'),
                        'errorPengendapan'      => form_error('pengendapan'),
                        'errorPegawai'          => form_error('pegawai_id'),
                        'errorJenisDenda'       => form_error('jenis_denda'),
                        'errorJumlahDenda'      => form_error('jumlah_denda'),
                        'errorJumlahSimpanan'   => form_error('jumlah_deposito'),
                        'errorNoRekening'       => form_error('nomor_rekening'),
                        'errorDurasi'           => form_error('durasi'),
                    ]
                ];
            } else {

                $data = [
                    'tanggal_deposito' => $tanggal_deposito,
                    'no_rekening' => $no_rekening,
                    'nasabah_id' => $nasabah,
                    'pegawai_id' => $pegawai,
                    'jenistabungan_id' => $jenis_tabungan,
                    'jumlah_deposito' => $jumlah_deposito,
                    'durasi' => $durasi,
                    'rate_bunga' => $rate_bunga,
                    'nama_ahli_waris' => $nama_ahli_waris,
                    'telp_ahli_waris' => $kontak_ahli_waris,
                    'hubungan_ahli_waris' => $hubungan_ahli_waris,
                ];

                // echo '<pre>';
                // print_r($data);
                // exit;

                $this->db->trans_start();

                $this->Deposito_model->insert_data($data);

                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE) {
                    $msg = ['error' => 'Gagal menyimpan data deposito.'];
                } else {
                    $msg = ['success' => 'Data deposito berhasil ditambahkan.'];
                    push_event('deposito-channel', 'deposito-event', ['message' => 'Deposito baru ditambahkan!']);
                }
            }

            echo json_encode($msg);
        // } else {
        //     redirect('unauthorized_403');
        // }
    }

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');

            $has_penarikan = $this->db->get_where('tbpenarikan', ['simpanan_id' => $id])->num_rows();

            if ($has_penarikan > 0) {
                $msg = [
                    'error' => 'Data tidak bisa dihapus karena memiliki riwayat setoran atau penarikan.'
                ];
                echo json_encode($msg);
                return;
            }

            $this->Deposito_model->delete_data($id);

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
        $deposito = $this->Deposito_model->get_data_by_norek($no_rekening);
        $nasabah = $this->Nasabah_model->get_data_by_id($deposito->nasabah_id);

        if (!$deposito) {
            show_custom_404();
            return;
        }

        $query_jenis = $this->db
            ->select('nama, id')
            ->from('tbjenistabungan')
            ->like('nama', 'deposito')
            ->get();

        $jenistabungan = $query_jenis->row();

        $data = [
            'deposito' => $deposito,
            'nasabah' => $nasabah,
            'jenis' => $jenistabungan,
            'pegawai' => $this->Pegawai_model->get_data(),
            'level' => $this->session->userData('level'),
        ];

        $parser = [
            'judul' => "Form Edit Deposito",
            'isi'   => $this->load->view('deposito/editForm', $data, TRUE)
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
            $tanggal_deposito = $this->input->post('tanggal_deposito');
            $nasabah = $this->input->post('nasabah');
            $jenis_tabungan = $this->input->post('jenis_tabungan');
            $pegawai = $this->input->post('pegawai_id');
            $jumlah_deposito = str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_deposito'));
            $durasi = $this->input->post('durasi');
            $nama_ahli_waris = $this->input->post('nama_ahli_waris');
            $kontak_ahli_waris = $this->input->post('kontak_ahli_waris');
            $hubungan_ahli_waris = $this->input->post('hubungan_ahli_waris');
            $no_rekening = $this->input->post('nomor_rekening');

            $this->form_validation->set_rules('tanggal_deposito', 'Tanggal Simpanan', 'required', [
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

                $this->form_validation->set_rules('jumlah_deposito', 'Jumlah Deposito', 'required|callback_check_minimum[' . $minimum_jumlah . ']', [
                    'required' => 'Jumlah deposito harus diisi.',
                ]);
            } else if (empty($jenis_data)) {
                $this->form_validation->set_rules('jumlah_deposito', 'Jumlah Deposito', 'required', [
                    'required' => 'Jumlah deposito harus diisi.',
                ]);
            }

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

            $Simpanan = $this->Deposito_model->get_data_by_id($id);
            if ($Simpanan->no_rekening == $no_rekening) {
                $this->form_validation->set_rules('nomor_rekening', 'Nomer Rekening', 'required', [
                    'required' => 'Nomer rekening harus diisi.',
                ]);
            } else {
                $this->form_validation->set_rules('nomor_rekening', 'Nomer Rekening', 'required|is_unique[tbdeposito.no_rekening]', [
                    'required' => 'Nomer rekening harus diisi.',
                    'is_unique' => 'Nomer rekening sudah terdaftar.'
                ]);
            }

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorTanggalDeposito'  => form_error('tanggal_deposito'),
                        'errorNasabah'          => form_error('nasabah'),
                        'errorJenisTabungan'    => form_error('jenis_tabungan'),
                        'errorBunga'            => form_error('bunga'),
                        'errorBiayaRegistrasi'  => form_error('biaya_registrasi'),
                        'errorSimpananAwal'     => form_error('simpanan_awal'),
                        'errorPengendapan'      => form_error('pengendapan'),
                        'errorJenisDenda'       => form_error('jenis_denda'),
                        'errorJumlahDenda'      => form_error('jumlah_denda'),
                        'errorJummlahDeposito'  => form_error('jumlah_deposito'),
                        'errorNoRekening'       => form_error('nomor_rekening'),
                        'errorDurasi'           => form_error('durasi'),
                    ]
                ];
            } else {
                $data = [
                    'tanggal_deposito' => $tanggal_deposito,
                    'no_rekening' => $no_rekening,
                    'nasabah_id' => $nasabah,
                    'pegawai_id' => $pegawai,
                    'jenistabungan_id' => $jenis_tabungan,
                    'jumlah_deposito' => $jumlah_deposito,
                    'durasi' => $durasi,
                    'nama_ahli_waris' => $nama_ahli_waris,
                    'telp_ahli_waris' => $kontak_ahli_waris,
                    'hubungan_ahli_waris' => $hubungan_ahli_waris,
                ];

                // echo '<pre>';
                // print_r($data);
                // exit;

                $updated = $this->Deposito_model->edit_data($id, $data);
                if ($updated) {
                    $msg = ['success' => 'Data tabungan berhasil dirubah.'];
                    push_event('deposito-channel', 'deposito-event', ['message' => 'Deposito berhasil diubah!']);
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

        if (!function_exists('safe_base64_decode_detail_deposito')) {
            function safe_base64_decode_detail_deposito($string)
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

        $no_rekening = safe_base64_decode_detail_deposito($encoded_rek);

        if ($no_rekening === false || empty(trim($no_rekening))) {
            show_404("Nomor rekening tidak valid.");
            return;
        }

        $deposito = $this->Deposito_model->get_data_by_norek($no_rekening);

        if (!$deposito) {
            show_404("Data deposito tidak ditemukan untuk nomor rekening: " . html_escape($no_rekening));
            return;
        }

        $nasabah = $this->Nasabah_model->get_data_by_id($deposito->nasabah_id);
        $jenis_tabungan = $this->Kategori_model->get_data_by_id($deposito->jenistabungan_id);
        $pegawai = $this->Pegawai_model->get_data_by_id($deposito->pegawai_id);

        $this->load->model('Penarikan_model');
        $akumulasi_data_penarikan = $this->Penarikan_model->get_akumulasi_penarikan_by_simpanan($deposito->id);

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
            'deposito'        => $deposito,
            'nasabah'         => $nasabah,
            'jenis'           => $jenis_tabungan,
            'pegawai'         => $pegawai,
            'level'           => $this->session->userdata('level'),
            'formatted_durasi' => format_durasi($deposito->durasi ?? null),
            'total_akumulasi_penarikan' => $akumulasi_data_penarikan ? ($akumulasi_data_penarikan->total_akumulasi_penarikan ?? 0) : 0,
            'total_akumulasi_denda'     => $akumulasi_data_penarikan ? ($akumulasi_data_penarikan->total_akumulasi_denda ?? 0) : 0,
        ];

        $parser = [
            'judul' => "<a href=\"" . base_url('deposito') . "\" class=\"btn btn-warning\">
                        <i class=\"fa fa-backward\"></i> Kembali
                    </a> 
                    ",
            'isi'   => $this->load->view('deposito/detail', $data, TRUE)
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
            'tanggal_deposito' => $simpanan->tanggal_deposito
        ];

        $html = $this->load->view('deposito/cetak_nasabah', $data, true);

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
        $deposito = $this->Deposito_model->get_data_by_norek($no_rek);

        $data = [
            'deposito' => $deposito
        ];

        $parser = [
            'judul' => "<i class='fa fa-money-check'></i> Laporan Deposito",
            'isi'   => $this->load->view('deposito/laporan', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function print_laporan()
    {
        $id = $this->input->get('id');
        $tanggal_mulai = $this->input->get('tanggal_mulai');
        $tanggal_akhir = $this->input->get('tanggal_akhir');
        $jenis_laporan = $this->input->get('jenis_laporan');

        // Default laporan ke '3' (Setor dan Tarik)
        if (empty($jenis_laporan)) {
            $jenis_laporan = '3';
        }

        // Ambil data tabungan dan nasabah
        $tabungan = $this->Tabungan_model->get_data_by_id($id);
        if (!$tabungan) {
            echo "Error: Data tabungan tidak ditemukan.";
            return;
        }

        $nasabah = (object) ['nama_lengkap' => $tabungan->nama_lengkap];

        // Ambil data transaksi (gabungan setor dan tarik)
        $transaksi = $this->Tabungan_model->get_transaksi_by_simpanan($tabungan->id, $tanggal_mulai, $tanggal_akhir, $jenis_laporan);

        $data = [
            'tabungan' => $tabungan,
            'nasabah' => $nasabah,
            'transaksi' => $transaksi,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_akhir' => $tanggal_akhir,
            'jenis_laporan' => $jenis_laporan,
        ];

        // Load view
        $html = $this->load->view('deposito/cetak_laporan', $data, true);

        // PDF dompdf
        $this->load->library('dompdf_lib');
        $this->dompdf_lib->loadHtml($html);
        $this->dompdf_lib->setPaper('A4', 'portrait');
        $this->dompdf_lib->render();

        $filename = "laporan_" . $nasabah->nama_lengkap . "_" . $tabungan->no_rekening . ".pdf";
        $this->dompdf_lib->stream($filename, false);
    }


    private function _safe_base64_encode($string)
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }

    private function _safe_base64_decode($string)
    {
        return base64_decode(strtr($string, '-_', '+/'));
    }

    public function print_sertifikat($id)
    {
        if (empty($id)) show_error("Error: ID sertifikat tidak boleh kosong.", 400);

        $sertifikat_data = $this->Deposito_model->get_detail_for_sertifikat($id);
        if (!$sertifikat_data) show_error('Data sertifikat dengan ID ' . $id . ' tidak ditemukan.', 404);

        $tanggal_depo = new DateTime($sertifikat_data->tanggal_deposito);
        $bulan_romawi = $this->_bulan_romawi($tanggal_depo->format('n'));
        $tahun = $tanggal_depo->format('Y');
        $nomor_sertifikat_lengkap = $sertifikat_data->no_rekening . '/DEP/' . $bulan_romawi . '/' . $tahun;

        $durasi = $sertifikat_data->durasi;
        $durasi_terbilang = $this->terbilang($durasi);

        $tanggal_mulai = new DateTime($sertifikat_data->tanggal_deposito);
        $tanggal_mulai->add(new DateInterval('P' . $sertifikat_data->durasi . 'M'));
        $tanggal_jatuh_tempo = $tanggal_mulai->format('Y-m-d');

        $data = [
            'nomor_sertifikat'   => $nomor_sertifikat_lengkap,
            'nama_nasabah'       => $sertifikat_data->nama_nasabah ?? '',
            'alamat_nasabah'     => $sertifikat_data->alamat_nasabah ?? '',
            'jumlah_deposito'    => $sertifikat_data->jumlah_deposito ?? 0,
            'terbilang'          => ucwords($this->terbilang_rupiah($sertifikat_data->jumlah_deposito)),
            'durasi'             => sprintf('%d (%s)', $durasi, $durasi_terbilang) ?? 0,
            'tanggal_deposito'   => $sertifikat_data->tanggal_deposito,
            'tanggal_jatuh_tempo' => $tanggal_jatuh_tempo,
            'suku_bunga' => (float) ($sertifikat_data->suku_bunga ?? 0),
            'nama_pimpinan'      => $sertifikat_data->nama_pimpinan ?? 'N/A',
            'nama_bendahara'     => $sertifikat_data->nama_bendahara ?? 'N/A',
            'nik_nasabah'        => $sertifikat_data->nik_nasabah ?? '',
            'tempat_lahir'       => $sertifikat_data->tempat_lahir ?? '',
            'tanggal_lahir'      => $sertifikat_data->tanggal_lahir,
            'telp_nasabah'       => $sertifikat_data->telp_nasabah ?? '',
        ];

        $html = $this->load->view('deposito/cetak_sertifikat', $data, TRUE);

        $this->load->library('dompdf_lib');
        $this->dompdf_lib->loadHtml($html);
        $this->dompdf_lib->setPaper('A4', 'landscape');
        $this->dompdf_lib->render();

        $nomor_sertifikat_untuk_file = str_replace('/', '_', $data['nomor_sertifikat']);

        $filename = "Sertifikat -" . $data['nama_nasabah'] . " - " . $nomor_sertifikat_untuk_file . ".pdf";

        $this->dompdf_lib->stream($filename, false);
    }

    // TAMBAHKAN FUNGSI BARU INI di dalam controller Deposito.php Anda
    private function _bulan_romawi($bulan)
    {
        $romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        return $romawi[$bulan - 1];
    }

    private function terbilang($angka)
    {
        $angka = intval(abs($angka));
        $baca = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $terbilang = '';

        if ($angka < 12) {
            $terbilang = $baca[$angka];
        } else if ($angka < 20) {
            $terbilang = $baca[$angka - 10] . ' belas';
        } else if ($angka < 100) {
            $terbilang = $this->terbilang(intval($angka / 10)) . ' puluh ' . $this->terbilang($angka % 10);
        } else if ($angka < 200) {
            $terbilang = 'seratus ' . $this->terbilang($angka - 100);
        } else if ($angka < 1000) {
            $terbilang = $this->terbilang(intval($angka / 100)) . ' ratus ' . $this->terbilang($angka % 100);
        } else if ($angka < 2000) {
            $terbilang = 'seribu ' . $this->terbilang($angka - 1000);
        } else if ($angka < 1000000) {
            $terbilang = $this->terbilang(intval($angka / 1000)) . ' ribu ' . $this->terbilang($angka % 1000);
        } else if ($angka < 1000000000) {
            $terbilang = $this->terbilang(intval($angka / 1000000)) . ' juta ' . $this->terbilang($angka % 1000000);
        } else if ($angka < 1000000000000) {
            $terbilang = $this->terbilang(intval($angka / 1000000000)) . ' miliar ' . $this->terbilang($angka % 1000000000);
        } else if ($angka < 1000000000000000) {
            $terbilang = $this->terbilang(intval($angka / 1000000000000)) . ' triliun ' . $this->terbilang($angka % 1000000000000);
        }

        return trim(preg_replace('/\s+/', ' ', $terbilang));
    }

    private function terbilang_rupiah($angka_float)
    {

        $rupiah = floor($angka_float);
        $sen = round(($angka_float - $rupiah) * 100);

        $terbilang_rupiah = $this->terbilang($rupiah) . ' rupiah';

        if ($sen > 0) {
            $terbilang_sen = ' koma ' . $this->terbilang($sen) . ' sen';
            return $terbilang_rupiah . $terbilang_sen;
        }

        return $terbilang_rupiah;
    }
}
