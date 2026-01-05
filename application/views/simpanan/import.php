<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">
            <i class="fa fa-file-excel-o"></i> Import Data Tabungan dari Excel
        </h4>
        <a href="<?= base_url('simpanan') ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <strong><i class="fa fa-info-circle"></i> Import Desember 2026:</strong><br>
            <ul class="mb-0">
                <li>File harus berformat <strong>.xls</strong> atau <strong>.xlsx</strong></li>
                <li>Hanya membaca <strong>sheet DES</strong> (Desember)</li>
                <li>Format nomor rekening: <strong>T001, T002, T003...</strong></li>
                <li>Import setoran & penarikan <strong>per hari</strong></li>
                <li>Import <strong>bunga</strong> dari Excel (rekap per nasabah)</li>
                <li class="text-danger"><strong>Data tabungan lama akan dihapus!</strong></li>
            </ul>
        </div>

        <form id="form-import" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">File Excel <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xls,.xlsx">
                        <small class="text-muted">Maksimal 100MB - TABUNGAN 2026.xls</small>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success btn-lg" id="btn-import-december">
                    <i class="fa fa-calendar"></i> Import Desember 2026
                </button>
                <button type="button" class="btn btn-secondary btn-lg" id="btn-loading" style="display:none" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Memproses... (Mohon tunggu, ini bisa memakan waktu)
                </button>
            </div>
        </form>

        <!-- Progress Section -->
        <div id="import-progress" class="mt-4" style="display:none">
            <div class="progress" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar"
                    style="width: 0%">0%</div>
            </div>
            <p class="text-muted mt-2">Sedang memproses sheet Desember...</p>
        </div>

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
        console.log('Import form initialized');

        $('#form-import').on('submit', function (e) {
            e.preventDefault();
            console.log('Form submit triggered');

            // Check if file is selected
            var fileInput = document.getElementById('excel_file');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Silakan pilih file Excel terlebih dahulu!');
                console.log('No file selected');
                return false;
            }
            console.log('File selected:', fileInput.files[0].name);

            console.log('About to show confirm dialog...');
            var userConfirmed = window.confirm('PERHATIAN: Semua data tabungan yang ada akan DIHAPUS dan diganti dengan data dari file Excel. Lanjutkan?');
            console.log('Confirm result:', userConfirmed);

            if (!userConfirmed) {
                console.log('User cancelled');
                return;
            }

            console.log('User confirmed, proceeding with upload...');

            var formData = new FormData(this);

            $('#btn-import-december').hide();
            $('#btn-loading').show();
            $('#import-results').hide();
            $('#import-progress').show();

            var progress = 0;
            var progressBar = $('.progress-bar');
            var progressInterval = setInterval(function () {
                if (progress < 90) {
                    progress += Math.random() * 10;
                    progress = Math.min(progress, 90);
                    progressBar.css('width', progress + '%').text(Math.round(progress) + '%');
                }
            }, 2000);

            $.ajax({
                url: '<?= base_url("simpanan/proses_import_december") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 600000, // 10 minutes timeout
                success: function (response) {
                    clearInterval(progressInterval);
                    progressBar.css('width', '100%').text('100%').removeClass('progress-bar-animated');

                    setTimeout(function () {
                        $('#btn-import-december').show();
                        $('#btn-loading').hide();
                        $('#import-progress').hide();
                        $('#import-results').show();

                        var html = '';

                        if (response.success) {
                            html += '<div class="alert alert-success"><strong><i class="fa fa-check-circle"></i> Import Berhasil!</strong></div>';
                        } else {
                            html += '<div class="alert alert-danger"><strong><i class="fa fa-times-circle"></i> Import Gagal</strong></div>';
                        }

                        html += '<p><strong>Sheet diimport:</strong> ' + (response.importing_sheet || '-') + '</p>';

                        // Data dihapus
                        if (response.deleted) {
                            html += '<div class="alert alert-info"><strong>Data Dihapus:</strong> ';
                            html += 'Simpanan: ' + (response.deleted.simpanan || 0) + ', ';
                            html += 'Setoran: ' + (response.deleted.setoran || 0) + ', ';
                            html += 'Penarikan: ' + (response.deleted.penarikan || 0) + ', ';
                            html += 'Transaksi: ' + (response.deleted.transaksi || 0);
                            html += '</div>';
                        }

                        html += '<div class="row mb-3">';
                        html += '<div class="col-md-2"><div class="card bg-light"><div class="card-body text-center p-2">';
                        html += '<h5 class="mb-0">' + (response.nasabah ? response.nasabah.created : 0) + '</h5>';
                        html += '<small>Nasabah Baru</small></div></div></div>';

                        html += '<div class="col-md-2"><div class="card bg-success text-white"><div class="card-body text-center p-2">';
                        html += '<h5 class="mb-0">' + (response.simpanan ? response.simpanan.inserted : 0) + '</h5>';
                        html += '<small>Tabungan Baru</small></div></div></div>';

                        html += '<div class="col-md-2"><div class="card bg-info text-white"><div class="card-body text-center p-2">';
                        html += '<h5 class="mb-0">' + (response.setoran ? response.setoran.inserted : 0) + '</h5>';
                        html += '<small>Setoran Harian</small></div></div></div>';

                        html += '<div class="col-md-2"><div class="card bg-warning text-dark"><div class="card-body text-center p-2">';
                        html += '<h5 class="mb-0">' + (response.penarikan ? response.penarikan.inserted : 0) + '</h5>';
                        html += '<small>Penarikan Harian</small></div></div></div>';

                        // Bunga summary
                        if (response.bunga) {
                            html += '<div class="col-md-2"><div class="card bg-primary text-white"><div class="card-body text-center p-2">';
                            html += '<h5 class="mb-0">' + (response.bunga.count || 0) + '</h5>';
                            html += '<small>Rekap Bunga</small></div></div></div>';

                            html += '<div class="col-md-2"><div class="card bg-dark text-white"><div class="card-body text-center p-2">';
                            var totalBunga = response.bunga.total || 0;
                            html += '<h6 class="mb-0">Rp ' + new Intl.NumberFormat('id-ID').format(totalBunga) + '</h6>';
                            html += '<small>Total Bunga</small></div></div></div>';
                        }

                        html += '</div>';

                        // Show errors if any
                        if (response.errors && response.errors.length > 0) {
                            html += '<div class="alert alert-warning mt-3"><strong>Peringatan:</strong><ul class="mb-0">';
                            response.errors.slice(0, 10).forEach(function (err) {
                                html += '<li>' + err + '</li>';
                            });
                            if (response.errors.length > 10) {
                                html += '<li>... dan ' + (response.errors.length - 10) + ' error lainnya</li>';
                            }
                            html += '</ul></div>';
                        }

                        // Show details (first 10)
                        if (response.details && response.details.length > 0) {
                            html += '<h6 class="mt-3">Detail Import (10 pertama):</h6>';
                            html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                            html += '<thead class="table-dark"><tr><th>Row</th><th>Nama</th><th>No Rek</th><th>Bunga</th><th>Saldo Akhir</th></tr></thead><tbody>';
                            response.details.slice(0, 10).forEach(function (d) {
                                var bunga = d.bunga ? 'Rp ' + new Intl.NumberFormat('id-ID').format(d.bunga) : '-';
                                var saldo = d.saldo_akhir ? 'Rp ' + new Intl.NumberFormat('id-ID').format(d.saldo_akhir) : '-';
                                html += '<tr><td>' + d.row + '</td><td>' + d.nama + '</td><td><strong>' + (d.no_rekening || '-') + '</strong></td>';
                                html += '<td class="text-end">' + bunga + '</td><td class="text-end">' + saldo + '</td></tr>';
                            });
                            html += '</tbody></table></div>';
                        }

                        $('#results-content').html(html);
                    }, 500);
                },
                error: function (xhr, status, error) {
                    clearInterval(progressInterval);
                    $('#btn-import-december').show();
                    $('#btn-loading').hide();
                    $('#import-progress').hide();
                    $('#import-results').show();
                    var errMsg = error;
                    if (status === 'timeout') errMsg = 'Request timeout - proses terputus';
                    $('#results-content').html('<div class="alert alert-danger">Error: ' + errMsg + '<br><small>' + (xhr.responseText || '').substring(0, 500) + '</small></div>');
                }
            });
        });
    });
</script>