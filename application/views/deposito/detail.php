<div class="row">
    <?php
    // DIKEMBALIKAN: Fungsi diletakkan kembali di view untuk perbaikan minimal
    // Ini akan memperbaiki error "Call to undefined function"
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
                        <td id="totalPenarikanValue">:
                            <span id="akumulasi_penarikan">Rp <?= number_format($total_akumulasi_penarikan, 2, ',', '.') ?></span>
                            <?php if ($total_akumulasi_denda > 0): ?>
                                (Denda: <span id="akumulasi_denda">Rp <?= number_format($total_akumulasi_denda, 2, ',', '.') ?></span>)
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
                </table>
                <div class="d-flex justify-content-end gap-2">
                    <button onclick="printNasabah('<?= $deposito->id ?>', '<?= $nasabah->nama_lengkap ?>')" class="btn btn-primary">
                        Cetak Nasabah <i class="fa fa-user-circle ms-2"></i>
                    </button>
                    <button onclick="window.location='<?= base_url('deposito/laporan') . '?id=' . safe_base64_encode($deposito->no_rekening) . '&code=1' ?>'" class="btn btn-warning">
                        Cetak Laporan <i class="fa fa-file-alt ms-2"></i>
                    </button>
                    <button onclick="printSertifikat('<?= $deposito->id ?>', '<?= $nasabah->nama_lengkap ?>')" class="btn btn-info">
                        Cetak Sertifikat <i class="fa fa-id-card ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-list"></i> Detail Bunga</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped" id="tabel_bunga">
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
                <button class="btn btn-danger" onclick="window.location='<?= base_url('pencairan/') . '?id=' . safe_base64_encode($deposito->no_rekening) ?>'"><i class="fa fa-credit-card"></i> Tarik Tunai</button>
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

    // Inisialisasi Tabel Bunga
    var tabel_bunga = $('#tabel_bunga').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?= site_url('bunga/fetchNasabahDepositoBunga') ?>",
            "type": "POST",
            "data": {
                "no_rekening": noRekening
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
                "data": 2
            },
            {
                "data": 3
            },
            {
                "data": 4,
                "orderable": false,
                "visible": userLevel === 'Admin'
            }
        ]
    });

    // Inisialisasi Tabel Penarikan
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
            cancelButtonText: "Batal"
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

    function deleteRecordBunga(id, nama, tipe) {
        Swal.fire({
            title: "Hapus data ini?",
            html: `Yakin ingin menghapus bunga dari no. rekening: <strong>${nama}</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("<?= base_url('bunga/delete') ?>", {
                    id: id,
                    tipe: tipe
                }, function(response) {
                    if (response.success) {
                        Swal.fire("Berhasil!", response.success, "success");
                        tabel_bunga.ajax.reload(null, false);
                    } else {
                        Swal.fire("Gagal!", response.error || "Terjadi kesalahan.", "error");
                    }
                }, "json").fail(function() {
                    Swal.fire("Error", "Tidak dapat terhubung ke server.", "error");
                });
            }
        });
    }

    function printNasabah(id, nama) {
        Swal.fire({
            title: "Cetak Data Nasabah?",
            html: `Membuka tab baru untuk mencetak data <strong>${nama}</strong>.`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Lanjutkan",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("<?= base_url('deposito/print_nasabah?id=') ?>" + id, "_blank");
            }
        });
    }

    function printSertifikat(id, nama) {
        Swal.fire({
            title: "Cetak Sertifikat?",
            html: `Yakin ingin mencetak sertifikat untuk <strong>${nama}</strong>?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#17a2b8", // Warna tombol info
            confirmButtonText: "Ya, Cetak!",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                // Ini akan memanggil fungsi print_sertifikat($id) di controller Anda
                window.open("<?= base_url('deposito/print_sertifikat/') ?>" + id, "_blank");
            }
        });
    }
</script>