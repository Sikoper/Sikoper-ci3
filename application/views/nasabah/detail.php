<div class="card shadow-sm">
    <div class="card-header text-white">
        <button class="btn btn-warning" onclick="window.location='<?= base_url('nasabah') ?>'">
            <i class="fa fa-backward"></i> Kembali
        </button>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <tr>
                <th>NIK</th>
                <td><?= $nasabah->nik ?></td>
            </tr>
            <tr>
                <th>Nama Lengkap</th>
                <td><?= $nasabah->nama_lengkap ?></td>
            </tr>
            <tr>
                <th>Jenis Kelamin</th>
                <td><?= $nasabah->jenis_kelamin ?></td>
            </tr>
            <tr>
                <th>Tempat / Tanggal Lahir</th>
                <td><?= $nasabah->tempat_lahir ?> /
                    <?= $nasabah->tanggal_lahir ? date('d-m-Y', strtotime($nasabah->tanggal_lahir)) : '-' ?></td>
            </tr>
            <tr>
                <th>Agama</th>
                <td><?= $nasabah->agama ?></td>
            </tr>
            <tr>
                <th>Alamat</th>
                <td><?= $nasabah->alamat ?>
            </tr>
            <tr>
                <th>Pekerjaan</th>
                <td><?= $nasabah->pekerjaan ?></td>
            </tr>
            <tr>
                <th>No Telp</th>
                <td><?= $nasabah->telp ?></td>
            </tr>
            <tr>
                <th>Nama Ibu Kandung</th>
                <td><?= $nasabah->nama_ibu_kandung ?></td>
            </tr>
            <tr>
                <th>Tanggal dan Jam Dibuat</th>
                <td><?= $nasabah->created_at ? date('d-m-Y H:i', strtotime($nasabah->created_at)) : '-' ?></td>
            </tr>
        </table>
        <?php
        // Menggunakan safe_base64_encode dari secure_helper.php (autoloaded)
        ?>
        <div class="d-flex justify-content-end mt-4">
            <?php if ($level == 'Admin'): ?>
                <button type="button"
                    onclick="window.location='<?= base_url('nasabah/edit/' . safe_base64_encode($nasabah->id)) . '?code=1' ?>'"
                    class="btn btn-success me-2">
                    <i class="fa fa-edit fa-fw"></i> Edit
                </button>
                <button class="btn btn-danger"
                    onclick="deleteItem('<?= $nasabah->id ?>', '<?= addslashes($nasabah->nama_lengkap) ?>')">
                    <i class="fa fa-trash fa-fw"></i> Hapus
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fa fa-book"></i> Daftar Tabungan</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Rekening</th>
                            <th>Jenis</th>
                            <th>Saldo</th>
                            <th>#</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($simpanan)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada data tabungan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($simpanan as $s): ?>
                                <tr>
                                    <td><?= $s->no_rekening ?></td>
                                    <td><?= $s->jenis_tabungan ?></td>
                                    <td>Rp <?= number_format($s->jumlah_simpanan, 2, ',', '.') ?></td>
                                    <td>
                                        <a href="<?= base_url('simpanan/detail/' . safe_base64_encode($s->no_rekening)) ?>" class="btn btn-sm btn-info text-white" title="Lihat Detail">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fa fa-certificate"></i> Daftar Deposito</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Rekening</th>
                            <th>Jenis</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th>#</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deposito)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Belum ada data deposito.</td></tr>
                        <?php else: ?>
                            <?php foreach ($deposito as $d): ?>
                                <tr>
                                    <td><?= $d->no_rekening ?></td>
                                    <td><?= $d->jenis_tabungan ?></td>
                                    <td>Rp <?= number_format($d->jumlah_deposito, 2, ',', '.') ?></td>
                                    <td>
                                        <?php if ($d->status === 'aktif'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php elseif ($d->status === 'nonaktif'): ?>
                                            <span class="badge bg-danger">Nonaktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($d->status) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('deposito/detail/' . safe_base64_encode($d->id)) ?>" class="btn btn-sm btn-info text-white" title="Lihat Detail">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function deleteItem(id, nama) {
        Swal.fire({
            title: "Hapus data ini?",
            html: `Yakin ingin menghapus data dari <strong>${nama}</strong>?`,
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
                    url: "<?= base_url('nasabah/delete') ?>",
                    data: {
                        id: id
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
                                    window.location = '<?= base_url('nasabah') ?>';
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "Error!",
                                text: response.error,
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                icon: "error"
                            }).then((result) => {
                                if (result.isConfirmed) {
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
</script>
