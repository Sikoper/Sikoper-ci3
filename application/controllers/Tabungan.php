<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tabungan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Tabungan_model');

        $allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }

    public function fetchTabungan()
    {
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_?');
        }
        $id = $this->input->post('id');
        if ($this->input->is_ajax_request() == true) {
            $list = $this->Tabungan_model->get_datatables($id);
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $field) {
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->tanggal;
                $row[] = "Rp " . number_format($field->jumlah_uang, 2, ',', '.');
                $badgeClass = ($field->keterangan == 'Setor') ? 'bg-success' : 'bg-danger';
                $row[] = "<span class=\"badge $badgeClass\">{$field->keterangan}</span>";
                $row[] = $field->pegawai;
                $row[] = "<button class=\"btn btn-danger\" onclick=\"deleteRecord('" . $field->detail_id . "', '" . $field->jumlah_uang . "','" . $field->keterangan . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";
                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Tabungan_model->count_all($id),
                "recordsFiltered" => $this->Tabungan_model->count_filtered($id),
                "data" => $data,
            );

            echo json_encode($output);
        } else {
            exit('Maaf data tidak bisa ditampilkan');
        }
    }

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');
            $keterangan = $this->input->post('keterangan');

            if ($keterangan === 'Setor') {
                $detail = $this->db->get_where('tbdetail_simpanan', ['id' => $id])->row();
                $simpanan_id = $detail->simpanan_id;
                $this->db->delete('tbdetail_simpanan', ['id' => $id]);
                $this->db->set('jumlah_simpanan', 'jumlah_simpanan - ' . $detail->jumlah_setoran, false)
                    ->where('id', $simpanan_id)
                    ->update('tbsimpanan');
            } elseif ($keterangan === 'Tarik') {
                $detail = $this->db->get_where('tbdetail_penarikan', ['id' => $id])->row();
                $simpanan_id = $detail->simpanan_id;
                $this->db->delete('tbdetail_penarikan', ['id' => $id]);
                $this->db->set('jumlah_simpanan', 'jumlah_simpanan + ' . $detail->jumlah_penarikan, false)
                    ->where('id', $simpanan_id)
                    ->update('tbsimpanan');
            } else {
                echo json_encode(['error' => 'Jenis transaksi tidak dikenal.']);
                return;
            }

            echo json_encode(['success' => 'Data berhasil dihapus.']);
        }
    }
}
