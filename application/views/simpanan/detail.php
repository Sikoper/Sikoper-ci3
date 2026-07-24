<div class="row">
    <?php
    // Menggunakan safe_base64_encode dari secure_helper.php (autoloaded)
    ?>
    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa fa-id-card"></i> Informasi Simpanan</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th>No. Rekening</th>
                        <td>: <?= $simpanan->no_rekening ?></td>
                    </tr>
                    <tr>
                        <th>Nasabah</th>
                        <td>: <?= $nasabah->nama_lengkap ?? '-' ?></td>
                    </tr>
                    <tr>
                        <th>Pegawai yang mendata</th>
                        <td>: <?= $pegawai->nama_lengkap ?? '-' ?></td>
                    </tr>
                    <tr>
                        <th>Jenis Tabungan</th>
                        <td>: <?= $jenis->nama ?? '-' ?></td>
                    </tr>
                    <tr>
                        <th>Saldo</th>
                        <td>: Rp <?= number_format($simpanan->jumlah_simpanan, 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Simpanan</th>
                        <td>: <?= date('d-m-Y', strtotime($simpanan->tanggal_simpanan)) ?></td>
                    </tr>
                    <?php if ($jenis->nama == 'Deposito'): ?>
                        <tr id="field-durasi-deposito">
                            <th>Durasi</th>
                            <td>: <?= format_durasi($simpanan->durasi) ?></td>
                        </tr>
                        <?php if (!empty($simpanan->nama_ahli_waris)): ?>
                            <tr id="field-ahli-waris">
                                <th>Ahli Waris</th>
                                <td>: <?= $simpanan->nama_ahli_waris ?> (<?= $simpanan->hubungan_ahli_waris ?> dari
                                    <?= $nasabah->nama_lengkap ?>), <?= $simpanan->telp_ahli_waris ?>
                                </td>
                            </tr>
                        <?php endif ?>
                    <?php endif; ?>
                </table>
                <div class="d-flex justify-content-end gap-2">
                    <button onclick="printNasabah('<?= $simpanan->id ?>', '<?= $nasabah->nama_lengkap ?>')"
                        class="btn btn-primary">
                        Cetak Nasabah <i class="fa fa-file ms-2"></i>
                    </button>
                    <button
                        onclick="window.location='<?= base_url('simpanan/laporan') . '?id=' . safe_base64_encode($simpanan->no_rekening) . '&code=1' ?>'"
                        class="btn btn-warning">
                        Cetak Laporan <i class="fa fa-file ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-list"></i> Detail Tabungan</h5>
                <div>
                    <button class="btn btn-success"
                        onclick="window.location='<?= base_url('setoran') . '?id=' . safe_base64_encode($simpanan->no_rekening) ?>'"><i
                            class="fa fa-credit-card"></i> Setor Tunai</button>
                    <button class="btn btn-danger"
                        onclick="window.location='<?= base_url('penarikan/') . '?id=' . safe_base64_encode($simpanan->no_rekening) ?>'"><i
                            class="fa fa-credit-card"></i> Tarik Tunai</button>
                </div>
            </div>
            <div class="card-body">
                <div class="dataTable-wrapper dataTable-loading no-footer sortable searchable fixed-columns">
                    <div class="dataTable-container">
                        <table class="table table-bordered table-striped" id="detail_tabungan">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah Uang</th>
                                    <th>Keterangan</th>
                                    <th>Pegawai</th>
                                    <th>#</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="dataTable-bottom">
                        <ul class="pagination pagination-primary float-end dataTable-pagination">

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-list"></i> Detail bunga</h5>
                <div class="text-end mb-3">
                    <strong>Total Bunga:</strong> <span id="total_bunga">Rp 0</span>
                </div>
            </div>
            <div class="card-body">
                <div class="dataTable-wrapper dataTable-loading no-footer sortable searchable fixed-columns">
                    <div class="dataTable-container">
                        <table class="table table-striped dataTable-table" id="tabel_bunga">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal bunga</th>
                                    <th>Jumlah bunga</th>
                                    <th>Bunga riil</th>
                                    <th>Presentase bunga</th>
                                    <th>#</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                    <div class="dataTable-bottom">
                        <ul class="pagination pagination-primary float-end dataTable-pagination">

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('assets') ?>/vendors/autonumeric.js/autoNumeric.js"></script>
<script>
    const userLevel = '<?= $this->session->userdata('level') ?>';
    table = $('#detail_tabungan').DataTable({
        responsive: true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [],
        autoWidth: false,

        "ajax": {
            "url": "<?= site_url('tabungan/fetchTabungan') ?>",
            "type": "POST",
            "data": {
                id: <?= $simpanan->id ?>
            }
        },

        "columns": [{
            "type": "string"
        },
        {
            "type": "string"
        },
        {
            "type": "string"
        },
        {
            "type": "string"
        },
        {
            "type": "string"
        },
        {
            "orderable": false
        }
        ],
        language: {
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoFiltered: ""
        },
        "columnDefs": [{
            "targets": 0,
            "orderable": false,
            "width": "5%"
        },
        {
            "targets": 5,
            "visible": userLevel === 'Admin',
            "orderable": false,
            "width": "10%"
        }
        ],
    });

    var noRekening = '<?= $simpanan->no_rekening ?>';

    table = $('#tabel_bunga').DataTable({
        responsive: true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [],
        autoWidth: false,

        "ajax": {
            "url": "<?= site_url('bunga/fetchNasabahTabunganBunga') ?>",
            "type": "POST",
            data: function (d) {
                d.no_rekening = noRekening;
            },
            dataSrc: function (json) {
                $('#total_bunga').text('Rp ' + json.total_bunga);
                return json.data;
            }
        },

        "columns": [{
            "type": "string"
        },
        {
            "type": "string"
        },
        {
            "type": "string"
        },
        {
            "type": "string"
        },
        {
            "type": "string"
        },
        {
            "orderable": false
        }
        ],
        language: {
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoFiltered: ""
        },
        "columnDefs": [{
            "targets": 0,
            "orderable": false,
            "width": "5%"
        },
        {
            "targets": 4,
            "visible": userLevel === 'Admin',
            "orderable": false,
            "width": "15%"
        }
        ],
    });

    function deleteRecord(id, jumlah, keterangan) {
        Swal.fire({
            title: "Hapus data ini?",
            html: `Yakin ingin menghapus history sejumlah:<br/> <strong><span id="jumlah_setoran">${jumlah}</span></strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            didOpen: () => {
                new AutoNumeric('#jumlah_setoran', jumlah, {
                    decimalCharacter: ',',
                    digitGroupSeparator: '.',
                    currencySymbol: 'Rp ',
                    currencySymbolPlacement: 'p',
                    decimalPlaces: 0
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('tabungan/delete') ?>",
                    data: {
                        id: id,
                        keterangan: keterangan
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: "Success!",
                                text: response.success,
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                icon: "success"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        } else if (response.error) {
                            Swal.fire({
                                title: "Gagal!",
                                text: response.error,
                                icon: "error",
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false
                            });
                        }
                    },
                    error: function (xhr, thrownError) {
                        Swal.fire({
                            title: "Error!",
                            text: "Terjadi kesalahan pada server. Silakan coba lagi.",
                            icon: "error"
                        });
                    }
                });
            }
        });
    }

    function deleteDetailPenarikan(id, jumlah) {
        Swal.fire({
            title: "Hapus data penarikan ini?",
            html: `Yakin ingin menghapus detail penarikan sejumlah:<br/> <strong>${jumlah}</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= site_url('penarikan/hapus_detail_penarikan_ajax') ?>",
                    data: {
                        penarikan_id: id
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: "Berhasil!",
                                text: response.success,
                                icon: "success",
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                            }).then(() => {
                                window.location.reload();
                            });
                        } else if (response.error) {
                            Swal.fire("Gagal!", response.error, "error");
                        } else {
                            Swal.fire("Error!", "Terjadi kesalahan yang tidak diketahui.", "error");
                        }
                    },
                    error: function (xhr, thrownError) {
                        Swal.fire("Error AJAX!", "Terjadi kesalahan: " + xhr.status + " \n" + xhr.responseText + " \n" + thrownError, "error");
                    }
                });
            }
        });
    }

    function deleteRecordBunga(id, nama, tipe) {
        Swal.fire({
            title: "Hapus data ini?",
            html: `Yakin ingin menghapus bunga dari no. rekening: <strong>${nama}</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('bunga/delete') ?>",
                    data: {
                        id: id,
                        tipe: tipe
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: "Success!",
                                text: response.success,
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                icon: "success"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        }
                    },
                    error: function (xhr, thrownError) {
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                    }
                });
            }
        });
    }

    function printNasabah(id, nama) {
        Swal.fire({
            title: "Print data ini?",
            html: `Yakin ingin print data dari <strong>${nama}</strong>?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("<?= base_url('simpanan/print_nasabah?id=') ?>" + id, "_blank");
                window.location.reload();
            }
        });
    }

    function printLaporan(id, nama) {
        Swal.fire({
            title: "Print data ini?",
            html: `Yakin ingin print data dari <strong>${nama}</strong>?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("<?= base_url('simpanan/print_laporan?id=') ?>" + id, "_blank");
                window.location.reload();
            }
        });
    }
</script>
