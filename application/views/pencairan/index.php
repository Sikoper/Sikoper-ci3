<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('pencairan/proses', ['id' => 'form_simpan']) ?>

                    <input type="hidden" name="deposito_id" id="deposito_id_hidden" value="<?= isset($data_pencairan_awal) ? $data_pencairan_awal->id : '' ?>">

                    <div class="form-group mb-3">
                        <label for="tanggal_penarikan">Tanggal Penarikan</label>
                        <input type="date" class="form-control" name="tanggal_penarikan" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label for="comboRekening">Pilih No. Rekening</label>
                        <select id="comboRekening" class="form-control select2" style="width: 100%;" <?= isset($data_pencairan_awal) ? 'disabled' : '' ?>>
                            <?php if (isset($data_pencairan_awal)) : ?>
                                <option value="<?= $data_pencairan_awal->id ?>" selected="selected">
                                    <?= $data_pencairan_awal->no_rekening ?> - <?= $data_pencairan_awal->nama_lengkap ?>
                                </option>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback" id="error_deposito_id"></div>
                    </div>

                    <div id="infoNasabah" style="display: none; margin-bottom: 15px;">
                        <p><strong>Nama Nasabah:</strong> <span id="infoNama"></span></p>
                    </div>

                    <div id="detail_pencairan" style="display: none;">
                        <div class="form-group">
                            <label for="saldo">Saldo Pokok</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control text-end" id="saldo" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bunga_tersedia">Bunga Tersedia</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control text-end" id="bunga_tersedia" readonly>
                            </div>
                        </div>

                        <div class="form-group" id="denda_container" style="display: none;">
                            <label for="denda">Potongan Denda</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control text-end text-danger" id="denda" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="total_yang_diterima">Total Diterima Nasabah</label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">Rp</span>
                                <input type="text" id="total_yang_diterima" class="form-control text-end fw-bold" readonly style="background-color: #e9ecef; opacity: 1;" />
                            </div>
                        </div>
                    </div>

                    <?php if ($this->session->userdata('level') == 'Admin') : ?>
                        <div class="form-group mb-3">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control">
                                <option value=""> -- Pilih Pegawai -- </option>
                                <?php foreach ($pegawai as $item) : ?>
                                    <option value="<?= $item->id ?>"><?= $item->nama_lengkap ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="error_pegawai_id"></div>
                        </div>
                    <?php else : ?>
                        <input type="hidden" name="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
                    <?php endif; ?>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Cairkan Keseluruhan Saldo</button>
                        <a href="<?= base_url('deposito') ?>" class="btn btn-danger">Batal</a>
                    </div>

                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0"></script>
