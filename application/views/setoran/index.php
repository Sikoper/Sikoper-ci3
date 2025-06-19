<section class="section">
    <div class="card">
        <div class="card-header">

        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <div class="form-group" style="height: 80px;">
                        <label for="tanggal_setoran">Tanggal</label>
                        <div class="input-group">
                            <input type="date" value="<?= date('Y-m-d') ?>" id="tanggal_setoran" class="form-control select2" name="tanggal_setoran" readonly>
                            <div id="errorTanggalSetoran" class="invalid-feedback" style="display: none;"></div>
                            <div class="valid-feedback" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nasabah">Pilih Nasabah</label>
                        <div class="input-group">
                            <select id="nasabah" class="form-control select2" name="nasabah" style="width: auto; flex: 1;"></select>
                            <div id="errorNasabah" class="invalid-feedback" style="display: none;"></div>
                            <div class="valid-feedback" style="display: none;"></div>
                        </div>
                    </div>

                    <div id="detail_nasabah_info" class="alert alert-light mt-2" style="display:none;"></div>

                    <div class="form-group mb-3">
                        <label for="tabungan">Pilih Tabungan</label>
                        <div class="input-group">
                            <select id="tabungan" class="form-control select2" name="tabungan" style="width: auto; flex: 1;">
                                <option value=""> --- Pilih tabungan --- </option>
                            </select>
                        </div>
                        <div id="errorTabungan" class="invalid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="sisa_saldo">Saldo Saat Ini</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="text" id="sisa_saldo" class="form-control" placeholder="Pilih rekening untuk melihat saldo..." readonly>
                        </div>
                    </div>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="jumlah_setoran">Jumlah Setoran</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="jumlah_setoran" id="jumlah_setoran" class="form-control text-end">
                        </div>
                        <div id="errorJumlahSetoran" class="invalid-feedback" style="display: none;"></div>
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
                        <button type="submit" id="tombol_simpan" class="btn btn-success" disabled>Simpan</button>
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
        // Inisialisasi plugin
        $('#jumlah_setoran').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
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

        function cekKesiapanForm() {
            const nasabah = $('#nasabah').val();
            const tabungan = $('#tabungan').val();
            const jumlahSetoran = $('#jumlah_setoran').autoNumeric('get');

            if (nasabah && tabungan && parseFloat(jumlahSetoran) > 0) {
                $('#tombol_simpan').prop('disabled', false);
            } else {
                $('#tombol_simpan').prop('disabled', true);
            }
        }

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            const namaNasabah = $('#nasabah option:selected').text();
            const noRekening = $('#tabungan option:selected').text();
            const jumlahSetoran = $('#jumlah_setoran').val();

            Swal.fire({
                title: 'Konfirmasi Setoran',
                html: `Anda akan melakukan setoran dengan rincian:<br><br>
                        <div style="text-align: left; margin-left: 20px;">
                        <strong>Nasabah :</strong> ${namaNasabah}<br>
                        <strong>Rekening:</strong> ${noRekening}<br>
                        <strong>Jumlah :</strong> Rp ${jumlahSetoran}
                        </div><br>
                        Apakah data sudah benar?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Lanjutkan & Simpan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = $('#form_simpan')[0];
                    let data = new FormData(form);
                    $.ajax({
                        type: "POST",
                        url: "<?= base_url('setoran/simpanData') ?>",
                        data: data,
                        dataType: "json",
                        processData: false,
                        contentType: false,
                        cache: false,
                        beforeSend: function() {
                            $('#tombol_simpan').prop('disabled', true);
                            $('#tombol_simpan').html('<i class="fa fa-spin fa-spinner"></i>');
                        },
                        complete: function() {
                            $('#tombol_simpan').html('Simpan');
                        },
                        success: function(response) {
                            if (response.error) {
                                let dataError = response.error;
                                if (dataError.errorTanggalSetoran) {
                                    $('#errorTanggalSetoran').html(dataError.errorTanggalSetoran).show();
                                    $('#tanggal_setoran').addClass('is-invalid');
                                } else {
                                    $('#errorTanggalSetoran').fadeOut();
                                    $('#tanggal_setoran').removeClass('is-invalid').addClass('is-valid');
                                }
                                if (dataError.errorNasabah) {
                                    $('#errorNasabah').html(dataError.errorNasabah).show();
                                    $('#nasabah').addClass('is-invalid');
                                } else {
                                    $('#errorNasabah').fadeOut();
                                    $('#nasabah').removeClass('is-invalid').addClass('is-valid');
                                }
                                if (dataError.errorTabungan) {
                                    $('#errorTabungan').html(dataError.errorTabungan).show();
                                    $('#tabungan').addClass('is-invalid');
                                } else {
                                    $('#errorTabungan').fadeOut();
                                    $('#tabungan').removeClass('is-invalid').addClass('is-valid');
                                }
                                if (dataError.errorJumlahSetoran) {
                                    $('#errorJumlahSetoran').html(dataError.errorJumlahSetoran).show();
                                    $('#jumlah_setoran').addClass('is-invalid');
                                } else {
                                    $('#errorJumlahSetoran').fadeOut();
                                    $('#jumlah_setoran').removeClass('is-invalid').addClass('is-valid');
                                }
                            } else {
                                Swal.fire({
                                    icon: "success",
                                    title: "Berhasil!",
                                    html: response.success
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = response.redirect;
                                    }
                                });
                            }
                        },
                        error: function(xhr, thrownError) {
                            alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                        }
                    });
                }
            });
        });

        $('#nasabah').on('change', function() {
            let nasabahId = $(this).val();
            const selectTabungan = $('#tabungan');
            const inputSaldo = $('#sisa_saldo');
            const inputJumlahSetoran = $('#jumlah_setoran');
            const detailInfo = $('#detail_nasabah_info');

            detailInfo.hide().html('');
            selectTabungan.html('<option value="">--- Pilih tabungan ---</option>').trigger('change.select2');
            inputSaldo.val('');
            inputJumlahSetoran.autoNumeric('set', '');

            if (nasabahId) {
                selectTabungan.prop('disabled', false);
                selectTabungan.html('<option value="">--- Memuat rekening... ---</option>');
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('setoran/get_no_rekening') ?>",
                    data: {
                        nasabah: nasabahId
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.data) {
                            selectTabungan.html(response.data);
                            if (response.detail_nasabah) {
                                let detail = response.detail_nasabah;
                                detailInfo.html(`<strong>NIK:</strong> ${detail.nik || '-'}<br><strong>Alamat:</strong> ${detail.alamat || '-'}`).show();
                            }
                        }
                    },
                    error: function(xhr, thrownError) {
                    }
                });
            } else {
                selectTabungan.prop('disabled', true);
            }
            cekKesiapanForm();
        });

        $('#tabungan').on('change', function() {
            let idRekening = $(this).val();
            let inputSaldo = $('#sisa_saldo');
            inputSaldo.val('');

            if (idRekening) {
                inputSaldo.val('Memuat...');
                $.ajax({
                    url: '<?= base_url("setoran/get-saldo") ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id_rekening: idRekening
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            let saldoFormatted = new Intl.NumberFormat('id-ID').format(response.saldo);
                            inputSaldo.val(saldoFormatted);
                        } else {
                            inputSaldo.val(response.message);
                        }
                    },
                    error: function() {
                        inputSaldo.val('Gagal memuat saldo.');
                    }
                });
            }
            cekKesiapanForm();
        });

        $('#jumlah_setoran').on('keyup change', function() {
            cekKesiapanForm();
        });
        cekKesiapanForm();
    });
</script>