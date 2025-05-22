<?php
// Bisa akses data di $penarikan, misal $penarikan['total_penarikan']
?>

<form action="<?= base_url('penarikan/update') ?>" method="POST">
    <input type="hidden" name="id" value="<?= $penarikan['id'] ?>" />

    <div class="mb-3">
        <label>Nomor Rekening</label>
        <input type="text" class="form-control" value="<?= $penarikan['no_rekening'] ?>" readonly />
    </div>

    <div class="mb-3">
        <label>Nama Nasabah</label>
        <input type="text" class="form-control" value="<?= $penarikan['nama_nasabah'] ?>" readonly />
    </div>

    <div class="mb-3">
        <label>Jenis Tabungan</label>
        <input type="text" class="form-control" value="<?= $penarikan['jenis_tabungan'] ?>" readonly />
    </div>

    <div class="mb-3">
        <label>Total Penarikan</label>
        <input type="number" class="form-control" name="total_penarikan" value="<?= $penarikan['total_penarikan'] ?>" required />
    </div>

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-select" required>
            <option value="Berhasil" <?= $penarikan['status'] == 'Berhasil' ? 'selected' : '' ?>>Berhasil</option>
            <option value="Pending" <?= $penarikan['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Gagal" <?= $penarikan['status'] == 'Gagal' ? 'selected' : '' ?>>Gagal</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="<?= base_url('penarikan') ?>" class="btn btn-secondary">Batal</a>
</form>

<script>
    // Submit form edit
    $('#editForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: "<?= base_url('penarikan/update') ?>",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    Swal.fire('Berhasil', response.success, 'success').then(() => {
                        $('#editModal').modal('hide');
                        $('#tabel_penarikan').DataTable().ajax.reload();
                    });
                } else {
                    Swal.fire('Gagal', response.error || 'Terjadi kesalahan', 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Terjadi kesalahan: ' + error, 'error');
            }
        });
    });
</script>