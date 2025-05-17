<div class="card shadow-sm">
    <div class="card-header text-white">
        <button class="btn btn-warning" onclick="window.location='<?= base_url('nasabah') ?>'">
                <i class="fa fa-backward"></i> Kembali
            </button>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <tr>
                <th>No Rekening</th>
                <td><?= $nasabah->no_rekening ?></td>
            </tr>
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
                <td><?= $nasabah->tempat_lahir ?> / <?= date('d-m-Y', strtotime($nasabah->tanggal_lahir)) ?></td>
            </tr>
            <tr>
                <th>Agama</th>
                <td><?= $nasabah->agama ?></td>
            </tr>
            <tr>
                <th>Alamat</th>
                <td><?= $nasabah->alamat ?> RT <?= $nasabah->rt ?> /RW <?= $nasabah->rw ?></td>
            </tr> 
            <tr>
                <th>Desa</th>
                <td><?= $nama_desa ?></td>
            </tr>
            <tr>
                <th>Kecamatan</th>
                <td><?= $nama_kecamatan ?></td>
            </tr>
            <tr>
                <th>Kabupaten</th>
                <td><?= $nama_kabupaten ?></td>
            </tr>
            <tr>
                <th>Provinsi</th>
                <td><?= $nama_provinsi ?></td>
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
                <th>Email</th>
                <td><?= $nasabah->email ?></td>
            </tr>
            <tr>
                <th>Nama Ibu Kandung</th>
                <td><?= $nasabah->nama_ibu_kandung ?></td>
            </tr>
            <tr>
                <th>Tanggal dan Jam Dibuat</th>
                <td><?= date('d-m-Y H:i', strtotime($nasabah->created_at)) ?></td>
            </tr>
        </table>
        <?php
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_.');
        }
        ?>
            <div class="d-flex justify-content-end mt-4">
                <button type="button" onclick="window.location='<?= base_url('nasabah/edit/' . safe_base64_encode($nasabah->nik)) . '?code=1' ?>'" class="btn btn-success me-2">
                    <i class="fa fa-edit fa-fw"></i> Edit
                </button>
                <button class="btn btn-danger" onclick="deleteItem('<?= $nasabah->id ?>', '<?= addslashes($nasabah->nama_lengkap) ?>')">
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
                    url: "<?= base_url('nasabah/delete') ?>",
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
                                    window.location= '<?= base_url('nasabah') ?>';
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "Error!",
                                text: response.error,
                                icon: "error"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location = '<?= base_url('nasabah') ?>';
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