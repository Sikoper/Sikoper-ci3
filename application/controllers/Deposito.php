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
        $this->Deposito_model->update_status_jatuh_tempo();
        $this->Deposito_model->perpanjang_otomatis();
        $parser = [
            'judul' => "Data Deposito",
            'isi' => $this->load->view('deposito/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetchData()
    {
        // Menggunakan helper function dari secure_helper.php
        if ($this->input->is_ajax_request() == true) {
            $list = $this->Deposito_model->get_datatables();
            $data = array();
            $no = $_POST['start'];
            $level = $this->session->userdata('level');


            foreach ($list as $field) {
                $no++;
                $row = array();

                $badgeClass = 'bg-secondary';

                switch ($field->status) {
                    case 'aktif':
                        $badgeClass = 'bg-success';
                        break;
                    case 'jatuh tempo':
                        $badgeClass = 'bg-warning';
                        break;
                    case 'nonaktif':
                        $badgeClass = 'bg-secondary';
                        break;
                    case 'ditutup':
                        $badgeClass = 'bg-secondary';
                        break;
                    default:
                        $badgeClass = 'bg-light text-dark';
                        break;
                }

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->nama_nasabah;
                $row[] = $field->no_rekening;
                $row[] = $field->telp_nasabah;
                $row[] = "<div class=\"text-end\">" . number_format($field->jumlah_deposito, 0, ',', '.') . "</div>";
                $row[] = "<div class=\"text-center\">
                        <span class=\"badge $badgeClass text-capitalize\" style=\"min-width:100px; display:inline-block;\">{$field->status}</span>
                        </div>";
                $perpanjangDisabled = ($field->status === 'aktif' || $field->status === 'ditutup') ? 'disabled' : '';
                $pencairanDisabled = ($field->status === 'ditutup') ? 'disabled' : '';
                $row[] = ' <button type="button" class="btn btn-danger ' . $pencairanDisabled . ' " onclick="window.location=\'' . base_url('pencairan?id=') . safe_base64_encode($field->id) . '\'">Pencairan</button>
                            <button type="button" class="btn btn-primary" ' . $perpanjangDisabled . ' onclick="window.location=\'' . base_url('deposito/perpanjang?id=') . safe_base64_encode($field->id) . '\'">Perpanjang</button>';
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
            'isi' => $this->load->view('deposito/addForm', $data, TRUE)
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

            $quick_add_mode = $this->input->post('quick_add_mode');

            $this->form_validation->set_rules('tanggal_deposito', 'Tanggal Deposito', 'required', [
                'required' => 'Tanggal deposito wajib diisi.'
            ]);

            if ($quick_add_mode == '1') {
                $this->form_validation->set_rules('nama_langsung', 'Nama Nasabah', 'required|trim', [
                    'required' => 'Nama Nasabah wajib diisi.'
                ]);
                $this->form_validation->set_rules('telepon_langsung', 'No. Telepon', 'required|numeric|trim', [
                    'required' => 'No. Telepon wajib diisi.',
                    'numeric' => 'No. Telepon harus berupa angka.'
                ]);
            } else {
                $this->form_validation->set_rules('nasabah', 'Nasabah', 'required', [
                    'required' => 'Nasabah tidak boleh kosong.'
                ]);
            }

            $this->form_validation->set_rules('jenis_tabungan', 'Jenis Tabungan', 'required', [
                'required' => 'Jenis tabungan harus diisi.',
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
                        'errorTanggalSimpanan' => form_error('tanggal_deposito'),
                        'errorNasabah' => form_error('nasabah'),
                        'errorJenisTabungan' => form_error('jenis_tabungan'),
                        'errorBunga' => form_error('bunga'),
                        'errorBiayaRegistrasi' => form_error('biaya_registrasi'),
                        'errorSimpananAwal' => form_error('simpanan_awal'),
                        'errorPengendapan' => form_error('pengendapan'),
                        'errorPegawai' => form_error('pegawai_id'),
                        'errorJenisDenda' => form_error('jenis_denda'),
                        'errorJumlahDenda' => form_error('jumlah_denda'),
                        'errorJumlahSimpanan' => form_error('jumlah_deposito'),
                        'errorNoRekening' => form_error('nomor_rekening'),
                        'errorDurasi' => form_error('durasi'),
                    ]
                ];
            } else {

                $total_bunga_didapat = ($jumlah_deposito * ($rate_bunga / 100)) * $durasi;
                $hutang_bunga = $total_bunga_didapat;

                // echo '<pre>';
                // print_r($total_bunga_didapat);
                // exit;

                if ($quick_add_mode == '1') {
                    // Auto-create Nasabah
                    $nama_baru = $this->input->post('nama_langsung', true);
                    $telepon_baru = $this->input->post('telepon_langsung', true);
                    $alamat_baru = $this->input->post('alamat_langsung', true);

                    $data_nasabah = [
                        'nik' => '-',  // Quick add - can be filled later
                        'nama_lengkap' => $nama_baru,
                        'jenis_kelamin' => '?',  // Unknown - can be updated later
                        'tempat_lahir' => '',
                        'tanggal_lahir' => null,
                        'agama' => '',
                        'alamat' => $alamat_baru ?: '',
                        'pekerjaan' => '',
                        'telp' => $telepon_baru,
                        'nama_ibu_kandung' => '',
                        'pegawai_id' => $pegawai  // Required foreign key
                    ];

                    $this->db->insert('tbnasabah', $data_nasabah);
                    $nasabah_id_final = $this->db->insert_id();

                    if (!$nasabah_id_final) {
                        $msg = ['error' => ['errorGeneral' => 'Gagal membuat data nasabah baru.']];
                        echo json_encode($msg);
                        return;
                    }
                } else {
                    $nasabah_id_final = $nasabah;
                }

                $data = [
                    'tanggal_deposito' => $tanggal_deposito,
                    'no_rekening' => $no_rekening,
                    'nasabah_id' => $nasabah_id_final, // Use the determined ID
                    'pegawai_id' => $pegawai,
                    'jenistabungan_id' => $jenis_tabungan,
                    'jumlah_deposito' => str_replace(['.', ','], ['', '.'], $jumlah_deposito),
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
                $deposito_id = $this->db->insert_id();

                // === BACKDATE LOGIC ===
                // If tanggal_deposito is in the past, generate bunga logs
                $tgl_deposito = new DateTime($tanggal_deposito);
                $today = new DateTime();

                // Reset time to compare dates only
                $tgl_deposito->setTime(0, 0, 0);
                $today->setTime(0, 0, 0);

                if ($tgl_deposito < $today && $deposito_id) {
                    $start_date = clone $tgl_deposito;

                    // Logic: Get months difference
                    $interval = DateInterval::createFromDateString('1 month');
                    $period = new DatePeriod($start_date, $interval, $today);

                    $total_accumulated = 0;
                    $bunga_logs = [];
                    // Check user choice: 'akumulasi' (default) or 'ditarik'
                    $withdrawal_mode = $this->input->post('status_bunga_lampau') === 'ditarik';

                    foreach ($period as $dt) {
                        // Skip the start date itself if it exactly matches loop start (DatePeriod behavior varies slightly)
                        if ($dt == $tgl_deposito)
                            continue;

                        // Calculate Monthly Bunga: (Amount * Rate / 100) / 12
                        $amt = floatval(str_replace(['.', ','], ['', '.'], $jumlah_deposito));
                        $bg_rate = floatval($rate_bunga);

                        $bunga_bulanan = ($amt * $bg_rate / 100) / 12;
                        $bunga_bulanan_rounded = round($bunga_bulanan);

                        // Date for calculation log (e.g. 15th of the month)
                        $log_date = $dt->format('Y-m-15');

                        // Determine status based on user choice
                        $status_penarikan = $withdrawal_mode ? 'sudah_ditarik' : 'belum_ditarik';

                        // Get actual nasabah name for log
                        $nasabah_data = $this->Nasabah_model->get_data_by_id($nasabah_id_final);
                        $nama_nasabah_log = $nasabah_data ? $nasabah_data->nama_lengkap : 'Unknown';

                        $bunga_logs[] = [
                            'deposito_id' => $deposito_id,
                            'no_rekening' => $no_rekening,
                            'nama_nasabah' => $nama_nasabah_log, // Now uses actual name instead of ID
                            'jumlah_bunga' => $bunga_bulanan_rounded,
                            'rate_bunga' => $bg_rate,
                            'tanggal_perhitungan' => $log_date,
                            'status_penarikan' => $status_penarikan,
                            'input_method' => 'auto',
                            'pegawai_id' => $this->session->userdata('pegawai_id'),
                            'keterangan' => 'Bunga otomatis (' . ($withdrawal_mode ? 'Riwayat' : 'Akumulasi') . ') ' . $dt->format('F Y')
                        ];

                        $total_accumulated += $bunga_bulanan_rounded;

                        if ($withdrawal_mode) {
                            // Create actual withdrawal record in tbpenarikan
                            $this->db->insert('tbpenarikan', [
                                'simpanan_id' => $deposito_id,
                                'no_rekening' => $no_rekening,
                                // 'nama_nasabah' => $nasabah, // Need to verify if table uses ID or Name. Usually ID if integer.
                                'tanggal_penarikan' => $log_date,
                                'jumlah_penarikan' => $bunga_bulanan_rounded,
                                'pegawai_id' => $this->session->userdata('pegawai_id'),
                                'keterangan' => 'Riwayat penarikan bunga ' . $dt->format('F Y'),
                                'jenis_penarikan' => 'Bunga Deposito' // Adjust if column requires specific enum
                            ]);
                        }
                    }

                    if (!empty($bunga_logs)) {
                        $this->db->insert_batch('tb_bunga_deposito_log', $bunga_logs);

                        // Update Deposito Totals
                        // If withdrawn: total_accumulated increases as a record of earnings, but bunga_belum_ditarik stays 0
                        $bunga_belum_ditarik = $withdrawal_mode ? 0 : $total_accumulated;

                        $this->db->where('id', $deposito_id);
                        $this->db->update('tbdeposito', [
                            'total_bunga_akumulasi' => $total_accumulated,
                            'bunga_belum_ditarik' => $bunga_belum_ditarik
                        ]);
                    }
                }

                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE) {
                    $msg = ['error' => 'Gagal menyimpan data deposito.'];
                } else {
                    $msg = ['success' => 'Data deposito berhasil ditambahkan.'];
                    push_event('deposito-channel', 'deposito-event', ['message' => 'Deposito baru ditambahkan!']);
                }
            }

            echo json_encode($msg);
        } else {
            redirect('unauthorized_403');
        }
    }

    public function get_next_rekening()
    {
        $this->db->select('no_rekening');
        $this->db->from('tbdeposito');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get();

        if ($query && $query->num_rows() > 0) {
            $last = $query->row();
            $lastNumber = (int) substr($last->no_rekening, 1);
            $nextNumber = $lastNumber + 1;
            $lastRek = $last->no_rekening;
        } else {
            $nextNumber = 1;
            $lastRek = '-';
        }

        $newRek = 'D' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        echo json_encode([
            'next_rekening' => $newRek,
            'last_rekening' => $lastRek
        ]);
    }

    public function perpanjang()
    {
        // Get the encoded ID from the URL query string
        $encoded_id = $this->input->get('id');
        if (!$encoded_id) {
            // Handle missing ID
            show_error('ID Deposito tidak ditemukan.', 404);
            return;
        }

        // Menggunakan helper function dari secure_helper.php
        // Decode the ID
        $id = safe_base64_decode($encoded_id);
        $data['title'] = "Perpanjang Deposito";
        $data['level'] = $this->session->userdata('level');
        $data['deposito'] = $this->Deposito_model->get_data_by_id($id);

        if (!$data['deposito']) {
            show_error('Data Deposito tidak valid.', 404);
            return;
        }

        $data['nasabah'] = $this->Nasabah_model->get_data_by_id($data['deposito']->nasabah_id);
        $query_jenis = $this->db
            ->select('*')
            ->from('tbjenistabungan')
            ->like('nama', 'deposito', 'both') // 'both' adds wildcards %deposito%
            ->get();
        $data['jenis'] = $query_jenis->row();
        $data['pegawai'] = $this->Pegawai_model->get_data();

        // Load the new view for renewal
        $parser = [
            'judul' => "Form Perpanjang Deposito",
            'isi' => $this->load->view('deposito/perpanjang', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function proses_perpanjang()
    {
        if ($this->input->is_ajax_request()) {
            $allowed_roles = ['Admin', 'Direktur', 'Pegawai'];
            $level = $this->session->userdata('level');

            if (!in_array($level, $allowed_roles)) {
                echo json_encode(['error' => 'Unauthorized 403']);
                return;
            }

            $id = $this->input->post('id');
            $durasi = $this->input->post('durasi');
            $bunga_baru = str_replace(',', '.', $this->input->post('bunga'));

            // --- Validation Rules ---
            $this->form_validation->set_rules('durasi', 'Jangka Waktu', 'required', [
                'required' => 'Jangka waktu perpanjangan wajib dipilih.'
            ]);
            $this->form_validation->set_rules('bunga', 'Jumlah Bunga', 'required', [
                'required' => 'Bunga tidak boleh kosong.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorDurasi' => form_error('durasi'),
                        'errorBunga' => form_error('bunga'),
                    ]
                ];
            } else {
                $tanggal_perpanjangan = date('Y-m-d');

                // Data array updated to exclude 'tanggal_jatuh_tempo'
                $data = [
                    'tanggal_deposito' => $tanggal_perpanjangan,
                    'durasi' => $durasi,
                    'rate_bunga' => $bunga_baru,
                    'status' => 'aktif'
                ];

                $updated = $this->Deposito_model->edit_data($id, $data);

                if ($updated) {
                    $msg = ['success' => 'Deposito berhasil diperpanjang dengan suku bunga terbaru.'];
                    push_event('deposito-channel', 'deposito-event', ['message' => 'Deposito berhasil diperpanjang!']);
                } else {
                    $msg = ['error' => 'Gagal memperpanjang data deposito.'];
                }
            }

            echo json_encode($msg);
        }
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

        // Menggunakan helper function dari secure_helper.php
        if ($encoded_rek === null) {
            show_custom_404();
            return;
        }

        $no_rekening = safe_base64_decode($encoded_rek);
        $deposito = $this->Deposito_model->get_data_by_norek($no_rekening);

        if (!$deposito) {
            show_custom_404();
            return;
        }

        $nasabah = $this->Nasabah_model->get_data_by_id($deposito->nasabah_id);

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
            'isi' => $this->load->view('deposito/editForm', $data, TRUE)
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
                'required' => 'Tanggal simpanan wajib diisi.'
            ]);

            $this->form_validation->set_rules('nasabah', 'Nasabah', 'required', [
                'required' => 'Nasabah tidak boleh kosong.'
            ]);

            $this->form_validation->set_rules('jenis_tabungan', 'Jenis Tabungan', 'required', [
                'required' => 'Jenis tabungan harus diisi.',
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
                        'errorTanggalDeposito' => form_error('tanggal_deposito'),
                        'errorNasabah' => form_error('nasabah'),
                        'errorJenisTabungan' => form_error('jenis_tabungan'),
                        'errorBunga' => form_error('bunga'),
                        'errorBiayaRegistrasi' => form_error('biaya_registrasi'),
                        'errorSimpananAwal' => form_error('simpanan_awal'),
                        'errorPengendapan' => form_error('pengendapan'),
                        'errorJenisDenda' => form_error('jenis_denda'),
                        'errorJumlahDenda' => form_error('jumlah_denda'),
                        'errorJummlahDeposito' => form_error('jumlah_deposito'),
                        'errorNoRekening' => form_error('nomor_rekening'),
                        'errorDurasi' => form_error('durasi'),
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

        // Menggunakan helper function dari secure_helper.php
        if ($encoded_rek === null) {
            show_404();
            return;
        }

        $no_rekening = safe_base64_decode($encoded_rek);

        if ($no_rekening === false || empty(trim($no_rekening))) {
            show_404("Nomor rekening tidak valid.");
            return;
        }

        $deposito = $this->Deposito_model->get_data_by_norek($no_rekening);
        log_message('debug', 'Level: ' . $level . ' | Rek: ' . $no_rekening . ' | Result: ' . print_r($deposito, true));
        if (!$deposito) {
            show_404("Data deposito tidak ditemukan untuk nomor rekening: " . html_escape($no_rekening));
            return;
        }

        $nasabah = $this->Nasabah_model->get_data_by_id($deposito->nasabah_id);
        $jenis_tabungan = $this->Kategori_model->get_data_by_id($deposito->jenistabungan_id);
        $pegawai = $this->Pegawai_model->get_data_by_id($deposito->pegawai_id);

        $this->load->model('Pencairan_model');
        $akumulasi_data_penarikan = $this->Pencairan_model->get_akumulasi_penarikan_by_deposito($deposito->id);

        // Get bunga values from log
        $bunga_tersedia_from_log = $this->Deposito_model->get_bunga_tersedia_from_log($deposito->id);
        $bunga_sudah_dibayar = $this->Deposito_model->get_bunga_sudah_dibayar_from_log($deposito->id);

        // Calculate bunga sampai jatuh tempo (total interest if held until maturity)
        $bunga_sampai_jatuh_tempo = ($deposito->jumlah_deposito * ($deposito->rate_bunga / 100) * $deposito->durasi);

        // Calculate months elapsed using Excel method:
        // Count complete months based on deposit anniversary date
        $start_date = new DateTime($deposito->tanggal_deposito);
        $now = new DateTime();

        // Excel counts complete months from deposit date
        // If today is before the same day of month as deposit, subtract 1
        $months_elapsed = ($now->format('Y') - $start_date->format('Y')) * 12
            + ($now->format('n') - $start_date->format('n'));

        // If we haven't reached the deposit anniversary day this month, reduce by 1
        if ((int) $now->format('j') < (int) $start_date->format('j')) {
            $months_elapsed--;
        }

        // Ensure months_elapsed is not negative and capped at duration
        $months_elapsed = max(0, min($months_elapsed, $deposito->durasi));

        // Calculate bunga_earned_so_far (interest earned based on complete months - "BUNGA JATUH TEMPO" in Excel)
        $bunga_earned_so_far = $deposito->jumlah_deposito * ($deposito->rate_bunga / 100) * $months_elapsed;

        // CORRECTED: Hutang Bunga = Bunga Jatuh Tempo - Bunga Yang Sudah Dibayar
        // Follows Excel KWITANSI calculation exactly
        // Negative = overpaid (customer received advance), Positive = owed to customer
        if ($deposito->status == 'ditutup') {
            $hutang_bunga_saat_ini = 0;
        } else {
            $hutang_bunga_saat_ini = $bunga_earned_so_far - $bunga_sudah_dibayar;
        }

        // Bunga tersedia = interest that can be withdrawn now (cannot be negative)
        $bunga_tersedia = max(0, $hutang_bunga_saat_ini);

        // Total Diterima = Bunga Yang Sudah Dibayar (what customer has received)
        $total_diterima_nasabah = $bunga_sudah_dibayar;

        $data = [
            'deposito' => $deposito,
            'nasabah' => $nasabah,
            'jenis' => $jenis_tabungan,
            'pegawai' => $pegawai,
            'level' => $this->session->userdata('level'),
            'formatted_durasi' => format_durasi($deposito->durasi ?? null),
            'total_akumulasi_penarikan' => $total_diterima_nasabah, // Now equals bunga_sudah_dibayar
            'total_akumulasi_denda' => $akumulasi_data_penarikan ? ($akumulasi_data_penarikan->total_akumulasi_denda ?? 0) : 0,
            'bunga_tersedia' => $bunga_tersedia,
            'bunga_sudah_dibayar' => $bunga_sudah_dibayar,
            'hutang_bunga' => $hutang_bunga_saat_ini,
            'bunga_sampai_jatuh_tempo' => $bunga_sampai_jatuh_tempo,
            'bunga_earned_so_far' => $bunga_earned_so_far,       // Bunga terakumulasi s/d hari ini (= Excel "BUNGA JATUH TEMPO")
            'months_elapsed' => $months_elapsed,                  // Bunga Ke (periode bunga saat ini)
        ];

        // print_r($data);
        // echo "</pre>";
        // die();


        $parser = [
            'judul' => "<a href=\"" . base_url('deposito') . "\" class=\"btn btn-warning\">
                        <i class=\"fa fa-backward\"></i> Kembali
                    </a>",
            'isi' => $this->load->view('deposito/detail', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function reload_data_deposito($id)
    {
        $deposito = $this->Deposito_model->get_data_by_id($id);
        if (!$deposito) {
            show_error('Data deposito tidak ditemukan.');
        }

        $nasabah = $this->Nasabah_model->get_data_by_id($deposito->nasabah_id);
        $pegawai = $this->Pegawai_model->get_data_by_id($deposito->pegawai_id);
        $jenis_tabungan = $this->Jenis_tabungan_model->get_data_by_id($deposito->jenis_id);
        $akumulasi_data_penarikan = $this->Deposito_model->get_akumulasi_data_penarikan($id);

        $data = [
            'deposito' => $deposito,
            'nasabah' => $nasabah,
            'jenis' => $jenis_tabungan,
            'pegawai' => $pegawai,
            'level' => $this->session->userdata('level'),
            'formatted_durasi' => format_durasi($deposito->durasi ?? null),
            'total_akumulasi_penarikan' => $akumulasi_data_penarikan->total_akumulasi_penarikan ?? 0,
            'total_akumulasi_denda' => $akumulasi_data_penarikan->total_akumulasi_denda ?? 0,
        ];

        $this->load->view('deposito/partials/data_deposito', $data);
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
        // Menggunakan helper function dari secure_helper.php
        $id = $this->input->get('id');
        $no_rek = safe_base64_decode($id);
        $deposito = $this->Deposito_model->get_data_by_norek($no_rek);

        $data = [
            'deposito' => $deposito
        ];

        $parser = [
            'judul' => "<i class='fa fa-money-check'></i> Laporan Deposito",
            'isi' => $this->load->view('deposito/laporan', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function print_laporan()
    {
        $id = $this->input->get('id');
        $tanggal_mulai = $this->input->get('tanggal_mulai');
        $tanggal_akhir = $this->input->get('tanggal_akhir');
        $jenis_laporan = $this->input->get('jenis_laporan') ?: '3';

        $deposito = $this->Deposito_model->get_data_by_id($id);
        if (!$deposito) {
            show_error("Error: Data deposito tidak ditemukan.", 404);
            return;
        }

        $nasabah = $this->db->get_where('tbnasabah', ['id' => $deposito->nasabah_id])->row();
        if (!$nasabah) {
            show_error("Error: Data nasabah terkait tidak ditemukan.", 404);
            return;
        }

        // IMPORTANT: default range that won't accidentally exclude bunga
        $start = $tanggal_mulai ?: $deposito->tanggal_deposito;
        $end = $tanggal_akhir ?: date('Y-m-d');

        $rekening_data = $this->Deposito_model->get_transaksi_by_deposito(
            $deposito->id,
            $start,
            $end,
            $jenis_laporan
        );

        $data = [
            'deposito' => $deposito,
            'nasabah' => $nasabah,
            'rekening' => $rekening_data,
            'tanggal_mulai' => $start,
            'tanggal_akhir' => $end,
        ];

        $html = $this->load->view('deposito/cetak_laporan', $data, true);

        $this->load->library('dompdf_lib');
        $this->dompdf_lib->loadHtml($html);
        $this->dompdf_lib->setPaper('A4', 'portrait');
        $this->dompdf_lib->render();
        $filename = "Rekening_Koran_" . str_replace(' ', '_', $nasabah->nama_lengkap) . "_" . $deposito->no_rekening . ".pdf";
        $this->dompdf_lib->stream($filename);
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
        if (empty($id))
            show_error("Error: ID sertifikat tidak boleh kosong.", 400);

        $sertifikat_data = $this->Deposito_model->get_detail_for_sertifikat($id);
        if (!$sertifikat_data)
            show_error('Data sertifikat dengan ID ' . $id . ' tidak ditemukan.', 404);
        $pegawai = $this->getNamaPegawai();

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
            'nomor_sertifikat' => $nomor_sertifikat_lengkap,
            'nama_nasabah' => $sertifikat_data->nama_nasabah ?? '',
            'nama_pegawai' => $pegawai,
            'alamat_nasabah' => $sertifikat_data->alamat_nasabah ?? '',
            'jumlah_deposito' => $sertifikat_data->jumlah_deposito ?? 0,
            'terbilang' => ucwords($this->terbilang_rupiah($sertifikat_data->jumlah_deposito)),
            'durasi' => sprintf('%d (%s)', $durasi, $durasi_terbilang) ?? 0,
            'tanggal_deposito' => $sertifikat_data->tanggal_deposito,
            'tanggal_jatuh_tempo' => $tanggal_jatuh_tempo,
            'suku_bunga' => (float) ($sertifikat_data->suku_bunga ?? 0),
            'nama_pimpinan' => $sertifikat_data->nama_pimpinan ?? 'N/A',
            'nama_bendahara' => $sertifikat_data->nama_bendahara ?? 'N/A',
            'nik_nasabah' => $sertifikat_data->nik_nasabah ?? '',
            'tempat_lahir' => $sertifikat_data->tempat_lahir ?? '',
            'tanggal_lahir' => $sertifikat_data->tanggal_lahir,
            'telp_nasabah' => $sertifikat_data->telp_nasabah ?? '',
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

    private function getNamaPegawai()
    {
        $level = $this->session->userdata('level');
        $pegawai_id = $this->session->userdata('pegawai_id');

        if ($level === 'Admin') {
            $pegawai = $this->Pegawai_model->get_first_by_jabatan('PEMBUKUAN TABUNGAN');
            return $pegawai ? $pegawai->nama_lengkap : 'N/A';
        } else {
            $pegawai = $this->Pegawai_model->get_data_by_id($pegawai_id);
            return $pegawai ? $pegawai->nama_lengkap : 'N/A';
        }
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

    public function get_combo_rekening_nasabah()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $search = $this->input->get('q');
        $data = $this->Deposito_model->cari_rekening_deposito_nasabah($search);

        echo json_encode($data);
    }

    public function fetch_detail_rekening()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $deposito_id = $this->input->post('id');
        $response = ['status' => 'error', 'message' => 'Data tidak ditemukan.'];

        if ($deposito_id) {
            $deposito = $this->Deposito_model->get_detail_deposito_by_id($deposito_id);
            $bunga_tersedia = $this->Deposito_model->get_bunga_tersedia_from_log($deposito_id);

            if ($deposito) {
                $response = [
                    'status' => 'success',
                    'nama_nasabah' => $deposito->nama_nasabah,
                    'bunga_tersedia' => $bunga_tersedia
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    public function proses_penarikan_bunga()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $deposito_id = $this->input->post('deposito_id');
        $pegawai_id = $this->input->post('pegawai_id');

        if (empty($deposito_id) || empty($pegawai_id)) {
            $msg = ['error_validation' => 'Rekening dan Pegawai tidak boleh kosong.'];
            echo json_encode($msg);
            return;
        }

        $penarikan_id = $this->Deposito_model->tarik_bunga_deposito($deposito_id, $pegawai_id);

        if ($penarikan_id) {
            $msg = [
                'success' => 'Penarikan seluruh bunga yang tersedia berhasil diproses.',
                'penarikan_id' => $penarikan_id
            ];
        } else {
            $msg = ['error_save' => 'Gagal memproses penarikan. Kemungkinan tidak ada bunga yang tersedia untuk ditarik.'];
        }

        header('Content-Type: application/json');
        echo json_encode($msg);
    }

    public function print_kwitansi_bunga($id)
    {
        if (empty($id)) {
            show_error("Error: ID penarikan tidak boleh kosong.", 400);
            return;
        }

        // Get penarikan deposito data
        $penarikan = $this->db->get_where('tbpenarikan_deposito', ['id' => $id])->row();
        if (!$penarikan) {
            show_error('Data penarikan bunga dengan ID ' . $id . ' tidak ditemukan.', 404);
            return;
        }

        // Get deposito and related data
        $deposito = $this->Deposito_model->get_data_by_id($penarikan->deposito_id);
        if (!$deposito) {
            show_error('Data deposito tidak ditemukan.', 404);
            return;
        }

        $nasabah = $this->db->get_where('tbnasabah', ['id' => $deposito->nasabah_id])->row();
        $pegawai = $this->Pegawai_model->get_data_by_id($penarikan->pegawai_id);

        // Calculate sisa bunga after this withdrawal
        $sisa_bunga = $this->Deposito_model->get_bunga_tersedia_from_log($deposito->id);

        // Generate kwitansi number
        $tanggal_p = new DateTime($penarikan->tanggal_penarikan);
        $no_kwitansi = 'KWB/' . $deposito->no_rekening . '/' . $tanggal_p->format('dmY') . '/' . str_pad($id, 4, '0', STR_PAD_LEFT);

        $data = [
            'no_kwitansi' => $no_kwitansi,
            'no_rekening' => $deposito->no_rekening,
            'nama_nasabah' => $nasabah->nama_lengkap ?? $deposito->nama_nasabah,
            'jenis_tabungan' => 'Deposito',
            'tanggal_penarikan' => $penarikan->tanggal_penarikan,
            'jumlah_penarikan' => $penarikan->jumlah_penarikan_bunga,
            'sisa_bunga' => $sisa_bunga,
            'terbilang' => $this->terbilang_rupiah($penarikan->jumlah_penarikan_bunga),
            'nama_pegawai' => $pegawai->nama_lengkap ?? 'N/A',
        ];

        $html = $this->load->view('penarikan_bunga/cetak_kwitansi', $data, true);

        $this->load->library('dompdf_lib');
        $this->dompdf_lib->loadHtml($html);
        $this->dompdf_lib->setPaper([0, 0, 793.7, 283.46], 'landscape'); // Similar size to penarikan kwitansi
        $this->dompdf_lib->render();

        $filename = "Kwitansi_Bunga_" . $deposito->no_rekening . "_" . date('Ymd', strtotime($penarikan->tanggal_penarikan)) . ".pdf";
        $this->dompdf_lib->stream($filename, false);
    }


    public function download_template()
    {
        // Method removed
        show_404();
    }

    /**
     * Show import form
     */
    public function import()
    {
        $allowed_roles = ['Admin', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }

        $data = [
            'level' => $level
        ];

        $parser = [
            'judul' => "Import Data Deposito dari Excel",
            'isi' => $this->load->view('deposito/import', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    /**
     * Process import
     */
    public function proses_import()
    {
        $allowed_roles = ['Admin', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
            return;
        }

        // Check file upload
        if (empty($_FILES['excel_file']['name'])) {
            echo json_encode(['success' => false, 'errors' => ['File tidak ditemukan']]);
            return;
        }

        // Configure upload
        $config['upload_path'] = './uploads/import/';
        $config['allowed_types'] = 'xls|xlsx';
        $config['max_size'] = 102400; // 100MB
        $config['file_name'] = 'deposito_' . date('YmdHis') . '_' . uniqid();

        // Create directory if not exists
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('excel_file')) {
            echo json_encode(['success' => false, 'errors' => [$this->upload->display_errors('', '')]]);
            return;
        }

        $upload_data = $this->upload->data();
        $file_path = $upload_data['full_path'];

        // Get pegawai_id from session or use default
        $pegawai_id = $this->session->userdata('pegawai_id') ?: 1;

        // Get jenistabungan_id for Deposito
        $jenis = $this->db->like('nama', 'Deposito', 'both')->get('tbjenistabungan')->row();
        $jenistabungan_id = $jenis ? $jenis->id : 1;

        // Run import
        $results = $this->Deposito_model->import_full_migration($file_path, $pegawai_id, $jenistabungan_id);

        echo json_encode($results);
    }
}

