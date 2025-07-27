<section class="section">
    <div class="card">
        <div class="card-header">
            <a href="<?= site_url('simpanan') ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>
                    <input type="hidden" name="id" id="id" value="<?= $simpanan->id ?>">
                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="tanggal_simpanan">Tanggal</label>
                        <div class="input-group">
                            <input type="date" name="tanggal_simpanan" id="tanggal_simpanan" class="form-control" value="<?= $simpanan->tanggal_simpanan ?>">
                        </div>
                        <div id="errorTanggalSimpanan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nasabah">Nasabah</label>
                        <div class="d-flex align-items-center">
                            <select id="nasabah" class="form-control select2" name="nasabah" style="width: auto; flex: 1;" disabled>
                                <option value="<?= $nasabah->id ?>"><?= $nasabah->nama_lengkap ?></option>
                            </select>

                            <input type="hidden" name="nasabah" id="nasabah" value="<?= $nasabah->id ?>">
                        </div>
                        <div id="errorNasabah" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nomor_rekening">Nomor Rekening</label>
                        <div class="input-group">
                            <input type="text" name="nomor_rekening" value="<?= $simpanan->no_rekening ?>" id="nomor_rekening" class="form-control" readonly>
                        </div>
                        <div id="errorNoRekening" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jenis_tabungan_display">Jenis Rekening</label>
                        <input type="text" class="form-control" id="jenis_tabungan_display" value="<?= $jenis->nama ?>" name="jenis_tabungan_display" readonly>
                        </input>
                        <div id="errorJenisTabungan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <input type="hidden" value="<?= $jenis->id ?>" id="jenis_tabungan" name="jenis_tabungan">

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="bunga">Jumlah Bunga</label>
                                <div class="input-group">
                                    <input type="text" name="bunga" id="bunga" class="form-control text-end" readonly>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div id="errorBunga" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="biaya_registrasi">Biaya Registrasi</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" name="biaya_registrasi" id="biaya_registrasi" class="form-control text-end" readonly>
                                </div>
                                <div id="errorBiayaRegistrasi" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="simpanan_awal">Simpanan Awal</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" name="simpanan_awal" id="simpanan_awal" class="form-control text-end" readonly>
                                </div>
                                <div id="errorSimpananAwal" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="pengendapan">Pengendapan</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" name="pengendapan" id="pengendapan" class="form-control text-end" readonly>
                                </div>
                                <div id="errorPengendapan" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($level == 'Admin'): ?>
                        <div class="form-group mb-3" style="height: 80px;">
                            <label for="pegawai_display">Pegawai</label>
                            <select id="pegawai_display" name="pegawai_display" class="form-control" autocomplete="off" disabled>
                                <option value=""> -- Pilih Pegawai -- </option>
                                <?php foreach ($pegawai as $item): ?>
                                    <option value="<?= $item->id ?>" <?= ($item->id == $simpanan->pegawai_id) ? 'selected' : '' ?>>
                                        <?= $item->nama_lengkap ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="pegawai_id" name="pegawai_id" value="<?= $simpanan->pegawai_id ?>">
                            <div id="errorPegawai" class="invalid-feedback" style="display: none;"></div>
                            <div class="valid-feedback" style="display: none;"></div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pegawai_id" id="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
                    <?php endif; ?>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="jumlah_simpanan">Jumlah Simpanan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="jumlah_simpanan" id="jumlah_simpanan" class="form-control text-end" value="<?= $simpanan->jumlah_simpanan ?>">
                        </div>
                        <div id="errorJumlahSimpanan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= base_url('simpanan') ?>'" class="btn btn-danger">Batal</button>
                    </div>

                    <?= form_close() ?>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0"></script>
