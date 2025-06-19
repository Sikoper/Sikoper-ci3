<section class="section">
    <div class="card">
        <div class="card-header">
            <button class="btn btn-warning" onclick="window.location='<?= base_url('nasabah') ?>'">
                <i class="fa fa-backward"></i> Kembali
            </button>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <div class="form-group" style="height: 80px;">
                        <label for="nik">NIK</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="NIK sesuai KTP" autocomplete="off">
                        <div id="errorNik" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Nama lengkap sesuai KTP" autocomplete="off">
                        <div id="errorNamaLengkap" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                            <option value=""> -- Pilih Jenis Kelamin -- </option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                        <div id="errorJenisKelamin" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="tempat_lahir">Tempat Lahir</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" placeholder="Tempat lahir sesuai KTP" autocomplete="off">
                        <div id="errorTempatLahir" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tgl_lahir" class="form-control">
                        <div id="errorTanggalLahir" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="agama">Agama</label>
                        <input type="text" id="agama" name="agama" class="form-control" placeholder="Agama sesuai KTP" autocomplete="off">
                        <div id="errorAgama" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="min-height: 80px;">
                        <label for="pekerjaan">Pekerjaan</label>
                        <select id="pekerjaan" name="pekerjaan" class="form-control">
                            <option value="" selected disabled>Pilih pekerjaan Nasabah</option>
                            <option value="Petani/Pekebun">Petani/Pekebun</option>
                            <option value="Peternak">Peternak</option>
                            <option value="Nelayan">Nelayan</option>
                            <option value="Pedagang">Pedagang</option>
                            <option value="Tukang (Kayu, Batu, dll)">Tukang (Kayu, Batu, dll)</option>
                            <option value="Guru">Guru</option>
                            <option value="Perangkat Desa">Perangkat Desa</option>
                            <option value="Ibu Rumah Tangga">Ibu Rumah Tangga</option>
                            <option value="Buruh Tani/Harian">Buruh Tani/Harian</option>
                            <option value="Wiraswasta">Wiraswasta</option>
                            <option value="Pensiunan">Pensiunan</option>
                            <option value="Belum/Tidak Bekerja">Belum/Tidak Bekerja</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        <div id="errorPekerjaan" class="invalid-feedback" style="display: none;"></div>

                        <div id="form-pekerjaan-lainnya" style="display: none; margin-top: 15px;">
                            <label for="pekerjaan_lainnya">Tulis Pekerjaan Nasabah</label>
                            <input type="text" id="pekerjaan_lainnya" name="pekerjaan_lainnya" class="form-control" placeholder="Tulis pekerjaan di sini">
                        </div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nama_ibu_kanudng">Nama Ibu Kandung</label>
                        <input type="text" id="nama_ibu_kandung" name="nama_ibu_kandung" class="form-control" placeholder="Nama Ibu Kandung" autocomplete="off">
                        <div id="errorNama_ibu_kandung" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Email Aktif" autocomplete="off">
                        <div id="errorEmail" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="provinsi">Provinsi Asal</label>
                        <select class="form-control" id="provinsi" name="provinsi">
                            <option value=""> -- Pilih Provinsi Asal -- </option>
                            <?php foreach ($provinces as $prov): ?>
                                <option value="<?= $prov['code'] ?>"><?= $prov['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="errorProvinsi" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="kabupaten">Kabupaten Asal</label>
                        <select class="form-control" id="kabupaten" name="kabupaten">
                            <option value=""> -- Pilih Kabupaten Asal -- </option>
                        </select>
                        <div id="errorKabupaten" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="kecamatan">Kecamatan Asal</label>
                        <select class="form-control" id="kecamatan" name="kecamatan">
                            <option value=""> -- Pilih Kecamatan Asal -- </option>
                        </select>
                        <div id="errorKecamatan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="desa">Desa Asal</label>
                        <select class="form-control" id="desa" name="desa">
                            <option value=""> -- Pilih Desa Asal -- </option>
                        </select>
                        <div id="errorDesa" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="row" style="height: 80px;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rt">RT</label>
                                <small class="text-muted"><i>opsional</i></small>
                                <input type="text" id="rt" name="rt" class="form-control" value="000">
                                <div id="errorRt" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rw">RW</label>
                                <small class="text-muted"><i>opsional</i></small>
                                <input type="text" id="rw" name="rw" class="form-control" value="000">
                                <div id="errorRw" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" class="form-control" placeholder="Alamat Sesuai KTP" autocomplete="off">
                        <div id="errorAlamat" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="telp">Nomer Telpon</label>
                        <input type="text" id="telp" name="telp" class="form-control" placeholder="Nomer aktif/whatsapp" autocomplete="off">
                        <div id="errorTelp" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <?php if ($level == 'Admin'): ?>
                        <div class="form-group mb-5" style="height: 80px;">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control" autocomplete="off">
                                <option value=""> -- Pilih Pegawai -- </option>
                                <?php foreach ($pegawai as $item): ?>
                                    <option value="<?= $item->id ?>"><?= $item->nama_lengkap ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="errorJabatan" class="invalid-feedback" style="display: none;"></div>
                            <div class="valid-feedback" style="display: none;"></div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pegawai_id" id="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
                    <?php endif; ?>

                    <div class="text-center mb-3">
                        <button type="button" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= base_url('nasabah') ?>'" class="btn btn-danger">Batal</button>
                    </div>
                    <?= form_close() ?>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
    </div>
</section>
<script>
    function formatInput($input, e, nextField, prevField) {
        e.preventDefault();
        let key = e.key;
        let val = $input.val().split('');
        let cursor = $input.data('cursor') || 0;

        if (!/^[0-9]$/.test(key) && key !== "Backspace" && key !== "Delete") return;

        if ((key === "Backspace" || key === "Delete") && cursor === 0 && prevField) {
            const $prev = $('#' + prevField);
            $prev.focus();
            const prevCursor = Math.max(($prev.data('cursor') || 3) - 1, 0);
            $prev.data('cursor', prevCursor);
            const prevVal = $prev.val().split('');
            prevVal[prevCursor] = '0';
            $prev.val(prevVal.join(''));
            setTimeout(() => {
                $prev.get(0).setSelectionRange(prevCursor, prevCursor);
            }, 10);
            return;
        }

        if (key === "Backspace" || key === "Delete") {
            if (cursor > 0) cursor--;
            val[cursor] = '0';
        } else {
            if (cursor >= 3) return;
            val[cursor] = key;
            cursor++;
        }

        $input.val(val.join(''));
        $input.data('cursor', cursor);

        const inputEl = $input.get(0);
        inputEl.setSelectionRange(cursor, cursor);

        if (cursor >= 3 && nextField) {
            setTimeout(() => {
                const $next = $('#' + nextField);
                $next.focus();
                $next.data('cursor', 0);
                $next.get(0).setSelectionRange(0, 0);
            }, 10);
        }
    }

    function setCursorToStart(input) {
        $(input).data('cursor', 0);
        input.setSelectionRange(0, 0);
    }

    $(document).ready(function() {
        $('#rt, #rw').val('000');

        $('#rt, #rw').on('focus click', function() {
            setCursorToStart(this);
        });

        $('#rt').on('keydown', function(e) {
            formatInput($(this), e, 'rw', null);
        });

        $('#rw').on('keydown', function(e) {
            formatInput($(this), e, null, 'rt');
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#provinsi').select2();
        $('#kabupaten').select2();
        $('#kecamatan').select2();
        $('#desa').select2();

        $('#provinsi').on('change', function() {
            let provinsi = $('#provinsi').val()
            $.ajax({
                type: "POST",
                url: "<?= base_url('nasabah/getKab') ?>",
                data: {
                    provinsi: provinsi
                },
                dataType: "json",
                success: function(response) {
                    if (response.data) {
                        $('#kabupaten').html(response.data);
                        $('#kabupaten').prop('disabled', false);
                    }
                },
                error: function(xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
        });

        $('#kabupaten').on('change', function() {
            let kabupaten = $('#kabupaten').val()
            $.ajax({
                type: "POST",
                url: "<?= base_url('nasabah/getKec') ?>",
                data: {
                    kabupaten: kabupaten
                },
                dataType: "json",
                success: function(response) {
                    if (response.data) {
                        $('#kecamatan').html(response.data);
                        $('#kecamatan').prop('disabled', false);
                    }
                },
                error: function(xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
        });

        $('#kecamatan').on('change', function() {
            let kecamatan = $('#kecamatan').val()
            $.ajax({
                type: "POST",
                url: "<?= base_url('nasabah/getKel') ?>",
                data: {
                    kecamatan: kecamatan
                },
                dataType: "json",
                success: function(response) {
                    if (response.data) {
                        $('#desa').html(response.data);
                        $('#desa').prop('disabled', false);
                    }
                },
                error: function(xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
        });

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('nasabah/simpanData') ?>",
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
                        if (dataError.errorProvinsi) {
                            $('#errorProvinsi').html(dataError.errorProvinsi).show();
                            $('#provinsi').addClass('is-invalid');
                        } else {
                            $('#errorProvinsi').fadeOut();
                            $('#provinsi').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorKabupaten) {
                            $('#errorKabupaten').html(dataError.errorKabupaten).show();
                            $('#kabupaten').addClass('is-invalid');
                        } else {
                            $('#errorKabupaten').fadeOut();
                            $('#kabupaten').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorKecamatan) {
                            $('#errorKecamatan').html(dataError.errorKecamatan).show();
                            $('#kecamatan').addClass('is-invalid');
                        } else {
                            $('#errorKecamatan').fadeOut();
                            $('#kecamatan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorDesa) {
                            $('#errorDesa').html(dataError.errorDesa).show();
                            $('#desa').addClass('is-invalid');
                        } else {
                            $('#errorDesa').fadeOut();
                            $('#desa').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorRt) {
                            $('#errorRt').html(dataError.errorRt).show();
                            $('#rt').addClass('is-invalid');
                        } else {
                            $('#errorRt').fadeOut();
                            $('#rt').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorRw) {
                            $('#errorRw').html(dataError.errorRw).show();
                            $('#rw').addClass('is-invalid');
                        } else {
                            $('#errorRw').fadeOut();
                            $('#rw').removeClass('is-invalid').addClass('is-valid');
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
                            $('#pegawai_id').addClass('is-invalid');
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
                            html: response.success
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= base_url('nasabah') ?>';
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
        });
    });
</script>
<script>
    $('#btn-generate').click(function() {
        $.ajax({
            url: '<?= base_url("nasabah/generate_norek") ?>',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#nomor_rekening').val(data.norek);
            },
            error: function() {
                alert("Gagal generate nomor rekening.");
            }
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