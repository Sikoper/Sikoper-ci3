<section class="section">
    <div class="card">
        <div class="card-header">
        </div>
        <div class="card-body">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-6">
                        <?= form_open('', ['id' => 'form_simpan', 'data-level' => $this->session->userdata('level')]) ?>

                        <div class="form-group mb-3">
                            <label for="tanggal_setoran_display">Tanggal</label>
                            <input type="text" id="tanggal_setoran_display" value="<?= date('d-m-Y') ?>" class="form-control" style="background-color: #e9ecef;" readonly>
                            <input type="hidden" id="tanggal_setoran" name="tanggal_setoran" value="<?= date('Y-m-d') ?>">
                            <div id="errorTanggalSetoran" class="invalid-feedback"></div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="nasabah">Pilih Nasabah</label>
                            <select id="nasabah" class="form-control select2" name="nasabah" <?= $disabled ? 'disabled' : '' ?>>
                                <option value="">-- Pilih Nasabah --</option>
                                <?php foreach ($nasabah as $n): ?>
                                    <option value="<?= $n->id ?>" <?= ($selected_nasabah == $n->id) ? 'selected' : '' ?>>
                                        <?= $n->nama_lengkap ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="errorNasabah" class="invalid-feedback"></div>
                            <small class="form-text text-muted">Mulai ketik untuk mencari nama nasabah.</small>
                        </div>

                        <div id="detail_nasabah_info" class="alert alert-light mt-2" style="display:none;"></div>

                        <div class="form-group mb-3">
                            <label for="tabungan">Pilih Tabungan</label>
                            <select class="form-control select2" name="tabungan" id="tabungan" <?= $disabled ? 'disabled' : '' ?>>
                                <?php if (!empty($tabungan)): ?>
                                    <option value="<?= $tabungan->id ?>" selected><?= $tabungan->no_rekening ?></option>
                                <?php else: ?>
                                    <option value="">-- Pilih Rekening --</option>
                                <?php endif; ?>
                            </select>
                            <div id="errorTabungan" class="invalid-feedback"></div>
                        </div>

                        <?php if (!empty($tabungan)): ?>
                            <input type="hidden" id="preselectedNasabah" value="<?= $tabungan->nasabah_id ?>">
                            <input type="hidden" id="preselectedTabungan" value="<?= $tabungan->id ?>">
                        <?php endif; ?>

                        <?php if (!empty($tabungan)): ?>
                            <input type="hidden" name="nasabah" value="<?= $tabungan->nasabah_id ?>">
                            <input type="hidden" name="tabungan" value="<?= $tabungan->id ?>">
                        <?php endif; ?>

                        <div class="form-group mb-3">
                            <label for="sisa_saldo">Saldo Saat Ini</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #e9ecef; border-right: none;">Rp</span>
                                <input type="text" id="sisa_saldo" class="form-control border-0 shadow-none fw-bold fs-5 ps-2" style="background-color: #e9ecef;" value="Pilih Tabungan Untuk Melihat Saldo" readonly>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="jumlah_setoran">Jumlah Setoran</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="jumlah_setoran" id="jumlah_setoran" class="form-control text-end">
                            </div>
                            <div id="errorJumlahSetoran" class="invalid-feedback"></div>
                            <small class="form-text text-muted">Hanya masukkan angka. Format ribuan akan ditambahkan otomatis.</small>
                        </div>

                        <?php if ($level == 'Admin') : ?>
                            <div class="form-group mb-5">
                                <label for="pegawai_id">Pegawai</label>
                                <select id="pegawai_id" name="pegawai_id" class="form-control" autocomplete="off">
                                    <option value="">-- Pilih Pegawai --</option>
                                    <?php foreach ($pegawai as $item) : ?>
                                        <option value="<?= $item->id ?>"><?= $item->nama_lengkap ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="errorPegawai" class="invalid-feedback"></div>
                            </div>
                        <?php else : ?>
                            <input type="hidden" name="pegawai_id" id="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
                        <?php endif; ?>

                        <div class="text-center mb-3">
                            <button type="submit" id="tombol_simpan_setoran" class="btn btn-success" disabled>
                                <i class="bi bi-check-circle"></i> Simpan
                            </button>
                            <button type="button" onclick="window.location='<?= base_url('setoran') ?>'" class="btn btn-danger">Batal</button>
                        </div>

                        <?= form_close() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        $('#jumlah_setoran').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        $('#nasabah').select2({
            placeholder: '-- Pilih Nasabah --'
        });
        $('#tabungan').select2({
            placeholder: '-- Pilih Rekening --'
        });
        $('#pegawai_id').select2({
            placeholder: '-- Pilih Pegawai --'
        });

        $('#nasabah').on('change', function() {
            console.log('Nasabah selection changed. Firing event...');
            let nasabahId = $(this).val();
            const selectTabungan = $('#tabungan');
            const detailInfo = $('#detail_nasabah_info');

            $('#sisa_saldo').val('Pilih Tabungan Untuk Melihat Saldo');
            $('#jumlah_setoran').autoNumeric('set', '');
            detailInfo.hide().html('');
            selectTabungan.html('<option value="">--- Pilih tabungan ---</option>').trigger('change');

            if (nasabahId) {
                selectTabungan.prop('disabled', false).html('<option value="">--- Memuat rekening... ---</option>');
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('setoran/get_no_rekening') ?>",
                    data: {
                        nasabah: nasabahId
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log('AJAX success: Received rekening data.');
                        if (response.data) {
                            selectTabungan.html(response.data);

                            if (response.detail_nasabah) {
                                let detail = response.detail_nasabah;
                                detailInfo.html(`<strong>NIK:</strong> ${detail.nik || '-'}<br><strong>Alamat:</strong> ${detail.alamat || '-'}`).show();
                            }

                            const preselectedTabunganId = $('#preselectedTabungan').val();
                            if (preselectedTabunganId) {
                                console.log('Preselected tabungan found. Triggering its change event.');
                                selectTabungan.val(preselectedTabunganId).trigger('change');

                                $('#nasabah').prop('disabled', true);
                                selectTabungan.prop('disabled', true);
                            }
                        } else {
                            selectTabungan.html('<option value="">-- Tidak ada rekening --</option>');
                        }
                    },
                    error: function() {
                        selectTabungan.html('<option value="">-- Gagal memuat --</option>');
                    }
                });
            } else {
                selectTabungan.prop('disabled', true);
            }
            cekKesiapanForm();
        });

        $('#tabungan').on('change', function() {
            console.log('SUCCESS: Tabungan change event fired!');
            let idRekening = $(this).val();
            let inputSaldo = $('#sisa_saldo');

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
                            let saldoFormatted = new Intl.NumberFormat('id-ID', {
                                style: 'decimal',
                                minimumFractionDigits: 0
                            }).format(response.saldo);

                            inputSaldo.val(saldoFormatted);
                        } else {
                            inputSaldo.val(response.message || 'Gagal memuat');
                        }
                        cekKesiapanForm();
                    },
                    error: function() {
                        inputSaldo.val('Gagal memuat saldo.');
                        cekKesiapanForm();
                    }
                });
            } else {
                inputSaldo.val('Pilih Tabungan Untuk Melihat Saldo');
                cekKesiapanForm();
            }
        });

        $('#jumlah_setoran, #pegawai_id').on('keyup change', function() {
            cekKesiapanForm();
        });

        function cekKesiapanForm() {
            const nasabah = $('#nasabah').val();
            const tabungan = $('#tabungan').val();
            const jumlahSetoran = $('#jumlah_setoran').autoNumeric('get');
            const level = $('#form_simpan').data('level');
            let pegawaiValid = true;

            if (level === 'Admin') {
                pegawaiValid = ($('#pegawai_id').val() !== "");
            }

            if (nasabah && tabungan && parseFloat(jumlahSetoran) > 0 && pegawaiValid) {
                $('#tombol_simpan_setoran').prop('disabled', false);
            } else {
                $('#tombol_simpan_setoran').prop('disabled', true);
            }
        }

        $('#tombol_simpan_setoran').click(function(e) {
            e.preventDefault();

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
                    $('#tombol_simpan_setoran').prop('disabled', true)
                    $('#tombol_simpan_setoran').html('<i class="fa fa-spin fa-spinner"></i>')
                },
                complete: function() {
                    $('#tombol_simpan_setoran').prop('disabled', false)
                    $('#tombol_simpan_setoran').html('Simpan')
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
                            title: "Success!",
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
        });

        cekKesiapanForm();

        const preselectedNasabah = $('#preselectedNasabah').val();
        if (preselectedNasabah) {
            console.log('Page loaded with preselected nasabah. Firing trigger...');
            $('#nasabah').trigger('change');
        }
    });
</script>