<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <div class="form-group mb-3">
                        <label for="tanggal_penarikan">Tanggal</label>
                        <input type="text" value="<?= date('d-m-Y') ?>" class="form-control" style="background-color: #e9ecef;" readonly>
                        <input type="hidden" name="tanggal_penarikan" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label for="nasabah">Pilih Nasabah</label>
                        <select id="nasabah" class="form-control select2" name="nasabah" <?= isset($disabled) && $disabled ? 'disabled' : '' ?> style="width: 100%;">
                            <?php if (isset($selected_nasabah) && isset($nasabah_info)): ?>
                               <option value="<?= $selected_nasabah ?>" selected><?= $nasabah_info->nama_lengkap ?></option>
                            <?php else: ?>
                               <option value="">-- Pilih Nasabah --</option>
                            <?php endif; ?>
                        </select>
                        <small class="form-text text-muted">Mulai ketik untuk mencari nama nasabah.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="rekening">Nomor Rekening</label>
                        <select class="form-control select2" name="simpanan_id" id="rekening" <?= isset($disabled) && $disabled ? 'disabled' : '' ?>>
                             <?php if (isset($tabungan) && !empty($tabungan)): ?>
                                <option value="<?= $tabungan->id ?>" selected><?= $tabungan->no_rekening ?></option>
                            <?php else: ?>
                                <option value="">-- Pilih Rekening --</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <?php if (isset($tabungan) && !empty($tabungan)): ?>
                        <input type="hidden" id="preselectedNasabah" value="<?= $tabungan->nasabah_id ?>">
                        <input type="hidden" id="preselectedRekening" value="<?= $tabungan->id ?>">
                        <input type="hidden" name="nasabah" value="<?= $tabungan->nasabah_id ?>">
                        <input type="hidden" name="simpanan_id" value="<?= $tabungan->id ?>">
                    <?php endif; ?>

                    <div class="form-group mb-3">
                        <label for="saldo">Saldo Tersedia</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background-color: #e9ecef; border-right: none;">Rp</span>
                            <input type="text" class="form-control border-0 shadow-none fw-bold fs-5 ps-2" id="saldo" name="saldo" style="background-color: #e9ecef;" readonly>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="jumlah_penarikan">Jumlah Penarikan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="jumlah_penarikan" id="jumlah_penarikan" class="form-control text-end" autocomplete="off" />
                        </div>
                        <div id="errorJumlah" class="invalid-feedback"></div>
                        <small class="form-text text-muted">Pastikan jumlah tidak melebihi saldo tersedia.</small>
                    </div>

                    <div id="jenis_tabungan" style="display: none;">
                        <div class="card border bg-light mb-3">
                            <div class="card-header py-2">
                                <h5 class="mb-0 fs-6">Informasi Tambahan (Deposito)</h5>
                            </div>
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label small">Durasi</label>
                                        <input type="text" class="form-control form-control-sm" id="durasi" name="durasi" readonly>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label small">Jatuh Tempo</label>
                                        <input type="text" class="form-control form-control-sm" id="tenor" name="tenor" readonly>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label small">Jenis Denda</label>
                                        <input type="text" class="form-control form-control-sm" id="jenis_denda" name="jenis_denda" readonly>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label small">Denda (%)</label>
                                        <input type="text" class="form-control form-control-sm" id="jumlah_denda" name="jumlah_denda" readonly>
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label small">Jumlah Denda (Rp)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control" id="denda" name="denda" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="total_yang_ditarik_display">Total Akan Ditarik</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background-color: #e9ecef; border-right: none;">Rp</span>
                            <input type="text" id="total_yang_ditarik_display" class="form-control border-0 shadow-none text-end fw-bold fs-5 ps-2" readonly style="background-color: #e9ecef;" />
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="perkiraan_sisa_saldo_display">Perkiraan Sisa Saldo</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background-color: #e9ecef; border-right: none;">Rp</span>
                            <input type="text" id="perkiraan_sisa_saldo_display" class="form-control border-0 shadow-none text-end fw-bold fs-5 ps-2" readonly style="background-color: #e9ecef;" />
                        </div>
                    </div>

                    <?php if ($this->session->userdata('level') == 'Admin'): ?>
                        <div class="form-group mb-4">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control" autocomplete="off">
                                <option value="">-- Pilih Pegawai --</option>
                                <?php foreach ($pegawai as $item): ?>
                                    <option value="<?= $item->id ?>"><?= $item->nama_lengkap ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="errorPegawai" class="invalid-feedback"></div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pegawai_id" id="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
                    <?php endif; ?>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">
                            <i></i> Tarik Uang
                        </button>
                        <button type="button" onclick="window.location='<?= base_url('penarikan') ?>'" class="btn btn-danger">
                            <i></i> Batal
                        </button>
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
            const totalAkanDitarik = jumlahPenarikanVal + jumlahDendaVal;
            totalDitarikAN.set(totalAkanDitarik);

            const saldoSaatIniVal = saldoAN.getNumber() || 0;
            const sisaSaldo = saldoSaatIniVal - totalAkanDitarik;
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
            const namaNasabahText = $('#nasabah option:selected').text() || 'Nasabah belum dipilih';
            const noRekeningText = $('#rekening option:selected').text() || 'Rekening belum dipilih';
            const saldoSaatIniNum = saldoAN.getNumber() || 0;
            const jumlahPenarikanNum = parseFloat($('#jumlah_penarikan').autoNumeric('get').replace(/\./g, '').replace(',', '.')) || 0;
            const jumlahDendaNum = dendaRpAN.getNumber() || 0;
            const totalAkanDitarikNum = jumlahPenarikanNum + jumlahDendaNum;
            const perkiraanSisaSaldoNum = saldoSaatIniNum - totalAkanDitarikNum;
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
                    (jumlahDendaNum > 0 ?
                        `  <tr>` +
                        `    <td style="padding: 4px 10px 4px 0; font-weight: bold; vertical-align: top; width: 220px;">Jumlah Denda:</td>` +
                        `    <td style="padding: 4px 0; vertical-align: top;">${formatRp(jumlahDendaNum)}</td>` +
                        `  </tr>` : '') +
                    `  <tr>` +
                    `    <td style="padding: 4px 10px 4px 0; font-weight: bold; vertical-align: top; width: 220px;">Total Akan Didebet:</td>` +
                    `    <td style="padding: 4px 0; vertical-align: top;">${formatRp(totalAkanDitarikNum)}</td>` +
                    `  </tr>` +
                    `  <tr>` +
                    `    <td style="padding: 4px 10px 4px 0; font-weight: bold; vertical-align: top; width: 220px;">Perkiraan Sisa Saldo:</td>` +
                    `    <td style="padding: 4px 0; vertical-align: top;">${formatRp(perkiraanSisaSaldoNum)}</td>` +
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
                    url: '<?= base_url("penarikan/get_rekening_by_nasabah") ?>',
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
                    url: '<?= base_url('penarikan/fetchRekening') ?>',
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