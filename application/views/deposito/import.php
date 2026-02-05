<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">
            <i class="fa fa-file-excel-o"></i> Import Data Deposito dari Excel
        </h4>
        <a href="<?= base_url('deposito') ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong><i class="fa fa-info-circle"></i> Petunjuk:</strong><br>
            <ul class="mb-0">
                <li>File harus berformat <strong>.xls</strong> atau <strong>.xlsx</strong></li>
                <li>Sheet yang akan diproses: <strong>DAFTAR DEPOSAN</strong>, <strong>PEMBAYARAN BUNGA
                        DEPOSITO</strong>, <strong>HUTANG BUNGA</strong></li>
                <li>Data nasabah baru akan otomatis dibuat jika belum ada</li>
                <li>Data deposito yang sudah ada (berdasarkan NO.SERI) akan diupdate</li>
            </ul>
        </div>

        <form id="form-import" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">File Excel <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xls,.xlsx"
                            required>
                        <small class="text-muted">Maksimal 100MB</small>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary" id="btn-import">
                    <i class="fa fa-upload"></i> Import Data
                </button>
                <button type="button" class="btn btn-secondary" id="btn-loading" style="display:none" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Memproses...
                </button>
            </div>
        </form>

        <!-- Results Section -->
        <div id="import-results" class="mt-4" style="display:none">
            <hr>
            <h5><i class="fa fa-list"></i> Hasil Import</h5>
            <div id="results-content"></div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#form-import').on('submit', function (e) {
            e.preventDefault();

            var formData = new FormData(this);

            $('#btn-import').hide();
            $('#btn-loading').show();
            $('#import-results').hide();

            $.ajax({
                url: '<?= base_url("deposito/proses_import") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    $('#btn-import').show();
                    $('#btn-loading').hide();
                    $('#import-results').show();

                    var html = '';

                    if (response.success) {
                        html += '<div class="alert alert-success"><strong><i class="fa fa-check-circle"></i> Import Berhasil!</strong></div>';
                    } else {
                        html += '<div class="alert alert-danger"><strong><i class="fa fa-times-circle"></i> Import Gagal</strong></div>';
                    }

                    html += '<div class="row mb-3">';
                    html += '<div class="col-md-4"><div class="card bg-light"><div class="card-body text-center">';
                    html += '<h5 class="mb-0">' + (response.nasabah ? response.nasabah.created : 0) + '</h5>';
                    html += '<small>Nasabah Baru</small></div></div></div>';

                    html += '<div class="col-md-4"><div class="card bg-success text-white"><div class="card-body text-center">';
                    html += '<h5 class="mb-0">' + (response.deposito ? response.deposito.inserted : 0) + '</h5>';
                    html += '<small>Deposito Ditambahkan</small></div></div></div>';

                    html += '<div class="col-md-4"><div class="card bg-info text-white"><div class="card-body text-center">';
                    html += '<h5 class="mb-0">' + (response.deposito ? response.deposito.updated : 0) + '</h5>';
                    html += '<small>Deposito Diupdate</small></div></div></div>';
                    html += '</div>';

                    html += '<div class="row mb-3">';
                    html += '<div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body text-center">';
                    html += '<h5 class="mb-0">' + (response.bunga_log ? response.bunga_log.inserted : 0) + '</h5>';
                    html += '<small>Log Bunga Baru</small></div></div></div>';

                    html += '<div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body text-center">';
                    html += '<h5 class="mb-0">' + (response.bunga_log ? (response.bunga_log.deleted || 0) : 0) + '</h5>';
                    html += '<small>Log Bunga Lama Dihapus</small></div></div></div>';

                    html += '<div class="col-md-3"><div class="card bg-secondary text-white"><div class="card-body text-center">';
                    html += '<h5 class="mb-0">' + (response.batch_id || '-') + '</h5>';
                    html += '<small>Batch ID</small></div></div></div>';
                    html += '</div>';

                    // Show errors if any
                    if (response.errors && response.errors.length > 0) {
                        html += '<div class="alert alert-warning mt-3"><strong>Peringatan:</strong><ul class="mb-0">';
                        response.errors.forEach(function (err) {
                            html += '<li>' + err + '</li>';
                        });
                        html += '</ul></div>';
                    }

                    // Show details
                    if (response.details && response.details.length > 0) {
                        html += '<h6 class="mt-3">Detail (20 pertama):</h6>';
                        html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                        html += '<thead><tr><th>Row</th><th>Nama</th><th>No Seri</th><th>Aksi</th></tr></thead><tbody>';
                        response.details.slice(0, 20).forEach(function (d) {
                            var badge = d.action == 'inserted' ? 'bg-success' : (d.action == 'updated' ? 'bg-info' : 'bg-secondary');
                            html += '<tr><td>' + d.row + '</td><td>' + d.nama + '</td><td>' + (d.no_seri || '-') + '</td>';
                            html += '<td><span class="badge ' + badge + '">' + d.action + '</span></td></tr>';
                        });
                        html += '</tbody></table></div>';
                    }

                    // Sheets found
                    if (response.sheets_found) {
                        html += '<div class="mt-3"><small class="text-muted">Sheets: ' + response.sheets_found.join(', ') + '</small></div>';
                    }

                    $('#results-content').html(html);
                },
                error: function (xhr, status, error) {
                    $('#btn-import').show();
                    $('#btn-loading').hide();
                    $('#import-results').show();
                    $('#results-content').html('<div class="alert alert-danger">Error: ' + error + '<br><small>' + xhr.responseText + '</small></div>');
                }
            });
        });
    });
</script>