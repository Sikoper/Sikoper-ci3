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
        <div class="alert alert-info">
            <strong><i class="fa fa-info-circle"></i> Petunjuk Import per Bulan:</strong><br>
            <ul class="mb-0">
                <li>File harus berformat <strong>.xls</strong> atau <strong>.xlsx</strong></li>
                <li>Pilih <strong>bulan</strong> yang ingin diimport (JAN, FEB, MAR, dst.)</li>
                <li>Pilih <strong>tahun</strong> data (default: 2026)</li>
                <li>Data setoran dari kolom <strong>F-AJ</strong> (hari 1-31)</li>
                <li>Data penarikan dari kolom <strong>AK-BO</strong> (hari 1-31)</li>
                <li class="text-danger"><strong>Data transaksi bulan tersebut akan dihapus dan diganti!</strong></li>
            </ul>
        </div>

        <form id="form-import-month" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">File Excel <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xls,.xlsx"
                            required>
                        <small class="text-muted">TABUNGAN 2026.xls atau .xlsx</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="month_code" class="form-label">Pilih Bulan <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="month_code" name="month_code" required>
                            <option value="JAN">Januari (JAN)</option>
                            <option value="FEB" selected>Februari (FEB)</option>
                            <option value="MAR">Maret (MAR)</option>
                            <option value="APR">April (APR)</option>
                            <option value="MEI">Mei (MEI)</option>
                            <option value="JUNI">Juni (JUNI)</option>
                            <option value="JULI">Juli (JULI)</option>
                            <option value="AGS">Agustus (AGS)</option>
                            <option value="SEP">September (SEP)</option>
                            <option value="OKT">Oktober (OKT)</option>
                            <option value="NOP">November (NOP)</option>
                            <option value="DES">Desember (DES)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="year" class="form-label">Tahun <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="year" name="year" value="2026" min="2020"
                            max="2030" required>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-lg" id="btn-import-month">
                    <i class="fa fa-upload"></i> Import Bulan <span id="selected-month-display">FEB</span>
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
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar"
                    style="width: 0%">0%</div>
            </div>
            <p class="text-muted mt-2" id="progress-text">Sedang memproses...</p>
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

        // Update button text when month changes
        $('#month_code').on('change', function () {
            $('#selected-month-display').text($(this).val());
        });

        $('#form-import-month').on('submit', function (e) {
            e.preventDefault();
            console.log('Form submit triggered');

            // Check if file is selected
            var fileInput = document.getElementById('excel_file');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Silakan pilih file Excel terlebih dahulu!');
                console.log('No file selected');
                return false;
            }

            var monthCode = $('#month_code').val();
            var year = $('#year').val();
            console.log('File selected:', fileInput.files[0].name, 'Month:', monthCode, 'Year:', year);

            var userConfirmed = window.confirm('PERHATIAN: Data transaksi bulan ' + monthCode + ' ' + year + ' akan DIHAPUS dan diganti dengan data dari file Excel. Lanjutkan?');
            console.log('Confirm result:', userConfirmed);

            if (!userConfirmed) {
                console.log('User cancelled');
                return;
            }

            console.log('User confirmed, proceeding with upload...');

            var formData = new FormData(this);

            $('#btn-import-month').hide();
            $('#btn-loading').show();
            $('#import-results').hide();
            $('#import-progress').show();
            $('#progress-text').text('Sedang memproses sheet ' + monthCode + '...');

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
                url: '<?= base_url("simpanan/proses_import_month") ?>',
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
                        $('#btn-import-month').show();
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
                            html += 'Setoran: ' + (response.deleted.setoran || 0) + ', ';
                            html += 'Penarikan: ' + (response.deleted.penarikan || 0);
                            html += '</div>';
                        }

                        html += '<div class="row mb-3">';
                        html += '<div class="col-md-3"><div class="card bg-light"><div class="card-body text-center p-2">';
                        html += '<h5 class="mb-0">' + (response.nasabah ? response.nasabah.created : 0) + '</h5>';
                        html += '<small>Nasabah Baru</small></div></div></div>';

                        html += '<div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center p-2">';
                        html += '<h5 class="mb-0">' + (response.simpanan ? response.simpanan.inserted : 0) + '</h5>';
                        html += '<small>Tabungan Baru</small></div></div></div>';

                        html += '<div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center p-2">';
                        html += '<h5 class="mb-0">' + (response.setoran ? response.setoran.inserted : 0) + '</h5>';
                        html += '<small>Setoran Harian</small></div></div></div>';

                        html += '<div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body text-center p-2">';
                        html += '<h5 class="mb-0">' + (response.penarikan ? response.penarikan.inserted : 0) + '</h5>';
                        html += '<small>Penarikan Harian</small></div></div></div>';

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
                            html += '<thead class="table-dark"><tr><th>Row</th><th>Nama</th><th>No Rek</th><th>Status</th></tr></thead><tbody>';
                            response.details.slice(0, 10).forEach(function (d) {
                                html += '<tr><td>' + d.row + '</td><td>' + d.nama + '</td><td><strong>' + (d.no_rekening || '-') + '</strong></td>';
                                html += '<td>' + (d.action || '-') + '</td></tr>';
                            });
                            html += '</tbody></table></div>';
                        }

                        // Show debug info if available
                        if (response.debug) {
                            html += '<div class="alert alert-secondary mt-3"><strong>Debug Info:</strong><br>';
                            html += 'Total rows: ' + response.debug.total_rows + '<br>';
                            html += 'Columns in first row: ' + response.debug.first_row_col_count + '<br>';
                            html += '<strong>Column offset detected:</strong> ' + (response.debug.col_offset || 0) + '<br>';
                            html += '<strong>Penarikan amounts found:</strong> ' + (response.debug.penarikan_found || 0) + '<br>';
                            if (response.debug.penarikan_insert_error) {
                                html += '<strong class="text-danger">Penarikan insert error:</strong> ' + JSON.stringify(response.debug.penarikan_insert_error) + '<br>';
                            }
                            html += '</div>';
                        }

                        $('#results-content').html(html);
                    }, 500);
                },
                error: function (xhr, status, error) {
                    clearInterval(progressInterval);
                    $('#btn-import-month').show();
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
