<section class="section">
    <div class="card">
        <?php
        // Menggunakan safe_base64_encode dari secure_helper.php (autoloaded)
        $backUrl = $this->input->get('code') == 1
            ? site_url('pegawai/detail/' . safe_base64_encode($pegawai->id))
            : site_url('pegawai');
        ?>
        <div class="card-header">
            <a href="<?= $backUrl ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>
                    <input type="hidden" id="id" name="id" value="<?= $pegawai->id ?>">
                    <div class="form-group" style="height: 80px;">
                        <label for="nik">NIK</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="NIK sesuai KTP"
                            autocomplete="off" value="<?= $pegawai->nik; ?>">
                        <div id="errorNik" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control"
                            placeholder="Nama lengkap sesuai KTP" value="<?= $pegawai->nama_lengkap; ?>"
                            autocomplete="off">
                        <div id="errorNamaLengkap" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                            <option value="Laki-laki" <?= ($pegawai->jenis_kelamin == 'Laki-laki') ? 'selected' : ''; ?>>
                                Laki-laki</option>
                            <option value="Perempuan" <?= ($pegawai->jenis_kelamin == 'Perempuan') ? 'selected' : ''; ?>>
                                Perempuan</option>
                        </select>
                        <div id="errorJenisKelamin" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="tempat_lahir">Tempat Lahir</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control"
                            placeholder="Tempat lahir sesuai KTP" value="<?= $pegawai->tempat_lahir ?>"
                            autocomplete="off">
                        <div id="errorTempatLahir" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control"
                            value="<?= $pegawai->tanggal_lahir ?>">
                        <div id="errorTanggalLahir" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="agama">Agama</label>
                        <input type="text" id="agama" name="agama" class="form-control" placeholder="Agama sesuai KTP"
                            value="<?= $pegawai->agama ?>" autocomplete="off">
                        <div id="errorAgama" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" class="form-control"
                            placeholder="Alamat Sesuai KTP" value="<?= $pegawai->alamat ?>" autocomplete="off">
                        <div id="errorAlamat" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="telp">Nomer Telpon</label>
                        <input type="text" id="telp" name="telp" class="form-control" placeholder="Nomer aktif/whatsapp"
                            value="<?= $pegawai->telp ?>" autocomplete="off">
                        <div id="errorTelp" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group mb-5" style="height: 80px;">
                        <label for="jabatan">Jabatan</label>
                        <select id="jabatan" name="jabatan" class="form-select">
                            <option value=""> -- Pilih Jabatan -- </option>
                            <option value="PENANGGUNG JAWAB" <?= ($pegawai->jabatan == 'PENANGGUNG JAWAB') ? 'selected' : ''; ?>>PENANGGUNG JAWAB</option>
                            <option value="PENGAWAS I" <?= ($pegawai->jabatan == 'PENGAWAS I') ? 'selected' : ''; ?>>
                                PENGAWAS I</option>
                            <option value="KEPALA SPBS" <?= ($pegawai->jabatan == 'KEPALA SPBS') ? 'selected' : ''; ?>>
                                KEPALA SPBS</option>
                            <option value="PENGAWAS II" <?= ($pegawai->jabatan == 'PENGAWAS II') ? 'selected' : ''; ?>>
                                PENGAWAS II</option>
                            <option value="BADAN PEMERIKSA" <?= ($pegawai->jabatan == 'BADAN PEMERIKSA') ? 'selected' : ''; ?>>BADAN PEMERIKSA</option>
                            <option value="KEPALA BAGIAN TATA USAHA" <?= ($pegawai->jabatan == 'KEPALA BAGIAN TATA USAHA') ? 'selected' : ''; ?>>KEPALA BAGIAN TATA USAHA</option>
                            <option value="PEMBUKUAN 1" <?= ($pegawai->jabatan == 'PEMBUKUAN 1') ? 'selected' : ''; ?>>
                                PEMBUKUAN 1</option>
                            <option value="CUSTOMER SERVICE" <?= ($pegawai->jabatan == 'CUSTOMER SERVICE') ? 'selected' : ''; ?>>CUSTOMER SERVICE</option>
                            <option value="PEMASARAN KREDIT" <?= ($pegawai->jabatan == 'PEMASARAN KREDIT') ? 'selected' : ''; ?>>PEMASARAN KREDIT</option>
                            <option value="KEPALA BAGIAN KEUANGAN" <?= ($pegawai->jabatan == 'KEPALA BAGIAN KEUANGAN') ? 'selected' : ''; ?>>KEPALA BAGIAN KEUANGAN</option>
                            <option value="PEMBUKUAN TABUNGAN" <?= ($pegawai->jabatan == 'PEMBUKUAN TABUNGAN') ? 'selected' : ''; ?>>PEMBUKUAN TABUNGAN</option>
                            <option value="PEMUNGUTAN TABUNGAN" <?= ($pegawai->jabatan == 'PEMUNGUTAN TABUNGAN') ? 'selected' : ''; ?>>PEMUNGUTAN TABUNGAN</option>
                            <option value="PEMBANTU UMUM" <?= ($pegawai->jabatan == 'PEMBANTU UMUM') ? 'selected' : ''; ?>>
                                PEMBANTU UMUM</option>
                        </select>
                        <div id="errorJabatan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>
                    <div class="text-center mb-3">
                        <button type="button" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= base_url('pegawai') ?>'"
                            class="btn btn-danger">Batal</button>
                    </div>
                    <?= form_close() ?>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
    </div>
