<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bunga extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Simpanan_model');
        $this->load->model('Deposito_model');
        $this->load->model('Bunga_model');
        $this->load->model('Nasabah_bunga_model');
        $this->load->model('Kategori_model');
        $this->load->model('Pegawai_model');

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
        $pegawai = $this->Pegawai_model->get_data();
        $data = [
            'pegawai' => $pegawai,
            'level' => $this->session->userdata('level'),
        ];

        $parser = [
            'judul' => "Daftar Bunga Tabungan",
            'isi' => $this->load->view('bunga/index', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetchBungaTabungan()
    {
        $start = $this->input->post('start_date');
        $end = $this->input->post('end_date');

        $level = $this->session->userdata('level');

        $list = $this->Bunga_model->get_datatables($start, $end);
        $data = [];
        $no = isset($_POST['start']) ? $_POST['start'] : 0;

        foreach ($list as $field) {
            $no++;
            $row = [];
            $row[] = $no;
            $row[] = $field->nama_lengkap;
            $row[] = $field->no_rekening;
            $row[] = date('d-m-Y', strtotime($field->tanggal_transaksi));
            $row[] = number_format($field->jumlah_transaksi, 0, ',', '.');
            $row[] = number_format($field->bunga_riil, 0, ',', '.');
            $row[] = $field->rate_bunga . " %";

            if ($level == 'Admin') {
                $row[] = "<button class='btn btn-sm btn-danger' onclick=\"deleteItem('$field->id', '$field->no_rekening')\"><i class=\"fa fa-trash\"></i></button>";
            } else {
                $row[] = "";
            }

            $data[] = $row;
        }

        $total_bunga = $this->Bunga_model->get_total_bunga_filtered($start, $end);

        $output = [
            "draw" => isset($_POST['draw']) ? $_POST['draw'] : 1,
            "recordsTotal" => $this->Bunga_model->count_all(),
            "recordsFiltered" => $this->Bunga_model->count_filtered($start, $end),
            "data" => $data,
            "total_bunga" => number_format($total_bunga, 0, ',', '.')
        ];
        echo json_encode($output);
    }

    public function fetchNasabahTabunganBunga()
    {
        // It's good practice to uncomment this for security in production
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }

        $no_rekening = $this->input->post('no_rekening');

        // The model calls are now simpler, no 'Simpanan' type needed
        $list = $this->Nasabah_bunga_model->get_datatables($no_rekening);

        $data = array();
        $no = $_POST['start'];

        foreach ($list as $field) {
            $no++;
            $row = array();
            $row[] = "<div class=\"text-center\">$no</div>";
            $row[] = date('d-m-Y', strtotime($field->tanggal_transaksi)); // Format date for consistency
            $row[] = "Rp " . number_format($field->jumlah_transaksi, 2, ',', '.');

            // This line now works correctly because the new query selects 'bunga_riil'
            $row[] = "Rp " . number_format($field->bunga_riil, 2, ',', '.');

            $row[] = number_format((float) $field->rate_bunga, 2, ',', '.') . " %";

            // The delete button remains compatible
            $row[] = "<button class=\"btn btn-danger btn-sm\" onclick=\"deleteRecordBunga('" . $field->source_id . "', '" . $field->jumlah_transaksi . "','" . $field->tipe . "')\"><i class=\"fa fa-trash fa-fw\"></i></button>";

            $data[] = $row;
        }

        $total_bunga = $this->Nasabah_bunga_model->get_total_bunga_by_rekening($no_rekening);

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->Nasabah_bunga_model->count_all($no_rekening),
            "recordsFiltered" => $this->Nasabah_bunga_model->count_filtered($no_rekening),
            "data" => $data,
            "total_bunga" => number_format($total_bunga, 2, ',', '.')
        );

        echo json_encode($output);
    }

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');

            // Get the transaksi bunga simpanan
            $transaksi = $this->Bunga_model->get_data_by_id($id);

            if (!$transaksi) {
                echo json_encode(['error' => 'Transaksi tidak ditemukan.']);
                return;
            }

            $this->db->trans_start();

            // 1. Lock tabel tbsimpanan for update
            $simpanan_query = $this->db->query(
                "SELECT id, jumlah_simpanan FROM tbsimpanan WHERE id = ? FOR UPDATE",
                [$transaksi->simpanan_id]
            );
            $simpanan = $simpanan_query->row();

            if (!$simpanan) {
                $this->db->trans_rollback();
                echo json_encode(['error' => 'Data simpanan tidak ditemukan.']);
                return;
            }

            // 2. Recalculate and update
            $selisih = $simpanan->jumlah_simpanan - $transaksi->jumlah_transaksi;
            $this->db->where('id', $transaksi->simpanan_id);
            $this->db->update('tbsimpanan', ['jumlah_simpanan' => $selisih]);

            // 3. Delete transaksi
            $this->Bunga_model->delete_data($id);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                echo json_encode(['error' => 'Gagal menghapus bunga tabungan.']);
            } else {
                echo json_encode(['success' => 'Bunga tabungan berhasil dihapus.']);
            }
        }
    }

    public function run_bunga()
    {
        if (!$this->input->is_ajax_request()) {
            redirect('unauthorized_403');
        }

        $processed = $this->Bunga_model->checkAndRunBunga();

        if ($processed) {
            $msg = ['success' => 'Bunga tabungan berhasil dihitung'];
        } else {
            $msg = ['error' => 'Bunga tabungan sudah diperbarui bulan ini.'];
        }

        header('Content-Type: application/json');
        echo json_encode($msg);
    }

    /**
     * Print monthly interest report for Tabungan (Savings)
     */
    public function print_laporan_bulanan()
    {
        $start_date = $this->input->get('start_date') ?? date('Y-m-01');
        $end_date = $this->input->get('end_date') ?? date('Y-m-t');

        // Get data for the report
        $list = $this->Bunga_model->get_report_data($start_date, $end_date);
        $total_bunga = $this->Bunga_model->get_total_bunga_filtered($start_date, $end_date);

        // Format dates for display
        $formatter = new \IntlDateFormatter('id_ID', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
        $formatter->setPattern('MMMM yyyy');
        $periode = $formatter->format(new DateTime($start_date));

        $formatter->setPattern('d MMMM yyyy');
        $tanggal_cetak = $formatter->format(new DateTime());

        $data = [
            'list' => $list,
            'total_bunga' => $total_bunga,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'periode' => $periode,
            'tanggal_cetak' => $tanggal_cetak,
            'tipe' => 'Tabungan'
        ];

        $html = $this->load->view('bunga/cetak_laporan', $data, TRUE);

        $this->load->library('dompdf_lib');
        $this->dompdf_lib->loadHtml($html);
        $this->dompdf_lib->setPaper('A4', 'portrait');
        $this->dompdf_lib->render();

        $filename = "Laporan_Bunga_Tabungan_" . date('Y-m', strtotime($start_date)) . ".pdf";
        $this->dompdf_lib->stream($filename, false);
    }

    /**
     * Search simpanan for Select2 dropdown (AJAX)
     */
    public function search_simpanan()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $search = $this->input->get('q') ?? '';
        $target_date = $this->input->get('target_date') ?? date('Y-m-d');

        $month = date('m', strtotime($target_date));
        $year = date('Y', strtotime($target_date));

        // Get simpanan that don't have bunga for this month
        $this->db->select('s.id, s.no_rekening, s.jumlah_simpanan, s.bunga_rate,
            COALESCE(s.nama_nasabah, n.nama_lengkap) as nama_nasabah');
        $this->db->from('tbsimpanan s');
        $this->db->join('tbnasabah n', 'n.id = s.nasabah_id', 'left');
        $this->db->where('s.status', 'aktif');

        // Exclude simpanan that already have bunga this month
        $subquery = $this->db->select('simpanan_id')
            ->where('MONTH(tanggal_transaksi)', $month)
            ->where('YEAR(tanggal_transaksi)', $year)
            ->get_compiled_select('tbtransaksi');
        $this->db->where("s.id NOT IN ($subquery)", null, false);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('s.no_rekening', $search);
            $this->db->or_like('s.nama_nasabah', $search);
            $this->db->or_like('n.nama_lengkap', $search);
            $this->db->group_end();
        }

        $this->db->order_by('s.no_rekening', 'ASC');
        $this->db->limit(20);

        $results = $this->db->get()->result();

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'id' => $row->id,
                'text' => $row->no_rekening . ' - ' . $row->nama_nasabah,
                'jumlah_simpanan' => $row->jumlah_simpanan,
                'bunga_rate' => $row->bunga_rate,
                'nama_nasabah' => $row->nama_nasabah
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['results' => $data]);
    }

    /**
     * Save manual bunga entry for tabungan (AJAX)
     */
    public function simpan_bunga_manual()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        header('Content-Type: application/json');

        // Validation
        $simpanan_id = $this->input->post('simpanan_id');
        $tanggal = $this->input->post('tanggal_transaksi');
        $jumlah_bunga = str_replace(['.', ','], ['', '.'], $this->input->post('jumlah_bunga'));

        if (empty($simpanan_id) || empty($tanggal) || empty($jumlah_bunga)) {
            echo json_encode(['error' => 'Simpanan, tanggal, dan jumlah bunga wajib diisi.']);
            return;
        }

        // Check for duplicate (only 1 per month allowed)
        $month = date('m', strtotime($tanggal));
        $year = date('Y', strtotime($tanggal));

        $exists = $this->db->where('simpanan_id', $simpanan_id)
            ->where('MONTH(tanggal_transaksi)', $month)
            ->where('YEAR(tanggal_transaksi)', $year)
            ->count_all_results('tbtransaksi');

        if ($exists > 0) {
            $bulan = date('F Y', strtotime($tanggal));
            echo json_encode(['error' => "Bunga untuk tabungan ini pada bulan $bulan sudah ada."]);
            return;
        }

        // Get simpanan details
        $simpanan = $this->Simpanan_model->get_data_by_id($simpanan_id);
        if (!$simpanan) {
            echo json_encode(['error' => 'Data simpanan tidak ditemukan.']);
            return;
        }

        // Prepare data
        $data = [
            'simpanan_id' => $simpanan_id,
            'no_rekening' => $simpanan->no_rekening,
            'nama_nasabah' => $simpanan->nama_nasabah,
            'tanggal_transaksi' => $tanggal,
            'jumlah_transaksi' => $jumlah_bunga,
            'rate_bunga' => $simpanan->bunga_rate,
            'bunga_riil' => $jumlah_bunga
        ];

        // Insert into tbtransaksi
        $this->db->trans_start();

        $this->db->insert('tbtransaksi', $data);

        // Update simpanan balance
        $new_balance = $simpanan->jumlah_simpanan + $jumlah_bunga;
        $this->db->where('id', $simpanan_id);
        $this->db->update('tbsimpanan', ['jumlah_simpanan' => $new_balance]);

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            echo json_encode(['success' => 'Bunga manual berhasil disimpan.']);
        } else {
            echo json_encode(['error' => 'Gagal menyimpan bunga manual.']);
        }
    }
}

