<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:transparent; z-index:9999; pointer-events:all;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center; backdrop-filter:blur(3px); padding:1rem 2rem; border-radius:1rem;">
        <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
        <p style="margin-top: 1rem; color:#333;">Memproses perhitungan bunga tabungan...</p>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="card-title mb-0">
                <?php if ($level == 'Admin'): ?>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalTambahBunga">
                        <i class="fa fa-plus-circle"></i> Tambah Bunga Manual
                    </button>
                    <button class="btn btn-success" id="btnPembungaanTabungan">
                        <i class="fa fa-calculator"></i> Hitung Bunga Hari Ini
                    </button>
                <?php endif; ?>
            </h4>
        </div>
        <div class="row mt-3">
            <div class="col-md-2">
                <label>Dari Tanggal</label>
                <input type="date" id="start_date" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-2">
                <label>Sampai Tanggal</label>
                <input type="date" id="end_date" class="form-control" value="<?= date('Y-m-t') ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button id="filterBtn" class="btn btn-info w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button id="printBtn" class="btn btn-secondary w-100" onclick="printLaporan()">
                    <i class="fa fa-print"></i> Cetak Laporan
                </button>
            </div>
            <div class="col-md-4 text-end d-flex align-items-center justify-content-end">
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
                        <th>Bunga</th>
                        <th>Bunga Riil</th>
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

<!-- Modal Tambah Bunga Manual -->
<div class="modal fade" id="modalTambahBunga" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Bunga Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTambahBunga">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="simpanan_id">Pilih Tabungan</label>
                        <select id="simpanan_id" name="simpanan_id" class="form-control" style="width:100%"></select>
                        <small class="text-muted">Ketik nama nasabah atau nomor rekening</small>
                    </div>
                    
                    <div id="infoSimpanan" class="mb-3" style="display:none;">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <th width="40%">Nasabah</th>
                                <td>: <span id="infoNasabah">-</span></td>
                            </tr>
                            <tr>
                                <th>Saldo Tabungan</th>
                                <td>: Rp <span id="infoJumlah">-</span></td>
                            </tr>
                            <tr>
                                <th>Rate Bunga</th>
                                <td>: <span id="infoRate">-</span>%</td>
                            </tr>
                            <tr class="table-success">
                                <th>Bunga/Bulan</th>
                                <td>: <strong>Rp <span id="infoBunga">-</span></strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="tanggal_transaksi">Tanggal Perhitungan</label>
                        <input type="date" id="tanggal_transaksi" name="tanggal_transaksi" class="form-control" value="<?= date('Y-m-d') ?>">
                        <small class="text-muted">Hanya 1 bunga per tabungan per bulan</small>
                    </div>
                    
                    <input type="hidden" id="jumlah_bunga" name="jumlah_bunga" value="0">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="btnSimpanBunga" disabled>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Select2 CSS -->
<link href="<?= base_url('assets') ?>/vendors/select2/css/select2.min.css" rel="stylesheet" />
<!-- Select2 JS -->
<script src="<?= base_url('assets') ?>/vendors/select2/js/select2.min.js"></script>

