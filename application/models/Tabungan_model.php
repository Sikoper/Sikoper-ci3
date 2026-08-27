<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tabungan_model extends CI_Model
{
	var $table = 'tbsimpanan';
	var $column_order = array(null, 'tanggal', 'jumlah_uang', 'keterangan', 'pegawai', null);
	var $column_search = array('trans.tanggal', 'trans.keterangan', 'tbpegawai.nama_lengkap');
	var $order = array('tanggal' => 'ASC');

	private function _get_datatables_query($id = null)
	{
		$subquery = "
        (
            SELECT 
                ds.id AS detail_id,
                tbs.id AS simpanan_id,
                tbs.pegawai_id,
                ds.tanggal_setoran AS tanggal,
                ds.jumlah_setoran AS jumlah_uang,
                tp.nama_lengkap as pegawai,
                'Setor' AS keterangan
            FROM tbdetail_simpanan ds
            JOIN tbpegawai tp ON tp.id = ds.pegawai_id
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id

            UNION ALL

            SELECT 
                dp.id AS detail_id,
                tbs.id AS simpanan_id,
                tbs.pegawai_id,
                dp.tanggal_penarikan AS tanggal,
                dp.jumlah_penarikan AS jumlah_uang,
                tp.nama_lengkap as pegawai,
                'Tarik' AS keterangan
            FROM tbdetail_penarikan dp
            JOIN tbpegawai tp ON tp.id = dp.pegawai_id
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
        ) AS trans
        ";

		$this->db->from($subquery);

		if ($id !== null) {
			$this->db->where('trans.simpanan_id', $id);
		}

		$i = 0;
		foreach ($this->column_search as $item) {
			if ($_POST['search']['value']) {
				if ($i === 0) {
					$this->db->group_start();
					$this->db->like($item, $_POST['search']['value']);
				} else {
					$this->db->or_like($item, $_POST['search']['value']);
				}

				if (count($this->column_search) - 1 == $i)
					$this->db->group_end();
			}
			$i++;
		}

		if (isset($_POST['order'])) {
			$this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
		} else if (isset($this->order)) {
			$order = $this->order;
			$this->db->order_by(key($order), $order[key($order)]);
		}
	}

	function get_datatables($id = null)
	{
		$this->_get_datatables_query($id);
		if ($_POST['length'] != -1)
			$this->db->limit($_POST['length'], $_POST['start']);
		$query = $this->db->get();
		return $query->result();
	}

	function count_filtered($id = null)
	{
		$this->_get_datatables_query($id);
		$query = $this->db->get();
		return $query->num_rows();
	}

	public function count_all($id = null)
	{
		$where = "";
		$bind = [];

		if ($id !== null) {
			$where = "WHERE simpanan_id = ?";
			$bind[] = $id;
		}

		$sql = "
        SELECT COUNT(*) AS total FROM (
            SELECT 
                tbs.id AS simpanan_id
            FROM tbdetail_simpanan ds
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id

            UNION ALL

            SELECT 
                tbs.id AS simpanan_id
            FROM tbdetail_penarikan dp
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
        ) AS trans
        $where
    ";

		$query = $this->db->query($sql, $bind);
		return $query->row()->total;
	}

	public function get_data_by_id($id)
	{
		$sql = "
        SELECT tbs.id, tbs.no_rekening, tbs.nasabah_id, tbnasabah.nama_lengkap
        FROM (
            SELECT tbs.id, tbs.no_rekening, tbs.nasabah_id
            FROM tbdetail_simpanan ds
            JOIN tbsimpanan tbs ON tbs.id = ds.simpanan_id
            WHERE tbs.id = ?

            UNION ALL

            SELECT tbs.id, tbs.no_rekening, tbs.nasabah_id
            FROM tbdetail_penarikan dp
            JOIN tbsimpanan tbs ON tbs.id = dp.simpanan_id
            WHERE tbs.id = ?
        ) AS tbs
        JOIN tbnasabah ON tbnasabah.id = tbs.nasabah_id
        LIMIT 1
    ";

		$query = $this->db->query($sql, [$id, $id]);
		return $query->row();
	}

	public function get_transaksi_rekening_koran($source_id, $tanggal_mulai = null, $tanggal_akhir = null, $jenis_laporan = 3, $jenis_tabungan = 'simpanan')
	{
		$queries = [];
		$saldo_awal = 0;

		if ($jenis_tabungan === 'simpanan') {
			// --- Hitung saldo awal Simpanan (Corrected and Simplified) ---
			$this->db->select_sum('jumlah_setoran', 'total');
			$this->db->where('simpanan_id', $source_id);
			if ($tanggal_mulai) {
				// FIX: Cast the datetime column to date for accurate comparison
				$this->db->where('DATE(tanggal_setoran) <', $tanggal_mulai);
			}
			$q1 = $this->db->get('tbdetail_simpanan');
			$setor_awal = ($q1->num_rows() > 0 && $q1->row()->total !== null) ? (float) $q1->row()->total : 0;

			$this->db->select_sum('jumlah_penarikan', 'total');
			$this->db->where('simpanan_id', $source_id);
			if ($tanggal_mulai) {
				// FIX: Cast the datetime column to date for accurate comparison
				$this->db->where('DATE(tanggal_penarikan) <', $tanggal_mulai);
			}
			$q2 = $this->db->get('tbdetail_penarikan');
			$tarik_awal = ($q2->num_rows() > 0 && $q2->row()->total !== null) ? (float) $q2->row()->total : 0;

			$this->db->select_sum('jumlah_transaksi', 'total');
			$this->db->where('simpanan_id', $source_id);
			if ($tanggal_mulai) {
				// FIX: Assuming tanggal_transaksi is also datetime or date, casting is safest.
				$this->db->where('DATE(tanggal_transaksi) <', $tanggal_mulai);
			}
			$q3 = $this->db->get('tbtransaksi');
			$bunga_awal = ($q3->num_rows() > 0 && $q3->row()->total !== null) ? (float) $q3->row()->total : 0;

			$saldo_awal = ($setor_awal + $bunga_awal) - $tarik_awal;

			// --- Hitung saldo pindahan (initial balance dari import yg tidak tercatat sebagai transaksi) ---
			$simpanan_data = $this->db->where('id', $source_id)->get('tbsimpanan')->row();
			if ($simpanan_data) {
				// Total SEMUA transaksi yang ada di database (tanpa filter tanggal)
				$all_setor_q = $this->db->select_sum('jumlah_setoran', 'total')->where('simpanan_id', $source_id)->get('tbdetail_simpanan');
				$all_setor = ($all_setor_q->num_rows() > 0 && $all_setor_q->row()->total !== null) ? (float) $all_setor_q->row()->total : 0;

				$all_tarik_q = $this->db->select_sum('jumlah_penarikan', 'total')->where('simpanan_id', $source_id)->get('tbdetail_penarikan');
				$all_tarik = ($all_tarik_q->num_rows() > 0 && $all_tarik_q->row()->total !== null) ? (float) $all_tarik_q->row()->total : 0;

				$all_bunga_q = $this->db->select_sum('jumlah_transaksi', 'total')->where('simpanan_id', $source_id)->get('tbtransaksi');
				$all_bunga = ($all_bunga_q->num_rows() > 0 && $all_bunga_q->row()->total !== null) ? (float) $all_bunga_q->row()->total : 0;

				// Saldo pindahan = jumlah_simpanan saat ini - total semua transaksi tercatat
				$saldo_pindahan = (float) $simpanan_data->jumlah_simpanan - (($all_setor + $all_bunga) - $all_tarik);
				if ($saldo_pindahan > 0) {
					$saldo_awal += $saldo_pindahan;
				}
			}

			// --- Transaksi Setoran ---
			if ($jenis_laporan == 1 || $jenis_laporan == 3) {
				$this->db->select("
                    ds.tanggal_setoran AS tanggal,
                    0 AS debit,
                    ds.jumlah_setoran AS kredit,
                    'Setoran Tunai' AS keterangan,
                    p.nama_lengkap AS pegawai
                ");
				$this->db->from('tbdetail_simpanan ds');
				$this->db->join('tbpegawai p', 'p.id = ds.pegawai_id', 'left');
				$this->db->where('ds.simpanan_id', $source_id);
				if ($tanggal_mulai && $tanggal_akhir) {
					// FIX: Use DATE() to correctly filter the range on a DATETIME column
					$this->db->where('DATE(ds.tanggal_setoran) >=', $tanggal_mulai);
					$this->db->where('DATE(ds.tanggal_setoran) <=', $tanggal_akhir);
				}
				// --- REMOVED: The condition that excluded the "first setor" date ---
				$queries[] = $this->db->get_compiled_select();
			}

			// --- Transaksi Penarikan ---
			if ($jenis_laporan == 2 || $jenis_laporan == 3) {
				$this->db->select("
                    dp.tanggal_penarikan AS tanggal,
                    dp.jumlah_penarikan AS debit,
                    0 AS kredit,
                    'Penarikan Tunai' AS keterangan,
                    p.nama_lengkap AS pegawai
                ");
				$this->db->from('tbdetail_penarikan dp');
				$this->db->join('tbpegawai p', 'p.id = dp.pegawai_id', 'left');
				$this->db->where('dp.simpanan_id', $source_id);
				if ($tanggal_mulai && $tanggal_akhir) {
					// FIX: Use DATE() to correctly filter the range on a DATETIME column
					$this->db->where('DATE(dp.tanggal_penarikan) >=', $tanggal_mulai);
					$this->db->where('DATE(dp.tanggal_penarikan) <=', $tanggal_akhir);
				}
				$queries[] = $this->db->get_compiled_select();
			}

			// --- Transaksi Bunga Simpanan ---
			$this->db->select("
                tanggal_transaksi AS tanggal,
                0 AS debit,
                jumlah_transaksi AS kredit,
                'Bunga Simpanan' AS keterangan,
                'SYSTEM' AS pegawai
            ");
			$this->db->from('tbtransaksi');
			$this->db->where('simpanan_id', $source_id);
			if ($tanggal_mulai && $tanggal_akhir) {
				// FIX: Use DATE() for consistency and safety
				$this->db->where('DATE(tanggal_transaksi) >=', $tanggal_mulai);
				$this->db->where('DATE(tanggal_transaksi) <=', $tanggal_akhir);
			}
			$queries[] = $this->db->get_compiled_select();
		} else if ($jenis_tabungan === 'deposito') {
			// ... (Your deposito logic can remain, applying the DATE() fix if necessary) ...
		}

		// --- Final Execution (No changes needed here) ---
		if (empty($queries)) {
			return [
				'saldo_awal' => $saldo_awal,
				'total_setor' => 0,
				'total_tarik' => 0,
				'saldo_akhir' => $saldo_awal,
				'transaksi' => []
			];
		}

		$final_query = implode(" UNION ALL ", $queries) . " ORDER BY tanggal ASC";
		$transaksi = $this->db->query($final_query)->result();

		$total_setor_periode = array_sum(array_column($transaksi, 'kredit'));
		$total_tarik_periode = array_sum(array_column($transaksi, 'debit'));
		$saldo_akhir = $saldo_awal + $total_setor_periode - $total_tarik_periode;

		return [
			'saldo_awal' => $saldo_awal,
			'total_setor' => $total_setor_periode,
			'total_tarik' => $total_tarik_periode,
			'saldo_akhir' => $saldo_akhir,
			'transaksi' => $transaksi
		];
	}


	public function import_full_migration($file_path, $pegawai_id, $jenistabungan_id, $sheets_to_import = null, $year = null) {
		$this->load->library('Tabungan_import_service');
		return $this->tabungan_import_service->import_full_migration($file_path, $pegawai_id, $jenistabungan_id, $sheets_to_import, $year);
	}

	public function import_saldo_akhir_tahun($file_path, $pegawai_id, $jenistabungan_id, $delete_existing = true, $year = null) {
		$this->load->library('Tabungan_import_service');
		return $this->tabungan_import_service->import_saldo_akhir_tahun($file_path, $pegawai_id, $jenistabungan_id, $delete_existing, $year);
	}

	public function import_with_rekap_bunga($file_path, $pegawai_id, $jenistabungan_id, $delete_existing = true, $year = null) {
		$this->load->library('Tabungan_import_service');
		return $this->tabungan_import_service->import_with_rekap_bunga($file_path, $pegawai_id, $jenistabungan_id, $delete_existing, $year);
	}

	public function import_by_month($file_path, $month_code, $year, $pegawai_id, $jenistabungan_id, $delete_month = true) {
		$this->load->library('tabungan_import_service');
		return $this->tabungan_import_service->import_by_month($file_path, $month_code, $year, $pegawai_id, $jenistabungan_id, $delete_month);
	}

	public function get_excel_sheet_names($file_path) {
		$this->load->library('tabungan_import_service');
		return $this->tabungan_import_service->get_excel_sheet_names($file_path);
	}

	public function preview_sheet_data($file_path, $month_code, $limit = 50) {
		$this->load->library('tabungan_import_service');
		return $this->tabungan_import_service->preview_sheet_data($file_path, $month_code, $limit);
	}

	public function import_by_month_with_mapping($file_path, $month_code, $year, $pegawai_id, $jenistabungan_id, $mapping, $delete_month = true) {
		$this->load->library('tabungan_import_service');
		return $this->tabungan_import_service->import_by_month_with_mapping($file_path, $month_code, $year, $pegawai_id, $jenistabungan_id, $mapping, $delete_month);
	}
}
