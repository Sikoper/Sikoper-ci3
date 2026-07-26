<section class="section">
    <div class="card">
        <div class="card-header">
            <a href="<?= site_url('deposito') ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="tanggal_deposito">Tanggal Deposito</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="tanggal_deposito" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div id="errorTanggalDeposito" class="invalid-feedback" style="display: none;"></div>
                    </div>

                    <!-- Historical Withdrawal Option (Visible only for backdated deposits) -->
                    <div id="historical_withdrawal_section" class="alert alert-warning mb-3" style="display: none;">
                        <h6 class="alert-heading"><i class="fas fa-history"></i> Pengaturan Bunga Lampau</h6>
                        <p class="mb-2 text-sm">Anda memasukkan tanggal masa lalu. Bagaimana status bunga yang sudah lewat?</p>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status_bunga_lampau" id="bunga_akumulasi" value="akumulasi" checked>
                            <label class="form-check-label" for="bunga_akumulasi">
                                <strong>Akumulasikan (Belum Ditarik)</strong> <br>
                                <small class="text-muted">Bunga akan ditambahkan ke saldo 'Bunga Belum Ditarik'</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="status_bunga_lampau" id="bunga_ditarik" value="ditarik">
                            <label class="form-check-label" for="bunga_ditarik">
                                <strong>Sudah Ditarik (Riwayat Penarikan)</strong> <br>
                                <small class="text-muted">Sistem akan otomatis membuat riwayat penarikan untuk bunga tersebut</small>
                            </label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="quick_add_mode" name="quick_add_mode" value="1">
                            <label class="form-check-label" for="quick_add_mode"><strong>Input Nasabah Baru (Langsung)</strong></label>
                            <small class="d-block text-muted">Centang jika nasabah belum terdaftar. Anda bisa input data langsung di sini.</small>
                        </div>
                    </div>

                    <!-- Mode: Pilih Nasabah Lama -->
                    <div id="nasabah_lama_section" class="form-group" style="height: 80px;">
                        <label for="nasabah">Pilih Nasabah</label>
                        <div class="d-flex align-items-center">
                            <select id="nasabah" class="form-control select2" name="nasabah" style="width: auto; flex: 1;"></select>
                            <button type="button" onclick="window.location='<?= base_url('nasabah/add') . '?code=1' ?>'" class="btn btn-primary ml-2" title="Tambah Menu Lengkap">
                                <i class="fa fa-circle-plus"></i>
                            </button>
                        </div>
                        <div id="errorNasabah" class="invalid-feedback" style="display: none;"></div>
                    </div>

                    <!-- Mode: Input Nasabah Baru -->
                    <div id="nasabah_baru_section" style="display: none;">
                        <div class="form-group mb-3">
                            <label class="form-label required">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_langsung" id="nama_langsung" placeholder="Nama Nasabah Baru">
                            <div id="errorNamaLangsung" class="invalid-feedback" style="display: none;"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label required">No. Telepon / WA</label>
                                    <input type="text" class="form-control" name="telepon_langsung" id="telepon_langsung" placeholder="08..." onkeypress="return isNumber(event)">
                                    <div id="errorTeleponLangsung" class="invalid-feedback" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Alamat Singkat</label>
                                    <input type="text" class="form-control" name="alamat_langsung" id="alamat_langsung" placeholder="Alamat Domisili">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="height: auto;">
                        <label for="nomor_rekening">Nomor Rekening</label>
                        <div class="input-group">
                            <input type="text" name="nomor_rekening" id="nomor_rekening"
                                class="form-control text-end" autocomplete="off" readonly>
                            <button type="button" id="refreshRek" class="btn btn-secondary">
                                <i class="fa fa-sync"></i>
                            </button>
                        </div>

                        <div id="lastRekening" class="mt-2 text-primary fw-bold">
                            Nomor rekening terakhir: <span id="lastRekeningValue">-</span>
                        </div>

                        <div id="errorNoRekening" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jenis_tabungan_display">Jenis Rekening</label>
                        <input type="text" class="form-control" id="jenis_tabungan_display" value="<?= $jenis->nama ?>" name="jenis_tabungan_display" readonly>
                        </input>
                        <div id="errorJenisTabungan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <input type="hidden" value="<?= $jenis->id ?>" id="jenis_tabungan" name="jenis_tabungan">

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="bunga">Jumlah Bunga</label>
                                <div class="input-group">
                                    <input type="text" name="bunga" id="bunga" class="form-control text-end" readonly>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div id="errorBunga" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="biaya_registrasi">Biaya Registrasi</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" name="biaya_registrasi" id="biaya_registrasi" class="form-control text-end" readonly>
                                </div>
                                <div id="errorBiayaRegistrasi" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="simpanan_awal">Simpanan Awal</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" name="simpanan_awal" id="simpanan_awal" class="form-control text-end" readonly>
                                </div>
                                <div id="errorSimpananAwal" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="pengendapan">Pengendapan</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" name="pengendapan" id="pengendapan" class="form-control text-end" readonly>
                                </div>
                                <div id="errorPengendapan" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-6">
                            <div class="form-group" style="height: 80px;">
                                <label for="jenis_denda" class="form-label">Jenis Denda</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-end" name="jenis_denda" id="jenis_denda" readonly>
                                </div>
                                <div id="errorJenisDenda" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group" style="height: 80px;">
                                <label for="jumlah_denda" class="form-label">Jumlah Denda</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-end" name="jumlah_denda" id="jumlah_denda" readonly>
                                </div>
                                <div id="errorJumlahDenda" class="invalid-feedback" style="display: none;"></div>
                                <div class="valid-feedback" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="durasi">Jangka Waktu Deposito</label>
                        <select id="durasi" name="durasi" class="form-control" autocomplete="off">
                            <option value=""> -- Pilih Jangka Waktu -- </option>
                            <option value="6">6 Bulan / &frac12; Tahun</option>
                            <option value="12">12 Bulan / 1 Tahun</option>
                            <option value="18">18 Bulan / 1.5 Tahun</option>
                            <option value="24">24 Bulan / 2 Tahun</option>
                            <option value="30">30 Bulan / 2.5 Tahun</option>
                            <option value="36">36 Bulan / 3 Tahun</option>
                            <option value="42">42 Bulan / 3.5 Tahun</option>
                            <option value="48">48 Bulan / 4 Tahun</option>
                            <option value="54">54 Bulan / 4.5 Tahun</option>
                            <option value="60">60 Bulan / 5 Tahun</option>
                        </select>
                        <div id="errorDurasi" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nama_ahli_waris" class="form-label">Nama Ahli Waris</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="nama_ahli_waris" id="nama_ahli_waris">
                        </div>
                        <div id="errorNamaAhliWaris" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="kontak_ahli_waris" class="form-label">Kontak Ahli Waris</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="kontak_ahli_waris" id="kontak_ahli_waris">
                        </div>
                        <div id="errorKontakAhliWaris" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="hubungan_ahli_waris" class="form-label">Hubungan Dengan Deposan</label>
                        <div class="input-group">
                            <select class="form-control" name="hubungan_ahli_waris" id="hubungan_ahli_waris">
                                <option value=""> -- Pilih hubungan -- </option>
                                <option value="Anak">Anak dari Deposan</option>
                                <option value="Cucu">Cucu dari Deposan</option>
                                <option value="Suami/Istri">Suami / Istri dari Deposan</option>
                            </select>
                        </div>
                        <div id="errorHubunganAhliWaris" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <?php if ($level == 'Admin'): ?>
                        <div class="form-group mb-3" style="height: 80px;">
                            <label for="pegawai_id">Pegawai</label>
                            <select id="pegawai_id" name="pegawai_id" class="form-control" autocomplete="off" style="width: 100%;">
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

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="jumlah_deposito">Jumlah Simpanan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="jumlah_deposito" id="jumlah_deposito" class="form-control text-end">
                        </div>
                        <div id="errorJumlahSimpanan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= base_url('deposito') ?>'" class="btn btn-danger">Batal</button>
                    </div>

                    <?= form_close() ?>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
    </div>