<script>
    $(document).ready(function() {
        $('#bunga').autoNumeric('init', {
            aSep: ',',
            aDec: '.',
            mDec: '2',
            vMin: '0',
            vMax: '100'
        });

        $('#biaya_registrasi').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        $('#jumlah_simpanan').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        $('#jenis_tabungan').on('change', function() {
            var jenis_id = $(this).val();

            if (jenis_id !== null) {
                $.ajax({
                    url: '<?= base_url('simpanan/getJenisData') ?>',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        id: jenis_id
                    },
                    success: function(response) {
                        jenis_tabungan_handler(response);
                        $('#btn-generate').attr('disabled', false);
                        if (response.kategori.nama === 'Deposito') {
                            $('#form_deposito').show();
                        } else {
                            $('#form_deposito').hide();
                        }
                    },
                    error: function(xhr, thrownError) {
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                    }
                });
            }
        });

        $('#jenis_tabungan').trigger('change');

        function jenis_tabungan_handler(response) {
            const kategori = response?.kategori;

            if (!kategori) {
                console.warn("Kategori data not found in response.");
                return;
            }

            let biaya = parseFloat(kategori.biaya_registrasi ?? 0);
            biaya = biaya % 1 === 0 ? parseInt(biaya) : biaya;
            let simpanan_awal = parseFloat(kategori.simpanan_awal ?? 0);
            simpanan_awal = simpanan_awal % 1 === 0 ? parseInt(simpanan_awal) : simpanan_awal;
            let pengendapan = parseFloat(kategori.pengendapan ?? 0);
            pengendapan = pengendapan % 1 === 0 ? parseInt(pengendapan) : pengendapan;
            let jenis_denda = kategori.jenis_denda;
            let jumlah_denda = parseFloat(kategori.jumlah_denda ?? 0);
            jumlah_denda = jumlah_denda % 1 === 0 ? parseInt(jumlah_denda) : jumlah_denda;

            const bungaAN = new AutoNumeric('#bunga', {
                digitGroupSeparator: ',',
                decimalCharacter: '.',
                decimalPlaces: 2,
                minimumValue: '0',
                maximumValue: '100'
            });

            const biayaAN = new AutoNumeric('#biaya_registrasi', {
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                decimalPlaces: 0
            });

            const simpananAwalAN = new AutoNumeric('#simpanan_awal', {
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                decimalPlaces: 0
            });

            const pengendapanAN = new AutoNumeric('#pengendapan', {
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                decimalPlaces: 0
            });

            let jumlahDendaAN;

            bungaAN.set(kategori.bunga ?? '');
            biayaAN.set(biaya ?? '');
            simpananAwalAN.set(simpanan_awal ?? '');
            pengendapanAN.set(pengendapan ?? '');
            $('#jenis_denda').val(kategori.jenis_denda);
            if (jumlahDendaAN) {
                jumlahDendaAN.set(jumlah_denda ?? '');
            }
        }

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('simpanan/updateData') ?>",
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
                    $('#tombol_simpan').html('Simpan')
                },
                success: function(response) {
                    if (response.error) {
                        let dataError = response.error;
                        if (dataError.errorTanggalSimpanan) {
                            $('#errorTanggalSimpanan').html(dataError.errorTanggalSimpanan).show();
                            $('#tanggal_simpanan').addClass('is-invalid');
                        } else {
                            $('#errorTanggalSimpanan').fadeOut();
                            $('#tanggal_simpanan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorNasabah) {
                            $('#errorNasabah').html(dataError.errorNasabah).show();
                            $('#nasabah').addClass('is-invalid');
                        } else {
                            $('#errorNasabah').fadeOut();
                            $('#nasabah').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJenisTabungan) {
                            $('#errorJenisTabungan').html(dataError.errorJenisTabungan).show();
                            $('#jenis_tabungan').addClass('is-invalid');
                        } else {
                            $('#errorJenisTabungan').fadeOut();
                            $('#jenis_tabungan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorBunga) {
                            $('#errorBunga').html(dataError.errorBunga).show();
                            $('#bunga').addClass('is-invalid');
                        } else {
                            $('#errorBunga').fadeOut();
                            $('#bunga').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorBiayaRegistrasi) {
                            $('#errorBiayaRegistrasi').html(dataError.errorBiayaRegistrasi).show();
                            $('#biaya_registrasi').addClass('is-invalid');
                        } else {
                            $('#errorBiayaRegistrasi').fadeOut();
                            $('#biaya_registrasi').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorSimpananAwal) {
                            $('#errorSimpananAwal').html(dataError.errorSimpananAwal).show();
                            $('#simpanan_awal').addClass('is-invalid');
                        } else {
                            $('#errorSimpananAwal').fadeOut();
                            $('#simpanan_awal').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorPengendapan) {
                            $('#errorPengendapan').html(dataError.errorPengendapan).show();
                            $('#pengendapan').addClass('is-invalid');
                        } else {
                            $('#errorPengendapan').fadeOut();
                            $('#pengendapan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJenisDenda) {
                            $('#errorJenisDenda').html(dataError.errorJenisDenda).show();
                            $('#jenis_denda').addClass('is-invalid');
                        } else {
                            $('#errorJenisDenda').fadeOut();
                            $('#jenis_denda').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJumlahDenda) {
                            $('#errorJumlahDenda').html(dataError.errorJumlahDenda).show();
                            $('#jumlah_denda').addClass('is-invalid');
                        } else {
                            $('#errorJumlahDenda').fadeOut();
                            $('#jumlah_denda').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJumlahDenda) {
                            $('#errorJumlahDenda').html(dataError.errorJumlahDenda).show();
                            $('#jumlah_denda').addClass('is-invalid');
                        } else {
                            $('#errorJumlahDenda').fadeOut();
                            $('#jumlah_denda').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJumlahSimpanan) {
                            $('#errorJumlahSimpanan').html(dataError.errorJumlahSimpanan).show();
                            $('#jumlah_simpanan').addClass('is-invalid');
                        } else {
                            $('#errorJumlahSimpanan').fadeOut();
                            $('#jumlah_simpanan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorTandaTangan) {
                            $('#errorTandaTangan').html(dataError.errorTandaTangan).show();
                            $('#signature_input').addClass('is-invalid');
                        } else {
                            $('#errorTandaTangan').fadeOut();
                            $('#signature_input').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorNoRekening) {
                            $('#errorNoRekening').html(dataError.errorNoRekening).show();
                            $('#nomor_rekening').addClass('is-invalid');
                        } else {
                            $('#errorNoRekening').fadeOut();
                            $('#nomor_rekening').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorDurasi) {
                            $('#errorDurasi').html(dataError.errorDurasi).show();
                            $('#durasi').addClass('is-invalid');
                        } else {
                            $('#errorDurasi').fadeOut();
                            $('#durasi').removeClass('is-invalid').addClass('is-valid');
                        }
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            allowEnterKey: false,
                            html: response.success
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= base_url('simpanan') ?>';
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