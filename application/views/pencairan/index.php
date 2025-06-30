<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <input type="hidden" name="penarikan_penuh" value="1">

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
                        <select id="nasabah" class="form-control select2" name="nasabah" <?= $disabled ? 'disabled' : '' ?> style="width: 100%;">
                            <?php if (!empty($tabungan) && !empty($selected_nasabah)): ?>
                                <option value="<?= $selected_nasabah ?>" selected><?= $nasabah ? $nasabah->nama_lengkap : '' ?></option>
                            <?php else: ?>
                                <option value="">-- Pilih Nasabah --</option>
                            <?php endif; ?>
                        </select>
                        <div id="errorNasabah" class="invalid-feedback" style="display: none;"></div>
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
                    </div>
                    <?php if (!empty($tabungan)): ?>
                        <input type="hidden" id="preselectedNasabah" value="<?= $tabungan->nasabah_id ?>">
                        <input type="hidden" id="preselectedRekening" value="<?= $tabungan->id ?>">
                        <input type="hidden" name="nasabah" value="<?= $tabungan->nasabah_id ?>">
                        <input type="hidden" name="simpanan_id" value="<?= $tabungan->id ?>">
                    <?php endif; ?>

                    <div id="jenis_tabungan" style="display: none;">
                        <div class="alert alert-light-primary">Detail Informasi Deposito</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="durasi">Durasi</label>
                                    <input type="text" class="form-control" id="durasi" name="durasi" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tenor">Jatuh Tempo</label>
                                    <input type="text" class="form-control" id="tenor" name="tenor" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="denda">Denda (Jika Ditarik Sebelum Jatuh Tempo)</label>
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

                    <div class="form-group mb-3" style="display: none;">
                        <label for="jumlah_penarikan">Jumlah Penarikan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="jumlah_penarikan" id="jumlah_penarikan" class="form-control text-end" autocomplete="off" />
                        </div>
                        <div id="errorJumlah" class="invalid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label for="total_yang_ditarik_display">Total Penarikan Keseluruhan (Pengurangan Saldo)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="total_yang_ditarik_display" class="form-control text-end" readonly style="font-weight: bold; background-color: #e9ecef; opacity: 1;" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Perkiraan Sisa Saldo Setelah Transaksi</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="perkiraan_sisa_saldo_display" class="form-control text-end" readonly style="display: none;" />
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
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pegawai_id" id="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
                    <?php endif; ?>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Tarik Keseluruhan Saldo</button>
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
        // Inisialisasi AutoNumeric
        const autoNumericV4OptionsRp = {
            currencySymbol: '',
            decimalCharacter: ',',
            digitGroupSeparator: '.',
            decimalPlaces: 0,
            readOnly: true
        };
        const saldoAN = new AutoNumeric('#saldo', autoNumericV4OptionsRp);
        const dendaRpAN = new AutoNumeric('#denda', autoNumericV4OptionsRp);
        const totalDitarikAN = new AutoNumeric('#total_yang_ditarik_display', autoNumericV4OptionsRp);

        // --- PERBAIKAN KRITIS UNTUK PENCARIAN NASABAH ---
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

        $('#rekening').select2({
            placeholder: '-- Pilih Rekening --'
        });

        $('#nasabah').on('change', function() {
            var nasabahId = $(this).val();
            $('#rekening').html('<option value="">-- Pilih Rekening --</option>').val("").trigger('change');
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
                        }
                    }
                });
            }
        });

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
                            return;
                        }

                        saldoAN.set(response.saldo);
                        dendaRpAN.set(response.calculated_penalty_rp);
                        $('#tenor').val(response.tenor);
                        $('#durasi').val(response.durasi + ' Bulan');

                        // DITAMBAHKAN: Otomatis isi Total Penarikan Keseluruhan dengan nilai saldo
                        totalDitarikAN.set(response.saldo);

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
                    }
                });
            } else {
                saldoAN.set(0);
                dendaRpAN.set(0);
                $('#tenor').val('');
                $('#durasi').val('');
                $('#jenis_tabungan').hide();
                totalDitarikAN.set(0);
            }
        });

        if ($('#preselectedNasabah').val()) {
            $('#nasabah').trigger('change');
        }

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();
            const namaNasabahText = $('#nasabah option:selected').text().trim() || 'Nasabah belum dipilih';
            const noRekeningText = $('#rekening option:selected').text().trim() || 'Rekening belum dipilih';
            const saldoSaatIniNum = saldoAN.getNumber() || 0;
            const jumlahDendaNum = dendaRpAN.getNumber() || 0;
            const formatRp = (num) => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);

            Swal.fire({
                title: 'Konfirmasi Penarikan Penuh',
                width: '600px',
                html: `Anda akan menarik <b>seluruh sisa saldo</b> dari rekening berikut:<br><br>` +
                    `<table style="width:100%; text-align: left; border-collapse: collapse; line-height: 1.6;">` +
                    `<tr><td style="width: 220px; font-weight: bold;">Nasabah</td><td>: ${namaNasabahText}</td></tr>` +
                    `<tr><td style="font-weight: bold;">No. Rekening</td><td>: ${noRekeningText}</td></tr>` +
                    `<tr style="border-top: 1px solid #ddd; padding-top: 5px;"><td style="font-weight: bold;">Total Pengurangan Saldo</td><td>: ${formatRp(saldoSaatIniNum)}</td></tr>` +
                    `<tr><td style="font-weight: bold;">Perkiraan Potongan Denda</td><td>: ${formatRp(jumlahDendaNum)}</td></tr>` +
                    `<tr style="font-weight: bold; color: green;"><td style="font-weight: bold;">Jumlah Diterima Nasabah</td><td>: ${formatRp(saldoSaatIniNum - jumlahDendaNum)}</td></tr>` +
                    `</table><br>Rekening akan menjadi <b>nonaktif</b> setelah transaksi ini. Lanjutkan?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Lanjutkan!',
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
                            $('#tombol_simpan').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i>');
                        },
                        complete: function() {
                            $('#tombol_simpan').prop('disabled', false).html('Tarik Keseluruhan Saldo');
                        },
                        success: function(response) {
                            if (response.error) {
                                let msg = Object.values(response.error).join('<br>');
                                Swal.fire("Gagal!", msg, "error");
                            } else if (response.error_save) {
                                Swal.fire("Gagal!", response.error_save, "error");
                            } else if (response.success) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Berhasil!",
                                    html: response.success,
                                    allowOutsideClick: false
                                }).then(() => {
                                    window.location.href = response.redirect || '<?= base_url('penarikan') ?>';
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire("Error!", "Terjadi kesalahan: " + xhr.status + " " + xhr.statusText, "error");
                        }
                    });
                }
            });
        });
    });
</script>