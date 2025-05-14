<section class="section">
    <div class="card">
        <div class="card-header">
            <button class="btn btn-warning" onclick="window.location='<?= base_url('pegawai') ?>'">
                <i class="fa fa-backward"></i> Kembali
            </button>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <div class="form-group" style="height: 80px;">
                        <label for="no_rekening">Nomor Rekening</label>
                        <input type="text" class="form-control" id="no_rekening" name="no_rekening" placeholder="Nomor rekening otomatis" readonly>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nik">NIK</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="NIK sesuai KTP" autocomplete="off">
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Nama lengkap sesuai KTP" autocomplete="off">
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                            <option value=""> -- Pilih Jenis Kelamin -- </option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="tempat_lahir">Tempat Lahir</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" placeholder="Tempat lahir sesuai KTP" autocomplete="off">
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control">
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" class="form-control" placeholder="Alamat Sesuai KTP" autocomplete="off">
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="telp">Nomer Telepon</label>
                        <input type="text" id="telp" name="telp" class="form-control" placeholder="Nomor aktif/whatsapp" autocomplete="off">
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jabatan">Jabatan</label>
                        <select id="jabatan" name="jabatan" class="form-select">
                            <option value=""> -- Pilih Jabatan -- </option>
                            <option value="Direktur">Direktur</option>
                            <option value="Teller">Teller</option>
                            <option value="Lapangan">Lapangan</option>
                        </select>
                    </div>

                    <div class="text-center mb-3">
                        <button type="button" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= base_url('pegawai') ?>'" class="btn btn-danger">Batal</button>
                    </div>
                    <?= form_close() ?>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        $.ajax({
            url: '<?= base_url('pegawai/generateNoRekening') ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.no_rekening) {
                    $('#no_rekening').val(response.no_rekening);
                }
            }
        });

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();
            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: 'POST',
                url: '<?= base_url('pegawai/simpanData') ?>',
                data: data,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Berhasil!', 'Data berhasil disimpan', 'success');
                    } else {
                        Swal.fire('Gagal!', 'Terjadi kesalahan saat menyimpan', 'error');
                    }
                }
            });
        });
    });
</script>