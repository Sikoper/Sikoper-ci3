<section class="section">
    <div class="card">
        <?php
        $backUrl = $this->input->get('code') == 1
            ? site_url('simpanan/add')
            : site_url('penarikan');
        ?>
        <div class="card-header">
            <button class="btn btn-warning" onclick="window.location='<?= $backUrl ?>'">
                <i class="fa fa-backward"></i> Kembali
            </button>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="tanggal_penarikan">Tanggal</label>
                        <div class="input-group">
                            <input type="date" name="tanggal_penarikan" id="tanggal_penarikan" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                        </div>
                        <div id="errorTanggalSimpanan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nasabah">Pilih Nasabah</label>
                        <div class="d-flex align-items-center">
                            <select id="nasabah" class="form-control select2" name="nasabah" style="width: auto; flex: 1;"></select>
                        </div>
                        <div id="errorNasabah" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="simpanan_id">Nomor Rekening</label>
                        <select class="form-control select2" name="simpanan_id" id="rekening">
                            <option value="">-- Pilih Rekening --</option>
                        </select>
                        <div id="errorSimpanan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label for="saldo">Saldo</label>
                        <input type="text" class="form-control" id="saldo" name="saldo" readonly>
                    </div>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="jumlah_penarikan">Jumlah Penarikan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="jumlah_penarikan" id="jumlah_penarikan" class="form-control text-end" autocomplete="off" />
                        </div>
                        <div id="errorJumlah" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <?php if ($level == 'Admin'): ?>
                        <div class="form-group mb-3" style="height: 80px;">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control" autocomplete="off">
                                <option value=""> -- Pilih Pegawai -- </option>
                                <?php foreach ($pegawai as $item): ?>
                                    <option value="<?= $item->id ?>"><?= $item->nama_lengkap ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="errorPegawai" class="invalid-feedback" style="display: none;"></div>
                            <div class="valid-feedback" style="display: none;"></div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pegawai_id" id="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
                    <?php endif; ?>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Tarik Uang</button>
                        <button type="button" onclick="window.location='<?= base_url('penarikan') ?>'" class="btn btn-danger">Batal</button>
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
        $('#jumlah_penarikan').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('penarikan/proses') ?>",
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
                        if (dataError.errorNasabah) {
                            $('#errorNasabah').html(dataError.errorNasabah).show();
                            $('#nasabah').addClass('is-invalid');
                        } else {
                            $('#errorNasabah').fadeOut();
                            $('#nasabah').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorSimpanan) {
                            $('#errorSimpanan').html(dataError.errorSimpanan).show();
                            $('#simpanan_id').addClass('is-invalid');
                        } else {
                            $('#errorSimpanan').fadeOut();
                            $('#simpanan_id').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJumlah) {
                            $('#errorJumlah').html(dataError.errorJumlah).show();
                            $('#jumlah_penarikan').addClass('is-invalid');
                        } else {
                            $('#errorJumlah').fadeOut();
                            $('#jumlah_penarikan').removeClass('is-invalid').addClass('is-valid');
                        }
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
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

        $('#nasabah').select2({
            placeholder: 'Cari nama nasabah...',
            ajax: {
                url: '<?= base_url("nasabah/cari_nasabah") ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });

        $('#nasabah').on('change', function() {
            var nasabahId = $(this).val();
            $('#rekening').html('<option value="">Loading...</option>');

            $.ajax({
                url: '<?= base_url("penarikan/get_rekening_by_nasabah") ?>',
                method: 'POST',
                data: {
                    nasabah_id: nasabahId
                },
                dataType: 'json',
                success: function(data) {
                    var html = '<option value="">-- Pilih Rekening --</option>';
                    data.forEach(function(item) {
                        html += `<option value="${item.id}">${item.text}</option>`;
                    });
                    $('#rekening').html(html);
                },
                error: function() {
                    alert('Gagal mengambil data rekening.');
                    $('#rekening').html('<option value="">-- Pilih Rekening --</option>');
                }
            });
        });

        $('#rekening').on('change', function() {
            const simpananId = $(this).val();
            if (simpananId) {
                $.ajax({
                    url: `<?= base_url('penarikan/get_saldo/') ?>${simpananId}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.saldo !== undefined) {
                            let formatted = new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR'
                            }).format(response.saldo);
                            $('#saldo').val(formatted);
                        } else {
                            $('#saldo').val('Tidak ditemukan');
                        }
                    },
                    error: function() {
                        $('#saldo').val('Gagal mengambil saldo');
                    }
                });
            } else {
                $('#saldo').val('');
            }
        });
    });
</script>