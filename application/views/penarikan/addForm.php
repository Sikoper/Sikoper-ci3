<section class="section">
    <div class="card">
        <?php
        $backUrl = $this->input->get('code') == 1
            ? site_url('simpanan/add')
            : site_url('penarikan');
        ?>
        <div class="card-header">
            <button class="btn btn-warning" onclick="window.location='<?= $backUrl ?>'">
                <i class="fa fa-backward"></i> Kembali
            </button>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <?= form_open('penarikan/proses', ['id' => 'form_penarikan']) ?>

                    <div class="form-group" style="height: 80px;">
                        <label for="nasabah">Pilih Nasabah</label>
                        <div class="d-flex align-items-center">
                            <select id="nasabah" class="form-control select2" name="nasabah" style="width: auto; flex: 1;"></select>
                        </div>
                        <div id="errorNasabah" class="invalid-feedback" style="display: none;"></div>
                        <div class="valid-feedback" style="display: none;"></div>
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

                    <div class="form-group" style="height: 80px;">
                        <label for="nomor_rekening">Nomor Rekening</label>
                        <input type="text" class="form-control" id="nomor_rekening" name="no_rekening" readonly>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="saldo">Saldo</label>
                        <input type="text" class="form-control" id="saldo" readonly>
                    </div>

                    <div class="form-group" style="height: 80px;">
                        <label for="jumlah">Jumlah Penarikan</label>
                        <input type="number" class="form-control" name="jumlah" id="jumlah" min="1" required>
                    </div>

                    <div class="text-center mb-3">
                        <button type="submit" id="tombol_simpan" class="btn btn-success">Simpan</button>
                        <button type="button" onclick="window.location='<?= $backUrl ?>'" class="btn btn-danger">Batal</button>
                    </div>

                    <?= form_close() ?>
                </div>
                <div class="col-md-3"></div>
            </div>
        </div>
    </div>
</section>

<script>
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
</script>