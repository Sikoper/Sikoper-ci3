<section class="section">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"></div>
            <div class="col-md-6">
                <?= form_open('', ['id' => 'form_simpan']) ?>
                <input type="hidden" name="id" id="id" value="<?= $penarikan['id'] ?>">

                <div class="form-group mb-5" style="height: 80px;">
                    <label for="nomor_rekening">Nomor Rekening</label>
                    <div class="input-group">
                        <input type="text" name="nomor_rekening" value="<?= $penarikan['no_rekening'] ?>" id="nomor_rekening" class="form-control" readonly>
                    </div>
                </div>
                <div class="form-group mb-5" style="height: 80px;">
                    <label for="nama_nasabah">Nama Nasabah</label>
                    <div class="input-group">
                        <input type="text" name="nama_nasabah" value="<?= $penarikan['nama_nasabah'] ?>" id="nama_nasabah" class="form-control" readonly>
                    </div>
                </div>
                <div class="form-group mb-5" style="height: 80px;">
                    <label for="jenis_tabungan">Jenis Tabungan</label>
                    <div class="input-group">
                        <input type="text" name="jenis_tabungan" value="<?= $penarikan['jenis_tabungan'] ?>" id="jenis_tabungan" class="form-control" readonly>
                    </div>
                </div>

                <div class="form-group mb-3" style="height: 80px;">
                    <label for="total_penarikan">Jumlah Penarikan</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="total_penarikan" id="total_penarikan" class="form-control text-end" value="<?= $penarikan['total_penarikan'] ?>" autocomplete="off" required />
                    </div>
                    <div id="error_total_penarikan" class="invalid-feedback" style="display: none;"></div>
                </div>

                <div class="text-center mb-3">
                    <button type="submit" id="tombol_simpan" class="btn btn-success">Simpan Penarikan</button>
                    <button type="button" onclick="window.location='<?= base_url('penarikan') ?>'" class="btn btn-danger">Batal</button>
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
    $(document).ready(function() {
        $('#total_penarikan').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            // Update value numerik ke format asli
            let formattedValue = $('#total_penarikan').autoNumeric('get');
            $('#total_penarikan').val(formattedValue);

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('penarikan/updateData') ?>",
                data: data,
                dataType: "json",
                processData: false,
                contentType: false,
                cache: false,
                beforeSend: function() {
                    $('#tombol_simpan').prop('disabled', true)
                        .html('<i class="fa fa-spin fa-spinner"></i>');
                },
                complete: function() {
                    $('#tombol_simpan').prop('disabled', false)
                        .html('Simpan Penarikan');
                },
                success: function(response) {
                    if (response.error) {
                        if (response.error.total_penarikan) {
                            $('#error_total_penarikan').html(response.error.total_penarikan).show();
                            $('#total_penarikan').addClass('is-invalid');
                        } else {
                            $('#error_total_penarikan').hide();
                            $('#total_penarikan').removeClass('is-invalid').addClass('is-valid');
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
                                window.location = '<?= base_url('penarikan') ?>';
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