<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penarikan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
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

        // header('Content-Type: application/json');
        // echo json_encode($output);
        // exit;
    }

    public function index()
    {
        function safe_base64_decode($string)
        {
            return base64_decode(strtr($string, '-_?', '+/='));
        }

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
            'selected_nasabah' => $selected_nasabah, // << Tambahkan ini
            'combo_value' => $combo_value,
            'disabled' => !empty($tabungan),
            'jenis' => $this->Kategori_model->get_data(),
            'pegawai' => $this->Pegawai_model->get_data(),
            'nasabah' => $this->Penarikan_model->get_rekening_nasabah_combo(),
            'level' => $this->session->userData('level'),
        ];


        $parser = [
            'judul' => "Formulir Penarikan Tunai",
            'isi'   => $this->load->view('penarikan/index', $data, TRUE)
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
        return strtr(base64_encode($string), '+/=', '-_?');
    }

public function proses()
{
    $waktu_sekarang = date('H:i:s');
    $tanggal_penarikan_input = $this->input->post('tanggal_penarikan') . ' ' . $waktu_sekarang;
    $simpanan_id = $this->input->post('tabungan'); // dari hidden input
    $jumlah_penarikan_diminta = (float) str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_penarikan') ?? '');
    $pegawai_id = $this->input->post('pegawai_id');
    $level_user = $this->session->userdata('level');

    // VALIDASI
    $this->form_validation->set_rules('tabungan', 'Tabungan', 'required', [
        'required' => 'Tabungan wajib dipilih.'
    ]);
    $this->form_validation->set_rules('jumlah_penarikan', 'Jumlah Penarikan', 'required', [
        'required' => 'Jumlah penarikan wajib diisi.'
    ]);
    if ($level_user == 'Admin') {
        $this->form_validation->set_rules('pegawai_id', 'Pegawai', 'required', [
            'required' => 'Pegawai wajib dipilih oleh Admin.'
        ]);
    }

    // Default
    $penalty_rp_final = 0;
    $simpanan_data = null;
    $jenis_tabungan_data = null;
    $saldo_saat_ini = 0;
    $pengendapan_minimal = 0;

    // AMBIL DATA SIMPANAN
    if (!empty($simpanan_id)) {
        $simpanan_data = $this->Penarikan_model->get_simpanan_by_id($simpanan_id);

        if (!empty($simpanan_data)) {
            $jenis_tabungan_data = $this->Kategori_model->get_data_by_id($simpanan_data->jenistabungan_id);

            if (!empty($jenis_tabungan_data)) {
                $saldo_saat_ini = (float) $simpanan_data->jumlah_simpanan;
                $pengendapan_minimal = (float) $jenis_tabungan_data->pengendapan;

                // Cek denda jika perlu (misal: deposito)
                $penalty_rate = (float) $jenis_tabungan_data->jumlah_denda;
                $penalty_rp_final = 0;
            }

            // VALIDASI JUMLAH SETELAH CEK SALDO
            $this->form_validation->set_rules(
                'jumlah_penarikan',
                'Jumlah Penarikan',
                'required|callback_valid_jumlah_penarikan[' . $saldo_saat_ini . ',' . $pengendapan_minimal . ',' . $penalty_rp_final . ']',
                ['required' => 'Jumlah penarikan wajib diisi.']
            );
        }
    }

    if ($this->form_validation->run() == FALSE) {
        $errors = [
            'errorSimpanan'  => form_error('tabungan'),
            'errorJumlah'    => form_error('jumlah_penarikan')
        ];
        if ($level_user == 'Admin' && form_error('pegawai_id')) {
            $errors['errorPegawai'] = form_error('pegawai_id');
        }
        echo json_encode(['error' => $errors]);
        return;
    }

    // MULAI TRANSAKSI
    $this->db->trans_start();

    $data_penarikan_header = [
        'simpanan_id'       => $simpanan_id,
        'pegawai_id'        => $pegawai_id,
        'tanggal_penarikan' => $tanggal_penarikan_input,
        'jumlah_denda'      => $penalty_rp_final,
        'total_penarikan'   => $jumlah_penarikan_diminta
    ];
    $inserted_header = $this->Penarikan_model->simpan_penarikan($data_penarikan_header);

    if ($inserted_header) {
        $data_penarikan_detail = [
            'simpanan_id'       => $simpanan_id,
            'tanggal_penarikan' => $tanggal_penarikan_input,
            'jumlah_penarikan'  => $jumlah_penarikan_diminta,
            'pegawai_id'        => $pegawai_id,
            'status'            => 'disetujui'
        ];
        $inserted_detail = $this->Tarik_model->simpan_detail_penarikan($data_penarikan_detail);

        if ($inserted_detail) {
            $total_deduction = $jumlah_penarikan_diminta + $penalty_rp_final;
            $saldo_berkurang = $this->Penarikan_model->kurangi_saldo_simpanan($simpanan_id, $total_deduction);

            if ($saldo_berkurang) {
                $this->db->trans_commit();

                $redirect_url = site_url('penarikan');
                if (!empty($simpanan_data) && isset($simpanan_data->no_rekening)) {
                    $encoded = $this->_safe_base64_encode($simpanan_data->no_rekening);
                    $redirect_url = site_url('penarikan/detail/' . $encoded);
                }

                echo json_encode([
                    'success' => 'Penarikan berhasil diproses.',
                    'redirect' => $redirect_url
                ]);
                return;
            } else {
                $this->db->trans_rollback();
                echo json_encode(['error_save' => 'Gagal mengurangi saldo.']);
                return;
            }
        } else {
            $this->db->trans_rollback();
            echo json_encode(['error_save' => 'Gagal menyimpan detail penarikan.']);
            return;
        }
    } else {
        $this->db->trans_rollback();
        echo json_encode(['error_save' => 'Gagal menyimpan header penarikan.']);
        return;
    }
}


    public function valid_jumlah_penarikan($jumlah_diminta_str, $params)
    {
        list($saldo_str, $pengendapan_str, $penalty_dihitung_str) = explode(',', $params);

        $jumlah_diminta = (float) str_replace(['.', ','], ['', '.'], $jumlah_diminta_str);
        $saldo_saat_ini = (float) $saldo_str;
        $pengendapan_minimal = (float) $pengendapan_str;
        $penalty_dihitung = (float) $penalty_dihitung_str;

        if (!is_numeric($jumlah_diminta) || $jumlah_diminta <= 0) {
            $this->form_validation->set_message('valid_jumlah_penarikan', 'Jumlah penarikan harus berupa angka positif.');
            return FALSE;
        }

        $total_pengurangan_aktual = $jumlah_diminta + $penalty_dihitung;
        $sisa_saldo_setelah_pengurangan = $saldo_saat_ini - $total_pengurangan_aktual;

        if ($sisa_saldo_setelah_pengurangan < $pengendapan_minimal) {
            $pesan = 'Penarikan gagal. Saldo tidak mencukupi ';
            if ($penalty_dihitung > 0) {
                $pesan .= 'setelah dikurangi penarikan (Rp ' . number_format($jumlah_diminta, 0, ',', '.') . ') dan denda (Rp ' . number_format($penalty_dihitung, 0, ',', '.') . '). ';
            } else {
                $pesan .= 'setelah dikurangi penarikan (Rp ' . number_format($jumlah_diminta, 0, ',', '.') . '). ';
            }
            $pesan .= 'Sisa saldo minimal setelah transaksi harus Rp ' . number_format($pengendapan_minimal, 0, ',', '.');
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
    $data = $this->Penarikan_model->get_simpanan_detail_by_id($id);

    if ($data) {
        echo json_encode([
            'nama_nasabah'      => $data->nama_lengkap,
            'jenis_tabungan'    => $data->jenis_tabungan,
            'saldo'             => $data->jumlah_simpanan
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
                'numeric'  => 'Total Penarikan harus berupa angka.'
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

        function safe_base64_decode_simpanan($string)
        {
            return base64_decode(strtr($string, '-_?', '+/='));
        }

        if ($encoded_rek === null) {
            show_custom_404();
            return;
        }

        $no_rekening = safe_base64_decode_simpanan($encoded_rek);;
        $simpanan = $this->Simpanan_model->get_data_by_norek($no_rekening);

        if (!$simpanan) {
            show_custom_404();
            return;
        }

        $nasabah = $this->Nasabah_model->get_data_by_id($simpanan->nasabah_id);
        $jenis_tabungan = $this->Kategori_model->get_data_by_id($simpanan->jenistabungan_id);
        $pegawai = $this->Pegawai_model->get_data_by_id($simpanan->pegawai_id);

        $akumulasi_penarikan = $this->Penarikan_model->get_akumulasi_penarikan_by_simpanan($simpanan->id);

        if (!function_exists('format_durasi')) {
            function format_durasi($bulan)
            {
                if (!$bulan || $bulan <= 0) return '-';
                $tahun = floor($bulan / 12);
                $sisa_bulan = $bulan % 12;

                $output = "$bulan bulan";
                if ($tahun > 0) {
                    $output .= " / {$tahun} tahun";
                    if ($sisa_bulan > 0) {
                        $output .= " {$sisa_bulan} bulan";
                    }
                }
                return $output;
            }
        }

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
            'isi'   => $this->load->view('simpanan/detail', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetch_detail_penarikan_by_simpanan()
    {
        $simpanan_id = $this->input->post('simpanan_id');

        if (empty($simpanan_id) || !ctype_digit((string)$simpanan_id)) {
            echo json_encode([
                "draw"            => $this->input->post('draw') ? intval($this->input->post('draw')) : 0,
                "recordsTotal"    => 0,
                "recordsFiltered" => 0,
                "data"            => [],
                "error"           => "ID Simpanan tidak valid."
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
            "draw"            => $this->input->post('draw') ? intval($this->input->post('draw')) : 0,
            "recordsTotal"    => $this->Penarikan_model->count_all_detail_penarikan($simpanan_id),
            "recordsFiltered" => $this->Penarikan_model->count_filtered_detail_penarikan($simpanan_id),
            "data"            => $data,
        ];

        header('Content-Type: application/json');
        echo json_encode($output);
    }

    public function hapus_detail_penarikan_ajax()
    {
        $penarikan_id = $this->input->post('penarikan_id');
        if (empty($penarikan_id) || !ctype_digit((string)$penarikan_id)) {
            echo json_encode(['error' => 'ID Penarikan tidak valid.']);
            return;
        }

        $this->db->trans_start();
        $penarikan_header_data = $this->Penarikan_model->get_penarikan_untuk_dihapus($penarikan_id);
        if (!$penarikan_header_data) {
            $this->db->trans_rollback();
            echo json_encode(['error' => 'Data penarikan (header) tidak ditemukan.']);
            return;
        }

        $simpanan_id = $penarikan_header_data->simpanan_id;
        $jumlah_kembali_ke_saldo = (float)$penarikan_header_data->total_penarikan + (float)$penarikan_header_data->jumlah_denda;
        $deleted_header = $this->Penarikan_model->hapus_data_penarikan_by_id($penarikan_id);

        if ($deleted_header) {
            $kriteria_hapus_detail = [
                'simpanan_id'       => $penarikan_header_data->simpanan_id,
                'tanggal_penarikan' => $penarikan_header_data->tanggal_penarikan,
                'jumlah_penarikan'  => $penarikan_header_data->total_penarikan,
                'pegawai_id'        => $penarikan_header_data->pegawai_id,
                'status'            => 'disetujui'
            ];

            $this->Tarik_model->hapus_detail_by_kriteria($kriteria_hapus_detail);

            $saldo_updated = $this->Penarikan_model->tambah_saldo_simpanan($simpanan_id, $jumlah_kembali_ke_saldo);

            if ($saldo_updated) {
                $this->db->trans_commit();
                echo json_encode(['success' => 'Data penarikan berhasil dihapus dan saldo telah dikembalikan.']);
            } else {
                $this->db->trans_rollback();
                echo json_encode(['error' => 'Gagal mengembalikan saldo simpanan setelah penghapusan. Transaksi dibatalkan.']);
            }
        } else {
            $this->db->trans_rollback();
            echo json_encode(['error' => 'Gagal menghapus data penarikan (header). Transaksi dibatalkan.']);
        }
    }

    public function get_combo_rekening_nasabah()
    {
        $search = $this->input->get('q');
        $this->load->model('Penarikan_model');
        $result = $this->Penarikan_model->get_combo_rekening_nasabah($search);
        echo json_encode($result);
    }

    
}
