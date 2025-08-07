<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); z-index:9999;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center;">
        <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
        <p style="margin-top: 1rem; color:#333; font-weight:bold;">Memproses perhitungan bunga...</p>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <?php if ($level == 'Admin'): ?>
                <button class="btn btn-success" id="btnPembungaanDeposito">
                    <i class="fa fa-calculator"></i>
                    Hitung Bunga Deposito Hari Ini
                </button>
            <?php endif; ?>
        </h4>
        <div class="row mt-3">
            <div class="col-md-3">
                <label>Dari Tanggal</label>
                <input type="date" id="start_date" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-3">
                <label>Sampai Tanggal</label>
                <input type="date" id="end_date" class="form-control" value="<?= date('Y-m-t') ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button id="filterBtn" class="btn btn-info w-100">Filter</button>
            </div>
            <div class="col-md-3 text-end d-flex align-items-center justify-content-end">
                <h5 class="mb-0">Total Bunga: <span id="total_bunga_display" class="text-success">Rp 0</span></h5>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="tabel_bunga" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nasabah</th>
                        <th>No. Rekening</th>
                        <th>Tanggal Bunga</th>
                        <th>Jumlah Bunga</th>
                        <th>Rate</th>
                        <?php if ($level == 'Admin'): ?>
                            <th class="text-center">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Fungsi deleteItem diletakkan di luar document.ready agar bisa diakses secara global oleh tombol
function deleteItem(id, nama) {
    Swal.fire({
        title: "Anda Yakin?",
        html: `Ingin menghapus data bunga dari no. rekening: <strong>${nama}</strong>?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: "POST",
                url: "<?= base_url('bunga_deposito/delete') ?>",
                data: { id: id },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire("Berhasil!", response.success, "success");
                        $('#tabel_bunga').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire("Gagal!", response.error, "error");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error (Hapus Bunga):", { status, error, response: xhr.responseText });
                    Swal.fire('Oops... Terjadi Kesalahan', 'Sistem tidak dapat terhubung ke server.', 'error');
                }
            });
        }
    });
}

// Semua kode jQuery dibungkus di dalam $(document).ready()
$(document).ready(function() {
    var table = $('#tabel_bunga').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        order: [[ 3, "desc" ]],
        ajax: {
            url: "<?= site_url('bunga_deposito/fetchBungaDeposito') ?>",
            type: "POST",
            data: function(d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            },
            dataSrc: function(json) {
                $('#total_bunga_display').text('Rp ' + (json.total_bunga || '0'));
                return json.data;
            }
        },
        "columns": [
            { "data": 0, "orderable": false },
            { "data": 1 },
            { "data": 2 },
            { "data": 3 },
            { "data": 4, "className": "text-end" },
            { "data": 5, "className": "text-center" },
            <?php if ($this->session->userdata('level') == 'Admin'): ?>,
            { "data": 6, "orderable": false, "className": "text-center" }
            <?php endif; ?>
        ]
    });

    $('#filterBtn').on('click', function() {
        table.ajax.reload();
    });

    $('#btnPembungaanDeposito').click(function() {
        Swal.fire({
            title: 'Konfirmasi Proses Pembungaan',
            html: "Anda yakin ingin menjalankan proses perhitungan bunga untuk hari ini? <br><b>Proses ini tidak dapat dibatalkan.</b>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Lanjutkan Proses!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= site_url('bunga_deposito/run_bunga_deposito') ?>",
                    dataType: "json",
                    beforeSend: function() {
                        $('#btnPembungaanDeposito').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Memproses...');
                        $('#loadingOverlay').fadeIn();
                    },
                    complete: function() {
                        $('#btnPembungaanDeposito').prop('disabled', false).html('<i class="fa fa-calculator"></i> Hitung Bunga Deposito Hari Ini');
                        $('#loadingOverlay').fadeOut();
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire("Berhasil!", response.success, "success").then(() => table.ajax.reload()); 
                        } else if (response.empty) {
                            Swal.fire("Informasi", response.empty, "info");
                        } else {
                            Swal.fire("Gagal!", response.error || "Terjadi kesalahan.", "error");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error (Hitung Bunga):", { status, error, response: xhr.responseText });
                        Swal.fire('Oops... Terjadi Kesalahan', 'Sistem tidak dapat terhubung ke server.', 'error');
                    }
                });
            }
        });
    });
});
</script>