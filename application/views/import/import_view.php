<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">
            <i class="fa fa-file-text-o"></i> Import Data Deposito dari CSV
        </h4>
        <a href="<?= base_url('deposito') ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <!-- Instructions -->
        <div class="alert alert-info">
            <strong><i class="fa fa-info-circle"></i> Petunjuk:</strong><br>
            <ul class="mb-0">
                <li>Export file Excel ke format <strong>.csv</strong> (CSV UTF-8)</li>
                <li>Pastikan header berada di baris ke-3, data dimulai dari baris ke-4</li>
                <li>Kolom yang akan diproses:
                    <code>NO, NAMA, ALAMAT, NO.SERI, JUMLAH DEPOSITO, TGL DEPOSITO, JANGKA WAKTU, JATUH TEMPO, SUKU BUNGA, TELP, KET</code>
                </li>
                <li>Tanggal dalam format Excel Serial Date akan dikonversi otomatis</li>
                <li>Data anggota baru akan dibuat jika belum ada di database</li>
            </ul>
        </div>

        <!-- Expected Format -->
        <div class="card bg-light mb-4">
            <div class="card-header py-2">
                <strong><i class="fa fa-table"></i> Format CSV yang Diharapkan</strong>
            </div>
            <div class="card-body py-2">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>Kolom</th>
                                <th>A (0)</th>
                                <th>B (1)</th>
                                <th>C (2)</th>
                                <th>D (3)</th>
                                <th>E (4)</th>
                                <th>F (5)</th>
                                <th>G (6)</th>
                                <th>H (7)</th>
                                <th>I (8)</th>
                                <th>K (10)</th>
                                <th>L (11)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Header</strong></td>
                                <td>NO</td>
                                <td>NAMA</td>
                                <td>ALAMAT</td>
                                <td>NO.SERI</td>
                                <td>JUMLAH</td>
                                <td>TGL DEP</td>
                                <td>JANGKA</td>
                                <td>JTH TEMPO</td>
                                <td>BUNGA</td>
                                <td>TELP</td>
                                <td>KET</td>
                            </tr>
                            <tr class="text-muted small">
                                <td><em>Contoh</em></td>
                                <td>1.0</td>
                                <td>NI LUH...</td>
                                <td>BR. AME...</td>
                                <td>14.0</td>
                                <td>25000000</td>
                                <td>45912.0</td>
                                <td>12.0</td>
                                <td>46277.0</td>
                                <td>0.70%</td>
                                <td>08123...</td>
                                <td>BARU</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Upload Form -->
        <form id="form-import" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">
                            File CSV <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        <small class="text-muted">Maksimal 10MB, format .csv</small>
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
                url: '<?= base_url("import/upload") ?>',
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
                    var stats = response.stats || {};

                    if (response.success) {
                        html += '<div class="alert alert-success">';
                        html += '<strong><i class="fa fa-check-circle"></i> ' + response.message + '</strong>';
                        html += '</div>';
                    } else {
                        html += '<div class="alert alert-danger">';
                        html += '<strong><i class="fa fa-times-circle"></i> ' + response.message + '</strong>';
                        html += '</div>';
                    }

                    // Stats cards
                    html += '<div class="row mb-3">';

                    html += '<div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center py-3">';
                    html += '<h4 class="mb-0">' + (stats.deposito_inserted || 0) + '</h4>';
                    html += '<small>Deposito Baru</small></div></div></div>';

                    html += '<div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center py-3">';
                    html += '<h4 class="mb-0">' + (stats.deposito_updated || 0) + '</h4>';
                    html += '<small>Deposito Update</small></div></div></div>';

                    html += '<div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body text-center py-3">';
                    html += '<h4 class="mb-0">' + (stats.anggota_created || 0) + '</h4>';
                    html += '<small>Anggota Baru</small></div></div></div>';

                    html += '<div class="col-md-3"><div class="card bg-secondary text-white"><div class="card-body text-center py-3">';
                    html += '<h4 class="mb-0">' + (stats.rows_skipped || 0) + '</h4>';
                    html += '<small>Baris Dilewati</small></div></div></div>';

                    html += '</div>';

                    // Show errors if any
                    if (stats.errors && stats.errors.length > 0) {
                        html += '<div class="alert alert-warning mt-3">';
                        html += '<strong><i class="fa fa-exclamation-triangle"></i> Peringatan:</strong>';
                        html += '<ul class="mb-0 mt-2">';
                        stats.errors.slice(0, 10).forEach(function (err) {
                            html += '<li>' + err + '</li>';
                        });
                        if (stats.errors.length > 10) {
                            html += '<li>... dan ' + (stats.errors.length - 10) + ' error lainnya</li>';
                        }
                        html += '</ul></div>';
                    }

                    $('#results-content').html(html);
                },
                error: function (xhr, status, error) {
                    $('#btn-import').show();
                    $('#btn-loading').hide();
                    $('#import-results').show();
                    $('#results-content').html(
                        '<div class="alert alert-danger">' +
                        '<strong>Error:</strong> ' + error + '<br>' +
                        '<small>' + (xhr.responseText || '').substring(0, 500) + '</small>' +
                        '</div>'
                    );
                }
            });
        });
    });
</script>