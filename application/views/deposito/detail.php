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
            <div class="card-body" id="data_deposito">
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
                        <th>Presentase Bunga</th>
                        <td>: <?= number_format($deposito->rate_bunga, 2, ',', '.') ?>%</td>
                    </tr>
                    <tr>
                        <th>Bunga Sampai Jatuh Tempo</th>
                        <td>: Rp <?= number_format($bunga_sampai_jatuh_tempo, 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <th>Hutang Bunga</th>
                        <td>: <span style="color: red; font-weight: bold;"> - Rp <?= number_format($hutang_bunga, 2, ',', '.') ?></span></td>
                    </tr>
                    <tr>
                        <th>Bunga Tersedia Saat Ini (Bisa Ditarik)</th>
                        <td>: <strong>Rp <?= number_format($bunga_tersedia, 2, ',', '.') ?></strong></td>
                    </tr>
                    <tr>
                        <th>Bunga Yang Sudah Dibayar</th>
                        <td>: Rp <?= number_format($bunga_sudah_dibayar, 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <th class="align-top">Total Diterima Nasabah</th>
                        <td id="totalPenarikanValue" class="align-top">:
                            <span class="fw-bold text-success" style="font-size: 1.1rem;">
                                Rp <?= number_format($total_akumulasi_penarikan - $total_akumulasi_denda, 2, ',', '.') ?>
                            </span>
                            <?php if ($total_akumulasi_denda > 0): ?>
                                <br>
                                <small class="text-muted">(Total Bruto Rp <?= number_format($total_akumulasi_penarikan, 2, ',', '.') ?> dikurangi Denda)</small>
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
                            <td>: <?= isset($deposito->durasi) ? format_durasi($deposito->durasi) : '-' ?></td>
                        </tr>
                        <?php if (!empty($deposito->nama_ahli_waris)): ?>
                            <tr id="field-ahli-waris">
                                <th>Ahli Waris</th>
                                <td>: <?= $deposito->nama_ahli_waris ?> (<?= $deposito->hubungan_ahli_waris ?> dari <?= $nasabah->nama_lengkap ?>), <?= $deposito->telp_ahli_waris ?></td>
                            </tr>
                        <?php endif ?>
                    <?php endif; ?>
                    <tr>
                        <th>Status</th>
                        <td>:
                            <?php if ($deposito->status === 'aktif'): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php elseif ($deposito->status === 'nonaktif'): ?>
                                <span class="badge bg-danger">Nonaktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($deposito->status) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <div class="d-flex align-end justify-content-end">
                    <div class="d-flex justify-content-end me-2">
                        <button onclick="printLaporan('<?= $deposito->id ?>', '<?= $nasabah->nama_lengkap ?>')" class="btn btn-warning">
                            Cetak Laporan <i class="fa fa-id-card ms-2"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button onclick="printSertifikat('<?= $deposito->id ?>', '<?= $nasabah->nama_lengkap ?>')" class="btn btn-info">
                            Cetak Sertifikat <i class="fa fa-id-card ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-list"></i> Detail Bunga</h5>
                <div class="text-end mb-3">
                    <strong>Total Bunga:</strong> <span id="total_bunga">Rp 0</span>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped table-hover" id="tabel_bunga">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal Bunga</th>
                            <th>Jumlah Bunga</th>
                            <th>Presentase Bunga</th>
                            <th>#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-list"></i> Detail Penarikan</h5>

                <?php
                if (!function_exists('safe_base64_encode')) {
                    function safe_base64_encode($string)
                    {
                        return strtr(base64_encode($string), '+/=', '-_?');
                    }
                }
                ?>

                <a href="<?= base_url('pencairan?id=' . safe_base64_encode($deposito->id)) ?>"
                    class="btn btn-danger <?= ($deposito->status !== 'aktif') ? 'disabled' : '' ?>">
                    <i class="fa fa-credit-card"></i>
                    Cairkan Deposito
                </a>
            </div>
            <div class="card-body">
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
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0"></script>
<script>
    var noRekening = '<?= $deposito->no_rekening ?>';
    const userLevel = '<?= $this->session->userdata('level') ?>';
    var depositoId = '<?= $deposito->id ?>';

    var tabel_bunga = $('#tabel_bunga').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        language: {
            processing: "Memuat data...",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Tidak ada data tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                next: "›",
                previous: "‹"
            },
        },
        ajax: {
            url: "<?= site_url('bunga_deposito/fetchNasabahDepositoBunga') ?>",
            type: "POST",
            data: {
                "deposito_id": depositoId
            },
            dataSrc: function(json) {
                $('#total_bunga').text('Rp ' + json.total_bunga);
                return json.data;
            }
        },
        columns: [{
                data: 0,
                className: "text-center"
            },
            {
                data: 1,
                className: "text-center"
            },
            {
                data: 2,
                className: "text-end"
            },
            {
                data: 3,
                className: "text-end"
            },
            {
                data: 4,
                className: "text-center",
                visible: userLevel === 'Admin'
            }
        ],
        columnDefs: [{
                targets: 0,
                orderable: false,
                width: "5%",
            },
            {
                targets: 4,
                orderable: false,
            }
        ]
    });

    var tabel_penarikan = $('#tabel_penarikan').DataTable({
        "processing": true,
        "serverSide": true,
        "order": [
            [1, "desc"]
        ],
        "ajax": {
            "url": "<?= site_url('pencairan/fetch_detail_penarikan_by_deposito') ?>",
            "type": "POST",
            "data": {
                "deposito_id": depositoId
            },
            "dataSrc": function(json) {
                if (json.akumulasi) {
                    $('#akumulasi_penarikan').text('Rp ' + parseFloat(json.akumulasi.jumlah_penarikan).toLocaleString('id-ID', {
                        minimumFractionDigits: 2
                    }));
                    $('#akumulasi_denda').text('Rp ' + parseFloat(json.akumulasi.jumlah_denda).toLocaleString('id-ID', {
                        minimumFractionDigits: 2
                    }));
                }
                return json.data;
            }
        },
        "columns": [{
                "data": 0,
                "orderable": false
            },
            {
                "data": 1
            },
            {
                "data": 2,
                "className": "text-end"
            },
            {
                "data": 3,
                "className": "text-end"
            },
            {
                "data": 4
            },
            {
                "data": 5,
                "orderable": false,
                "visible": userLevel === 'Admin'
            }
        ]
    });

    function deleteDetailPenarikan(id, jumlah) {
        Swal.fire({
            title: "Hapus Penarikan?",
            html: `Yakin ingin menghapus data penarikan sejumlah <strong>${jumlah}</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("<?= site_url('penarikan/hapus_detail_penarikan_ajax') ?>", {
                    penarikan_id: id
                }, function(response) {
                    if (response.success) {
                        Swal.fire("Berhasil!", response.success, "success");
                        tabel_penarikan.ajax.reload(null, false);
                    } else {
                        Swal.fire("Gagal!", response.error || "Terjadi kesalahan.", "error");
                    }
                }, "json").fail(function() {
                    Swal.fire("Error", "Tidak dapat terhubung ke server.", "error");
                });
            }
        });
    }

    function deleteRecordBunga(id, tipe) {
        Swal.fire({
            title: "Hapus data ini?",
            html: `Yakin ingin menghapus bunga?`,
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
                    data: {
                        id: id
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            Swal.fire("Berhasil!", response.success, "success");
                            $('#tabel_bunga').DataTable().ajax.reload(null, false);
                            location.reload();
                        } else {
                            Swal.fire("Gagal!", response.error, "error");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error (Hapus Bunga):", {
                            status,
                            error,
                            response: xhr.responseText
                        });
                        Swal.fire('Oops...', 'Sistem tidak dapat terhubung ke server.', 'error');
                    }
                });
            }
        });
    }

    function printLaporan(id, nama) {
        Swal.fire({
            title: "Cetak laporan transaksi?",
            html: `Membuka tab baru untuk mencetak data <strong>${nama}</strong>.`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Lanjutkan",
            cancelButtonText: "Batal",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("<?= base_url('deposito/print_laporan?id=') ?>" + id, "_blank");
            }
        });
    }

    function printSertifikat(id, nama) {
        Swal.fire({
            title: "Cetak Sertifikat?",
            html: `Yakin ingin mencetak sertifikat untuk <strong>${nama}</strong>?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#17a2b8",
            confirmButtonText: "Ya, Cetak!",
            cancelButtonText: "Batal",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("<?= base_url('deposito/print_sertifikat/') ?>" + id, "_blank");
            }
        });
    }
</script>