</section>
<script>
    $(document).ready(function () {
        $('#nik').on('input', function () {
            let value = $(this).val();
            value = value.replace(/\D/g, '');
            if (value.length > 16) {
                value = value.slice(0, 16);
            }
            $(this).val(value);
        });

        $('#telp').on('input', function () {
            let value = $(this).val();
            value = value.replace(/\D/g, '');
            if (value.length > 0 && value.charAt(0) !== '0') {
                value = value.replace(/^[^0]+/, '');
            }
            if (value.length > 14) {
                value = value.slice(0, 14);
            }
            $(this).val(value);
        });

        $('#tombol_simpan').click(function (e) {
            e.preventDefault();
            let form = $('#form_simpan')[0];
            let data = new FormData(form);
            $.ajax({
                type: "POST",
                url: "<?= base_url('pegawai/updateData') ?>",
                data: data,
                dataType: "json",
                processData: false,
                contentType: false,
                cache: false,
                beforeSend: function () {
                    $('#tombol_simpan').prop('disabled', true)
                    $('#tombol_simpan').html('<i class="fa fa-spin fa-spinner"></i>')
                },
                complete: function () {
                    $('#tombol_simpan').prop('disabled', false)
                    $('#tombol_simpan').html('Simpan')
                },
                success: function (response) {
                    if (response.error) {
                        let dataError = response.error;
                        if (dataError.errorNik) {
                            $('#errorNik').html(dataError.errorNik).show();
                            $('#nik').addClass('is-invalid');
                        } else {
                            $('#errorNik').fadeOut();
                            $('#nik').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorNamaLengkap) {
                            $('#errorNamaLengkap').html(dataError.errorNamaLengkap).show();
                            $('#nama_lengkap').addClass('is-invalid');
                        } else {
                            $('#errorNamaLengkap').fadeOut();
                            $('#nama_lengkap').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorTempatLahir) {
                            $('#errorTempatLahir').html(dataError.errorTempatLahir).show();
                            $('#tempat_lahir').addClass('is-invalid');
                        } else {
                            $('#errorTempatLahir').fadeOut();
                            $('#tempat_lahir').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorTanggalLahir) {
                            $('#errorTanggalLahir').html(dataError.errorTanggalLahir).show();
                            $('#tanggal_lahir').addClass('is-invalid');
                        } else {
                            $('#errorTanggalLahir').fadeOut();
                            $('#tanggal_lahir').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJenisKelamin) {
                            $('#errorJenisKelamin').html(dataError.errorJenisKelamin).show();
                            $('#jenis_kelamin').addClass('is-invalid');
                        } else {
                            $('#errorJenisKelamin').fadeOut();
                            $('#jenis_kelamin').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorAlamat) {
                            $('#errorAlamat').html(dataError.errorAlamat).show();
                            $('#alamat').addClass('is-invalid');
                        } else {
                            $('#errorAlamat').fadeOut();
                            $('#alamat').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorTelp) {
                            $('#errorTelp').html(dataError.errorTelp).show();
                            $('#telp').addClass('is-invalid');
                        } else {
                            $('#errorTelp').fadeOut();
                            $('#telp').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJabatan) {
                            $('#errorJabatan').html(dataError.errorJabatan).show();
                            $('#jabatan').addClass('is-invalid');
                        } else {
                            $('#errorJabatan').fadeOut();
                            $('#jabatan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorAgama) {
                            $('#errorAgama').html(dataError.errorAgama).show();
                            $('#agama').addClass('is-invalid');
                        } else {
                            $('#errorAgama').fadeOut();
                            $('#agama').removeClass('is-invalid').addClass('is-valid');
                        }

                    } else if (response.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            allowEnterKey: false,
                            html: response.success
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= $backUrl ?>';
                            }
                        });
                    } else {
                        Swal.fire({
                            title: "Error!",
                            text: "Terjadi kesalahan saat update data.",
                            icon: "error"
                        });
                    }
                },
                error: function (xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
        });
    });
</script>
