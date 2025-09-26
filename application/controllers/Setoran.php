<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Setoran extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Nasabah_model');
        $this->load->model('Kategori_model');
        $this->load->model('Pegawai_model');
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
        function safe_base64_decode($string)
        {
            return base64_decode(strtr($string, '-_?', '+/='));
        }

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
            'isi'   => $this->load->view('setoran/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }


    public function simpanData()
    {
        // if ($this->input->is_ajax_request()) {
        // Set timezone ke Waktu Indonesia Tengah (WITA / UTC+8)
        date_default_timezone_set('Asia/Makassar');

        // Ambil tanggal dari form dan gabungkan dengan waktu saat ini
        $tanggal_dari_form = $this->input->post('tanggal_setoran');
        $waktu_sekarang = date('H:i:s'); // Mendapatkan waktu saat ini, misal: 09:42:00
        $tanggal_setoran = $tanggal_dari_form . ' ' . $waktu_sekarang; // Menggabungkan menjadi format DATETIME

        $tabungan = $this->input->post('tabungan');
        $jumlah_setoran = $this->input->post('jumlah_setoran');
        // echo '<pre>';
        // print_r($jumlah_setoran);
        // exit;
        $pegawai_id = $this->input->post('pegawai_id');

        if ($this->session->userdata('level') == 'Admin') {
            $this->form_validation->set_rules('pegawai_id', 'Pegawai', 'required', [
                'required' => 'Pegawai wajib dipilih.'
            ]);
        }

        $this->form_validation->set_rules('tanggal_setoran', 'Tanggal Setoran', 'required', [
            'required'  => 'Tanggal setoran wajib diisi.'
        ]);

        $this->form_validation->set_rules('nasabah', 'Nasabah', 'required', [
            'required'  => 'Nasabah wajib diisi.'
        ]);

        $this->form_validation->set_rules('tabungan', 'Tabungan', 'required', [
            'required'  => 'Tabungan wajib diisi.'
        ]);

        $this->form_validation->set_rules('jumlah_setoran', 'Jumlah Setoran', 'required', [
            'required'  => 'Jumlah setoran wajib diisi.'
        ]);

        if ($this->form_validation->run() == FALSE) {
            $msg = [
                'error' => [
                    'errorTanggalSetoran'   => form_error('tanggal_setoran'),
                    'errorNasabah'          => form_error('nasabah'),
                    'errorTabungan'         => form_error('tabungan'),
                    'errorJumlahSetoran'    => form_error('jumlah_setoran'),
                    'errorPegawai'          => form_error('pegawai_id')
                ]
            ];
        } else {
            // Data yang akan dimasukkan ke database, sekarang dengan datetime lengkap
            $data = [
                'simpanan_id' => $tabungan,
                'tanggal_setoran' => $tanggal_setoran, // Menggunakan variabel datetime yang sudah digabung
                'jumlah_setoran' => $jumlah_setoran,
                'pegawai_id' => $pegawai_id
            ];

            // Perbaikan: Pastikan fungsi hanya dideklarasikan sekali
            if (!function_exists('safe_base64_encode')) {
                function safe_base64_encode($string)
                {
                    return strtr(base64_encode($string), '+/=', '-_?');
                }
            }

            $this->db->trans_start(); // Mulai transaksi

            // 1. Masukkan detail setoran
            $this->Setoran_model->insert_data($data);

            // 2. Update saldo di tabel utama
            $sql = "UPDATE tbsimpanan SET jumlah_simpanan = jumlah_simpanan + ? WHERE id = ?";
            $this->db->query($sql, array($jumlah_setoran, $tabungan));

            $this->db->trans_complete(); // Selesaikan transaksi

            if ($this->db->trans_status() === FALSE) {
                // Jika transaksi gagal, kirim pesan error
                $msg = ['error' => 'Gagal menyimpan data karena ada masalah pada database.'];
            } else {
                // Jika transaksi berhasil
                $data_simpanan = $this->Simpanan_model->get_data_by_id($tabungan);
                $msg = [
                    'success' => 'Data berhasil ditambahkan.',
                    'redirect' => $_SERVER['HTTP_REFERER']
                ];
            }
        }

        echo json_encode($msg);
        // }
    }

    public function fetchData()
    {
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_?');
        }
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
                $row[] = $field->pegawai;
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
                        'saldo'  => $data_simpanan->jumlah_simpanan
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

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');

            $simpanan_detail = $this->Setoran_model->get_data_by_id($id);
            $simpanan = $this->Simpanan_model->get_data_by_id($simpanan_detail->simpanan_id);

            $selisih = $simpanan->jumlah_simpanan - $simpanan_detail->jumlah_setoran;

            $delete = $this->Setoran_model->delete_data($id);
            if ($delete) {
                $data = [
                    'jumlah_simpanan' => $selisih
                ];

                $this->Simpanan_model->edit_data($simpanan_detail->simpanan_id, $data);
                $msg = [
                    'success' => 'Bunga berhasil dihapus.'
                ];
            } else {
                $msg = [
                    'error' => 'Bunga gagal dihapus.'
                ];
            }

            echo json_encode($msg);
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
            log_message('debug', 'ID yang dikirim: ' . $id); // <--- tambahkan ini

            $data = $this->Simpanan_model->get_detail_tabungan_by_id($id);
            log_message('debug', 'Hasil query: ' . print_r($data, true)); // <---

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
