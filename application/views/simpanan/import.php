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
            <strong><i class="fa fa-info-circle"></i> Petunjuk Import:</strong><br>
            <ul class="mb-0">
                <li>Pilih file lalu klik <strong>"Baca Sheet"</strong></li>
                <li>Pilih <strong>sheet</strong> lalu klik <strong>"Preview"</strong></li>
                <li>Sesuaikan mapping kolom (preview otomatis diperbarui)</li>
                <li>Klik <strong>"Import"</strong></li>
            </ul>
        </div>

        <!-- Step 1: File Upload -->
        <form id="form-upload" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">File Excel <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xls,.xlsx" required>
                        <small class="text-muted">TABUNGAN 2026.xls atau .xlsx</small>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="mb-3 w-100">
                        <button type="submit" class="btn btn-secondary btn-lg w-100" id="btn-read-sheets">
                            <i class="fa fa-list"></i> Baca Sheet
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Loading -->
        <div id="loading-sheets" class="text-center p-4" style="display:none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Membaca daftar sheet...</p>
        </div>

        <!-- Step 2: Sheet Selection & Preview -->
        <div id="sheet-section" class="mt-3" style="display:none">
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label class="form-label"><strong>Pilih Sheet</strong></label>
                            <select class="form-select" id="sheet_select" name="sheet_select"></select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-info btn-lg w-100" id="btn-preview">
                                <i class="fa fa-eye"></i> Preview Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Loading -->
        <div id="preview-loading" class="text-center p-4" style="display:none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Membaca data sheet...</p>
        </div>

        <!-- Step 3: Preview & Column Mapping -->
        <div id="preview-section" class="mt-3" style="display:none">
            <hr>
            <h5><i class="fa fa-columns"></i> Preview Data & Mapping Kolom</h5>
            
            <!-- Column Mapping Controls -->
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="fa fa-cogs"></i> Mapping Kolom <span class="badge bg-info">Auto-refresh</span></h6>
                    <p class="text-muted small">Sesuaikan kolom - preview otomatis diperbarui.</p>
                    
                    <div class="row">
                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label small">NO</label>
                                <select class="form-select form-select-sm column-mapping" id="col_no_urut"></select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label small">NAMA</label>
                                <select class="form-select form-select-sm column-mapping" id="col_nama"></select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label small">NO TAB</label>
                                <select class="form-select form-select-sm column-mapping" id="col_no_tab"></select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label small">ALAMAT</label>
                                <select class="form-select form-select-sm column-mapping" id="col_alamat"></select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label small">SALDO AWAL</label>
                                <select class="form-select form-select-sm column-mapping" id="col_saldo_awal"></select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-2">
                                <label class="form-label small">BUNGA</label>
                                <select class="form-select form-select-sm column-mapping" id="col_bunga"></select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-success bg-opacity-10 mb-2">
                                <div class="card-body py-2">
                                    <label class="form-label small text-success fw-bold">SETORAN (Hijau)</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label small">Mulai (H1)</label>
                                            <select class="form-select form-select-sm column-mapping" id="col_setoran_start"></select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Akhir (H29)</label>
                                            <select class="form-select form-select-sm column-mapping" id="col_setoran_end"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-warning bg-opacity-10 mb-2">
                                <div class="card-body py-2">
                                    <label class="form-label small text-warning fw-bold">PENARIKAN (Coklat)</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label small">Mulai (H1)</label>
                                            <select class="form-select form-select-sm column-mapping" id="col_penarikan_start"></select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Akhir (H29)</label>
                                            <select class="form-select form-select-sm column-mapping" id="col_penarikan_end"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-2 pt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="delete_existing" checked>
                                    <label class="form-check-label text-danger" for="delete_existing">
                                        <strong>Hapus data bulan ini sebelum import</strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mapped Fields Preview -->
            <div class="card border-success mb-3">
                <div class="card-header bg-success text-white">
                    <i class="fa fa-check-circle"></i> Preview Berdasarkan Mapping
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>NAMA</th>
                                    <th>NO TAB</th>
                                    <th>ALAMAT</th>
                                    <th>SALDO AWAL</th>
                                    <th class="text-success">TOTAL SETORAN</th>
                                    <th class="text-danger">TOTAL PENARIKAN</th>
                                    <th>BUNGA</th>
                                    <th class="text-info">SALDO AKHIR</th>
                                </tr>
                            </thead>
                            <tbody id="mapped-preview-body"></tbody>
                        </table>
                    </div>
                    <input type="hidden" id="preview-page" value="1">
                    <div id="preview-pagination" class="card-footer"></div>
                </div>
            </div>

            <!-- Raw Preview Table -->
            <details class="mb-3">
                <summary class="text-muted">Lihat Data Excel Mentah</summary>
                <div class="table-responsive mt-2" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-striped">
                        <thead class="table-dark sticky-top" id="preview-header"></thead>
                        <tbody id="preview-body"></tbody>
                    </table>
                </div>
            </details>

            <!-- Import Button -->
            <div class="mt-3">
                <button type="button" class="btn btn-primary btn-lg" id="btn-import-mapped">
                    <i class="fa fa-upload"></i> Import dengan Mapping
                </button>
                <button type="button" class="btn btn-secondary btn-lg" id="btn-import-loading" style="display:none" disabled>
                    <span class="spinner-border spinner-border-sm"></span> Memproses...
                </button>
            </div>
        </div>

        <!-- Import Progress -->
        <div id="import-progress" class="mt-4" style="display:none">
            <div class="progress" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div>
            </div>
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
    var previewData = null;
    var selectedSheet = null;
    
    // Step 1: Read sheet names from file
    $('#form-upload').on('submit', function(e) {
        e.preventDefault();
        
        var fileInput = document.getElementById('excel_file');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Silakan pilih file Excel!');
            return false;
        }
        
        var formData = new FormData(this);
        
        $('#btn-read-sheets').prop('disabled', true);
        $('#loading-sheets').show();
        $('#sheet-section').hide();
        $('#preview-section').hide();
        
        $.ajax({
            url: '<?= base_url("simpanan/get_sheet_names") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 120000,
            success: function(response) {
                $('#btn-read-sheets').prop('disabled', false);
                $('#loading-sheets').hide();
                
                if (response.success && response.sheets) {
                    var options = '';
                    response.sheets.forEach(function(sheet) {
                        options += '<option value="' + sheet.name + '">' + sheet.index + ': ' + sheet.name + '</option>';
                    });
                    $('#sheet_select').html(options);
                    $('#sheet-section').show();
                } else {
                    alert('Error: ' + (response.errors ? response.errors.join(', ') : 'Gagal membaca sheet'));
                }
            },
            error: function(xhr, status, error) {
                $('#btn-read-sheets').prop('disabled', false);
                $('#loading-sheets').hide();
                alert('Error: ' + error);
            }
        });
    });
    
    // Step 2: Preview data from selected sheet
    $('#btn-preview').on('click', function() {
        selectedSheet = $('#sheet_select').val();
        if (!selectedSheet) {
            alert('Pilih sheet!');
            return;
        }
        
        var formData = new FormData();
        formData.append('month_code', selectedSheet);
        
        $('#btn-preview').prop('disabled', true);
        $('#preview-loading').show();
        $('#preview-section').hide();
        
        $.ajax({
            url: '<?= base_url("simpanan/preview_import") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 300000,
            success: function(response) {
                $('#btn-preview').prop('disabled', false);
                $('#preview-loading').hide();
                
                if (response.success) {
                    previewData = response;
                    renderPreview(response);
                    $('#preview-section').show();
                } else {
                    alert('Error: ' + (response.errors ? response.errors.join(', ') : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                $('#btn-preview').prop('disabled', false);
                $('#preview-loading').hide();
                alert('Error: ' + error);
            }
        });
    });
    
    // Auto-refresh preview when column mapping changes
    $(document).on('change', '.column-mapping', function() {
        if (previewData && previewData.rows) {
            updateMappedPreview();
        }
    });
    
    // Convert column index to Excel letter (0=A, 1=B, 26=AA, etc.)
    function colLetter(index) {
        var letter = '';
        var n = index;
        while (n >= 0) {
            letter = String.fromCharCode(65 + (n % 26)) + letter;
            n = Math.floor(n / 26) - 1;
        }
        return letter;
    }
    
    // Render preview data
    function renderPreview(data) {
        var columnOptions = '';
        for (var i = 0; i < data.total_columns; i++) {
            var header = data.headers[i] || '';
            var sample = '';
            if (data.rows && data.rows[0] && data.rows[0][i]) {
                sample = data.rows[0][i].value || '';
                if (sample.length > 15) sample = sample.substring(0, 15) + '...';
            }
            var label = colLetter(i) + ': ';
            if (header) {
                label += header.substring(0, 12);
            } else if (sample) {
                label += '[' + sample + ']';
            } else {
                label += '(kosong)';
            }
            columnOptions += '<option value="' + i + '">' + label + '</option>';
        }
        
        $('.column-mapping').html(columnOptions);
        
        // Set suggested mappings (H-AJ = Setoran 7-35, AK-BM = Penarikan 36-64)
        if (data.suggested_mapping) {
            $('#col_no_urut').val(data.suggested_mapping.no_urut || 0);
            $('#col_nama').val(data.suggested_mapping.nama || 1);
            $('#col_no_tab').val(data.suggested_mapping.no_tab || 4);
            $('#col_alamat').val(data.suggested_mapping.alamat || 5);
            $('#col_saldo_awal').val(data.suggested_mapping.saldo_awal || 6);
            $('#col_setoran_start').val(data.suggested_mapping.setoran_start || 7);  // H (day 1)
            $('#col_setoran_end').val(data.suggested_mapping.setoran_end || 35);      // AJ (day 29)
            $('#col_penarikan_start').val(data.suggested_mapping.penarikan_start || 36); // AK (day 1)
            $('#col_penarikan_end').val(data.suggested_mapping.penarikan_end || 64);   // BM (day 29)
            $('#col_bunga').val(data.suggested_mapping.bunga || 97);
        }
        
        // Render raw header row
        var headerHtml = '<tr><th>#</th>';
        for (var i = 0; i < Math.min(data.total_columns, 20); i++) {
            var header = data.headers[i] || '';
            headerHtml += '<th class="text-nowrap"><small>' + colLetter(i) + '</small><br>' + header.substring(0, 8) + '</th>';
        }
        if (data.total_columns > 20) headerHtml += '<th>...</th>';
        headerHtml += '</tr>';
        $('#preview-header').html(headerHtml);
        
        // Render raw data rows
        var bodyHtml = '';
        data.rows.forEach(function(row, rowIdx) {
            bodyHtml += '<tr><td>' + (rowIdx + 4) + '</td>';
            for (var i = 0; i < Math.min(row.length, 20); i++) {
                var val = row[i] ? (row[i].value || '') : '';
                bodyHtml += '<td class="text-nowrap">' + val.substring(0, 12) + '</td>';
            }
            if (row.length > 20) bodyHtml += '<td>...</td>';
            bodyHtml += '</tr>';
        });
        $('#preview-body').html(bodyHtml);
        
        updateMappedPreview();
    }
    
    // Update mapped preview - uses JAN name map for non-JAN sheets
    function updateMappedPreview() {
        if (!previewData || !previewData.rows) return;
        
        var colNo = parseInt($('#col_no_urut').val()) || 0;
        var colNama = parseInt($('#col_nama').val()) || 1;
        var colNoTab = parseInt($('#col_no_tab').val()) || 4;
        var colAlamat = parseInt($('#col_alamat').val()) || 5;
        var colSaldo = parseInt($('#col_saldo_awal').val()) || 6;
        var colSetoranStart = parseInt($('#col_setoran_start').val()) || 7;
        var colSetoranEnd = parseInt($('#col_setoran_end').val()) || 35;
        var colPenarikanStart = parseInt($('#col_penarikan_start').val()) || 36;
        var colPenarikanEnd = parseInt($('#col_penarikan_end').val()) || 64;
        var colBunga = parseInt($('#col_bunga').val()) || 101;
        
        var janMap = previewData.jan_name_map || {};
        var isJanSheet = previewData.is_jan_sheet || false;
        
        // Build a column lookup map for each row (col_index -> value)
        var buildColMap = function(row) {
            var map = {};
            row.forEach(function(cell) {
                map[cell.col_index] = cell.value;
            });
            return map;
        };
        
        var bodyHtml = '';
        
        // Pagination
        var rowsPerPage = 20;
        var currentPage = parseInt($('#preview-page').val()) || 1;
        var totalPages = Math.ceil(previewData.rows.length / rowsPerPage);
        var startIdx = (currentPage - 1) * rowsPerPage;
        var endIdx = Math.min(startIdx + rowsPerPage, previewData.rows.length);
        
        for (var rowIdx = startIdx; rowIdx < endIdx; rowIdx++) {
            var row = previewData.rows[rowIdx];
            var colMap = buildColMap(row);
            
            var getVal = function(col) {
                if (colMap[col] !== undefined && colMap[col] !== null && colMap[col] !== '') {
                    var v = String(colMap[col]);
                    return v.length > 20 ? v.substring(0, 20) + '...' : v;
                }
                return '-';
            };
            
            // Sum all setoran columns
            var totalSetoran = 0;
            for (var i = colSetoranStart; i <= colSetoranEnd; i++) {
                if (colMap[i] !== undefined && colMap[i] !== null && colMap[i] !== '') {
                    // Remove dots (thousand separator in ID locale) and non-numeric chars
                    var val = parseFloat(String(colMap[i]).replace(/\./g, '').replace(/[^0-9-]/g, '')) || 0;
                    totalSetoran += val;
                }
            }
            
            // Sum all penarikan columns
            var totalPenarikan = 0;
            for (var j = colPenarikanStart; j <= colPenarikanEnd; j++) {
                if (colMap[j] !== undefined && colMap[j] !== null && colMap[j] !== '') {
                    // Remove dots (thousand separator in ID locale) and non-numeric chars
                    var val = parseFloat(String(colMap[j]).replace(/\./g, '').replace(/[^0-9-]/g, '')) || 0;
                    totalPenarikan += val;
                }
            }
            
            // Get NO_TAB value to lookup in JAN map
            var noTab = colMap[colNoTab] ? parseInt(colMap[colNoTab]) : 0;
            
            // For non-JAN sheets, get NAMA and ALAMAT from JAN map
            var nama = getVal(colNama);
            var alamat = getVal(colAlamat);
            
            if (!isJanSheet && noTab > 0 && janMap[noTab]) {
                nama = janMap[noTab].nama || nama;
                alamat = janMap[noTab].alamat || alamat;
                if (nama.length > 20) nama = nama.substring(0, 20) + '...';
                if (alamat.length > 20) alamat = alamat.substring(0, 20) + '...';
            }
            
            // Parse numeric value (handle Indonesian locale with dots)
            var parseNum = function(val) {
                if (val === undefined || val === null || val === '') return 0;
                return parseFloat(String(val).replace(/\./g, '').replace(/[^0-9-]/g, '')) || 0;
            };
            
            // Get SALDO AWAL and BUNGA values
            var saldoAwal = parseNum(colMap[colSaldo]);
            var bunga = parseNum(colMap[colBunga]);
            
            // Calculate SALDO AKHIR = SALDO AWAL + SETORAN - PENARIKAN + BUNGA
            var saldoAkhir = saldoAwal + totalSetoran - totalPenarikan + bunga;
            
            // Format numbers with thousand separator
            var formatNumber = function(num) {
                if (num === 0) return '-';
                return num.toLocaleString('id-ID');
            };
            
            bodyHtml += '<tr>';
            bodyHtml += '<td><strong>' + nama + '</strong></td>';
            bodyHtml += '<td>' + getVal(colNoTab) + '</td>';
            bodyHtml += '<td>' + alamat + '</td>';
            bodyHtml += '<td class="text-end">' + formatNumber(saldoAwal) + '</td>';
            bodyHtml += '<td class="text-end text-success"><strong>' + formatNumber(totalSetoran) + '</strong></td>';
            bodyHtml += '<td class="text-end text-danger"><strong>' + formatNumber(totalPenarikan) + '</strong></td>';
            bodyHtml += '<td class="text-end text-primary">' + formatNumber(bunga) + '</td>';
            bodyHtml += '<td class="text-end text-info"><strong>' + formatNumber(saldoAkhir) + '</strong></td>';
            bodyHtml += '</tr>';
        }
        
        $('#mapped-preview-body').html(bodyHtml);
        
        // Update pagination info
        var paginationHtml = '<div class="d-flex justify-content-between align-items-center mt-2">';
        paginationHtml += '<span>Halaman ' + currentPage + ' dari ' + totalPages + ' (' + previewData.rows.length + ' baris)</span>';
        paginationHtml += '<div>';
        paginationHtml += '<button class="btn btn-sm btn-outline-secondary" id="prev-page" ' + (currentPage <= 1 ? 'disabled' : '') + '>&laquo; Sebelum</button> ';
        paginationHtml += '<button class="btn btn-sm btn-outline-secondary" id="next-page" ' + (currentPage >= totalPages ? 'disabled' : '') + '>Berikut &raquo;</button>';
        paginationHtml += '</div></div>';
        $('#preview-pagination').html(paginationHtml);
    }
    
    // Pagination event handlers
    $(document).on('click', '#prev-page', function() {
        var page = parseInt($('#preview-page').val()) || 1;
        if (page > 1) {
            $('#preview-page').val(page - 1);
            updateMappedPreview();
        }
    });
    
    $(document).on('click', '#next-page', function() {
        var page = parseInt($('#preview-page').val()) || 1;
        $('#preview-page').val(page + 1);
        updateMappedPreview();
    });
    
    // Import with mapping
    $('#btn-import-mapped').on('click', function() {
        if (!selectedSheet) {
            alert('Pilih sheet!');
            return;
        }
        
        var deleteChecked = $('#delete_existing').is(':checked');
        
        var msg = deleteChecked 
            ? 'PERHATIAN: Data transaksi sheet ' + selectedSheet + ' akan DIHAPUS dan diganti. Lanjutkan?'
            : 'Data baru akan ditambahkan. Lanjutkan?';
        
        if (!confirm(msg)) return;
        
        var formData = new FormData();
        formData.append('month_code', selectedSheet);
        formData.append('col_no_urut', $('#col_no_urut').val());
        formData.append('col_nama', $('#col_nama').val());
        formData.append('col_no_tab', $('#col_no_tab').val());
        formData.append('col_alamat', $('#col_alamat').val());
        formData.append('col_saldo_awal', $('#col_saldo_awal').val());
        formData.append('col_setoran_start', $('#col_setoran_start').val());
        formData.append('col_setoran_end', $('#col_setoran_end').val());
        formData.append('col_penarikan_start', $('#col_penarikan_start').val());
        formData.append('col_penarikan_end', $('#col_penarikan_end').val());
        formData.append('col_bunga', $('#col_bunga').val());
        formData.append('delete_existing', deleteChecked ? 'true' : 'false');
        
        $('#btn-import-mapped').hide();
        $('#btn-import-loading').show();
        $('#import-progress').show();
        $('#import-results').hide();
        
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
            url: '<?= base_url("simpanan/proses_import_with_mapping") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 600000,
            success: function(response) {
                clearInterval(progressInterval);
                progressBar.css('width', '100%').text('100%').removeClass('progress-bar-animated');
                
                setTimeout(function() {
                    $('#btn-import-mapped').show();
                    $('#btn-import-loading').hide();
                    $('#import-progress').hide();
                    $('#import-results').show();
                    renderResults(response);
                }, 500);
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                $('#btn-import-mapped').show();
                $('#btn-import-loading').hide();
                $('#import-progress').hide();
                $('#import-results').show();
                $('#results-content').html('<div class="alert alert-danger">Error: ' + error + '</div>');
            }
        });
    });
    
    // Render import results
    function renderResults(response) {
        var html = '';
        
        if (response.success) {
            html += '<div class="alert alert-success"><strong><i class="fa fa-check-circle"></i> Import Berhasil!</strong></div>';
        } else {
            html += '<div class="alert alert-danger"><strong><i class="fa fa-times-circle"></i> Import Gagal</strong></div>';
        }
        
        html += '<p><strong>Sheet:</strong> ' + (response.importing_sheet || '-') + '</p>';
        
        if (response.deleted) {
            html += '<div class="alert alert-info"><strong>Dihapus:</strong> ';
            html += 'Setoran: ' + (response.deleted.setoran || 0) + ', ';
            html += 'Penarikan: ' + (response.deleted.penarikan || 0) + ', ';
            html += 'Bunga: ' + (response.deleted.bunga || 0);
            html += '</div>';
        }
        
        html += '<div class="row mb-3">';
        html += '<div class="col-md-3"><div class="card bg-light"><div class="card-body text-center p-2">';
        html += '<h5 class="mb-0">' + (response.nasabah ? response.nasabah.created : 0) + '</h5>';
        html += '<small>Nasabah Baru</small></div></div></div>';
        
        html += '<div class="col-md-3"><div class="card bg-success text-white"><div class="card-body text-center p-2">';
        html += '<h5 class="mb-0">' + (response.setoran ? response.setoran.inserted : 0) + '</h5>';
        html += '<small>Setoran</small></div></div></div>';
        
        html += '<div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body text-center p-2">';
        html += '<h5 class="mb-0">' + (response.penarikan ? response.penarikan.inserted : 0) + '</h5>';
        html += '<small>Penarikan</small></div></div></div>';
        
        html += '<div class="col-md-3"><div class="card bg-info text-white"><div class="card-body text-center p-2">';
        html += '<h5 class="mb-0">' + (response.bunga ? response.bunga.inserted : 0) + '</h5>';
        html += '<small>Bunga</small></div></div></div>';
        html += '</div>';
        
        if (response.errors && response.errors.length > 0) {
            html += '<div class="alert alert-warning"><strong>Peringatan:</strong><ul class="mb-0">';
            response.errors.slice(0, 10).forEach(function(err) {
                html += '<li>' + err + '</li>';
            });
            if (response.errors.length > 10) {
                html += '<li>...dan ' + (response.errors.length - 10) + ' error lainnya</li>';
            }
            html += '</ul></div>';
        }
        
        $('#results-content').html(html);
    }
});
</script>
