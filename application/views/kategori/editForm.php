<section class="section">
    <div class="card">
        <?php
        function safe_base64_encode($string)
        {
            return strtr(base64_encode($string), '+/=', '-_.');
        }
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
                            <input type="text" class="form-control" id="nama" name="nama" placeholder="Nama jenis tabungan" value="<?= $kategori->nama ?>">
                            <span class="input-group-text"><i class="fa-regular fa-credit-card fa-fw"></i></span>
                        </div>
                        <div id="errorNama" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="bunga">Bunga</label>
                        <div class="input-group">
                            <input type="text" id="bunga" name="bunga" class="form-control" placeholder="Bunga untuk jenis tabungan" value="<?= $kategori->bunga ?>">
                            <span class="input-group-text"><i class="fa fa-percent fa-fw"></i></span>
                        </div>
                        <div id="errorBunga" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="biaya_registrasi">Biaya Registrasi</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="biaya_registrasi" name="biaya_registrasi" class="form-control text-end" value="<?= $kategori->biaya_registrasi ?>">
                        </div>
                        <div id="errorBiayaRegistrasi" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="simpanan_awal">Simpanan Awal</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="simpanan_awal" name="simpanan_awal" class="form-control text-end" value="<?= $kategori->simpanan_awal ?>">
                        </div>
                        <div id="errorSimpananAwal" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="pengendapan">Pengendapan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="pengendapan" name="pengendapan" class="form-control text-end" value="<?= $kategori->pengendapan ?>">
                        </div>
                        <div id="errorPengendapan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 180px;">
                        <label for="keterangan">Keterangan</label>
                        <div class="input-group">
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="5"><?= $kategori->keterangan ?></textarea>
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

        $('#tombol_simpan').click(function(e) {
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
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            html: response.success
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