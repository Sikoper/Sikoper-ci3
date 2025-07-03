<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <input type="hidden" name="penarikan_penuh" value="1">
                    <input type="hidden" name="nasabah_id" id="nasabah_id">

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="tanggal_penarikan">Tanggal Penarikan</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?= date('d/m/Y') ?>" readonly>
                            <input type="hidden" name="tanggal_penarikan" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="comboRekening">Pilih Rekening & Nasabah</label>
                        <select id="comboRekening" name="deposito_id" class="form-control select2" style="width: 100%;"></select>
                        <div class="invalid-feedback" id="errorSimpanan"></div>
                    </div>

                    <div id="jenis_tabungan" style="display: none;">
                        <div class="alert alert-light-primary">Detail Informasi Deposito</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="durasi">Durasi</label>
                                    <input type="text" class="form-control" id="durasi" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tenor">Jatuh Tempo</label>
                                    <input type="text" class="form-control" id="tenor" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="denda">Denda (Jika Ditarik Sebelum Jatuh Tempo)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control" id="denda" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="saldo">Saldo</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control" id="saldo" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="total_yang_ditarik_display">Total Pengurangan Saldo</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="total_yang_ditarik_display" class="form-control text-end" readonly style="font-weight: bold; background-color: #e9ecef; opacity: 1;" />
                        </div>
                    </div>

                    <?php if ($level == 'Admin') : ?>
                        <div class="form-group mb-3">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control">
                                <option value=""> -- Pilih Pegawai -- </option>
                                <?php foreach ($pegawai as $item) : ?>
                                    <option value="<?= $item->id ?>"><?= $item->nama_lengkap ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="errorPegawai"></div>
                        </div>
                    <?php else : ?>
                        <input type="hidden" name="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
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
    // Definisikan helper dan options di scope atas agar bisa dipakai di semua fungsi
    const autoNumericOpts = { digitGroupSeparator: '.', decimalCharacter: ',', decimalPlaces: 0, readOnly: true };
    const formatRp = (num) => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
    
    const saldoAN = new AutoNumeric('#saldo', autoNumericOpts);
    const dendaAN = new AutoNumeric('#denda', autoNumericOpts);
    const totalAN = new AutoNumeric('#total_yang_ditarik_display', autoNumericOpts);

    $('#comboRekening').select2({
        placeholder: 'Cari rekening atau nama nasabah...',
        ajax: {
            url: '<?= base_url("pencairan/get_combo_rekening_nasabah") ?>',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: (data) => ({ results: data.map(item => ({ id: item.id, text: item.text, nasabah_id: item.nasabah_id })) }),
            cache: true
        }
    });

    $('#comboRekening').on('select2:select', function(e) {
        const selected = e.params.data;
        $('#nasabah_id').val(selected.nasabah_id);
        $('.is-invalid').removeClass('is-invalid');

        $.ajax({
            url: '<?= base_url("pencairan/fetchRekening") ?>',
            method: 'POST',
            data: { id: selected.id },
            dataType: 'json',
            success: function(response) {
                if (response.error) return Swal.fire('Error', response.error, 'error');

                saldoAN.set(response.saldo);
                dendaAN.set(response.calculated_penalty_rp);
                $('#tenor').val(response.tenor);
                $('#durasi').val(response.durasi + ' Bulan');
                totalAN.set(response.saldo);

                if (response.kategori?.nama === 'Deposito') {
                    $('#jenis_tabungan').slideDown();
                    
                    // --- PERINGATAN DENDA (SWAL #1) DENGAN LAYOUT BARU ---
                    if (response.calculated_penalty_rp > 0) {
                        const rekeningInfoText = $('#comboRekening option:selected').text().trim();
                        const saldoInfo = response.saldo;
                        const dendaInfo = response.calculated_penalty_rp;
                        const diterimaInfo = saldoInfo - dendaInfo;

                        Swal.fire({
                            title: 'Peringatan Denda Penarikan',
                            icon: 'warning',
                            width: '600px',
                            html: `Penarikan untuk rekening ini akan dikenakan <strong>denda</strong> karena belum mencapai tanggal jatuh tempo (<b>${response.tenor}</b>).<br><br>` +
                                  `<table style="width:100%; text-align: left; border-collapse: collapse; line-height: 1.6;">` +
                                  `<tr><td style="width: 220px; font-weight: bold;">Rekening & Nasabah</td><td>: ${rekeningInfoText}</td></tr>` +
                                  `<tr style="border-top: 1px solid #ddd; padding-top: 5px;"><td style="font-weight: bold;">Total Saldo Saat Ini</td><td>: ${formatRp(saldoInfo)}</td></tr>` +
                                  `<tr style="font-weight: bold; color: red;"><td style="font-weight: bold;">Potongan Denda</td><td style="color: red;">: ${formatRp(dendaInfo)}</td></tr>` +
                                  `<tr style="font-weight: bold; color: green;"><td style="font-weight: bold;">Perkiraan Diterima Nasabah</td><td>: ${formatRp(diterimaInfo)}</td></tr>` +
                                  `</table><br>Pastikan nasabah telah memahami ketentuan ini.`,
                            confirmButtonText: 'Saya Mengerti',
                            allowOutsideClick: false
                        });
                    }
                } else {
                    $('#jenis_tabungan').slideUp();
                }
            }
        });
    });

    $('#form_simpan').submit(function(e) {
        e.preventDefault();
        $('.is-invalid').removeClass('is-invalid');

        const rekeningNasabahText = $('#comboRekening option:selected').text().trim() || 'Rekening belum dipilih';
        const saldoSaatIniNum = saldoAN.getNumber() || 0;
        const jumlahDendaNum = dendaAN.getNumber() || 0;

        if (saldoSaatIniNum <= 0 || rekeningNasabahText === 'Rekening belum dipilih') {
            Swal.fire('Perhatian', 'Silakan pilih rekening nasabah yang valid dan memiliki saldo.', 'warning');
            return;
        }

        // --- KONFIRMASI AKHIR (SWAL #2) DENGAN LAYOUT BARU ---
        Swal.fire({
            title: 'Konfirmasi Penarikan Penuh',
            icon: 'question',
            width: '600px',
            html: `Anda akan menarik <b>seluruh sisa saldo</b> dari rekening berikut:<br><br>` +
                  `<table style="width:100%; text-align: left; border-collapse: collapse; line-height: 1.6;">` +
                  `<tr><td style="width: 220px; font-weight: bold;">Rekening & Nasabah</td><td>: ${rekeningNasabahText}</td></tr>` +
                  `<tr style="border-top: 1px solid #ddd; padding-top: 5px;"><td style="font-weight: bold;">Total Pengurangan Saldo</td><td>: ${formatRp(saldoSaatIniNum)}</td></tr>` +
                  `<tr><td style="font-weight: bold;">Perkiraan Potongan Denda</td><td>: ${formatRp(jumlahDendaNum)}</td></tr>` +
                  `<tr style="font-weight: bold; color: green;"><td style="font-weight: bold;">Jumlah Diterima Nasabah</td><td>: ${formatRp(saldoSaatIniNum - jumlahDendaNum)}</td></tr>` +
                  `</table><br>Rekening akan menjadi <b>nonaktif</b> setelah transaksi ini. Lanjutkan?`,
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(this);
                $.ajax({
                    url: '<?= base_url('pencairan/proses') ?>',
                    method: 'POST',
                    data: formData,
                    processData: false, contentType: false, dataType: 'json',
                    beforeSend: () => $('#tombol_simpan').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...'),
                    complete: () => $('#tombol_simpan').prop('disabled', false).text('Tarik Keseluruhan Saldo'),
                    success: function(res) {
                        if (res.error) {
                            if (res.error.errorSimpanan) { $('#comboRekening').next('.select2-container').addClass('is-invalid'); $('#errorSimpanan').html(res.error.errorSimpanan).show(); }
                            if (res.error.errorPegawai) { $('#pegawai_id').addClass('is-invalid'); $('#errorPegawai').html(res.error.errorPegawai).show(); }
                        } else if(res.error_save){
                            Swal.fire('Gagal', res.error_save, 'error');
                        } else if (res.success) {
                            Swal.fire({
                                title: 'Berhasil', text: res.success, icon: 'success',
                                allowOutsideClick: false, allowEscapeKey: false
                            }).then(() => {
                                window.location.href = res.redirect || '<?= base_url('penarikan') ?>';
                            });
                        }
                    },
                    error: (xhr, status, error) => Swal.fire('Error', `Terjadi kesalahan: ${xhr.status} ${error}`, 'error')
                });
            }
        });
    });
});
</script>