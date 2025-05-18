<section class="section">
    <div class="card">
        <div class="card-header">
            <a href="<?= site_url('users') ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('', ['id' => 'form_simpan']) ?>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="tanggal_simpanan">Tanggal</label>
                        <div class="input-group">
                            <input type="date" name="tanggal_simpanan" id="tanggal_simpanan" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="nasabah">Pilih Nasabah</label>
                        <div class="d-flex align-items-center">
                            <select id="nasabah" class="form-control select2" name="nasabah" style="width: auto; flex: 1;"></select>
                            <button type="button" onclick="window.location='<?= base_url('nasabah/add') . '?code=1' ?>'" class="btn btn-primary ml-2" disabled>
                                <i class="fa fa-circle-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jenis_tabungan">Jenis Tabungan</label>
                        <select type="text" class="form-control" id="jenis_tabungan" name="jenis_tabungan">
                            <option value=""> -- Pilih Jenis Tabungan -- </option>
                            <?php foreach ($jenis as $item): ?>
                                <option value="<?= $item->id ?>"><?= $item->nama ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="errorJenisTabungan" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="bunga">Jumlah Bunga</label>
                                <div class="input-group">
                                    <input type="text" name="bunga" id="bunga" class="form-control" readonly>
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="biaya_registrasi">Biaya Registrasi</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" name="biaya_registrasi" id="biaya_registrasi" class="form-control" readonly>
                                </div>
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
                                    <input type="text" name="simpanan_awal" id="simpanan_awal" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group mb-3" style="height: 80px;">
                                <label for="pengendapan">Pengendapan</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" name="pengendapan" id="pengendapan" class="form-control" readonly>
                                </div>
                            </div>
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
                            <div id="errorJabatan" class="invalid-feedback" style="display: none;"></div>
                            <div class="valid-feedback" style="display: none;"></div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pegawai_id" id="pegawai_id" value="<?= $this->session->userdata('pegawai_id') ?>">
                    <?php endif; ?>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="jumlah_simpanan">Jumlah Simpanan</label>
                        <input type="text" name="jumlah_simpanan" id="jumlah_simpanan" class="form-control">
                    </div>

                    <div class="form-group mb-3" style="height: 80px;">
                        <label for="nomor_rekening">Nomor Rekening</label>
                        <div class="input-group">
                            <input type="text" name="nomor_rekening" id="nomor_rekening" class="form-control" readonly>
                            <span class="input-group-btn">
                                <button type="button" id="btn-generate" class="btn btn-success" disabled>Buat</button>
                            </span>
                        </div>
                    </div>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= base_url('users') ?>'" class="btn btn-danger">Batal</button>
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

        $('#jenis_tabungan').on('change', function() {
            var jenis_id = $(this).val();

            if (jenis_id) {
                $.ajax({
                    url: '<?= base_url('simpanan/getJenisData') ?>',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        id: jenis_id
                    },
                    success: jenis_tabungan_handler,
                    error: function(xhr, thrownError) {
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                    }
                });
            }
        });

        function jenis_tabungan_handler(response) {
            const kategori = response?.kategori;

            if (!kategori) {
                console.warn("Kategori data not found in response.");
                return;
            }

            console.log("Kategori Data:", kategori);

            let biaya = parseFloat(kategori.biaya_registrasi ?? 0);
            biaya = biaya % 1 === 0 ? parseInt(biaya) : biaya;
            let simpanan_awal = parseFloat(kategori.simpanan_awal ?? 0);
            simpanan_awal = simpanan_awal % 1 === 0 ? parseInt(simpanan_awal) : simpanan_awal;
            let pengendapan = parseFloat(kategori.pengendapan ?? 0);
            pengendapan = pengendapan % 1 === 0 ? parseInt(pengendapan) : pengendapan;

            const bungaAN = new AutoNumeric('#bunga', {
                digitGroupSeparator: ',',
                decimalCharacter: '.',
                decimalPlaces: 2,
                minimumValue: '0',
                maximumValue: '100'
            });

            const biayaAN = new AutoNumeric('#biaya_registrasi', {
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                decimalPlaces: 0
            });

            const simpananAwalAN = new AutoNumeric('#simpanan_awal', {
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                decimalPlaces: 0
            });

            const pengendapanAN = new AutoNumeric('#pengendapan', {
                digitGroupSeparator: '.',
                decimalCharacter: ',',
                decimalPlaces: 0
            });

            bungaAN.set(kategori.bunga ?? '');
            biayaAN.set(biaya ?? '');
            simpananAwalAN.set(simpanan_awal ?? '');
            pengendapanAN.set(pengendapan ?? '');
        }

        $('#tombol_simpan').click(function(e) {
            e.preventDefault();

            let form = $('#form_simpan')[0];
            let data = new FormData(form);

            $.ajax({
                type: "POST",
                url: "<?= base_url('users/simpanData') ?>",
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
                    $('#tombol_simpan').html('Save')
                },
                success: function(response) {
                    if (response.error) {
                        let dataError = response.error;
                        if (dataError.errorNama) {
                            $('#errorNama').html(dataError.errorNama).show();
                            $('#nama').addClass('is-invalid');
                        } else {
                            $('#errorNama').fadeOut();
                            $('#nama').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorUserName) {
                            $('#errorUserName').html(dataError.errorUserName).show();
                            $('#username').addClass('is-invalid');
                        } else {
                            $('#errorUserName').fadeOut();
                            $('#username').removeClass('is-invalid').addClass('is-valid');
                        }
                        if (dataError.errorPassword) {
                            $('#errorPassword').html(dataError.errorPassword).show();
                            $('#password').addClass('is-invalid');
                        } else {
                            $('#errorPassword').fadeOut();
                            $('#password').removeClass('is-invalid').addClass('is-valid');
                        }
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            html: response.success
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = '<?= base_url('users') ?>';
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

    $('#nasabah').on('select2:select', function(e) {
        let id = e.params.data.id;
        $('#nasabah_id').val(id);
    });

    $('#btn-generate').click(function() {
        $.ajax({
            url: '<?= base_url("simpanan/generate_norek") ?>',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#nomor_rekening').val(data.norek);
            },
            error: function() {
                alert("Gagal generate nomor rekening.");
            }
        });
    });
</script>