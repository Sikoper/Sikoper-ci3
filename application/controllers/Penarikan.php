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

        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function fetchData()
    {
        $this->load->model('Penarikan_model');

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
                'status' => $row->status,
                'aksi' => '
    <a href="' . base_url('penarikan/edit/' . $row->id) . '" class="btn btn-warning btn-sm">
        <i class="fa fa-edit"></i>
    </a>
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
        $parser = [
            'judul' => "<i class='fa fa-user-lock'></i> Penarikan",
            'isi'   => $this->load->view('penarikan/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function add()
    {
        $data = [
            'jenis' => $this->Kategori_model->get_data(),
            'pegawai' => $this->Pegawai_model->get_data(),
            'nasabah' => $this->Nasabah_model->get_data(),
            'level' => $this->session->userData('level')
        ];

        $parser = [
            'judul' => "<i class='fa fa-money-check'></i> Penarikan",
            'isi'   => $this->load->view('penarikan/addForm', $data, TRUE)
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

    public function proses()
    {
        $tanggal_penarikan = $this->input->post('tanggal_penarikan');
        $simpanan_id = $this->input->post('simpanan_id');
        $jumlah_penarikan = str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_penarikan'));
        $pegawai_id = $this->input->post('pegawai_id');

        $this->form_validation->set_rules('nasabah', 'Nasabah', 'required', [
            'required'   => 'Nasabah wajib dipilih.',
        ]);

        $this->form_validation->set_rules('simpanan_id', 'Tabungan', 'required', [
            'required'   => 'Tabungan wajib dipilih.',
        ]);

        $this->form_validation->set_rules('jumlah_penarikan', 'Jumlah', 'required', [
            'required'   => 'Jumlah penarikan wajib diisi.',
        ]);

        $simpanan = $this->Penarikan_model->get_simpanan_by_id($simpanan_id);
        if (!empty($simpanan)) {
            $saldo = $simpanan->jumlah_simpanan;
            $jenis_tabungan = $this->Kategori_model->get_data_by_id($simpanan->jenistabungan_id);
            $pengendapan = $jenis_tabungan->pengendapan;

            $this->form_validation->set_rules('jumlah_penarikan', 'Jumlah', 'required|callback_valid_jumlah_penarikan[' . $saldo . ',' . $pengendapan . ']', [
                'required'   => 'Jumlah penarikan wajib diisi.',
            ]);
        }

        if ($this->form_validation->run() == FALSE) {
            $msg = [
                'error' => [
                    'errorNasabah'       => form_error('nasabah'),
                    'errorSimpanan'      => form_error('simpanan_id'),
                    'errorJumlah'        => form_error('jumlah_penarikan')
                ]
            ];
        } else {
            $data = [
                'simpanan_id' => $simpanan_id,
                'total_penarikan' => $jumlah_penarikan,
                'tanggal_penarikan' => $tanggal_penarikan,
                'pegawai_id' => $pegawai_id,
            ];

            // echo "<pre>";
            // print_r($data);
            // exit;

            $inserted = $this->Penarikan_model->simpan_penarikan($data);
            $this->Penarikan_model->kurangi_saldo_simpanan($simpanan_id, $jumlah_penarikan);

            if ($inserted) {
                $msg = ['success' => 'Data berhasil diubah.'];
            } else {
                $msg = ['error' => 'Gagal menyimpan perubahan data.'];
            }
        }
        echo json_encode($msg);
    }

    public function valid_jumlah_penarikan($jumlah, $params)
    {
        list($saldo, $pengendapan) = explode(',', $params);

        // Bersihkan format jika masih ada titik/koma dari input
        $jumlah = str_replace(['.', ','], ['', '.'], $jumlah);

        if (!is_numeric($jumlah) || $jumlah <= 0) {
            $this->form_validation->set_message('valid_jumlah_penarikan', 'Jumlah penarikan harus lebih dari 0.');
            return FALSE;
        }

        $sisa_saldo = $saldo - $jumlah;

        if ($sisa_saldo < $pengendapan) {
            $this->form_validation->set_message('valid_jumlah_penarikan', 'Penarikan gagal. Minimal saldo mengendap harus Rp ' . number_format($pengendapan, 0, ',', '.'));
            return FALSE;
        }

        return TRUE;
    }

    public function delete()
    {
        $id = $this->request->getPost('id');

        $this->db->table('tbpenarikan')->where('id', $id)->delete();

        return $this->response->setJSON(['success' => 'Data berhasil dihapus.']);
    }

    public function getDataById()
    {
        $id = $this->input->post('id');
        $data = $this->Penarikan_model->getById($id);

        if ($data) {
            // Return data sebagai JSON
            echo json_encode($data);
        } else {
            echo json_encode(null);
        }
    }

    public function update()
    {
        $id = $this->input->post('id');
        $updateData = [
            'total_penarikan' => $this->input->post('total_penarikan'),
            'status' => $this->input->post('status'),
        ];

        $result = $this->Penarikan_model->update($id, $updateData);

        if ($result) {
            echo json_encode(['success' => 'Data berhasil diupdate']);
        } else {
            echo json_encode(['error' => 'Gagal mengupdate data']);
        }
    }

    public function edit($id = null)
    {
        if ($id === null) {
            show_404();
        }

        // Ambil data berdasarkan id
        $data['penarikan'] = $this->Penarikan_model->getById($id);

        if (!$data['penarikan']) {
            show_404();
        }

        // Load view editForm.php dengan data
        $this->load->view('editForm', $data);
    }
}
