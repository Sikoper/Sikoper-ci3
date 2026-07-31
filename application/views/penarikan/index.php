<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="tanggal_penarikan">Tanggal Penarikan</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="tanggal_penarikan" name="tanggal_penarikan" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div id="errorTanggal" class="invalid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="comboRekening">Pilih No. Rekening</label>

                        <input type="hidden" name="tabungan" id="hidden_tabungan">

                        <input type="hidden" id="preselectComboValue" value="<?= $combo_value ?>">
                        <input type="hidden" id="preselectComboId" value="<?= $selected_tabungan ?>">
                        <select id="comboRekening" class="form-control select2" name="comboRekening" <?= $disabled ? 'disabled' : '' ?>>
                            <option value="">-- Pilih No. Rekening --</option>
                            <?php foreach ($nasabah as $n): ?>
                                <option value="<?= $n->id_tabungan ?>">
                                    <?= $n->no_rekening ?> - <?= $n->nama_lengkap ?> (<?= $n->jenis_tabungan ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="errorSimpanan" class="invalid-feedback" style="display: none;"></div>
                    </div>

                    <!-- Tempat info nasabah -->
                    <div id="infoNasabah" style="display:none; margin-bottom: 15px;">
                        <p><strong>Nama Nasabah:</strong> <span id="infoNama"></span></p>
                        <p><strong>Jenis Tabungan:</strong> <span id="infoJenisTabungan"></span></p>
                    </div>

                    <div class="form-group">
                        <label for="saldo">Saldo</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control" id="saldo" name="saldo" readonly>
                        </div>
                    </div>

                    <div class="form-group mb-3"> <label for="jumlah_penarikan">Jumlah Penarikan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="jumlah_penarikan" id="jumlah_penarikan" class="form-control text-end" autocomplete="off" />
                        </div>
                        <div id="errorJumlah" class="invalid-feedback" style="display: none;">
                        </div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label for="total_yang_ditarik_display">Total Akan Ditarik</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="total_yang_ditarik_display" class="form-control text-end" readonly
                                style="font-weight: bold; background-color: #e9ecef; opacity: 1;" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="perkiraan_sisa_saldo_display">Perkiraan Sisa Saldo Setelah Transaksi</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="perkiraan_sisa_saldo_display" class="form-control text-end" readonly style="font-weight: bold; background-color: #e9ecef; opacity: 1;" />
                        </div>
                    </div>

                    <?php if ($level == 'Admin'): ?>
                        <div class="form-group mb-3" style="height: 80px;">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control" autocomplete="off" style="width: 100%;">
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
<script src="<?= base_url('assets') ?>/vendors/autonumeric.js/autoNumeric.js"></script>
<script>
    $(document).ready(function() {
        $('#jumlah_penarikan').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        const autoNumericOpts = {
            aSep: '.',
            aDec: ',',
            mDec: '0',
            vMin: '-9999999999999',
            vMax: '9999999999999'
        };

        $('#saldo').autoNumeric('init', autoNumericOpts);
        $('#saldo').autoNumeric('set', 0);
        $('#saldo').prop('readonly', true);

        $('#perkiraan_sisa_saldo_display').autoNumeric('init', autoNumericOpts);
        $('#perkiraan_sisa_saldo_display').autoNumeric('set', 0);
        $('#perkiraan_sisa_saldo_display').prop('readonly', true);

        $('#total_yang_ditarik_display').autoNumeric('init', autoNumericOpts);
        $('#total_yang_ditarik_display').autoNumeric('set', 0);
        $('#total_yang_ditarik_display').prop('readonly', true);

        function updateTotalYangAkanDitarik() {
            const jumlahPenarikanStr = $('#jumlah_penarikan').autoNumeric('get');
            // AutoNumeric get already returns standard JS number string format
            const jumlahPenarikanVal = parseFloat(jumlahPenarikanStr) || 0;

            $('#total_yang_ditarik_display').autoNumeric('set', jumlahPenarikanVal);

            const saldoSaatIniVal = parseFloat($('#saldo').autoNumeric('get')) || 0;
            const sisaSaldo = saldoSaatIniVal - jumlahPenarikanVal;
            $('#perkiraan_sisa_saldo_display').autoNumeric('set', sisaSaldo);

            if (sisaSaldo < 0) {
                $('#perkiraan_sisa_saldo_display').css('color', 'red');
            } else {
                $('#perkiraan_sisa_saldo_display').css('color', '');
            }
        }

        $('#jumlah_penarikan').on('input keyup change', function() {
            updateTotalYangAkanDitarik();
        });

        $('#comboRekening').select2({
            placeholder: '-- Cari rekening/nasabah --',
            ajax: {
                url: '<?= base_url("penarikan/get_combo_rekening_nasabah") ?>',
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

        $('#pegawai_id').select2({
            placeholder: '-- Pilih Pegawai --',
            allowClear: true,
            width: '100%'
        });

        $('#comboRekening').on('change', function() {
            $('#jumlah_penarikan').autoNumeric('set', '');
            $('#infoNasabah').hide();
            $('#saldo').autoNumeric('set', 0);
            $('#total_yang_ditarik_display').autoNumeric('set', 0);
            $('#perkiraan_sisa_saldo_display').autoNumeric('set', 0);

            <?php if ($level == 'Admin'): ?>
                $('#pegawai_id').val('').trigger('change');
            <?php endif; ?>

            const idTabungan = $(this).val();
            $('#hidden_tabungan').val(idTabungan);

            if (idTabungan) {
                $.ajax({
                    url: '<?= base_url('penarikan/fetchRekening') ?>',
                    method: 'POST',
                    data: {
                        id: idTabungan
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && !response.error) {
                            $('#infoNasabah').show();
                            $('#infoNama').text(response.nama_nasabah || '-');
                            $('#infoJenisTabungan').text(response.jenis_tabungan || '-');

                            $('#saldo').autoNumeric('set', response.saldo || 0);

                            updateTotalYangAkanDitarik();
                        }
                    },
                    error: function(xhr, thrownError) {
                        $('#saldo').autoNumeric('set', 0);
                        updateTotalYangAkanDitarik();
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                    }
                });
            }
        });

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            const noRekNamaText = $('#comboRekening option:selected').text() || 'Belum dipilih';
            const saldoSaatIniNum = parseFloat($('#saldo').autoNumeric('get')) || 0;
            const jumlahPenarikanNum = parseFloat($('#jumlah_penarikan').autoNumeric('get')) || 0;
            const perkiraanSisaSaldoNum = saldoSaatIniNum - jumlahPenarikanNum;
            const formatRp = (num) => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);

            Swal.fire({
                title: 'Konfirmasi Penarikan',
                width: '600px',
                html: `Mohon periksa kembali detail transaksi berikut:<br><br>` +
                    `<table style="width:100%; text-align: left; border-collapse: collapse; margin-bottom: 15px; line-height: 1.6;">` +
                    `<tr><td style="padding: 4px 10px 4px 0; font-weight: bold;">Rekening & Nasabah:</td><td>${noRekNamaText}</td></tr>` +
                    `<tr><td style="padding: 4px 10px 4px 0; font-weight: bold;">Saldo Saat Ini:</td><td>${formatRp(saldoSaatIniNum)}</td></tr>` +
                    `<tr><td style="padding: 4px 10px 4px 0; font-weight: bold;">Jumlah Penarikan:</td><td>${formatRp(jumlahPenarikanNum)}</td></tr>` +
                    `<tr><td style="padding: 4px 10px 4px 0; font-weight: bold;">Total Akan Didebet:</td><td>${formatRp(jumlahPenarikanNum)}</td></tr>` +
                    `<tr><td style="padding: 4px 10px 4px 0; font-weight: bold;">Perkiraan Sisa Saldo:</td><td>${formatRp(perkiraanSisaSaldoNum)}</td></tr>` +
                    `</table>Apakah Anda yakin ingin melanjutkan?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Lanjutkan Penarikan!',
                cancelButtonText: 'Batal',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false
            }).then((result) => {
                if (result.isConfirmed) {
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
                            $('#tombol_simpan').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i>');
                        },
                        complete: function() {
                            $('#tombol_simpan').prop('disabled', false).html('Tarik Uang');
                        },
                        success: function(response) {
                            if (response.error) {
                                let dataError = response.error;
                                if (dataError.errorSimpanan) {
                                    $('#errorSimpanan').html(dataError.errorSimpanan).show();
                                    $('#comboRekening').addClass('is-invalid');
                                } else {
                                    $('#errorSimpanan').fadeOut();
                                    $('#comboRekening').removeClass('is-invalid').addClass('is-valid');
                                }

                                if (dataError.errorJumlah) {
                                    $('#errorJumlah').html(dataError.errorJumlah).show();
                                    $('#jumlah_penarikan').addClass('is-invalid');
                                } else {
                                    $('#errorJumlah').fadeOut();
                                    $('#jumlah_penarikan').removeClass('is-invalid').addClass('is-valid');
                                }

                                if (dataError.errorPegawai) {
                                    $('#errorPegawai').html(dataError.errorPegawai).show();
                                    $('#pegawai_id').addClass('is-invalid');
                                } else {
                                    $('#errorPegawai').fadeOut();
                                    $('#pegawai_id').removeClass('is-invalid').addClass('is-valid');
                                }
                            } else if (response.error_save) {
                                Swal.fire("Gagal!", response.error_save, "error");
                            } else if (response.success) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Berhasil!",
                                    html: response.success + "<br><br><strong>Cetak kwitansi penarikan?</strong>",
                                    showCancelButton: true,
                                    confirmButtonColor: "#28a745",
                                    confirmButtonText: "Ya, Cetak Kwitansi",
                                    cancelButtonText: "Tidak, Nanti Saja",
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    allowEnterKey: false
                                }).then((result) => {
                                    if (result.isConfirmed && response.penarikan_id) {
                                        window.open("<?= base_url('penarikan/print_kwitansi/') ?>" + response.penarikan_id, "_blank");
                                    }
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire("Error!", "Terjadi kesalahan yang tidak diketahui saat memproses.", "error");
                            }
                        },
                        error: function(xhr, status, error) {
                            if (xhr.status === 422) {
                                let response = xhr.responseJSON;
                                if (response.error) {
                                    let dataError = response.error;
                                }
                            } else {
                                Swal.fire("Error!", "Terjadi kesalahan pada server.", "error");
                            }
                        }
                    });
                }
            });
        });

        // Preselect jika ada
        const preselectText = $('#preselectComboValue').val();
        const preselectId = $('#preselectComboId').val();
        if (preselectId && preselectText) {
            const newOption = new Option(preselectText, preselectId, true, true);
            $('#comboRekening').append(newOption).trigger('change');
        }
    });
</script>