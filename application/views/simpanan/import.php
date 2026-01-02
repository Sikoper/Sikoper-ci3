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
            <strong><i class="fa fa-info-circle"></i> Petunjuk:</strong><br>
            <ul class="mb-0">
                <li>File harus berformat <strong>.xls</strong> atau <strong>.xlsx</strong></li>
                <li>Import akan membaca <strong>SEMUA 12 sheet</strong> (JAN-DES)</li>
                <li>Termasuk <strong>setoran & penarikan harian</strong> per tanggal</li>
                <li>Nama yang sama akan digabung (tidak duplikat)</li>
                <li class="text-warning">Proses memakan waktu ~5-10 menit untuk file besar</li>
            </ul>
        </div>

        <form id="form-import" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">File Excel <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" 
                               accept=".xls,.xlsx" required>
                        <small class="text-muted">Maksimal 100MB</small>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-lg" id="btn-import">
                    <i class="fa fa-upload"></i> Import Semua Data (JAN-DES)
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
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
            </div>
            <p class="text-muted mt-2">Sedang memproses sheet...</p>
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
$(document).ready(function() {
    $('#form-import').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $('#btn-import').hide();
        $('#btn-loading').show();
        $('#import-results').hide();
        $('#import-progress').show();
        
        // Simulate progress
        var progress = 0;
        var progressBar = $('.progress-bar');
        var progressInterval = setInterval(function() {
            if (progress < 90) {
                progress += Math.random() * 10;
                progress = Math.min(progress, 90);
                progressBar.css('width', progress + '%').text(Math.round(progress) + '%');
            }
        }, 2000);
        
        $.ajax({
            url: '<?= base_url("simpanan/proses_import") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 600000, // 10 minutes timeout
            success: function(response) {
                clearInterval(progressInterval);
                progressBar.css('width', '100%').text('100%').removeClass('progress-bar-animated');
                
                setTimeout(function() {
                    $('#btn-import').show();
                    $('#btn-loading').hide();
                    $('#import-progress').hide();
                    $('#import-results').show();
                    
                    var html = '';
                    
                    if (response.success) {
                        html += '<div class="alert alert-success"><strong><i class="fa fa-check-circle"></i> Import Berhasil!</strong></div>';
                    } else {
                        html += '<div class="alert alert-danger"><strong><i class="fa fa-times-circle"></i> Import Gagal</strong></div>';
                    }
                    
                    html += '<p><strong>Sheets diimport:</strong> ' + (response.importing_sheet || '-') + '</p>';
                    
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
                    
                    html += '<div class="col-md-2"><div class="card bg-secondary text-white"><div class="card-body text-center p-2">';
                    html += '<h5 class="mb-0">' + (response.total_processed || 0) + '</h5>';
                    html += '<small>Total Nasabah</small></div></div></div>';
                    html += '</div>';
                    
                    // Show errors if any
                    if (response.errors && response.errors.length > 0) {
                        html += '<div class="alert alert-warning mt-3"><strong>Peringatan:</strong><ul class="mb-0">';
                        response.errors.slice(0, 10).forEach(function(err) {
                            html += '<li>' + err + '</li>';
                        });
                        if (response.errors.length > 10) {
                            html += '<li>... dan ' + (response.errors.length - 10) + ' error lainnya</li>';
                        }
                        html += '</ul></div>';
                    }
                    
                    // Show details
                    if (response.details && response.details.length > 0) {
                        html += '<h6 class="mt-3">Detail (10 pertama):</h6>';
                        html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                        html += '<thead><tr><th>Row</th><th>Nama</th><th>No Rek</th><th>Aksi</th></tr></thead><tbody>';
                        response.details.slice(0, 10).forEach(function(d) {
                            html += '<tr><td>' + d.row + '</td><td>' + d.nama + '</td><td>' + (d.no_rekening || '-') + '</td>';
                            html += '<td><span class="badge bg-success">' + d.action + '</span></td></tr>';
                        });
                        html += '</tbody></table></div>';
                    }
                    
                    $('#results-content').html(html);
                }, 500);
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                $('#btn-import').show();
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