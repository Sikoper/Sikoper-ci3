<section class="section">
    <div class="card">
        <div class="card-header">
            <a href="<?= site_url('jenis_tabungan') ?>" class="btn btn-warning">
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
                            <input type="text" id="biaya_registrasi" name="biaya_registrasi" class="form-control text-end">
                        </div>
                        <div id="errorBiayaRegistrasi" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="simpanan_awal">Simpanan Awal</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="simpanan_awal" name="simpanan_awal" class="form-control text-end">
                        </div>
                        <div id="errorSimpananAwal" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="pengendapan">Pengendapan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="pengendapan" name="pengendapan" class="form-control text-end">
                        </div>
                        <div id="errorPengendapan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jenis_denda">Jenis Denda</label>
                        <div class="input-group">
                            <select id="jenis_denda" name="jenis_denda" class="form-control">
                                <option value=""> -- Pilih jenis denda -- </option>
                                <option value="Rp">Denda dalam bentuk Rp</option>
                                <option value="%">Denda dalam bentuk %</option>
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
                            <input type="text" id="denda_persen" name="denda_persen" class="form-control text-end" disabled>
                            <span class="input-group-text">%</span>
                        </div>
                        <div id="errorDendaPersen" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 180px;">
                        <label for="keterangan">Keterangan</label>
                        <div class="input-group">
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="5"></textarea>
                        </div>
                        <div id="errorKeterangan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="text-center mt-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= base_url('jenis_tabungan') ?>'" class="btn btn-danger">Batal</button>
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
        $('#denda_idr').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0',
        });
        $('#denda_persen').autoNumeric('init', {
            aSep: ',',
            aDec: '.',
            mDec: '2',
            vMin: '0',
            vMax: '100',
        });

        $('#jenis_denda').on('change', function() {
            let value = $(this).val();
            if (value == 'Rp') {
                $('#denda_idr').attr('disabled', false);
                $('#denda_persen').attr('disabled', true);
                $('#denda_persen').val('');
            } else if (value == '%') {
                $('#denda_idr').attr('disabled', true);
                $('#denda_idr').val('');
                $('#denda_persen').attr('disabled', false);
            } else {
                $('#denda_idr').attr('disabled', true);
                $('#denda_persen').attr('disabled', true);
            }
        })

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('kategori/simpanData') ?>",
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
                            html: response.success
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= base_url('jenis_tabungan') ?>';
                            }
                        });
                    } else {
                        Swal.fire({
                            title: "Error!",
                            text: response.error,
                            icon: "error"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= base_url('jenis_tabungan') ?>';
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