<section class="section">
    <div class="card">
        <div class="card-header">
            <a href="<?= site_url('users') ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3">

                </div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <div class="form-group" style="height: 80px;">
                        <label for="nama">Nama</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="nama" name="nama" placeholder="Nama jenis tabungan">
                            <span class="input-group-text"><i class="fa-regular fa-credit-card fa-fw"></i></span>
                        </div>
                        <div id="errorNama" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="bunga">Bunga</label>
                        <div class="input-group">
                            <input type="text" id="bunga" name="bunga" class="form-control" placeholder="Bunga untuk jenis tabungan">
                            <span class="input-group-text"><i class="fa fa-percent fa-fw"></i></span>
                        </div>
                        <div id="errorBunga" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="biaya_registrasi">Biaya Registrasi</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="biaya_registrasi" name="biaya_registrasi" class="form-control text-end" placeholder="20.000" value="0.00">
                        </div>
                        <div id="errorBiayaRegistrasi" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="simpanan_awal">Simpanan Awal</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="simpanan_awal" name="simpanan_awal" class="form-control text-end" placeholder="20.000" value="0.00">
                        </div>
                        <div id="errorSimpananAwal" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="pengendapan">Pengendapan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="pengendapan" name="pengendapan" class="form-control text-end" placeholder="20.000" value="0.00">
                        </div>
                        <div id="errorPengendapan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="durasi">Durasi Simpanan</label>
                        <div class="input-group">
                            <select type="text" id="durasi" name="durasi" class="form-select">
                                <option value=""> --- Pilih Durasi Simpanan ---</option>
                                <option value="6">6 Bulan (&frac12; Tahun)</option>
                                <option value="12">12 Bulan (1 Tahun)</option>
                                <option value="24">24 Bulan (2 Tahun)</option>
                                <option value="36">36 Bulan (3 tahun)</option>
                            </select>
                            <span class="input-group-text"><i class="fa fa-calendar-days fa-fw"></i></span>
                        </div>
                        <div id="errorDurasi" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 180px;">
                        <label for="keterangan">Keterangan</label>
                        <div class="input-group">
                            <textarea type="text" class="form-control" id="keterangan" name="keterangan" rows="5"></textarea>
                        </div>
                        <div id="errorKeterangan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="text-center mt-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= base_url('users') ?>'" class="btn btn-danger">Batal</button>
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
        $('#togglePassword').on('click', function() {
            var passwordField = $('#password');
            var passwordFieldType = passwordField.attr('type');

            if (passwordFieldType === 'password') {
                passwordField.attr('type', 'text');
                $(this).html('<i class="fa fa-eye fa-fw"></i>');
            } else {
                passwordField.attr('type', 'password');
                $(this).html('<i class="fa fa-eye-slash fa-fw"></i>');
            }
        });

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('users/simpanData') ?>",
                data: data,
                dataType: "json",
                processData: false,
                contentType: false,
                cache: false,
                beforeSend: function() {
                    $('#tombol_simpan').prop('disabled', true)
                    $('#tombol_simpan').html('<i class="fa fa-spin fa-spinner"></i>')
                },
                complete: function() {
                    $('#tombol_simpan').prop('disabled', false)
                    $('#tombol_simpan').html('Save')
                },
                success: function(response) {
                    if (response.error) {
                        let dataError = response.error;
                        if (dataError.errorNama) {
                            $('#errorNama').html(dataError.errorNama).show();
                            $('#nama').addClass('is-invalid');
                        } else {
                            $('#errorNama').fadeOut();
                            $('#nama').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorUserName) {
                            $('#errorUserName').html(dataError.errorUserName).show();
                            $('#username').addClass('is-invalid');
                        } else {
                            $('#errorUserName').fadeOut();
                            $('#username').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorPassword) {
                            $('#errorPassword').html(dataError.errorPassword).show();
                            $('#password').addClass('is-invalid');
                        } else {
                            $('#errorPassword').fadeOut();
                            $('#password').removeClass('is-invalid').addClass('is-valid');
                        }
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            html: response.success
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= base_url('users') ?>';
                            }
                        });
                    }
                },
                error: function(xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
        });
    });
</script>