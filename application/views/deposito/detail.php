<div class="row">
    <?php
    function safe_base64_encode($string)
    {
        return strtr(base64_encode($string), '+/=', '-_.');
    }
    ?>
    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa fa-id-card"></i> Informasi Deposito</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th>No. Rekening</th>
                        <td>: <?= $deposito->no_rekening ?></td>
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
                        <th>Total Deposito</th>
                        <td>: Rp <?= number_format($deposito->jumlah_deposito, 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <th>Total Penarikan Keseluruhan</th>
                        <td id="totalPenarikanValue">: Rp <?= number_format($total_akumulasi_penarikan, 2, ',', '.') ?>
                            <?php if ($total_akumulasi_denda > 0): ?>
                                (Denda: Rp <?= number_format($total_akumulasi_denda, 2, ',', '.') ?>)
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Tanggal Deposito</th>
                        <td>: <?= date('d-m-Y', strtotime($deposito->tanggal_deposito)) ?></td>
                    </tr>
                    <?php if ($jenis->nama == 'Deposito'): ?>
                        <tr id="field-durasi-deposito">
                            <th>Durasi</th>
                            <td>: <?= format_durasi($deposito->durasi) ?></td>
                        </tr>
                        <?php if (!empty($deposito->nama_ahli_waris)): ?>
                            <tr id="field-ahli-waris">
                                <th>Ahli Waris</th>
                                <td>: <?= $deposito->nama_ahli_waris ?> (<?= $deposito->hubungan_ahli_waris ?> dari <?= $nasabah->nama_lengkap ?>), <?= $deposito->telp_ahli_waris ?></td>
                            </tr>
                        <?php endif ?>
                    <?php endif; ?>
                </table>
                <div class="d-flex justify-content-end gap-2">
                    <button onclick="printNasabah('<?= $deposito->id ?>', '<?= $nasabah->nama_lengkap ?>')" class="btn btn-primary">
                        Cetak Nasabah <i class="fa fa-file ms-2"></i>
                    </button>
                    <button onclick="window.location='<?= base_url('deposito/laporan') . '?id=' . safe_base64_encode($deposito->no_rekening) . '&code=1' ?>'" class="btn btn-warning">
                        Cetak Laporan <i class="fa fa-file ms-2"></i>
                    </button>
                    <a href="<?= base_url('deposito/print_sertifikat_depan/' . $deposito->id) ?>" target="_blank" class="btn btn-info">
                        Cetak Sertifikat Depan <i class="fa fa-id-card ms-2"></i>
                    </a>
                    <a href="<?= base_url('deposito/print_sertifikat_belakang/' . $deposito->id) ?>" target="_blank" class="btn btn-secondary">
                        Cetak Sertifikat Belakang <i class="fa fa-id-card ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-list"></i> Detail bunga</h5>
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

    <!-- Detail Simpanan Table (Bottom) -->
    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-list"></i> Detail Penarikan</h5>
                <button class="btn btn-danger" onclick="window.location='<?= base_url('pencairan/') . '?id=' . safe_base64_encode($deposito->no_rekening) ?>'"><i class="fa fa-credit-card"></i> Tarik Tunai</button>
            </div>
            <div class="card-body">
                <div class="dataTable-wrapper dataTable-loading no-footer sortable searchable fixed-columns">
                    <div class="dataTable-container">
                        <table class="table table-bordered table-striped" id="tabel_penarikan">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah Penarikan</th>
                                    <th>Jumlah Denda</th>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0"></script>
<script>
    var noRekening = '<?= $deposito->no_rekening ?>';

    table = $('#tabel_bunga').DataTable({
        responsive: true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [],
        autoWidth: false,

        "ajax": {
            "url": "<?= site_url('bunga/fetchNasabahDepositoBunga') ?>",
            "type": "POST",
            data: function(d) {
                d.no_rekening = noRekening;
            }
        },

        "columns": [{
                "type": "string"
            },
            {
                "type": "string"
            },
            {
                "orderable": false
            }
        ],

        "columnDefs": [{
                "targets": 0,
                "orderable": false,
                "width": "5%"
            },
            {
                "targets": 3,
                "orderable": false,
                "width": "15%"
            }
        ],
    });

    var depositoId = '<?= $deposito->id ?>';

    // FIX: Renamed variable from 'tabel_peanrikan' to 'tabel_penarikan'
    var tabel_penarikan = $('#tabel_penarikan').DataTable({ // FIX: Corrected selector
        "responsive": true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [
            [1, "desc"] // Sort by the second column (date) descending by default
        ],
        "autoWidth": false,
        "ajax": {
            "url": "<?= site_url('pencairan/fetch_detail_penarikan_by_deposito') ?>",
            "type": "POST",
            "data": function(d) {
                // Send the specific deposito_id with each AJAX request
                d.deposito_id = depositoId;
            },
            dataSrc: function(json) {
                // Update akumulasi display
                $('#akumulasi_penarikan').text('Rp ' + parseFloat(json.akumulasi.jumlah_penarikan).toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                }));
                $('#akumulasi_denda').text('Rp ' + parseFloat(json.akumulasi.jumlah_denda).toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                }));
                return json.data;
            }
        },
        "columns": [{
                "data": 0, // Corresponds to the first element in the server's data array (No.)
                "className": "text-center",
                "width": "5%",
                "orderable": false
            },
            {
                "data": 1 // Corresponds to the second element (Tanggal)
            },
            {
                "data": 2, // Corresponds to the third element (Total Penarikan)
                "className": "text-end"
            },
            {
                "data": 3, // Corresponds to the fourth element (Jumlah Denda)
                "className": "text-end"
            },
            {
                "data": 4 // Corresponds to the fifth element (Nama Pegawai)
            },
            {
                "data": 5, // Corresponds to the sixth element (Actions)
                "orderable": false,
                "className": "text-center",
                "width": "10%"
            }
        ],
    });

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
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= site_url('penarikan/hapus_detail_penarikan_ajax') ?>",
                    data: {
                        penarikan_id: id
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: "Berhasil!",
                                text: response.success,
                                icon: "success"
                            }).then(() => {
                                window.location.reload();
                            });
                        } else if (response.error) {
                            Swal.fire("Gagal!", response.error, "error");
                        } else {
                            Swal.fire("Error!", "Terjadi kesalahan yang tidak diketahui.", "error");
                        }
                    },
                    error: function(xhr, thrownError) {
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
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: "Success!",
                                text: response.success,
                                icon: "success"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        }
                    },
                    error: function(xhr, thrownError) {
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
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("<?= base_url('deposito/print_nasabah?id=') ?>" + id, "_blank");
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
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("<?= base_url('deposito/print_laporan?id=') ?>" + id, "_blank");
                window.location.reload();
            }
        });
    }
</script>