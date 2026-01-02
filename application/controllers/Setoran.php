<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Setoran extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Set timezone di constructor
        date_default_timezone_set('Asia/Makassar');

        $this->load->model('Simpanan_model');
        $this->load->model('Nasabah_model');
        $this->load->model('Kategori_model');
        $this->load->model('Pegawai_model');
        $this->load->model('Setoran_model');

        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function index()
    {
        // Menggunakan helper function dari secure_helper.php
        $encoded_rek = $this->input->get('id');
        $tabungan = null;

        if (!empty($encoded_rek)) {
            $no_rekening = safe_base64_decode($encoded_rek);
            $tabungan = $this->Simpanan_model->get_data_by_norek($no_rekening);
        }

        $pegawai = $this->Pegawai_model->get_data();

        $data = [
            'tabungan' => $tabungan,
            'selected_nasabah' => $tabungan->nasabah_id ?? null,
            'selected_rekening' => $tabungan->no_rekening ?? null,
            'selected_tabungan_id' => $tabungan->id ?? null,
            'disabled' => !empty($tabungan),
            'pegawai' => $pegawai,
            'nasabah' => $this->Nasabah_model->get_data(),
            'level' => $this->session->userdata('level'),
        ];

        $parser = [
            'judul' => "Formulir Setoran Tunai",
            'isi' => $this->load->view('setoran/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }


    public function simpanData()
    {
        // Ambil tanggal dari input user, jika kosong gunakan server timestamp
        $tanggal_input = $this->input->post('tanggal_setoran');

        // Jika tanggal diinput user, validasi dan gunakan. Jika kosong, gunakan waktu server.
        if (!empty($tanggal_input)) {
            // Cek apakah tanggal valid
            $tanggal_parsed = strtotime($tanggal_input);
            if ($tanggal_parsed === false) {
                // Tanggal tidak valid, gunakan server timestamp
                $tanggal_setoran = date('Y-m-d H:i:s');
            } else {
                // SECURITY: Cegah tanggal di masa depan
                if ($tanggal_parsed > time()) {
                    echo json_encode(['error' => ['errorTanggalSetoran' => 'Tanggal setoran tidak boleh di masa depan.']]);
                    return;
                }
                // Gunakan tanggal yang diinput user + waktu sekarang jika hanya tanggal
                if (strlen($tanggal_input) <= 10) {
                    // Hanya tanggal tanpa waktu (YYYY-MM-DD)
                    $tanggal_setoran = $tanggal_input . ' ' . date('H:i:s');
                } else {
                    $tanggal_setoran = $tanggal_input;
                }
            }
        } else {
            // Default: gunakan server timestamp
            $tanggal_setoran = date('Y-m-d H:i:s');
        }

        $tabungan = $this->input->post('tabungan');
        $jumlah_setoran = $this->input->post('jumlah_setoran');
        $pegawai_id = $this->input->post('pegawai_id');

        if ($this->session->userdata('level') == 'Admin') {
            $this->form_validation->set_rules('pegawai_id', 'Pegawai', 'required', [
                'required' => 'Pegawai wajib dipilih.'
            ]);
        }

        $this->form_validation->set_rules('nasabah', 'Nasabah', 'required', [
            'required' => 'Nasabah wajib diisi.'
        ]);

        $this->form_validation->set_rules('tabungan', 'Tabungan', 'required', [
            'required' => 'Tabungan wajib diisi.'
        ]);

        $this->form_validation->set_rules('jumlah_setoran', 'Jumlah Setoran', 'required', [
            'required' => 'Jumlah setoran wajib diisi.'
        ]);

        if ($this->form_validation->run() == FALSE) {
            $msg = [
                'error' => [
                    'errorTanggalSetoran' => form_error('tanggal_setoran'),
                    'errorNasabah' => form_error('nasabah'),
                    'errorTabungan' => form_error('tabungan'),
                    'errorJumlahSetoran' => form_error('jumlah_setoran'),
                    'errorPegawai' => form_error('pegawai_id')
                ]
            ];
        } else {
            // Data yang akan dimasukkan ke database dengan server timestamp
            $data = [
                'simpanan_id' => $tabungan,
                'tanggal_setoran' => $tanggal_setoran,
                'jumlah_setoran' => $jumlah_setoran,
                'pegawai_id' => $pegawai_id
            ];

            $this->db->trans_start(); // Mulai transaksi

            // 1. Masukkan detail setoran
            $this->Setoran_model->insert_data($data);

            // 2. Update saldo di tabel utama (atomic update)
            $sql = "UPDATE tbsimpanan SET jumlah_simpanan = jumlah_simpanan + ? WHERE id = ?";
            $this->db->query($sql, array($jumlah_setoran, $tabungan));

            $this->db->trans_complete(); // Selesaikan transaksi

            if ($this->db->trans_status() === FALSE) {
                $msg = ['error' => 'Gagal menyimpan data karena ada masalah pada database.'];
            } else {
                $msg = [
                    'success' => 'Data berhasil ditambahkan.',
                    'redirect' => $_SERVER['HTTP_REFERER']
                ];
            }
        }

        echo json_encode($msg);
    }

    public function fetchData()
    {
        // Menggunakan helper function dari secure_helper.php
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
                $row[] = "Setor";
                $row[] = $field->pegawai ?? '-'; // Handle null pegawai
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

            $nasabah_detail = $this->Nasabah_model->get_data_by_id($nasabah);

            echo json_encode([
                'data' => $Value,
                'detail_nasabah' => [
                    'nik' => $nasabah_detail->nik,
                    'alamat' => $nasabah_detail->alamat
                ]
            ]);
        }
    }

    public function get_saldo_rekening()
    {
        if ($this->input->is_ajax_request()) {
            header('Content-Type: application/json');
            $id_rekening = $this->input->post('id');

            if ($id_rekening) {
                $data_simpanan = $this->Simpanan_model->get_data_by_id($id_rekening);

                if ($data_simpanan) {
                    $response = [
                        'status' => 'success',
                        'saldo' => $data_simpanan->jumlah_simpanan
                    ];
                } else {
                    $response = ['status' => 'error', 'message' => 'Data rekening tidak ditemukan.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'ID Rekening tidak valid.'];
            }

            echo json_encode($response);
        } else {
            exit('No direct script access allowed');
        }
    }

    /**
     * CRITICAL FIX: Fungsi delete dengan keamanan ketat
     * - Admin only access
     * - Database transaction dengan FOR UPDATE locking
     * - Anti-negative balance validation
     * - Atomic update
     */
    public function delete()
    {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }

        // 1. CEK LEVEL: Hanya Admin yang boleh akses
        if ($this->session->userdata('level') !== 'Admin') {
            echo json_encode(['error' => 'Akses ditolak. Hanya Admin yang boleh menghapus data setoran.']);
            return;
        }

        $id = $this->input->post('id');

        if (empty($id)) {
            echo json_encode(['error' => 'ID tidak valid.']);
            return;
        }

        // 2. START TRANSAKSI
        $this->db->trans_start();

        // 3. LOCKING DATA: Ambil data setoran dengan FOR UPDATE
        $setoran = $this->db->query(
            "SELECT * FROM tbdetail_simpanan WHERE id = ? FOR UPDATE",
            [$id]
        )->row();

        if (!$setoran) {
            $this->db->trans_rollback();
            echo json_encode(['error' => 'Data setoran tidak ditemukan.']);
            return;
        }

        // 4. LOCKING: Ambil saldo nasabah saat ini dengan FOR UPDATE
        $tabungan = $this->db->query(
            "SELECT * FROM tbsimpanan WHERE id = ? FOR UPDATE",
            [$setoran->simpanan_id]
        )->row();

        if (!$tabungan) {
            $this->db->trans_rollback();
            echo json_encode(['error' => 'Data rekening tidak ditemukan.']);
            return;
        }

        // 5. VALIDASI ANTI-MINUS: Cek apakah saldo mencukupi untuk dikurangi
        if ((float) $tabungan->jumlah_simpanan < (float) $setoran->jumlah_setoran) {
            $this->db->trans_rollback();
            echo json_encode([
                'error' => 'Gagal! Saldo nasabah tidak mencukupi untuk menghapus setoran ini. Kemungkinan uang sudah ditarik.'
            ]);
            return;
        }

        // 6. EKSEKUSI (Jika Valid):
        // 6a. Hapus data di tbdetail_simpanan
        $this->db->delete('tbdetail_simpanan', ['id' => $id]);

        // 6b. Kurangi saldo dengan Atomic Update (bukan kalkulasi PHP)
        $this->db->set('jumlah_simpanan', 'jumlah_simpanan - ' . (float) $setoran->jumlah_setoran, FALSE);
        $this->db->where('id', $setoran->simpanan_id);
        $this->db->update('tbsimpanan');

        // 7. COMMIT: Selesaikan transaksi
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['error' => 'Gagal menghapus setoran karena masalah database.']);
        } else {
            echo json_encode(['success' => 'Setoran berhasil dihapus.']);
        }
    }

    public function get_combo_rekening_nasabah()
    {
        $term = $this->input->get('search');
        log_message('debug', 'Search keyword: ' . $term);

        $result = $this->Simpanan_model->cari_rekening_nasabah($term);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'id' => $row->id,
                'text' => $row->no_rekening
            ];
        }

        echo json_encode($data);
    }

    public function get_detail_rekening()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');
            log_message('debug', 'ID yang dikirim: ' . $id);

            $data = $this->Simpanan_model->get_detail_tabungan_by_id($id);
            log_message('debug', 'Hasil query: ' . print_r($data, true));

            if ($data) {
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $data->id,
                        'no_rekening' => $data->no_rekening,
                        'nasabah_id' => $data->nasabah_id,
                        'nama_lengkap' => $data->nama_lengkap,
                        'jenis_tabungan' => $data->jenis_tabungan,
                        'nik' => $data->nik,
                        'alamat' => $data->alamat,
                        'jumlah_simpanan' => $data->jumlah_simpanan
                    ]
                ];
            } else {
                $response = ['status' => 'error', 'message' => 'Data tidak ditemukan.'];
            }

            echo json_encode($response);
        } else {
            exit('No direct script access allowed');
        }
    }
}
