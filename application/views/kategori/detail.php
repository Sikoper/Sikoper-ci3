<div class="card shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <a href="<?= base_url('jenis_tabungan') ?>" class="btn btn-warning">
            <i class="fa fa-arrow-left me-1"></i> Kembali
        </a>
        <h5 class="mb-0"><i class="fa fa-info-circle text-primary me-2"></i>Detail Jenis Tabungan</h5>
    </div>

    <div class="card-body">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="p-3 border rounded bg-light">
                    <label class="text-muted mb-1"><strong>Jenis Simpanan</strong></label>
                    <div class="fs-5"><?= $kategori->nama ?></div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 border rounded bg-light">
                    <label class="text-muted mb-1"><strong>Bunga</strong></label>
                    <div class="fs-5"><?= $kategori->bunga ?>%</div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 border rounded bg-light">
                    <label class="text-muted mb-1"><strong>Biaya Registrasi</strong></label>
                    <div class="fs-5">Rp <?= number_format($kategori->biaya_registrasi, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 border rounded bg-light">
                    <label class="text-muted mb-1"><strong>Simpanan Awal</strong></label>
                    <div class="fs-5">Rp <?= number_format($kategori->simpanan_awal, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 border rounded bg-light">
                    <label class="text-muted mb-1"><strong>Pengendapan</strong></label>
                    <div class="fs-5">Rp <?= number_format($kategori->pengendapan, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="p-3 border rounded bg-light">
                <label class="text-muted mb-1"><strong>Keterangan</strong></label>
                <div class="fs-6"><?= nl2br($kategori->keterangan); ?></div>
            </div>
        </div>
        <?php
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_?');
        }
        ?>
        <div class="d-flex justify-content-end mt-4">
            <button type="button" onclick="window.location='<?= base_url('jenis_tabungan/edit/' . safe_base64_encode($kategori->id)) . '?code=1' ?>'" class="btn btn-success me-2">
                <i class="fa fa-edit fa-fw"></i> Edit
            </button>
            <button class="btn btn-danger" onclick="deleteItem('<?= $kategori->id ?>', '<?= addslashes($kategori->nama) ?>')">
                <i class="fa fa-trash fa-fw"></i> Hapus
            </button>
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
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('jenis_tabungan/delete') ?>",
                    data: {
                        id: id
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
</script>