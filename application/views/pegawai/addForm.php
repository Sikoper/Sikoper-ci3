<style>
    #loadingOverlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
        font-family: sans-serif;
        flex-direction: column;
    }
</style>
<div id="loadingOverlay" style="display: none;">
    <div style="text-align: center;">
        <i class="fas fa-spinner fa-spin fa-3x"></i>
        <div class="loader-text" style="margin-top: 20px;">Sedang memindai KTP...</div>
    </div>
</div>
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<section class="section">
    <div class="card">
        <div class="card-header">
            <button class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </button>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open() ?>

                    <div style="display: flex; justify-content: center;">
                        <div class="form-group" style="text-align: center;">
                            <label for="ktp">Foto KTP Penghuni</label><br>
                            <div style="width: 350px; height: 200px; border: 2px dashed #ccc; margin: 0 auto 10px;">
                                <img id="ktpPreview" src="<?= base_url('assets') ?>/uploads/ktp/default-ktp.png"
                                    alt="KTP Preview" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <input type="file" id="ktp" name="ktp" class="form-control-file" accept="image/*" capture="camera" style="margin: 0 auto;">
                            <input type="hidden" name="croppedKtpImage" id="croppedKtpImage">
                            <div id="errorKtp" class="invalid-feedback" style="display: none;"></div>
                            <div class="valid-feedback" style="display: none;"></div>
                            <pre style="display: none;" id="output"></pre>
                        </div>
                    </div>

                    <div class="modal fade" id="ktpCropperModal" data-backdrop="static" tabindex="-1" aria-labelledby="ktpCropperModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="ktpCropperModalLabel">Crop KTP Image</h5>
                                    <button type="button" class="close" onclick="closeKtpModal();">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body text-center">
                                    <div style="max-width: 100%; height: 400px; overflow: hidden; position: relative; margin: 0 auto;">
                                        <img id="ktpCropperImage" style="max-width: 100%; height: auto; object-fit: contain; display: block;">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="closeKtpModal();">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="ktpCropButton">Crop & Save</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nik">NIK</label>
                        <input type="text" class="form-control" id="nik" name="nik" placeholder="NIK sesuai KTP">
                    </div>

                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Nama lengkap sesuai KTP">
                    </div>

                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                            <option value=""> -- Pilih Jenis Kelamin -- </option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tempat_lahir">Tempat Lahir</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" placeholder="Tempat lahir sesuai KTP">
                    </div>

                    <div class="form-group">
                        <label for="tgl_lahir">Tanggal Lahir</label>
                        <input type="date" id="tgl_lahir" name="tgl_lahir" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="agama">Agama</label>
                        <input type="text" id="agama" name="agama" class="form-control" placeholder="Agama sesuai KTP">
                    </div>

                    <div class="form-group">
                        <label for="provinsi">Provinsi Asal</label>
                        <select class="form-select" id="provinsi" name="provinsi">
                            <option value=""> -- Pilih Provinsi Asal -- </option>
                            <?php foreach ($provinces as $prov): ?>
                                <option value="<?= $prov['code'] ?>"><?= $prov['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kabupaten">Kabupaten Asal</label>
                        <select class="form-select" id="kabupaten" name="kabupaten">
                            <option value=""> -- Pilih Kabupaten Asal -- </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kecamatan">Kecamatan Asal</label>
                        <select class="form-select" id="kecamatan" name="kecamatan">
                            <option value=""> -- Pilih Kecamatan Asal -- </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="desa">Desa Asal</label>
                        <select class="form-select" id="desa" name="desa">
                            <option value=""> -- Pilih Desa Asal -- </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="alamat">Aalamt</label>
                        <input type="text" id="alamat" name="alamat" class="form-control" placeholder="Alamat Sesuai KTP">
                    </div>

                    <div class="form-group">
                        <label for="telp">Nomer Telpon</label>
                        <input type="text" id="telp" name="telp" class="form-control" placeholder="Nomer aktif/whatsapp">
                    </div>

                    <div class="form-group mb-5">
                        <label for="jabatan">Jabatan</label>
                        <select id="jabatan" name="jabatan" class="form-select">
                            <option value=""> -- Pilih Jabatan -- </option>
                            <option value="Staf">Staf</option>
                            <option value="Manajer">Manajer</option>
                        </select>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@4.0.2/dist/tesseract.min.js"></script>
<script>
    $(document).ready(function() {
        const stopWords = ['AGAMA', 'STATUS', 'PEKERJAAN', 'KEWARGANEGARAAN', 'GOLONGAN DARAH'];

        let ktpCropperInstance;

        document.getElementById('ktp').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('ktpCropperImage').src = event.target.result;
                $('#ktpCropperModal').modal('show');
            };
            reader.readAsDataURL(file);
        });

        $('#ktpCropperModal').on('shown.bs.modal', function() {
            if (ktpCropperInstance) ktpCropperInstance.destroy();
            ktpCropperInstance = new Cropper(document.getElementById('ktpCropperImage'), {
                aspectRatio: 6 / 4,
                viewMode: 2
            });
        });

        $('#ktpCropperModal').on('hidden.bs.modal', function() {
            if (ktpCropperInstance) {
                ktpCropperInstance.destroy();
                ktpCropperInstance = null;
            }
        });

        document.getElementById('ktpCropButton').addEventListener('click', async function() {
            if (!ktpCropperInstance) return;

            const canvas = ktpCropperInstance.getCroppedCanvas({
                width: 600,
                height: 400
            });

            const base64Image = canvas.toDataURL('image/png');

            document.getElementById('ktpPreview').src = base64Image;

            $('#ktpCropperModal').modal('hide');

            document.getElementById('loadingOverlay').style.display = 'flex';
            const rawTextEl = document.getElementById('output');
            rawTextEl.textContent = "Membaca...";

            const {
                data: {
                    text
                }
            } = await Tesseract.recognize(base64Image, 'ind', {
                logger: m => console.log(m)
            });

            document.getElementById('loadingOverlay').style.display = 'none';
            rawTextEl.textContent = text;

            const lines = text.split('\n').map(line => line.trim()).filter(line => line !== '');

            let nik = '',
                nama = '',
                tempatLahir = '',
                tanggalLahir = '',
                gender = '',
                agama = '';

            for (let i = 0; i < lines.length; i++) {
                const lineRaw = lines[i];
                const line = lineRaw.toUpperCase().replace(/[^A-Z0-9\s:\/\-]/g, '').trim();

                if (!nik) {
                    const numberOnly = line.replace(/\D/g, '');
                    if (numberOnly.length >= 15) nik = numberOnly.substring(0, 16);
                }

                if (!nama && (line.includes('NAMA') || line.match(/NAMA[\s:\-]/))) {
                    const nameExtract = line.split(/NAMA\s*[:\-]?\s*/i)[1];
                    nama = nameExtract?.trim() || lines[i + 1]?.trim();
                }

                if (!tempatLahir && (line.includes('TEMPAT') || line.includes('LAHIR'))) {
                    const ttlMatch = line.match(/([A-Z\s]+)[, ]+(\d{2}[-\/]\d{2}[-\/]\d{4})/);
                    if (ttlMatch) {
                        tempatLahir = ttlMatch[1].trim();
                        const tglParts = ttlMatch[2].split(/[-\/]/);
                        tanggalLahir = `${tglParts[2]}-${tglParts[1]}-${tglParts[0]}`;
                    }
                }

                if (!gender && line.includes('KELAMIN')) {
                    if (line.includes('LAKI')) gender = 'Laki-laki';
                    else if (line.includes('PEREMPUAN')) gender = 'Perempuan';
                }

                if (!agama && line.includes('AGAMA')) {
                    const agamaExtract = line.split(/AGAMA\s*[:\-]?\s*/i)[1];
                    agama = capitalizeWords(agamaExtract?.trim() || lines[i + 1]?.trim());
                }
            }

            nama = capitalizeWords(nama?.replace(/[^A-Z\s]/gi, '').replace(/\s+/g, ' ').trim() || '');

            if (!gender) {
                const guessGender = lines.find(l => l.includes('PEREMPUAN') || l.includes('LAKI-LAKI'));
                gender = guessGender?.includes('PEREMPUAN') ? 'Perempuan' : 'Laki-laki';
            }

            document.getElementById('nik').value = nik;
            document.getElementById('nama_lengkap').value = nama;
            document.getElementById('tempat_lahir').value = capitalizeWords(tempatLahir);
            document.getElementById('tgl_lahir').value = tanggalLahir;
            document.getElementById('jenis_kelamin').value = gender;
            document.getElementById('agama').value = agama;
            document.getElementById('ktpPreview').src = base64Image;
            document.getElementById('croppedKtpImage').value = base64Image;
            rawTextEl.style.display = 'none';
        });

        function capitalizeWords(str) {
            return str.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
        }

    });

    function closeKtpModal() {
        $('#ktpCropperModal').modal('hide');

        document.getElementById('ktpCropperImage').src = '';

        document.getElementById('ktpPreview').src = '<?= base_url('assets') ?>/uploads/ktp/default-ktp.png';

        document.getElementById('nik').value = '';
        document.getElementById('nama_lengkap').value = '';
        document.getElementById('tempat_lahir').value = '';
        document.getElementById('tgl_lahir').value = '';
        document.getElementById('jenis_kelamin').value = '';
        document.getElementById('agama').value = '';
        document.getElementById('croppedKtpImage').value = '';

        document.getElementById('ktp').value = '';

        document.getElementById('output').style.display = 'none';
        document.getElementById('output').textContent = '';
    }
