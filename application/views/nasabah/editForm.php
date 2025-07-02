<section class="section">
    <div class="card">
        <?php
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_.');
        }
        $backUrl = $this->input->get('code') == 1
            ? site_url('nasabah/detail/' . safe_base64_encode($nasabah->nik))
            : site_url('nasabah');
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

                    <div class="alert alert-light-info color-info">
                        <i class="fa fa-info"></i> Mohon untuk semua field diisi dengan huruf kapital.
                    </div>

                    <input type="hidden" name="id" id="id" value="<?= $nasabah->id ?>">

                    <div class="form-group" style="height: 80px;">
                        <label for="nik">NIK</label>
                        <input type="text" class="form-control" id="nik" name="nik" value="<?= $nasabah->nik ?>" placeholder="NIK sesuai KTP" autocomplete="off">
                        <div id="errorNik" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" value="<?= $nasabah->nama_lengkap ?>" placeholder="Nama lengkap sesuai KTP" autocomplete="off">
                        <div id="errorNamaLengkap" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                            <option value="Laki-laki" <?= ($nasabah->jenis_kelamin == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?= ($nasabah->jenis_kelamin == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                        <div id="errorJenisKelamin" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="tempat_lahir">Tempat Lahir</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" value="<?= $nasabah->tempat_lahir ?>" placeholder="Tempat lahir sesuai KTP" autocomplete="off">
                        <div id="errorTempatLahir" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tgl_lahir" value="<?= $nasabah->tanggal_lahir ?>" class="form-control">
                        <div id="errorTanggalLahir" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="agama">Agama</label>
                        <input type="text" id="agama" name="agama" class="form-control" value="<?= $nasabah->agama ?>" placeholder="Agama sesuai KTP" autocomplete="off">
                        <div id="errorAgama" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <?php
                    $pekerjaan_tersimpan = $nasabah->pekerjaan;
                    $daftar_pekerjaan_standar = [
                        'Petani/Pekebun',
                        'Peternak',
                        'Nelayan',
                        'Pedagang',
                        'Tukang (Kayu, Batu, dll)',
                        'Guru',
                        'Perangkat Desa',
                        'Ibu Rumah Tangga',
                        'Buruh Tani/Harian',
                        'Wiraswasta',
                        'Pensiunan',
                        'Belum/Tidak Bekerja'
                    ];

                    $is_pekerjaan_standar = in_array($pekerjaan_tersimpan, $daftar_pekerjaan_standar);
                    $pekerjaan_lainnya_value = '';

                    if (!$is_pekerjaan_standar && !empty($pekerjaan_tersimpan)) {
                        $pekerjaan_lainnya_value = $pekerjaan_tersimpan;
                    }
                    ?>

                    <div class="form-group" style="min-height: 80px;">
                        <label for="pekerjaan">Pekerjaan</label>
                        <select id="pekerjaan" name="pekerjaan" class="form-control">
                            <option value="" <?= empty($pekerjaan_tersimpan) ? 'selected' : '' ?> disabled>Pilih pekerjaan Nasabah</option>

                            <?php foreach ($daftar_pekerjaan_standar as $pekerjaan) : ?>
                                <option value="<?= $pekerjaan ?>" <?= ($pekerjaan == $pekerjaan_tersimpan) ? 'selected' : '' ?>>
                                    <?= $pekerjaan ?>
                                </option>
                            <?php endforeach; ?>

                            <option value="Lainnya" <?= (!$is_pekerjaan_standar && !empty($pekerjaan_tersimpan)) ? 'selected' : '' ?>>
                                Lainnya
                            </option>
                        </select>
                        <div id="errorPekerjaan" class="invalid-feedback" style="display: none;"></div>

                        <div id="form-pekerjaan-lainnya" style="display: <?= (!$is_pekerjaan_standar && !empty($pekerjaan_tersimpan)) ? 'block' : 'none' ?>; margin-top: 15px;">
                            <label for="pekerjaan_lainnya">Sebutkan Pekerjaan Nasabah</label>
                            <input type="text" id="pekerjaan_lainnya" name="pekerjaan_lainnya" class="form-control" placeholder="Tulis pekerjaan di sini" value="<?= $pekerjaan_lainnya_value ?>">
                        </div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nama_ibu_kanudng">Nama Ibu Kandung</label>
                        <input type="text" id="nama_ibu_kandung" name="nama_ibu_kandung" class="form-control" value="<?= $nasabah->nama_ibu_kandung ?>" placeholder="Nama Ibu Kandung" autocomplete="off">
                        <div id="errorNama_ibu_kandung" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= $nasabah->email ?>" placeholder="Email Aktif" autocomplete="off">
                        <div id="errorEmail" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" class="form-control" value="<?= $nasabah->alamat ?>" placeholder="Alamat Sesuai KTP" autocomplete="off">
                        <div id="errorAlamat" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="telp">Nomer Telpon</label>
                        <input type="text" id="telp" name="telp" class="form-control" value="<?= $nasabah->telp ?>" placeholder="Nomer aktif/whatsapp" autocomplete="off">
                        <div id="errorTelp" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <?php if ($level == 'Admin'): ?>
                        <div class="form-group mb-3" style="height: 80px;">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control" autocomplete="off">
                                <option value=""> -- Pilih Pegawai -- </option>
                                <?php foreach ($pegawai as $item): ?>
                                    <option value="<?= $item->id ?>" <?= ($nasabah->pegawai_id == $item->id) ? 'selected' : ''; ?>>
                                        <?= $item->nama_lengkap ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="errorJabatan" class="invalid-feedback" style="display: none;"></div>
                            <div class="valid-feedback" style="display: none;"></div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pegawai_id" id="pegawai_id" value="<?= $this->session->userdata('id') ?>">
                    <?php endif; ?>

                    <div class="text-center mb-3">
                        <button type="button" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= $backUrl ?>'" class="btn btn-danger">Batal</button>
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
        $('#nik').on('input', function() {
            let value = $(this).val();

            value = value.replace(/\D/g, '');

            if (value.length > 16) {
                value = value.slice(0, 16);
            }

            $(this).val(value);
        });

        $('#telp').on('input', function() {
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

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('nasabah/updateData') ?>",
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
                        if (dataError.errorPekerjaan) {
                            $('#errorPekerjaan').html(dataError.errorPekerjaan).show();
                            $('#pekerjaan').addClass('is-invalid');
                        } else {
                            $('#errorPekerjaan').fadeOut();
                            $('#pekerjaan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorNama_ibu_kandung) {
                            $('#errorNama_ibu_kandung').html(dataError.errorNama_ibu_kandung).show();
                            $('#nama_ibu_kandung').addClass('is-invalid');
                        } else {
                            $('#errorNama_ibu_kandung').fadeOut();
                            $('#nama_ibu_kandung').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorEmail) {
                            $('#errorEmail').html(dataError.errorEmail).show();
                            $('#email').addClass('is-invalid');
                        } else {
                            $('#errorEmail').fadeOut();
                            $('#email').removeClass('is-invalid').addClass('is-valid');
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
                                window.location = '<?= base_url('nasabah') ?>';
                            }
                        });
                    } else {
                        Swal.fire({
                            title: "Error!",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            allowEnterKey: false,
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
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectPekerjaan = document.getElementById('pekerjaan');
        const formPekerjaanLainnya = document.getElementById('form-pekerjaan-lainnya');
        const inputPekerjaanLainnya = document.getElementById('pekerjaan_lainnya');

        selectPekerjaan.addEventListener('change', function() {
            if (this.value === 'Lainnya') {
                formPekerjaanLainnya.style.display = 'block';
                inputPekerjaanLainnya.focus();
            } else {
                formPekerjaanLainnya.style.display = 'none';
                inputPekerjaanLainnya.value = '';
            }
        });
    });
</script>