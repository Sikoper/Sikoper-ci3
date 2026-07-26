<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('deposito/proses_penarikan_bunga', ['id' => 'form_penarikan_bunga']) ?>

                    <div class="form-group mb-3">
                        <label for="tanggal_penarikan">Tanggal Penarikan</label>
                        <input type="date" id="tanggal_penarikan" class="form-control" name="tanggal_penarikan" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label for="comboRekening">Rekening Deposito</label>
                        <select id="comboRekening" class="form-control" name="deposito_id" style="width: 100%;"></select>
                    </div>

                    <div id="infoNasabah" style="display:none; margin-bottom: 15px;">
                        <p class="mb-0"><strong>Nama Nasabah:</strong> <span id="infoNama"></span></p>
                    </div>

                    <div class="form-group mb-3">
                        <label for="bunga_tersedia">Bunga Tersedia</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control text-end" id="bunga_tersedia" readonly>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="jumlah_penarikan">Jumlah Bunga Ditarik</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="jumlah_penarikan" id="jumlah_penarikan" class="form-control text-end" readonly />
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="perkiraan_sisa_bunga">Perkiraan Sisa Bunga</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="perkiraan_sisa_bunga" class="form-control text-end" value="0" readonly style="font-weight: bold; background-color: #e9ecef;" />
                        </div>
                    </div>

                    <?php if ($level == 'Admin'): ?>
                        <div class="form-group mb-3">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control" style="width: 100%;">
                                <option value=""> -- Pilih Pegawai -- </option>
                                <?php foreach ($pegawai as $item): ?>
                                    <option value="<?= $item->id ?>"><?= $item->nama_lengkap ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
                    <?php endif; ?>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Tarik Semua Bunga</button>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-danger">Batal</a>
                    </div>

                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?= base_url('assets') ?>/vendors/autonumeric.js/autoNumeric.js"></script>
<script>
    $(document).ready(function() {
        const anOpts = {
            aSep: '.',
            aDec: ',',
            mDec: 0
        };

        $('#jumlah_penarikan').autoNumeric('init', anOpts);
        $('#jumlah_penarikan').prop('readonly', true);
        
        $('#bunga_tersedia').autoNumeric('init', anOpts);
        $('#bunga_tersedia').prop('readonly', true);
        
        $('#perkiraan_sisa_bunga').autoNumeric('init', anOpts);
        $('#perkiraan_sisa_bunga').prop('readonly', true);

        $('#comboRekening').select2({
            placeholder: 'Cari no rekening...',
            ajax: {
                url: '<?= base_url("deposito/get_combo_rekening_nasabah") ?>',
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
            const idDeposito = $(this).val();
            $('#infoNasabah').hide();
            anBungaTersedia.set(0);
            anJumlah.set(0);
            anSisaBunga.set(0);

            if (idDeposito) {
                $.ajax({
                    url: '<?= base_url("deposito/fetch_detail_rekening") ?>',
                    method: 'POST',
                    data: {
                        id: idDeposito
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#infoNasabah').show();
                            $('#infoNama').text(response.nama_nasabah || '-');
                            $('#bunga_tersedia').autoNumeric('set', response.bunga_tersedia || 0);
                            $('#jumlah_penarikan').autoNumeric('set', response.bunga_tersedia || 0);
                            $('#perkiraan_sisa_bunga').autoNumeric('set', 0);
                        } else {
                            Swal.fire('Gagal!', response.message || 'Gagal mengambil detail rekening.', 'error');
                        }
                    },
                    error: () => Swal.fire('Error!', 'Terjadi kesalahan sistem saat mengambil data.', 'error')
                });
            }
        });

        $('#form_penarikan_bunga').submit(function(e) {
            e.preventDefault();
            const jumlahDitarik = parseFloat($('#jumlah_penarikan').autoNumeric('get')) || 0;
            if (jumlahDitarik <= 0) {
                Swal.fire('Gagal', 'Tidak ada bunga yang tersedia untuk ditarik.', 'error');
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Penarikan Bunga',
                html: `Anda yakin ingin menarik bunga sejumlah <strong>Rp ${$('#jumlah_penarikan').val()}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const postData = {
                        tanggal_penarikan: $('#tanggal_penarikan').val(),
                        deposito_id: $('#comboRekening').val(),
                        jumlah_penarikan: $('#jumlah_penarikan').autoNumeric('get'),
                        pegawai_id: $('[name="pegawai_id"]').val()
                    };

                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: postData,
                        dataType: 'json',
                        beforeSend: () => $('#tombol_simpan').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Memproses...'),
                        complete: () => $('#tombol_simpan').prop('disabled', false).html('Tarik Semua Bunga'),
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Berhasil!",
                                    html: response.success + "<br><br><strong>Cetak kwitansi penarikan bunga?</strong>",
                                    showCancelButton: true,
                                    confirmButtonColor: "#28a745",
                                    confirmButtonText: "Ya, Cetak Kwitansi",
                                    cancelButtonText: "Tidak, Nanti Saja",
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    allowEnterKey: false
                                }).then((result) => {
                                    if (result.isConfirmed && response.penarikan_id) {
                                        window.open("<?= base_url('deposito/print_kwitansi_bunga/') ?>" + response.penarikan_id, "_blank");
                                    }
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Gagal!', response.error_save || response.error_validation, 'error');
                            }
                        },
                        error: () => Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error')
                    });
                }
            });
        });
    });
</script>