<script>
$(document).ready(function() {
    // --- Inisialisasi Awal ---
    const autoNumericOpts = { digitGroupSeparator: '.', decimalCharacter: ',', decimalPlaces: 0, readOnly: true };
    const formatRp = (num) => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
    const saldoAN = new AutoNumeric('#saldo', autoNumericOpts);
    const bungaAN = new AutoNumeric('#bunga_tersedia', autoNumericOpts);
    const dendaAN = new AutoNumeric('#denda', autoNumericOpts);
    const totalAN = new AutoNumeric('#total_yang_diterima', autoNumericOpts);

    /**
     * Fungsi terpusat untuk mengambil detail rekening dan menampilkan di form.
     * Bisa dipanggil saat memilih dari dropdown ATAU saat halaman dimuat dengan data awal.
     */
    function prosesPencairanDetails(depositoId) {
        if (!depositoId) return;

        $('#deposito_id_hidden').val(depositoId);

        $.ajax({
            url: '<?= base_url("pencairan/fetchRekening") ?>',
            method: 'POST',
            data: { id: depositoId },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    Swal.fire('Error', response.error, 'error');
                    $('#detail_pencairan').slideUp();
                    return;
                }
                const pokok = parseFloat(response.saldo) || 0;
                const bunga = parseFloat(response.bunga_tersedia) || 0;
                const denda = parseFloat(response.calculated_penalty_rp) || 0;
                const totalDiterima = pokok + bunga - denda;

                saldoAN.set(pokok);
                bungaAN.set(bunga);
                dendaAN.set(denda);
                totalAN.set(totalDiterima);
                
                $('#detail_pencairan').slideDown();
                
                if (denda > 0) {
                    $('#denda_container').slideDown();
                    Swal.fire({
                        title: 'Peringatan Denda Penarikan',
                        icon: 'warning',
                        width: '600px',
                        html: `Penarikan dikenakan <strong>denda</strong> karena belum jatuh tempo (<b>${response.tenor}</b>).<br><br>` +
                            `<table style="width:100%; text-align: left; border-collapse: collapse; line-height: 1.6;">` +
                            `<tr><td style="width: 220px;">Saldo Pokok</td><td>: ${formatRp(pokok)}</td></tr>` +
                            `<tr><td>Bunga Tersedia</td><td>: ${formatRp(bunga)}</td></tr>` +
                            `<tr style="color: red;"><td>Potongan Denda</td><td>: ${formatRp(denda)}</td></tr>` +
                            `<tr style="font-weight: bold; color: green; border-top: 1px solid #ddd; padding-top: 5px;"><td>Jumlah Diterima Nasabah</td><td>: ${formatRp(totalDiterima)}</td></tr>` +
                            `</table>`,
                        confirmButtonText: 'Saya Mengerti'
                    });
                } else {
                    $('#denda_container').slideUp();
                }
            }
        });
    }

    // --- Inisialisasi Select2 ---
    $('#comboRekening').select2({
        placeholder: 'Cari no rekening atau nama nasabah...',
        ajax: {
            url: '<?= base_url("pencairan/get_combo_rekening_nasabah") ?>',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) {
                return {
                    results: data.results.map(item => ({
                        id: item.id,
                        text: item.text,
                        nama_nasabah: item.nama_nasabah
                    }))
                };
            }
        }
    });

    $('#comboRekening').on('select2:select', function(e) {
        const selected = e.params.data;
        $('#infoNama').text(selected.nama_nasabah); 
        $('#infoNasabah').slideDown();
        prosesPencairanDetails(selected.id);
    });

    <?php if (isset($data_pencairan_awal)) : ?>
        $('#infoNama').text('<?= addslashes($data_pencairan_awal->nama_lengkap) ?>');
        $('#infoNasabah').show();

        prosesPencairanDetails(<?= $data_pencairan_awal->id ?>);
    <?php endif; ?>

    function clearValidationErrors() {
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    $('#form_simpan').submit(function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Konfirmasi Pencairan Penuh',
            html: "Anda yakin ingin mencairkan seluruh dana dan menutup rekening ini? <br><b>Tindakan ini tidak dapat dibatalkan.</b>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan & Tutup Akun!',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                clearValidationErrors();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    beforeSend: () => $('#tombol_simpan').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...'),
                    complete: () => $('#tombol_simpan').prop('disabled', false).text('Cairkan Keseluruhan Saldo'),
                    success: function(res) {
                        if (res.status === 'validation_error') {
                            $.each(res.errors, function(key, value) {
                                if (value) {
                                    const field = $(`#${key}`);
                                    const errorContainer = $(`#error_${key}`);
                                    field.addClass('is-invalid');
                                    errorContainer.text(value);
                                }
                            });
                        } else if (res.success) {
                            Swal.fire('Berhasil', res.success, 'success').then(() => {
                                window.location.href = res.redirect || '<?= base_url("deposito") ?>';
                            });
                        } else {
                            Swal.fire('Gagal', res.error_save || 'Terjadi kesalahan pada server.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Koneksi Gagal', 'Tidak dapat terhubung ke server.', 'error');
                    }
                });
            }
        });
    });
});
</script>