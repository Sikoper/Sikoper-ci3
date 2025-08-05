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
                            <input type="text" id="perkiraan_sisa_bunga" class="form-control text-end" value="0,00" readonly style="font-weight: bold; background-color: #e9ecef;" />
                        </div>
                    </div>

                    <?php if ($level == 'Admin'): ?>
                        <div class="form-group mb-3">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control">
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
                <div class="col-md-3"></div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
    $(document).ready(function() {
        const anJumlah = new AutoNumeric('#jumlah_penarikan', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });
        const anBungaTersedia = new AutoNumeric('#bunga_tersedia', {
            aSep: '.',
            aDec: ',',
            mDec: '0',
            readOnly: true
        });
        const anSisaBunga = new AutoNumeric('#perkiraan_sisa_bunga', {
            aSep: '.',
            aDec: ',',
            mDec: '0',
            readOnly: true
        });

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

        function hitungSisaBunga() {
            const bungaTersedia = anBungaTersedia.getNumber() || 0;
            const jumlahDitarik = anJumlah.getNumber() || 0;
            const sisa = bungaTersedia - jumlahDitarik;
            anSisaBunga.set(sisa);
            $('#perkiraan_sisa_bunga').css('color', sisa < 0 ? 'red' : '');
        }

        $('#jumlah_penarikan').on('keyup change', hitungSisaBunga);

        $('#comboRekening').on('change', function() {
            const idDeposito = $(this).val();
            $('#infoNasabah').hide();
            anBungaTersedia.set(0);
            anJumlah.set(0);
            hitungSisaBunga();

            if (idDeposito) {
                $.ajax({
                    url: '<?= base_url("deposito/fetch_detail_rekening") ?>', // <-- URL Sudah Benar
                    method: 'POST',
                    data: {
                        id: idDeposito
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#infoNasabah').show();
                            $('#infoNama').text(response.nama_nasabah || '-');
                            anBungaTersedia.set(response.bunga_tersedia || 0);
                            anJumlah.set(response.bunga_tersedia || 0);
                            hitungSisaBunga();
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
            $('#jumlah_penarikan').val(anJumlah.getNumber());

            Swal.fire({
                title: 'Konfirmasi Penarikan Bunga',
                html: `Anda yakin ingin menarik bunga sejumlah <strong>Rp ${anJumlah.getFormatted()}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        beforeSend: () => $('#tombol_simpan').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Memproses...'),
                        complete: () => $('#tombol_simpan').prop('disabled', false).html('Tarik Semua Bunga'),
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Berhasil!', response.success, 'success').then(() => window.location.reload());
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