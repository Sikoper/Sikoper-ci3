<section class="section">
    <div class="card">
        <?php
        // Menggunakan safe_base64_encode dari secure_helper.php (autoloaded)
        $backUrl = $this->input->get('code') == 1
            ? site_url('jenis_tabungan/detail/' . safe_base64_encode($kategori->id))
            : site_url('jenis_tabungan');
        ?>
        <div class="card-header">
            <a href="<?= $backUrl ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3">

                </div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>
                    <input type="hidden" name="id" id="id" value="<?= $kategori->id ?>">
                    <div class="form-group" style="height: 80px;">
                        <label for="nama">Nama</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="nama" name="nama"
                                placeholder="Nama jenis tabungan" value="<?= $kategori->nama ?>">
                            <span class="input-group-text"><i class="fa-regular fa-credit-card fa-fw"></i></span>
                        </div>
                        <div id="errorNama" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="bunga">Bunga</label>
                        <div class="input-group">
                            <input type="text" id="bunga" name="bunga" class="form-control"
                                placeholder="Bunga untuk jenis tabungan" value="<?= $kategori->bunga ?>">
                            <span class="input-group-text"><i class="fa fa-percent fa-fw"></i></span>
                        </div>
                        <div id="errorBunga" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="biaya_registrasi">Biaya Registrasi</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="biaya_registrasi" name="biaya_registrasi"
                                class="form-control text-end" value="<?= $kategori->biaya_registrasi ?>">
                        </div>
                        <div id="errorBiayaRegistrasi" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="simpanan_awal">Simpanan Awal</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="simpanan_awal" name="simpanan_awal" class="form-control text-end"
                                value="<?= $kategori->simpanan_awal ?>">
                        </div>
                        <div id="errorSimpananAwal" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="pengendapan">Pengendapan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="pengendapan" name="pengendapan" class="form-control text-end"
                                value="<?= $kategori->pengendapan ?>">
                        </div>
                        <div id="errorPengendapan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="tanggal_pembungaan">Tanggal Pembungaan</label>
                        <div class="input-group">
                            <input type="text" id="tanggal_pembungaan" name="tanggal_pembungaan"
                                value="<?= $kategori->tanggal_bunga ?>" class="form-control">
                        </div>
                        <div id="errorTanggalPembungaan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jenis_denda">Jenis Denda</label>
                        <div class="input-group">
                            <select id="jenis_denda" name="jenis_denda" class="form-control">
                                <option value=""> -- Pilih jenis denda -- </option>
                                <option value="Rp" <?= ($kategori->jenis_denda == 'Rp') ? 'selected' : ''; ?>>Rp</option>
                                <option value="%" <?= ($kategori->jenis_denda == '%') ? 'selected' : ''; ?>>%</option>
                            </select>
                        </div>
                        <div id="errorJenisDenda" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>


                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="denda_idr">Pinalti/Denda Penarikan (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="denda_idr" name="denda_idr" class="form-control text-end" disabled>
                        </div>
                        <div id="errorDendaIdr" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="denda_persen">Pinalti/Denda Penarikan (%)</label>
                        <div class="input-group">
                            <input type="text" id="denda_persen" name="denda_persen" class="form-control text-end"
                                disabled>
                            <span class="input-group-text">%</span>
                        </div>
                        <div id="errorDendaPersen" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 180px;">
                        <label for="keterangan">Keterangan</label>
                        <div class="input-group">
                            <textarea class="form-control" id="keterangan" name="keterangan"
                                rows="5"><?= $kategori->keterangan ?></textarea>
                        </div>
                        <div id="errorKeterangan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="text-center mt-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= $backUrl ?>'"
                            class="btn btn-danger">Batal</button>
                    </div>

                    <?= form_close() ?>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
    </div>
</section>
<script src="<?= base_url('assets') ?>/vendors/autonumeric.js/autoNumeric.js"></script>
<script>
    $(document).ready(function () {
        $('#bunga').autoNumeric('init', {
            aSep: ',',
            aDec: '.',
            mDec: '2',
            vMin: '0',
            vMax: '100',
        });
        $('#biaya_registrasi').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0',
        });
        $('#simpanan_awal').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0',
        });
        $('#pengendapan').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0',
        });
        $('#tanggal_pembungaan').on('input', function () {
            let value = $(this).val();

            value = value.replace(/\D/g, '');

            if (value === '') {
                $(this).val('');
                return;
            }

            let num = parseInt(value, 10);

            if (num >= 1 && num <= 30) {
                $(this).val(num);
            } else {
                value = value.slice(0, -1);
                $(this).val(value);
            }
        });

        $('#denda_idr').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        $('#denda_persen').autoNumeric('init', {
            aSep: ',',
            aDec: '.',
            mDec: '2',
            vMin: '0',
            vMax: '100'
        });

        $('#jenis_denda').on('change', function () {
            const value = $(this).val() || '<?= $kategori->jenis_denda ?? '' ?>';
            const dendaValue = '<?= $kategori->jumlah_denda ?? '' ?>';
            const numericValue = parseFloat(dendaValue || 0);

            if (value === 'Rp') {
                $('#denda_persen').val('');
                $('#denda_persen').prop('disabled', true);

                $('#denda_idr').prop('disabled', false);
                $('#denda_idr').autoNumeric('set', numericValue);
            } else if (value === '%') {
                $('#denda_idr').val('');
                $('#denda_idr').prop('disabled', true);

                $('#denda_persen').prop('disabled', false);
                $('#denda_persen').autoNumeric('set', numericValue);
            } else {
                $('#denda_idr').val('');
                $('#denda_persen').val('');
                $('#denda_idr').prop('disabled', true);
                $('#denda_persen').prop('disabled', true);
            }
        });

        $('#jenis_denda').trigger('change');

        $('#tombol_simpan').click(function (e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('kategori/updateData') ?>",
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
                    $('#tombol_simpan').html('Save')
                },
                success: function (response) {
                    if (response.error) {
                        let dataError = response.error;
                        if (dataError.errorNama) {
                            $('#errorNama').html(dataError.errorNama).show();
                            $('#nama').addClass('is-invalid');
                        } else {
                            $('#errorNama').fadeOut();
                            $('#nama').removeClass('is-invalid').addClass('is-valid');
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
                        if (dataError.errorTanggalPembungaan) {
                            $('#errorTanggalPembungaan').html(dataError.errorTanggalPembungaan).show();
                            $('#tanggal_pembungaan').addClass('is-invalid');
                        } else {
                            $('#errorTanggalPembungaan').fadeOut();
                            $('#tanggal_pembungaan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorKeterangan) {
                            $('#errorKeterangan').html(dataError.errorKeterangan).show();
                            $('#keterangan').addClass('is-invalid');
                        } else {
                            $('#errorKeterangan').fadeOut();
                            $('#keterangan').removeClass('is-invalid').addClass('is-valid');
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
                            text: response.error,
                            icon: "error"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= $backUrl ?>';
                            }
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