</section>
<script src="<?= base_url('assets') ?>/vendors/autonumeric.js/autoNumeric.js"></script>
<script>
    $(document).ready(function() {
        fetchRekening();

        $('#refreshRek').on('click', function() {
            fetchRekening();
        });
        $('#bunga').autoNumeric('init', {
            aSep: ',',
            aDec: '.',
            mDec: '2',
            vMin: '0',
            vMax: '100'
        });

        $('#biaya_registrasi').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        $('#jumlah_deposito').autoNumeric('init', {
            aSep: '.',
            aDec: ',',
            mDec: '0'
        });

        $('#jenis_tabungan').on('change', function() {
            var jenis_id = $(this).val();
            fetchJenisData(jenis_id);
        });

        var initial_jenis_id = $('#jenis_tabungan').val();
        fetchJenisData(initial_jenis_id);

        function fetchJenisData(jenis_id) {
            if (jenis_id !== null && jenis_id !== '') {
                $.ajax({
                    url: '<?= base_url('simpanan/getJenisData') ?>',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        id: jenis_id
                    },
                    success: function(response) {
                        jenis_tabungan_handler(response);
                    },
                    error: function(xhr, thrownError) {
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                    }
                });
            }
        }

        function fetchRekening() {
            $.ajax({
                url: '<?= base_url("deposito/get_next_rekening") ?>',
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.next_rekening) {
                        $('#nomor_rekening').val(res.next_rekening);
                    }
                    if (res.last_rekening) {
                        $('#lastRekeningValue').text(res.last_rekening);
                    }
                }
            });
        }

        function jenis_tabungan_handler(response) {
            const kategori = response?.kategori;

            if (!kategori) {
                console.warn("Kategori data not found in response.");
                return;
            }

            let biaya = parseFloat(kategori.biaya_registrasi ?? 0);
            let simpanan_awal = parseFloat(kategori.simpanan_awal ?? 0);
            let pengendapan = parseFloat(kategori.pengendapan ?? 0);
            let jenis_denda = kategori.jenis_denda;
            let jumlah_denda = parseFloat(kategori.jumlah_denda ?? 0);
            let bunga = parseFloat(kategori.bunga ?? 0);

            if ($('#bunga').data('autoNumeric')) {
                $('#bunga').autoNumeric('set', bunga);
            } else {
                $('#bunga').val(bunga);
            }

            if ($('#biaya_registrasi').data('autoNumeric')) {
                $('#biaya_registrasi').autoNumeric('set', biaya);
            } else {
                $('#biaya_registrasi').val(biaya);
            }

            if (!$('#simpanan_awal').data('autoNumeric')) {
                $('#simpanan_awal').autoNumeric('init', { aSep: '.', aDec: ',', mDec: '0' });
            }
            $('#simpanan_awal').autoNumeric('set', simpanan_awal);

            if (!$('#pengendapan').data('autoNumeric')) {
                $('#pengendapan').autoNumeric('init', { aSep: '.', aDec: ',', mDec: '0' });
            }
            $('#pengendapan').autoNumeric('set', pengendapan);

            $('#jenis_denda').val(jenis_denda);

            if (jenis_denda === 'Rp') {
                if (!$('#jumlah_denda').data('autoNumeric')) {
                    $('#jumlah_denda').autoNumeric('init', { aSep: '.', aDec: ',', mDec: '0' });
                } else {
                    $('#jumlah_denda').autoNumeric('update', { aSep: '.', aDec: ',', mDec: '0' });
                }
                $('#jumlah_denda').autoNumeric('set', jumlah_denda);
            } else if (jenis_denda === '%') {
                if (!$('#jumlah_denda').data('autoNumeric')) {
                    $('#jumlah_denda').autoNumeric('init', { aSep: ',', aDec: '.', mDec: '2', vMin: '0', vMax: '100' });
                } else {
                    $('#jumlah_denda').autoNumeric('update', { aSep: ',', aDec: '.', mDec: '2', vMin: '0', vMax: '100' });
                }
                $('#jumlah_denda').autoNumeric('set', jumlah_denda);
            }
        }

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('deposito/simpanData') ?>",
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
                    $('#tombol_simpan').html('Simpan')
                },
                success: function(response) {
                    if (response.error) {
                        let dataError = response.error;
                        if (dataError.errorTanggalDeposito) {
                            $('#errorTanggalDeposito').html(dataError.errorTanggalDeposito).show();
                            $('#tanggal_deposito').addClass('is-invalid');
                        } else {
                            $('#errorTanggalDeposito').fadeOut();
                            $('#tanggal_deposito').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorNasabah) {
                            $('#errorNasabah').html(dataError.errorNasabah).show();
                            $('#nasabah').addClass('is-invalid');
                        } else {
                            $('#errorNasabah').fadeOut();
                            $('#nasabah').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJenisTabungan) {
                            $('#errorJenisTabungan').html(dataError.errorJenisTabungan).show();
                            $('#jenis_tabungan').addClass('is-invalid');
                        } else {
                            $('#errorJenisTabungan').fadeOut();
                            $('#jenis_tabungan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorBunga) {
                            $('#errorBunga').html(dataError.errorBunga).show();
                            $('#bunga').addClass('is-invalid');
                        } else {
                            $('#errorBunga').fadeOut();
                            $('#bunga').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorBiayaRegistrasi) {
                            $('#errorBiayaRegistrasi').html(dataError.errorBiayaRegistrasi).show();
                            $('#biaya_registrasi').addClass('is-invalid');
                        } else {
                            $('#errorBiayaRegistrasi').fadeOut();
                            $('#biaya_registrasi').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorSimpananAwal) {
                            $('#errorSimpananAwal').html(dataError.errorSimpananAwal).show();
                            $('#simpanan_awal').addClass('is-invalid');
                        } else {
                            $('#errorSimpananAwal').fadeOut();
                            $('#simpanan_awal').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorPengendapan) {
                            $('#errorPengendapan').html(dataError.errorPengendapan).show();
                            $('#pengendapan').addClass('is-invalid');
                        } else {
                            $('#errorPengendapan').fadeOut();
                            $('#pengendapan').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJenisDenda) {
                            $('#errorJenisDenda').html(dataError.errorJenisDenda).show();
                            $('#jenis_denda').addClass('is-invalid');
                        } else {
                            $('#errorJenisDenda').fadeOut();
                            $('#jenis_denda').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJumlahDenda) {
                            $('#errorJumlahDenda').html(dataError.errorJumlahDenda).show();
                            $('#jumlah_denda').addClass('is-invalid');
                        } else {
                            $('#errorJumlahDenda').fadeOut();
                            $('#jumlah_denda').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorJumlahSimpanan) {
                            $('#errorJumlahSimpanan').html(dataError.errorJumlahSimpanan).show();
                            $('#jumlah_deposito').addClass('is-invalid');
                        } else {
                            $('#errorJumlahSimpanan').fadeOut();
                            $('#jumlah_deposito').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorPegawai) {
                            $('#errorPegawai').html(dataError.errorPegawai).show();
                            $('#pegawai_id').addClass('is-invalid');
                        } else {
                            $('#errorPegawai').fadeOut();
                            $('#pegawai_id').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorNoRekening) {
                            $('#errorNoRekening').html(dataError.errorNoRekening).show();
                            $('#nomor_rekening').addClass('is-invalid');
                        } else {
                            $('#errorNoRekening').fadeOut();
                            $('#nomor_rekening').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorDurasi) {
                            $('#errorDurasi').html(dataError.errorDurasi).show();
                            $('#durasi').addClass('is-invalid');
                        } else {
                            $('#errorDurasi').fadeOut();
                            $('#durasi').removeClass('is-invalid').addClass('is-valid');
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
                                window.location.reload();
                            }
                        });
                    }
                },
                error: function(xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
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

    $('#pegawai_id').select2({
        placeholder: '-- Pilih Pegawai --',
        allowClear: true,
        width: '100%'
    });

    $('#nasabah').on('select2:select', function(e) {
        let id = e.params.data.id;
        $('#nasabah_id').val(id);
    });

    // Check for backdated input to show/hide historical options
    $('#tanggal_deposito').on('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        // Reset time parts for accurate date comparison
        selectedDate.setHours(0,0,0,0);
        today.setHours(0,0,0,0);

        if (selectedDate < today) {
            $('#historical_withdrawal_section').slideDown();
        } else {
            $('#historical_withdrawal_section').slideUp();
            // Reset to default
            $('#bunga_akumulasi').prop('checked', true);
        }
    });

    // Initial check on load
    $('#tanggal_deposito').trigger('change');

    // Quick Add Mode Toggle
    $('#quick_add_mode').on('change', function() {
        if ($(this).is(':checked')) {
            // Switch to Quick Add
            $('#nasabah_lama_section').slideUp();
            $('#nasabah_baru_section').slideDown();
            
            // Clear Select2 to avoid validation conflicts (optional)
            $('#nasabah').val(null).trigger('change');
        } else {
            // Switch back to Select
            $('#nasabah_lama_section').slideDown();
            $('#nasabah_baru_section').slideUp();
            
            // Clear inputs
            $('#nama_langsung').val('');
            $('#telepon_langsung').val('');
            $('#alamat_langsung').val('');
        }
    });

    // Validasi Angka helper
    function isNumber(evt) {
        evt = (evt) ? evt : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        return true;
    }
</script>