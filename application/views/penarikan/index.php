<section class="section">
    <div class="card">
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
                        <label for="total_yang_ditarik_display">Total Akan Ditarik</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="total_yang_ditarik_display" class="form-control text-end" readonly
                                style="font-weight: bold; background-color: #e9ecef; opacity: 1;" />
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
        }

        $('#jumlah_penarikan').on('input keyup change', function() {
            updateTotalYangAkanDitarik();
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
                    },
                    error: function() {
                        alert('Gagal mengambil data rekening.');
                        $('#rekening').html('<option value="">-- Pilih Rekening --</option>').val("").trigger('change');
                    }
                });
            }
        });

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