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
                            <input type="text" class="form-control" value="<?= date('d/m/Y') ?>" readonly>
                            <input type="hidden" name="tanggal_penarikan" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div id="errorTanggal" class="invalid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nasabah">Pilih Nasabah</label>
                        <div class="d-flex align-items-center">
                            <select id="nasabah" class="form-control select2" name="nasabah" <?= $disabled ? 'disabled' : '' ?> style="width: auto; flex: 1;">
                                <option value="">-- Pilih Nasabah --</option>
                                <?php foreach ($nasabah as $n): ?>
                                    <option value="<?= $n->id ?>" <?= ($selected_nasabah == $n->id) ? 'selected' : '' ?>>
                                        <?= $n->nama_lengkap ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="errorNasabah" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="rekening">Nomor Rekening</label>
                        <select class="form-control select2" name="simpanan_id" id="rekening" <?= $disabled ? 'disabled' : '' ?>>
                            <?php if (!empty($tabungan)): ?>
                                <option value="<?= $tabungan->id ?>" selected><?= $tabungan->no_rekening ?></option>
                            <?php else: ?>
                                <option value="">-- Pilih Rekening --</option>
                            <?php endif; ?>
                        </select>
                        <div id="errorSimpanan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <?php if (!empty($tabungan)): ?>
                        <input type="hidden" id="preselectedNasabah" value="<?= $tabungan->nasabah_id ?>">
                        <input type="hidden" id="preselectedRekening" value="<?= $tabungan->id ?>">
                    <?php endif; ?>

                    <?php if (!empty($tabungan)): ?>
                        <input type="hidden" name="nasabah" value="<?= $tabungan->nasabah_id ?>">
                        <input type="hidden" name="simpanan_id" value="<?= $tabungan->id ?>">
                    <?php endif; ?>

                    <div id="jenis_tabungan" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="durasi">Durasi</label>
                                    <input type="text" class="form-control" id="durasi" name="durasi" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tenor">Tenor</label>
                                    <input type="text" class="form-control" id="tenor" name="tenor" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jenis_denda">Jenis denda</label>
                                    <input type="text" class="form-control" id="jenis_denda" name="jenis_denda" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jumlah_denda">Denda</label>
                                    <input type="text" class="form-control" id="jumlah_denda" name="jumlah_denda" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="denda">Jumlah Denda</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="denda" name="denda" readonly>
                            </div>
                        </div>
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
                        <label for="total_yang_ditarik_display">Total Pengurangan Saldo</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="total_yang_ditarik_display" class="form-control text-end" readonly style="font-weight: bold; background-color: #e9ecef; opacity: 1;" />
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
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0"></script>
<script>
    $(document).ready(function() {
        $('#jumlah_penarikan').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        const autoNumericV4OptionsRp = {
            currencySymbol: '',
            decimalCharacter: ',',
            digitGroupSeparator: '.',
            decimalPlaces: 0,
            readOnly: true
        };

        const saldoAN = new AutoNumeric('#saldo', autoNumericV4OptionsRp);
        saldoAN.set(0);

        const perkiraanSisaSaldoAN = new AutoNumeric('#perkiraan_sisa_saldo_display', autoNumericV4OptionsRp);
        perkiraanSisaSaldoAN.set(0);

        const dendaRpAN = new AutoNumeric('#denda', autoNumericV4OptionsRp);
        dendaRpAN.set(0);

        const totalDitarikAN = new AutoNumeric('#total_yang_ditarik_display', autoNumericV4OptionsRp);
        totalDitarikAN.set(0);

        function updateTotalYangAkanDitarik() {
    let jumlahPenarikanVal = 0;
    const jumlahPenarikanStr = $('#jumlah_penarikan').autoNumeric('get');
    if (jumlahPenarikanStr && typeof jumlahPenarikanStr === 'string') {
        jumlahPenarikanVal = parseFloat(jumlahPenarikanStr.replace(/\./g, '').replace(',', '.')) || 0;
    }

    const jumlahDendaVal = dendaRpAN.getNumber() || 0;
    const totalDebetDariSaldo = jumlahPenarikanVal + jumlahDendaVal;
    totalDitarikAN.set(totalDebetDariSaldo);
    const saldoSaatIniVal = saldoAN.getNumber() || 0;
    const sisaSaldo = saldoSaatIniVal - totalDebetDariSaldo;
    perkiraanSisaSaldoAN.set(sisaSaldo);

    if (sisaSaldo < 0) {
        $('#perkiraan_sisa_saldo_display').css('color', 'red');
    } else {
        $('#perkiraan_sisa_saldo_display').css('color', '');
    }
}

        $('#jumlah_penarikan').on('input keyup change', function() {
            updateTotalYangAkanDitarik();
        });

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();
            const namaNasabahText = $('#nasabah option:selected').text().trim() || 'Nasabah belum dipilih';
            const noRekeningText = $('#rekening option:selected').text().trim() || 'Rekening belum dipilih';
            const saldoSaatIniNum = saldoAN.getNumber() || 0;
            const jumlahPenarikanNum = parseFloat($('#jumlah_penarikan').autoNumeric('get').replace(/\./g, '').replace(',', '.')) || 0;
            const jumlahDendaNum = dendaRpAN.getNumber() || 0;
            const totalAkanDidebet = jumlahPenarikanNum + jumlahDendaNum;
            const perkiraanSisaSaldoNum = saldoSaatIniNum - totalAkanDidebet;
            const formatRp = (num) => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);

            Swal.fire({
                title: 'Konfirmasi Penarikan',
                width: '600px',
                html: `Mohon periksa kembali detail transaksi berikut:<br><br>` +
                    `<table style="width:100%; text-align: left; border-collapse: collapse; margin-bottom: 15px; line-height: 1.6;">` +
                    `  <tr>` +
                    `    <td style="padding: 4px 10px 4px 0; font-weight: bold; vertical-align: top; width: 180px;">Nasabah:</td>` +
                    `    <td style="padding: 4px 0 4px 5px; vertical-align: top;">${namaNasabahText}</td>` +
                    `  </tr>` +
                    `  <tr>` +
                    `    <td style="padding: 4px 10px 4px 0; font-weight: bold; vertical-align: top; width: 220px;">No. Rekening:</td>` +
                    `    <td style="padding: 4px 0 4px 5px; vertical-align: top;">${noRekeningText}</td>` +
                    `  </tr>` +
                    `  <tr>` +
                    `    <td style="padding: 4px 10px 4px 0; font-weight: bold; vertical-align: top; width: 220px;">Saldo Saat Ini:</td>` +
                    `    <td style="padding: 4px 0; vertical-align: top;">${formatRp(saldoSaatIniNum)}</td>` +
                    `  </tr>` +
                    `  <tr>` +
                    `    <td style="padding: 4px 10px 4px 0; font-weight: bold; vertical-align: top; width: 220px;">Jumlah Penarikan:</td>` +
                    `    <td style="padding: 4px 0; vertical-align: top;">${formatRp(jumlahPenarikanNum)}</td>` +
                    `  </tr>` +
                    `  <tr>` +
                    `    <td style="padding: 4px 10px 4px 0; font-weight: bold; vertical-align: top; width: 220px;">Denda:</td>` +
                    `    <td style="padding: 4px 0; vertical-align: top;">${formatRp(jumlahDendaNum)}</td>` +
                    `  </tr>` +
                    `  <tr style="border-top: 1px solid #ddd;">` +
                    `    <td style="padding: 8px 10px 4px 0; font-weight: bold; vertical-align: top; width: 220px;">Total Akan Didebet:</td>` +
                    `    <td style="padding: 8px 0 4px 0; vertical-align: top; font-weight: bold;">${formatRp(totalAkanDidebet)}</td>` +
                    `  </tr>` +
                    `  <tr>` +
                    `    <td style="padding: 4px 10px 4px 0; font-weight: bold; vertical-align: top; width: 220px;">Perkiraan Sisa Saldo:</td>` +
                    `    <td style="padding: 4px 0; vertical-align: top; font-weight: bold;">${formatRp(perkiraanSisaSaldoNum)}</td>` +
                    `  </tr>` +
                    `</table>` +
                    `Apakah Anda yakin ingin melanjutkan?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Lanjutkan Penarikan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = $('#form_simpan')[0];
                    let data = new FormData(form);

                    $.ajax({
                        type: "POST",
                        url: "<?= base_url('pencairan/proses') ?>",
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
                            $('#tombol_simpan').html('Tarik Uang')
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
                                    $('#rekening').addClass('is-invalid');
                                } else {
                                    $('#errorSimpanan').fadeOut();
                                    $('#rekening').removeClass('is-invalid').addClass('is-valid');
                                }
                                if (dataError.errorJumlah) {
                                    $('#errorJumlah').html(dataError.errorJumlah).show();
                                    $('#jumlah_penarikan').addClass('is-invalid');
                                } else {
                                    $('#errorJumlah').fadeOut();
                                    $('#jumlah_penarikan').removeClass('is-invalid').addClass('is-valid');
                                }
                                if (dataError.errorTanggal) {
                                    $('#errorTanggal').html(dataError.errorTanggal).show();
                                    $('#tanggal_penarikan').addClass('is-invalid');
                                } else {
                                    $('#errorTanggal').fadeOut();
                                    $('#tanggal_penarikan').removeClass('is-invalid').addClass('is-valid');
                                }
                                if (dataError.errorPegawai) {
                                    $('#errorPegawai').html(dataError.errorPegawai).show();
                                    $('#pegawai_id').addClass('is-invalid');
                                } else {
                                    if ($('#pegawai_id').is('select')) {
                                        $('#errorPegawai').fadeOut();
                                        $('#pegawai_id').removeClass('is-invalid').addClass('is-valid');
                                    }
                                }
                            } else if (response.error_save) {
                                Swal.fire("Gagal!", response.error_save, "error");
                            } else if (response.success) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Berhasil!",
                                    html: response.success
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        if (response.redirect) {
                                            window.location.href = response.redirect;
                                        } else {
                                            window.location.href = '<?= base_url('penarikan') ?>';
                                        }
                                    }
                                });
                            } else {
                                Swal.fire("Error!", "Terjadi kesalahan yang tidak diketahui saat memproses.", "error");
                            }
                        },
                        error: function(xhr, thrownError) {
                            alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                        }
                    });
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

            $('#rekening').html('<option value="">-- Pilih Rekening --</option>').val("").trigger('change');
            $('#jumlah_penarikan').autoNumeric('set', '');
            totalDitarikAN.set(0);

            $('.invalid-feedback').each(function() {
                $(this).html('').hide();
            });
            $('form .is-invalid').removeClass('is-invalid');
            $('form .is-valid').removeClass('is-valid');

            if (nasabahId) {
                $('#rekening').html('<option value="">Loading...</option>');
                $.ajax({
                    url: '<?= base_url("pencairan/get_rekening_by_nasabah") ?>',
                    method: 'POST',
                    data: {
                        nasabah_id: nasabahId
                    },
                    dataType: 'json',
                    success: function(data) {
                        var html = '<option value="">-- Pilih Rekening --</option>';
                        if (data && data.length > 0) {
                            data.forEach(function(item) {
                                html += `<option value="${item.id}">${item.text}</option>`;
                            });
                        }

                        $('#rekening').html(html);

                        const preselected = $('#preselectedRekening').val();
                        if (preselected) {
                            $('#rekening').val(preselected).trigger('change');
                            $('#rekening').prop('disabled', true);
                        }
                    },
                    error: function() {
                        alert('Gagal mengambil data rekening.');
                        $('#rekening').html('<option value="">-- Pilih Rekening --</option>').val("").trigger('change');
                    }
                });
            }
        });

        $('#rekening').select2({
            placeholder: '-- Pilih Rekening --'
        });

        const preselectedNasabah = $('#preselectedNasabah').val();

        if (preselectedNasabah) {
            $('#nasabah').trigger('change');
        }

        $('#rekening').on('change', function() {
            const id = $(this).val();

            if (id) {
                $.ajax({
                    url: '<?= base_url('pencairan/fetchRekening') ?>',
                    method: 'POST',
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            alert(response.error);
                            saldoAN.set(0);
                            $('#tenor').val('');
                            $('#durasi').val('');
                            $('#jumlah_denda').val('');
                            $('#jenis_denda').val('');
                            dendaRpAN.set(0);
                            $('#jenis_tabungan').hide();
                            $('#jumlah_penarikan').autoNumeric('set', '');
                            updateTotalYangAkanDitarik();
                            return;
                        }

                        saldoAN.set(response.saldo);
                        $('#tenor').val(response.tenor);
                        $('#durasi').val(response.durasi + ' Bulan');
                        $('#jumlah_denda').val(response.jumlah_denda);
                        $('#jenis_denda').val(response.jenis_denda);
                        dendaRpAN.set(response.calculated_penalty_rp);

                        if (response.kategori && response.kategori.nama === 'Deposito') {
                            $('#jenis_tabungan').show();

                            if (response.calculated_penalty_rp > 0) {
                                Swal.fire({
                                    title: 'Informasi Denda Deposito',
                                    html: `Peringatan: Penarikan untuk rekening Deposito ini sebelum tanggal jatuh tempo (<b>${response.tenor}</b>) akan dikenakan denda.<br><br>` +
                                        `Perkiraan denda jika ditarik hari ini: <b>Rp ${new Intl.NumberFormat('id-ID').format(response.calculated_penalty_rp)}</b>.<br><br>` +
                                        `Pastikan nasabah telah memahami ketentuan ini sebelum melanjutkan proses penarikan.`,
                                    icon: 'warning',
                                    confirmButtonText: 'Saya Mengerti'
                                });
                            }

                        } else {
                            $('#jenis_tabungan').hide();
                        }

                        updateTotalYangAkanDitarik();
                    },
                    error: function(xhr, thrownError) {
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                        saldoAN.set(0);
                        $('#tenor').val('');
                        $('#durasi').val('');
                        $('#jumlah_denda').val('');
                        $('#jenis_denda').val('');
                        dendaRpAN.set(0);
                        $('#jenis_tabungan').hide();
                        $('#jumlah_penarikan').autoNumeric('set', '');
                        updateTotalYangAkanDitarik();
                    }
                });
            } else {
                saldoAN.set(0);
                $('#tenor').val('');
                $('#durasi').val('');
                $('#jumlah_denda').val('');
                $('#jenis_denda').val('');
                dendaRpAN.set(0);
                $('#jenis_tabungan').hide();
                $('#jumlah_penarikan').autoNumeric('set', '');
                updateTotalYangAkanDitarik();
            }
        });
    });
</script>