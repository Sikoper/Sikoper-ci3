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
		// Menggunakan helper function dari secure_helper.php
		$id = $this->input->post('id');
		// if ($this->input->is_ajax_request() == true) {
		$list = $this->Tabungan_model->get_datatables($id);
		$data = array();
		$no = $_POST['start'] ?? 0;

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
			"draw" => $_POST['draw'] ?? 1,
			"recordsTotal" => $this->Tabungan_model->count_all($id),
			"recordsFiltered" => $this->Tabungan_model->count_filtered($id),
			"data" => $data,
		);

		echo json_encode($output);
		// } else {
		//     exit('Maaf data tidak bisa ditampilkan');
		// }
	}

	public function delete()
	{
		if (!$this->input->is_ajax_request()) {
			echo json_encode(['error' => 'Invalid request.']);
			return;
		}

		// SECURITY: Hanya Admin yang boleh hapus
		if ($this->session->userdata('level') !== 'Admin') {
			echo json_encode(['error' => 'Anda tidak memiliki akses untuk menghapus data.']);
			return;
		}

		$id = $this->input->post('id');
		$keterangan = $this->input->post('keterangan');

		if (empty($id) || empty($keterangan)) {
			echo json_encode(['error' => 'Data tidak lengkap.']);
			return;
		}

		$this->db->trans_start();

		if ($keterangan === 'Setor') {
			// Hapus setoran - perlu cek anti-saldo negatif
			$detail = $this->db->get_where('tbdetail_simpanan', ['id' => $id])->row();

			if (!$detail) {
				$this->db->trans_rollback();
				echo json_encode(['error' => 'Data setoran tidak ditemukan.']);
				return;
			}

			$simpanan_id = $detail->simpanan_id;
			$nominal = floatval($detail->jumlah_setoran);

			// CRITICAL: Lock row dan cek saldo dengan FOR UPDATE
			$query = $this->db->query(
				"SELECT jumlah_simpanan FROM tbsimpanan WHERE id = ? FOR UPDATE",
				array($simpanan_id)
			);
			$simpanan = $query->row();

			if (!$simpanan) {
				$this->db->trans_rollback();
				echo json_encode(['error' => 'Rekening tidak ditemukan.']);
				return;
			}

			$saldo_sekarang = floatval($simpanan->jumlah_simpanan);

			// ANTI-NEGATIVE: Cek apakah saldo akan menjadi negatif
			if ($saldo_sekarang < $nominal) {
				$this->db->trans_rollback();
				echo json_encode([
					'error' => 'Gagal menghapus! Saldo saat ini Rp ' . number_format($saldo_sekarang, 0, ',', '.') .
						' tidak mencukupi untuk mengurangi setoran Rp ' . number_format($nominal, 0, ',', '.') .
						'. Saldo akan menjadi negatif.'
				]);
				return;
			}

			// Hapus detail setoran
			$this->db->delete('tbdetail_simpanan', ['id' => $id]);

			// Update saldo dengan atomic operation
			$this->db->set('jumlah_simpanan', 'jumlah_simpanan - ' . $nominal, false)
				->where('id', $simpanan_id)
				->update('tbsimpanan');

		} elseif ($keterangan === 'Tarik') {
			// Hapus penarikan - mengembalikan saldo (tidak perlu cek negatif)
			$detail = $this->db->get_where('tbdetail_penarikan', ['id' => $id])->row();

			if (!$detail) {
				$this->db->trans_rollback();
				echo json_encode(['error' => 'Data penarikan tidak ditemukan.']);
				return;
			}

			$simpanan_id = $detail->simpanan_id;
			$nominal = floatval($detail->jumlah_penarikan);

			// Hapus detail penarikan
			$this->db->delete('tbdetail_penarikan', ['id' => $id]);

			// Kembalikan saldo
			$this->db->set('jumlah_simpanan', 'jumlah_simpanan + ' . $nominal, false)
				->where('id', $simpanan_id)
				->update('tbsimpanan');
		} else {
			$this->db->trans_rollback();
			echo json_encode(['error' => 'Jenis transaksi tidak dikenal.']);
			return;
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			echo json_encode(['error' => 'Gagal menghapus data. Silakan coba lagi.']);
		} else {
			echo json_encode(['success' => 'Data berhasil dihapus.']);
		}
	}
}
