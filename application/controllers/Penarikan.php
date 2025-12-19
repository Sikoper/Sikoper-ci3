<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penarikan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Set timezone di constructor
        date_default_timezone_set('Asia/Makassar');

        $this->load->model('Penarikan_model');
        $this->load->model('Nasabah_model');
        $this->load->model('Kategori_model');
        $this->load->model('Pegawai_model');
        $this->load->model('Simpanan_model');
        $this->load->model('Tarik_model');

        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function fetchData()
    {
        $list = $this->Penarikan_model->get_datatables();
        $data = [];
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $no = $start;

        foreach ($list as $row) {
            $no++;
            $data[] = [
                'no' => '<div class="text-center">' . $no . '</div>',
                'no_rekening' => $row->no_rekening,
                'nama_nasabah' => $row->nama_nasabah,
                'jenis_tabungan' => $row->jenis_tabungan,
                'total_penarikan' => 'Rp ' . number_format($row->total_penarikan, 0, ',', '.'),
                'aksi' => '
    <button href="' . base_url('penarikan/edit/' . $row->id) . '" class="btn btn-warning btn-sm">
        <i class="fa fa-edit"></i>
    </button>
    <button class="btn btn-danger btn-sm" onclick="deleteItem(' . $row->id . ', \'' . $row->nama_nasabah . '\')">
        <i class="fa fa-trash"></i>
    </button>'

            ];
        }

        $output = [
            "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
            "recordsTotal" => $this->Penarikan_model->count_all(),
            "recordsFiltered" => $this->Penarikan_model->count_filtered(),
            "data" => $data,
        ];

        echo json_encode($output);
    }

    public function index()
    {
        // Menggunakan helper function dari secure_helper.php
        $encoded_rek = $this->input->get('id');
        $tabungan = null;
        $combo_value = '';
        $selected_nasabah = null;
        $selected_rekening = null;

        if (!empty($encoded_rek)) {
            $no_rekening = safe_base64_decode($encoded_rek);
            $tabungan = $this->Simpanan_model->get_data_tabungan_full_by_norek($no_rekening);

            if ($tabungan) {
                $selected_nasabah = $tabungan->nasabah_id;
                $selected_rekening = $tabungan->no_rekening;
                $combo_value = "{$tabungan->no_rekening}";
            }
        }

        $data = [
            'tabungan' => $tabungan,
            'selected_rekening' => $tabungan->no_rekening ?? null,
            'selected_tabungan' => $tabungan->id ?? null,
            'selected_nasabah' => $selected_nasabah,
            'combo_value' => $combo_value,
            'disabled' => !empty($tabungan),
            'jenis' => $this->Kategori_model->get_data(),
            'pegawai' => $this->Pegawai_model->get_data(),
            'nasabah' => $this->Penarikan_model->get_rekening_nasabah_combo(),
            'level' => $this->session->userData('level'),
        ];


        $parser = [
            'judul' => "Formulir Penarikan Tunai",
            'isi' => $this->load->view('penarikan/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function get_saldo($simpanan_id)
    {
        $simpanan = $this->Penarikan_model->get_simpanan_by_id($simpanan_id);
        if ($simpanan) {
            echo json_encode(['saldo' => $simpanan->jumlah_simpanan]);
        } else {
            echo json_encode(['error' => 'Data tidak ditemukan']);
        }
    }

    public function get_rekening_by_nasabah()
    {
        $nasabah_id = $this->input->post('nasabah_id');
        $data = $this->Penarikan_model->get_rekening_dengan_jenis($nasabah_id);
        echo json_encode($data);
    }

    private function _safe_base64_encode($string)
    {
        return safe_base64_encode($string); // Menggunakan helper
    }

    /**
     * CRITICAL FIX: Proses penarikan dengan FOR UPDATE locking
     * - Mencegah race condition
     * - Validasi dan update dalam satu transaksi
     */
    public function proses()
    {
        // --- Basic setup ---
        $waktu_sekarang = date('H:i:s');
        $tanggal_input = $this->input->post('tanggal_penarikan');
        $tanggal_penarikan_input = $tanggal_input . ' ' . $waktu_sekarang;
        $simpanan_id = $this->input->post('tabungan');
        $jumlah_penarikan_diminta = (float) str_replace(['.', ','], ['', '.'], (string) ($this->input->post('jumlah_penarikan') ?? ''));
        $pegawai_id = $this->session->userdata('level') == 'Admin' ? $this->input->post('pegawai_id') : $this->session->userdata('pegawai_id');
        $level_user = $this->session->userdata('level');

        // --- Validation rules ---
        $this->form_validation->set_rules('tabungan', 'Tabungan', 'required', ['required' => 'Tabungan wajib dipilih.']);
        $this->form_validation->set_rules('jumlah_penarikan', 'Jumlah Penarikan', 'required', ['required' => 'Jumlah penarikan wajib diisi.']);
        if ($level_user == 'Admin') {
            $this->form_validation->set_rules('pegawai_id', 'Pegawai', 'required', ['required' => 'Pegawai wajib dipilih oleh Admin.']);
        }

        // Basic validation first (without balance check - that happens inside transaction)
        if ($this->form_validation->run() == FALSE) {
            $errors = [
                'errorSimpanan' => form_error('tabungan'),
                'errorJumlah' => form_error('jumlah_penarikan')
            ];
            if ($level_user == 'Admin' && form_error('pegawai_id')) {
                $errors['errorPegawai'] = form_error('pegawai_id');
            }
            echo json_encode(['error' => $errors]);
            return;
        }

        // --- DATABASE TRANSACTION LOGIC WITH FOR UPDATE LOCKING ---
        $this->db->trans_start();

        try {
            // CRITICAL: Lock the row first to prevent race conditions
            $simpanan_locked = $this->db->query(
                "SELECT ts.id, ts.jumlah_simpanan, jt.pengendapan 
                 FROM tbsimpanan ts 
                 JOIN tbjenistabungan jt ON jt.id = ts.jenistabungan_id 
                 WHERE ts.id = ? FOR UPDATE",
                [$simpanan_id]
            )->row();

            if (!$simpanan_locked) {
                throw new Exception('Data rekening tidak ditemukan.');
            }

            // Re-validate with locked data
            $saldo_pokok = (float) $simpanan_locked->jumlah_simpanan;
            $pengendapan_minimal = (float) $simpanan_locked->pengendapan;
            $sisa_saldo = $saldo_pokok - $jumlah_penarikan_diminta;

            if ($sisa_saldo < $pengendapan_minimal) {
                throw new Exception('Penarikan gagal. Saldo tidak mencukupi. Sisa saldo minimal setelah transaksi harus Rp ' . number_format($pengendapan_minimal, 0, ',', '.'));
            }

            if ($jumlah_penarikan_diminta <= 0) {
                throw new Exception('Jumlah penarikan harus lebih dari 0.');
            }

            // 1. Check if a withdrawal header already exists for this account on this date
            $penarikan_header = $this->Penarikan_model->get_penarikan_by_date($simpanan_id, $tanggal_input);
            $penarikan_id = 0;

            if ($penarikan_header) {
                // 2a. If header exists, use its ID
                $penarikan_id = $penarikan_header->id;
            } else {
                // 2b. If not, create a new header record
                $data_penarikan_header = [
                    'simpanan_id' => $simpanan_id,
                    'pegawai_id' => $pegawai_id,
                    'tanggal_penarikan' => $tanggal_penarikan_input,
                    'jumlah_denda' => 0,
                    'total_penarikan' => 0
                ];
                $penarikan_id = $this->Penarikan_model->simpan_penarikan($data_penarikan_header);
            }

            if (!$penarikan_id) {
                throw new Exception('Gagal membuat atau menemukan header penarikan.');
            }

            // 3. Save the withdrawal detail record
            $data_penarikan_detail = [
                'penarikan_id' => $penarikan_id,
                'simpanan_id' => $simpanan_id,
                'tanggal_penarikan' => $tanggal_penarikan_input,
                'jumlah_penarikan' => $jumlah_penarikan_diminta,
                'pegawai_id' => $pegawai_id,
                'status' => 'disetujui'
            ];
            $this->Penarikan_model->simpan_penarikan_detail($data_penarikan_detail);

            // 4. Update the total in the header record by adding the new amount
            $this->Penarikan_model->update_total_penarikan($penarikan_id, $jumlah_penarikan_diminta);

            // 5. Reduce the main balance in the savings account (atomic update)
            $this->db->set('jumlah_simpanan', 'jumlah_simpanan - ' . (float) $jumlah_penarikan_diminta, FALSE);
            $this->db->where('id', $simpanan_id);
            $this->db->update('tbsimpanan');

            // If all operations were successful, commit the transaction
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaksi database gagal.');
            }

            echo json_encode(['success' => 'Penarikan berhasil diproses.', 'redirect' => site_url('simpanan')]);
        } catch (Exception $e) {
            // If any operation fails, roll back the transaction
            $this->db->trans_rollback();
            echo json_encode(['error_save' => $e->getMessage()]);
        }
    }

    public function valid_jumlah_penarikan($jumlah_diminta_str, $params)
    {
        list($saldo_str, $pengendapan_str) = explode(',', $params);

        $jumlah_diminta = (float) str_replace(['.', ','], ['', '.'], $jumlah_diminta_str);
        $saldo_saat_ini = (float) $saldo_str;
        $pengendapan_minimal = (float) $pengendapan_str;

        if (!is_numeric($jumlah_diminta) || $jumlah_diminta <= 0) {
            $this->form_validation->set_message('valid_jumlah_penarikan', 'Jumlah penarikan harus berupa angka positif.');
            return FALSE;
        }

        $sisa_saldo_setelah_pengurangan = $saldo_saat_ini - $jumlah_diminta;

        if ($sisa_saldo_setelah_pengurangan < $pengendapan_minimal) {
            $pesan = 'Penarikan gagal. Saldo tidak mencukupi. Sisa saldo minimal setelah transaksi harus Rp ' . number_format($pengendapan_minimal, 0, ',', '.');
            $this->form_validation->set_message('valid_jumlah_penarikan', $pesan);
            return FALSE;
        }

        return TRUE;
    }

    public function delete()
    {
        $id = $this->input->post('id');
        $deleted = $this->Penarikan_model->hapus_data_penarikan_by_id($id);
        if ($deleted) {
            echo json_encode(['success' => 'Data berhasil dihapus.']);
        } else {
            echo json_encode(['error' => 'Gagal menghapus data.']);
        }
    }

    public function getDataById()
    {
        $id = $this->input->post('id');
        $data = $this->Penarikan_model->getById($id);

        if ($data) {
            echo json_encode($data);
        } else {
            echo json_encode(null);
        }
    }

    public function fetchRekening()
    {
        $id = $this->input->post('id');
        log_message('debug', 'ID yang diterima fetchRekening: ' . $id);
        $cek = $this->db->get_where('tbsimpanan', ['id' => $id])->row();
        if (!$cek) {
            echo json_encode(['error' => 'Data ID tidak ada di tabel tbsimpanan']);
            return;
        }
        $data = $this->Penarikan_model->get_simpanan_detail_by_id($id);

        if ($data) {
            $total_saldo = (float) $data->jumlah_simpanan;
            echo json_encode([
                'nama_nasabah' => $data->nama_lengkap,
                'jenis_tabungan' => $data->jenis_tabungan,
                'saldo' => $total_saldo
            ]);
        } else {
            echo json_encode(['error' => 'Data tidak ditemukan']);
        }
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

            $id = $this->input->post('id');
            $total_penarikan = str_replace(['.', ','], ['', '.'], $this->input->post('total_penarikan'));

            $this->form_validation->set_rules('total_penarikan', 'Total Penarikan', 'required|numeric', [
                'required' => 'Total Penarikan wajib diisi.',
                'numeric' => 'Total Penarikan harus berupa angka.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                echo json_encode([
                    'error' => [
                        'total_penarikan' => form_error('total_penarikan'),
                    ]
                ]);
            } else {
                $updateData = [
                    'total_penarikan' => $total_penarikan,
                ];

                $result = $this->Penarikan_model->update($id, $updateData);

                if ($result) {
                    echo json_encode(['success' => 'Data berhasil diupdate']);
                } else {
                    echo json_encode(['error' => 'Gagal mengupdate data']);
                }
            }
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
            show_custom_404();
            return;
        }

        $no_rekening = safe_base64_decode($encoded_rek);
        $simpanan = $this->Simpanan_model->get_data_by_norek($no_rekening);

        if (!$simpanan) {
            show_custom_404();
            return;
        }

        $nasabah = $this->Nasabah_model->get_data_by_id($simpanan->nasabah_id);
        $jenis_tabungan = $this->Kategori_model->get_data_by_id($simpanan->jenistabungan_id);
        $pegawai = $this->Pegawai_model->get_data_by_id($simpanan->pegawai_id);

        $akumulasi_penarikan = $this->Penarikan_model->get_akumulasi_penarikan_by_simpanan($simpanan->id);

        // Menggunakan helper function dari secure_helper.php
        $data = [
            'simpanan' => $simpanan,
            'nasabah' => $nasabah,
            'jenis' => $jenis_tabungan,
            'pegawai' => $pegawai,
            'level' => $this->session->userData('level'),
            'total_akumulasi_penarikan' => $akumulasi_penarikan ? $akumulasi_penarikan->total_akumulasi_penarikan : 0,
            'total_akumulasi_denda' => $akumulasi_penarikan ? $akumulasi_penarikan->total_akumulasi_denda : 0,
        ];

        $parser = [
            'judul' => "<a href=" . base_url('simpanan') . " class=\"btn btn-warning\">
                            <i class=\"fa fa-backward\"></i> Kembali
                        </a>",
            'isi' => $this->load->view('simpanan/detail', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetch_detail_penarikan_by_simpanan()
    {
        $simpanan_id = $this->input->post('simpanan_id');

        if (empty($simpanan_id) || !ctype_digit((string) $simpanan_id)) {
            echo json_encode([
                "draw" => $this->input->post('draw') ? intval($this->input->post('draw')) : 0,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "error" => "ID Simpanan tidak valid."
            ]);
            return;
        }

        $list = $this->Penarikan_model->get_datatables_detail_penarikan($simpanan_id);
        $data = [];
        $no = $this->input->post('start') ? intval($this->input->post('start')) : 0;

        foreach ($list as $item) {
            $no++;
            $rowData = [];
            $rowData[] = '<div class="text-center">' . $no . '</div>';
            $rowData[] = date('d-m-Y', strtotime($item->tanggal_penarikan));
            $rowData[] = '<div class="text-end">Rp ' . number_format($item->total_penarikan, 0, ',', '.') . '</div>';
            $rowData[] = '<div class="text-end">Rp ' . number_format($item->jumlah_denda, 0, ',', '.') . '</div>';
            $rowData[] = $item->nama_pegawai ? htmlspecialchars($item->nama_pegawai, ENT_QUOTES, 'UTF-8') : '-';

            $actions = '<button class="btn btn-danger btn-sm" title="Hapus Penarikan" onclick="deleteDetailPenarikan(' . $item->id . ', \'' . htmlspecialchars(number_format($item->total_penarikan, 0, ',', '.'), ENT_QUOTES) . '\')">
                            <i class="fa fa-trash"></i>
                        </button>';
            $rowData[] = '<div class="text-center">' . $actions . '</div>';

            $data[] = $rowData;
        }

        $output = [
            "draw" => $this->input->post('draw') ? intval($this->input->post('draw')) : 0,
            "recordsTotal" => $this->Penarikan_model->count_all_detail_penarikan($simpanan_id),
            "recordsFiltered" => $this->Penarikan_model->count_filtered_detail_penarikan($simpanan_id),
            "data" => $data,
        ];

        header('Content-Type: application/json');
        echo json_encode($output);
    }

    public function hapus_detail_penarikan_ajax()
    {
        $penarikan_id = $this->input->post('penarikan_id');
        if (empty($penarikan_id) || !ctype_digit((string) $penarikan_id)) {
            echo json_encode(['error' => 'ID Penarikan tidak valid.']);
            return;
        }

        $this->db->trans_start();

        // Get the header data before deleting to know how much balance to restore
        $penarikan_data = $this->Penarikan_model->get_penarikan_untuk_dihapus($penarikan_id);
        if (!$penarikan_data) {
            $this->db->trans_rollback();
            echo json_encode(['error' => 'Data penarikan tidak ditemukan.']);
            return;
        }

        // The amount to return is the total from the header
        $simpanan_id = $penarikan_data->simpanan_id;
        $jumlah_total_kembali = (float) $penarikan_data->total_penarikan;

        // 1. Delete all detail records associated with this header
        $this->db->where('penarikan_id', $penarikan_id);
        $this->db->delete('tbdetail_penarikan');

        // 2. Delete the header record itself
        $deleted_header = $this->Penarikan_model->hapus_data_penarikan_by_id($penarikan_id);

        if ($deleted_header) {
            // 3. Restore the principal balance with the total withdrawn amount
            $this->Penarikan_model->tambah_saldo_pokok($simpanan_id, $jumlah_total_kembali);

            // Commit the transaction
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                echo json_encode(['error' => 'Gagal menghapus data penarikan.']);
            } else {
                echo json_encode(['success' => 'Data penarikan berhasil dihapus dan saldo telah dikembalikan.']);
            }
        } else {
            // If deletion fails, roll back
            $this->db->trans_rollback();
            echo json_encode(['error' => 'Gagal menghapus data penarikan.']);
        }
    }

    public function get_combo_rekening_nasabah()
    {
        $search = $this->input->get('q');
        $result = $this->Penarikan_model->get_combo_rekening_nasabah($search);
        echo json_encode($result);
    }
}