<script>
    let currentBunga = 0;

    $(document).ready(function() {
        const userLevel = '<?= $this->session->userdata('level') ?>';

        // Initialize Select2
        $('#simpanan_id').select2({
            dropdownParent: $('#modalTambahBunga'),
            placeholder: 'Cari nama nasabah...',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: '<?= site_url('bunga/search_simpanan') ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { 
                        q: params.term,
                        target_date: $('#tanggal_transaksi').val()
                    };
                },
                processResults: function(data) {
                    return { results: data.results };
                },
                cache: false
            }
        });

        // Reset selection when date changes
        $('#tanggal_transaksi').on('change', function() {
            $('#simpanan_id').val(null).trigger('change');
            $('#infoSimpanan').hide();
            $('#btnSimpanBunga').prop('disabled', true);
            currentBunga = 0;
        });

        // Show info when simpanan selected
        $('#simpanan_id').on('select2:select', function(e) {
            const data = e.params.data;
            $('#infoSimpanan').show();
            $('#infoNasabah').text(data.nama_nasabah);
            $('#infoRate').text(data.bunga_rate);
            $('#infoJumlah').text(new Intl.NumberFormat('id-ID').format(data.jumlah_simpanan));
            
            currentBunga = parseFloat(data.jumlah_simpanan) * (parseFloat(data.bunga_rate) / 100);
            $('#infoBunga').text(new Intl.NumberFormat('id-ID').format(currentBunga));
            $('#jumlah_bunga').val(currentBunga);
            $('#btnSimpanBunga').prop('disabled', false);
        });

        $('#simpanan_id').on('select2:clear', function() {
            $('#infoSimpanan').hide();
            currentBunga = 0;
            $('#jumlah_bunga').val(0);
            $('#btnSimpanBunga').prop('disabled', true);
        });

        // Form submit
        $('#formTambahBunga').submit(function(e) {
            e.preventDefault();
            
            if (!$('#simpanan_id').val() || !$('#tanggal_transaksi').val()) {
                Swal.fire('Peringatan', 'Pilih tabungan dan tanggal.', 'warning');
                return;
            }

            $.ajax({
                url: '<?= site_url('bunga/simpan_bunga_manual') ?>',
                type: 'POST',
                data: {
                    simpanan_id: $('#simpanan_id').val(),
                    tanggal_transaksi: $('#tanggal_transaksi').val(),
                    jumlah_bunga: currentBunga
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#btnSimpanBunga').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i>');
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            title: "Success!",
                            text: res.success,
                            icon: "success",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            allowEnterKey: false,
                        }).then(function() {
                            $('#modalTambahBunga').modal('hide');
                            $('#formTambahBunga')[0].reset();
                            $('#simpanan_id').val(null).trigger('change');
                            $('#infoSimpanan').hide();
                            currentBunga = 0;
                            table.ajax.reload();
                        });
                    } else {
                        Swal.fire('Gagal!', res.error, 'error');
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    Swal.fire('Error', 'Terjadi kesalahan koneksi.', 'error');
                },
                complete: function() {
                    $('#btnSimpanBunga').prop('disabled', false).html('Simpan');
                }
            });
        });

        // DataTable
        const columns = [
            { data: 0, orderable: false },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4, className: "text-end" },
            { data: 5, className: "text-end" },
            { data: 6, className: "text-center" }
        ];
        
        if (userLevel === 'Admin') {
            columns.push({
                data: 7,
                orderable: false,
                className: "text-center",
                render: function(data) { return data; }
            });
        }

        var table = $('#tabel_bunga').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            order: [[3, "desc"]],
            ajax: {
                url: "<?= site_url('bunga/fetchBungaTabungan') ?>",
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
            columns: columns
        });

        $('#filterBtn').on('click', function() {
            table.ajax.reload();
        });

        // Bulk bunga calculation
        $('#btnPembungaanTabungan').click(function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Hitung Bunga Hari Ini?',
                html: "Proses ini akan menghitung bunga untuk semua tabungan aktif.<br><b>Tidak dapat dibatalkan.</b>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Proses!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: "<?= site_url('bunga/run_bunga') ?>",
                        dataType: "json",
                        beforeSend: function() {
                            $('#btnPembungaanTabungan').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i>');
                            $('#loadingOverlay').fadeIn();
                        },
                        complete: function() {
                            $('#btnPembungaanTabungan').prop('disabled', false).html('<i class="fa fa-calculator"></i> Hitung Bunga Hari Ini');
                            $('#loadingOverlay').fadeOut();
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire("Berhasil!", response.success, "success").then(function() {
                                    table.ajax.reload();
                                });
                            } else {
                                Swal.fire("Info", response.error || "Bunga sudah diproses.", "info");
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
                        }
                    });
                }
            });
        });
    });

    function deleteItem(id, nama) {
        Swal.fire({
            title: "Hapus data ini?",
            html: "Yakin ingin menghapus bunga dari no. rekening: <strong>" + nama + "</strong>?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('bunga/delete') ?>",
                    data: { id: id },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: "Success!",
                                text: response.success,
                                icon: "success"
                            }).then(function() {
                                $('#tabel_bunga').DataTable().ajax.reload();
                            });
                        } else {
                            Swal.fire("Gagal!", response.error, "error");
                        }
                    },
                    error: function(xhr, thrownError) {
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                    }
                });
            }
        });
    }

    function printLaporan() {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        
        if (!startDate || !endDate) {
            Swal.fire("Peringatan", "Pilih rentang tanggal terlebih dahulu.", "warning");
            return;
        }
        
        Swal.fire({
            title: "Cetak Laporan?",
            html: "Cetak laporan bunga tabungan periode <strong>" + startDate + "</strong> s/d <strong>" + endDate + "</strong>?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            confirmButtonText: "Ya, Cetak!",
            cancelButtonText: "Batal"
        }).then(function(result) {
            if (result.isConfirmed) {
                window.open("<?= base_url('bunga/print_laporan_bulanan') ?>?start_date=" + startDate + "&end_date=" + endDate, "_blank");
            }
        });
    }
</script>