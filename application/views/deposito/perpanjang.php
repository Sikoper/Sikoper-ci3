<section class="section">
    <div class="card">
        <div class="card-header">
            <a href="<?= site_url('deposito') ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_perpanjang']) ?>
                    <input type="hidden" name="id" id="id" value="<?= $deposito->id ?>">

                    <div class="form-group mb-3">
                        <label for="tanggal_deposito">Tanggal Perpanjangan</label>
                        <input type="date" name="tanggal_deposito" id="tanggal_deposito" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="nasabah_display">Nasabah</label>
                        <input type="text" class="form-control" value="<?= $nasabah->nama_lengkap ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="nomor_rekening">Nomor Rekening</label>
                        <input type="text" value="<?= $deposito->no_rekening ?>" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="jumlah_deposito">Jumlah Deposito</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="jumlah_deposito" class="form-control text-end" value="<?= $deposito->jumlah_deposito ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="bunga">Suku Bunga Terbaru</label>
                        <div class="input-group">
                            <input type="text" name="bunga" id="bunga" class="form-control text-end" value="<?= $jenis->bunga ?? '0' ?>" readonly>
                            <div class="input-group-prepend">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div id="errorBunga" class="invalid-feedback" style="display: none;"></div>
                    </div>

                    <hr>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="durasi"><strong>Pilih Jangka Waktu Perpanjangan</strong></label>
                        <select id="durasi" name="durasi" class="form-control" autocomplete="off">
                            <option value=""> -- Pilih Jangka Waktu Baru -- </option>
                            <option value="6">6 Bulan / &frac12; Tahun</option>
                            <option value="12">12 Bulan / 1 Tahun</option>
                            <option value="18">18 Bulan / 1.5 Tahun</option>
                            <option value="24">24 Bulan / 2 Tahun</option>
                            <option value="30">30 Bulan / 2.5 Tahun</option>
                            <option value="36">36 Bulan / 3 Tahun</option>
                        </select>
                        <div id="errorDurasi" class="invalid-feedback" style="display: none;"></div>
                    </div>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_perpanjang" class="btn btn-success">Perpanjang Sekarang</button>
                        <a href="<?= site_url('deposito') ?>" class="btn btn-secondary">Batal</a>
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
        $('#jumlah_deposito').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        $('#bunga').autoNumeric('init', {
            aSep: ',',
            aDec: '.',
            mDec: '2',
            vMin: '0',
            vMax: '100'
        });

        $('#tombol_perpanjang').click(function(e) {
            e.preventDefault();
            let form = $('#form_perpanjang')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('deposito/proses_perpanjang') ?>",
                data: data,
                dataType: "json",
                processData: false,
                contentType: false,
                cache: false,
                beforeSend: function() {
                    $('#tombol_perpanjang').prop('disabled', true);
                    $('#tombol_perpanjang').html('<i class="fa fa-spin fa-spinner"></i> Processing...');
                },
                complete: function() {
                    $('#tombol_perpanjang').prop('disabled', false);
                    $('#tombol_perpanjang').html('Perpanjang Sekarang');
                },
                success: function(response) {
                    if (response.error) {
                        if (response.error.errorDurasi) {
                            $('#errorDurasi').html(response.error.errorDurasi).show();
                            $('#durasi').addClass('is-invalid');
                        } else {
                            $('#errorDurasi').fadeOut();
                            $('#durasi').removeClass('is-invalid');
                        }
                        if (response.error.errorBunga) {
                            $('#errorBunga').html(response.error.errorBunga).show();
                            $('#bunga').addClass('is-invalid');
                        } else {
                            $('#errorBunga').fadeOut();
                            $('#bunga').removeClass('is-invalid');
                        }
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            allowOutsideClick: false,
                            html: response.success
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= base_url('deposito') ?>';
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