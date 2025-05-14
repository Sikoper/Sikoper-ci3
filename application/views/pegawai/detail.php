<div class="card">
    <div class="card-header">
        <button class="btn btn-warning" onclick="window.location='<?= base_url('pegawai') ?>'">
            <i class="fa fa-backward"></i> Kembali
        </button>
    </div>

    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label><strong>NIK:</strong></label>
                <div><?= $pegawai->nik ?></div>
            </div>
            <div class="col-md-6">
                <label><strong>Nama Lengkap:</strong></label>
                <div><?= $pegawai->nama_lengkap ?></div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label><strong>Jenis Kelamin:</strong></label>
                <div><?= $pegawai->jenis_kelamin ?></div>
            </div>
            <div class="col-md-6">
                <label><strong>Tempat, Tanggal Lahir:</strong></label>
                <div><?= $pegawai->tempat_lahir . ', ' . date('d-m-Y', strtotime($pegawai->tanggal_lahir)) ?></div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label><strong>Agama:</strong></label>
                <div><?= $pegawai->agama ?></div>
            </div>
            <div class="col-md-6">
                <label><strong>No. Telepon:</strong></label>
                <div><?= $pegawai->telp ?></div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label><strong>Alamat Lengkap:</strong></label>
                <div><?= $pegawai->alamat ?>, Desa <?= $nama_desa ?>, Kecamatan <?= $nama_kecamatan ?>, <br /> Kabupaten <?= $nama_kabupaten ?>, Provinsi <?= $nama_provinsi ?></div>
            </div>
            <div class="col-md-6">
                <label><strong>Jabatan:</strong></label>
                <div><?= $pegawai->jabatan ?></div>
            </div>
        </div>
        <div class="text-muted">
            <small>Terdaftar sejak: <?= date('d M Y, H:i', strtotime($pegawai->created_at)) ?></small>
        </div>
        <?php
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_.');
        }
        ?>
        <?php if ($this->session->userdata('level') != 'Direktur') : ?>
            <div class="d-flex justify-content-end mt-4">
                <button type="button" onclick="window.location='<?= base_url('pegawai/edit/' . safe_base64_encode($pegawai->nik)) . '?code=1' ?>'" class="btn btn-success me-2">
                    <i class="fa fa-edit fa-fw"></i> Edit
                </button>
                <button class="btn btn-danger" onclick="deleteItem('<?= $pegawai->nik ?>', '<?= addslashes($pegawai->nama_lengkap) ?>')">
                    <i class="fa fa-trash fa-fw"></i> Hapus
                </button>
            </div>
        <?php endif; ?>
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
                    url: "<?= base_url('pegawai/delete') ?>",
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
                        } else {
                            Swal.fire({
                                title: "Error!",
                                text: response.error,
                                icon: "error"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location = '<?= base_url('pegawai') ?>';
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