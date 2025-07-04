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

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="tanggal_penarikan">Tanggal Penarikan</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?= date('d/m/Y') ?>" readonly>
                            <input type="hidden" name="tanggal_penarikan" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div id="errorTanggal" class="invalid-feedback" style="display: none;"></div>
                    </div>

                        <div class="form-group mb-3">
                            <label for="comboRekening">Pilih Rekening</label>
                            <select id="comboRekening" name="comboRekening" class="form-control select2">
                                <option value="">-- Pilih Rekening --</option>
                            </select>
                            <div id="errorTabungan" class="invalid-feedback"></div>
                            <small class="form-text text-muted">Cari berdasarkan nomor rekening</small>
                        </div>

                        <!-- Hidden untuk dikirim -->
                        <input type="hidden" name="tabungan" id="inputTabungan">
                        <input type="hidden" name="nasabah" id="inputNasabah">

                        <!-- Info deskriptif -->
                        <div id="detail_nasabah_info" class="alert alert-light mt-2" style="display:none;"></div>

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
                                <i></i> Simpan
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

        $('#comboRekening').select2({
            placeholder: '-- Pilih Rekening --',
            ajax: {
                url: '<?= base_url('setoran/get_combo_rekening_nasabah') ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term
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

        $('#comboRekening').on('change', function() {
            let idRekening = $(this).val();
            let infoDiv = $('#detail_nasabah_info');
            $('#inputTabungan').val('');
            $('#inputNasabah').val('');
            $('#sisa_saldo').val('Memuat...');
            $('#jumlah_setoran').autoNumeric('set', '');

            // Jika yang login adalah Admin, reset juga pilihan Pegawai
            if ($('#form_simpan').data('level') === 'Admin') {
                $('#pegawai_id').val('').trigger('change');
            }

            if (idRekening) {
                $.ajax({
                    url: '<?= base_url("setoran/get_detail_rekening") ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: idRekening
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            $('#inputTabungan').val(res.data.id);
                            $('#inputNasabah').val(res.data.nasabah_id);

                            infoDiv.html(`
                                <strong>Nama:</strong> ${res.data.nama_lengkap}<br>
                                <strong>Jenis Tabungan:</strong> ${res.data.jenis_tabungan}<br>
                                <strong>NIK:</strong> ${res.data.nik}<br>
                                <strong>Alamat:</strong> ${res.data.alamat}
                            `).show();

                            let saldo = new Intl.NumberFormat('id-ID').format(res.data.jumlah_simpanan);
                            $('#sisa_saldo').val(saldo);
                        } else {
                            infoDiv.html('<em>Gagal mengambil data rekening.</em>').show();
                            $('#sisa_saldo').val('Gagal');
                        }

                        cekKesiapanForm();
                    }
                });
            } else {
                infoDiv.hide().html('');
                $('#sisa_saldo').val('Pilih Rekening Untuk Melihat Saldo');
                cekKesiapanForm();
            }
        });

        <?php if ($level == 'Admin'): ?>
            $('#pegawai_id').select2({
                placeholder: '-- Pilih Pegawai --'
            });
        <?php endif; ?>

        $('#jumlah_setoran, #pegawai_id').on('keyup change', function() {
            cekKesiapanForm();
        });

        function cekKesiapanForm() {
            const nasabah = $('#inputNasabah').val();
            const tabungan = $('#inputTabungan').val();
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
                        if (dataError.errorTabungan) {
                            $('#errorTabungan').html(dataError.errorTabungan).show();
                            $('#comboRekening').addClass('is-invalid');
                        } else {
                            $('#errorTabungan').fadeOut();
                            $('#comboRekening').removeClass('is-invalid').addClass('is-valid');
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
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            allowEnterKey: false,
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

        // Inject data jika membuka dari link detail
        const selectedRekening = '<?= $selected_rekening ?? '' ?>';
        const selectedTabunganId = '<?= $selected_tabungan_id ?? '' ?>';
        const selectedNasabahId = '<?= $selected_nasabah ?? '' ?>';

        if (selectedRekening && selectedTabunganId && selectedNasabahId) {
            const newOption = new Option(selectedRekening, selectedTabunganId, true, true);
            $('#comboRekening').append(newOption).trigger('change');

            $('#inputTabungan').val(selectedTabunganId);
            $('#inputNasabah').val(selectedNasabahId);
        }

        cekKesiapanForm();
    });
</script>