</script>
<script>
    $(document).ready(function() {
        $('#provinsi').on('change', function() {
            let provinsi = $('#provinsi').val()
            $.ajax({
                type: "POST",
                url: "<?= base_url('pegawai/getKab') ?>",
                data: {
                    provinsi: provinsi
                },
                dataType: "json",
                success: function(response) {
                    if (response.data) {
                        $('#kabupaten').html(response.data);
                        $('#kabupaten').prop('disabled', false);
                    }
                },
                error: function(xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
        });

        $('#kabupaten').on('change', function() {
            let kabupaten = $('#kabupaten').val()
            $.ajax({
                type: "POST",
                url: "<?= base_url('pegawai/getKec') ?>",
                data: {
                    kabupaten: kabupaten
                },
                dataType: "json",
                success: function(response) {
                    if (response.data) {
                        $('#kecamatan').html(response.data);
                        $('#kecamatan').prop('disabled', false);
                    }
                },
                error: function(xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
        });

        $('#kecamatan').on('change', function() {
            let kecamatan = $('#kecamatan').val()
            $.ajax({
                type: "POST",
                url: "<?= base_url('pegawai/getKel') ?>",
                data: {
                    kecamatan: kecamatan
                },
                dataType: "json",
                success: function(response) {
                    console.log(response);
                    if (response.data) {
                        $('#desa').html(response.data);
                        $('#desa').prop('disabled', false);
                    }
                },
                error: function(xhr, thrownError) {
                    alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                }
            });
        });
    });
</script>