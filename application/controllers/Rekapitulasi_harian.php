<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekapitulasi_harian extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		// The model name should match the file name
		$this->load->model('Rekapitulasi_harian_model');

		// Your role-based access control
		$allowed_roles = ['Admin', 'Pegawai', 'Direktur'];
		if (!in_array($this->session->userdata('level'), $allowed_roles)) {
			redirect('unauthorized_403');
		}
	}

	public function index()
	{
		$data = [
			'title' => 'Rekapitulasi Transaksi Harian',
		];

		$parser = [
			'judul' => "Rekapitulasi Harian",
			'isi' => $this->load->view('rekapitulasi_harian/index', $data, TRUE)
		];
		$this->parser->parse('templates/main', $parser);
	}

	/**
	 * Fetch data for Deposit (Setoran) DataTable.
	 */
	public function fetch_setoran_data()
	{
		// Get selected date from POST, default to today
		$tanggal = $this->input->post('tanggal') ?: date('Y-m-d');

		$list = $this->Rekapitulasi_harian_model->get_setoran_datatables($tanggal);
		$summary = $this->Rekapitulasi_harian_model->get_setoran_summary($tanggal);

		$data = array();
		$no = $this->input->post('start');
		foreach ($list as $item) {
			$no++;
			$row = array();
			$row[] = '<div class="text-center">' . $no . '</div>';
			$row[] = $item->no_rekening ?? '-';
			$row[] = $item->nama_nasabah ?? '-';
			$row[] = '<div class="text-end fw-bold">Rp ' . number_format($item->jumlah_setoran, 0, ',', '.') . '</div>';
			$data[] = $row;
		}

		$total_setoran = $summary->total_setoran ?? 0;

		$output = array(
			"draw" => (int) $this->input->post('draw'),
			"recordsTotal" => $this->Rekapitulasi_harian_model->count_all_setoran($tanggal),
			"recordsFiltered" => $this->Rekapitulasi_harian_model->count_filtered_setoran($tanggal),
			"data" => $data,
			"total_setoran" => 'Rp ' . number_format($total_setoran, 0, ',', '.'),
		);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($output));
	}

	/**
	 * Fetch data for Withdrawal (Penarikan) DataTable.
	 */
	public function fetch_penarikan_data()
	{
		// Get selected date from POST, default to today
		$tanggal = $this->input->post('tanggal') ?: date('Y-m-d');

		$list = $this->Rekapitulasi_harian_model->get_penarikan_datatables($tanggal);
		$summary = $this->Rekapitulasi_harian_model->get_penarikan_summary($tanggal);

		$data = array();
		$no = $this->input->post('start');
		foreach ($list as $item) {
			$no++;
			$row = array();
			$row[] = '<div class="text-center">' . $no . '</div>';
			$row[] = $item->no_rekening ?? '-';
			$row[] = $item->nama_nasabah ?? '-';
			$row[] = '<div class="text-end fw-bold">Rp ' . number_format($item->jumlah_penarikan, 0, ',', '.') . '</div>';
			$data[] = $row;
		}

		$total_penarikan = $summary->total_penarikan ?? 0;

		$output = array(
			"draw" => (int) $this->input->post('draw'),
			"recordsTotal" => $this->Rekapitulasi_harian_model->count_all_penarikan($tanggal),
			"recordsFiltered" => $this->Rekapitulasi_harian_model->count_filtered_penarikan($tanggal),
			"data" => $data,
			"total_penarikan" => 'Rp ' . number_format($total_penarikan, 0, ',', '.'),
		);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($output));
	}

	/**
	 * Diagnostic page: detect ghost setoran & name mismatches
	 * Access: /rekapitulasi_harian/diagnose
	 */
	public function diagnose()
	{
		// Admin only
		if ($this->session->userdata('level') !== 'Admin') {
			redirect('unauthorized_403');
		}

		// 1. Detect ghost setoran (saldo awal inserted as setoran at 00:00:01)
		$ghost_setoran = $this->db->query("
            SELECT tds.id, tds.simpanan_id, tds.tanggal_setoran, tds.jumlah_setoran,
                   ts.no_rekening, ts.nama_nasabah
            FROM tbdetail_simpanan tds
            JOIN tbsimpanan ts ON ts.id = tds.simpanan_id
            WHERE TIME(tds.tanggal_setoran) = '00:00:01'
            ORDER BY tds.tanggal_setoran ASC
        ")->result();

		// 2. Detect name mismatches (tbsimpanan.nama_nasabah != tbnasabah.nama_lengkap)
		$name_mismatches = $this->db->query("
            SELECT ts.id as simpanan_id, ts.no_rekening,
                   ts.nama_nasabah as nama_di_simpanan,
                   tn.nama_lengkap as nama_di_nasabah,
                   tn.id as nasabah_id
            FROM tbsimpanan ts
            JOIN tbnasabah tn ON tn.id = ts.nasabah_id
            WHERE LOWER(TRIM(ts.nama_nasabah)) != LOWER(TRIM(tn.nama_lengkap))
              AND ts.nama_nasabah IS NOT NULL
              AND tn.nama_lengkap IS NOT NULL
            ORDER BY ts.no_rekening ASC
        ")->result();

		// 3. Detect duplicate nasabah (same name, different IDs)
		$duplicate_nasabah = $this->db->query("
            SELECT LOWER(TRIM(nama_lengkap)) as nama, COUNT(*) as jumlah,
                   GROUP_CONCAT(id ORDER BY id) as ids
            FROM tbnasabah
            GROUP BY LOWER(TRIM(nama_lengkap))
            HAVING COUNT(*) > 1
            ORDER BY jumlah DESC
        ")->result();

		$data = [
			'title' => 'Diagnostik Data Import',
			'ghost_setoran' => $ghost_setoran,
			'name_mismatches' => $name_mismatches,
			'duplicate_nasabah' => $duplicate_nasabah
		];

		$parser = [
			'judul' => "Diagnostik Data Import",
			'isi' => $this->load->view('rekapitulasi_harian/diagnose', $data, TRUE)
		];
		$this->parser->parse('templates/main', $parser);
	}

	/**
	 * Auto-fix: Delete ghost setoran records (saldo awal as setoran)
	 * AJAX endpoint for the diagnose page
	 */
	public function fix_ghost_setoran()
	{
		if ($this->session->userdata('level') !== 'Admin') {
			echo json_encode(['success' => false, 'error' => 'Unauthorized']);
			return;
		}

		// Delete ghost setoran records (time = 00:00:01 is the signature)
		$this->db->query("
            DELETE FROM tbdetail_simpanan
            WHERE TIME(tanggal_setoran) = '00:00:01'
        ");
		$deleted = $this->db->affected_rows();

		// Recalculate saldo for affected simpanan accounts
		// Get all simpanan IDs
		$all_simpanan = $this->db->get('tbsimpanan')->result();
		$recalculated = 0;

		foreach ($all_simpanan as $simpanan) {
			// Sum actual setoran
			$setoran = $this->db->select_sum('jumlah_setoran')
				->where('simpanan_id', $simpanan->id)
				->get('tbdetail_simpanan')->row();
			$total_setoran = ($setoran && $setoran->jumlah_setoran) ? floatval($setoran->jumlah_setoran) : 0;

			// Sum penarikan
			$penarikan = $this->db->select_sum('jumlah_penarikan')
				->where('simpanan_id', $simpanan->id)
				->get('tbdetail_penarikan')->row();
			$total_penarikan = ($penarikan && $penarikan->jumlah_penarikan) ? floatval($penarikan->jumlah_penarikan) : 0;

			// Get saldo_pindahan from tbsimpanan (if stored)
			$saldo_pindahan = floatval($simpanan->saldo_pindahan ?? 0);

			// Recalculate: saldo_pindahan + setoran - penarikan
			$new_saldo = $saldo_pindahan + $total_setoran - $total_penarikan;

			$this->db->where('id', $simpanan->id)
				->update('tbsimpanan', ['jumlah_simpanan' => $new_saldo]);
			$recalculated++;
		}

		echo json_encode([
			'success' => true,
			'deleted' => $deleted,
			'recalculated' => $recalculated
		]);
	}

	/**
	 * Auto-fix: For each simpanan with a name mismatch,
	 * find or create a nasabah with the EXACT FULL name from Excel,
	 * then re-link the simpanan to the correct nasabah.
	 * AJAX endpoint for the diagnose page
	 */
	public function fix_name_mismatches()
	{
		if ($this->session->userdata('level') !== 'Admin') {
			echo json_encode(['success' => false, 'error' => 'Unauthorized']);
			return;
		}

		$fixed = 0;
		$created = 0;

		// Get ALL simpanan where nama_nasabah doesn't match tbnasabah.nama_lengkap
		$mismatched = $this->db->query("
			SELECT ts.id as simpanan_id, ts.no_rekening, ts.nasabah_id,
				   ts.nama_nasabah as nama_excel
			FROM tbsimpanan ts
			JOIN tbnasabah tn ON tn.id = ts.nasabah_id
			WHERE LOWER(TRIM(ts.nama_nasabah)) != LOWER(TRIM(tn.nama_lengkap))
			  AND ts.nama_nasabah IS NOT NULL
			  AND tn.nama_lengkap IS NOT NULL
			  AND TRIM(ts.nama_nasabah) != ''
			ORDER BY ts.no_rekening
		")->result();

		// Cache to avoid re-querying same names
		$name_to_nasabah_id = [];

		foreach ($mismatched as $row) {
			$nama_excel = trim($row->nama_excel);
			$nama_lower = strtolower($nama_excel);

			// Skip if empty
			if (empty($nama_excel))
				continue;

			// Check cache first
			if (isset($name_to_nasabah_id[$nama_lower])) {
				$correct_nasabah_id = $name_to_nasabah_id[$nama_lower];
			} else {
				// Find existing nasabah with this EXACT full name
				$existing = $this->db
					->where('LOWER(TRIM(nama_lengkap))', $nama_lower)
					->get('tbnasabah')->row();

				if ($existing) {
					$correct_nasabah_id = $existing->id;
				} else {
					// Create new nasabah with the full Excel name
					$this->db->insert('tbnasabah', [
						'nik' => '-',
						'nama_lengkap' => $nama_excel,
						'jenis_kelamin' => '?',
						'tempat_lahir' => '-',
						'tanggal_lahir' => null,
						'agama' => '-',
						'alamat' => '-',
						'pekerjaan' => '-',
						'telp' => '-',
						'nama_ibu_kandung' => '-',
						'pegawai_id' => 1,
						'created_at' => date('Y-m-d H:i:s')
					]);
					$correct_nasabah_id = $this->db->insert_id();
					$created++;
				}

				$name_to_nasabah_id[$nama_lower] = $correct_nasabah_id;
			}

			// Re-link simpanan to the correct nasabah
			if ($correct_nasabah_id != $row->nasabah_id) {
				$this->db->where('id', $row->simpanan_id)
					->update('tbsimpanan', ['nasabah_id' => $correct_nasabah_id]);
			} else {
				// Same nasabah but name case/spacing differs — update nasabah name
				$this->db->where('id', $correct_nasabah_id)
					->update('tbnasabah', ['nama_lengkap' => $nama_excel]);
			}
			$fixed++;
		}

		echo json_encode([
			'success' => true,
			'fixed' => $fixed,
			'created' => $created,
			'message' => "$fixed simpanan di-fix, $created nasabah baru dibuat"
		]);
	}
}
