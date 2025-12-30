<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <?php
            // Menggunakan safe_base64_encode dari secure_helper.php (autoloaded)
            // Asumsi variabel $deposito ada dari controller
            $backUrl = isset($simpanan) && $this->input->get('code') == 1
                ? site_url('simpanan/detail/' . safe_base64_encode($simpanan->no_rekening))
                : site_url('simpanan');
            ?>
            <a href="<?= $backUrl ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </h4>
    </div>
    <?= form_open('', ['id' => 'form_laporan']) ?>
    <div class="card-body">
        <input type="hidden" name="id" id="id" value="<?= isset($simpanan) ? $simpanan->id : '' ?>">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="form-group mb-3" style="height: 80px;">
                    <label for="tanggal_mulai">Tanggal mulai transaksi</label>
                    <div class="input-group">
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control"
                            value="<?= date('Y-m-01') ?>">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3" style="height: 80px;">
                    <label for="tanggal_akhir">Tanggal akhir transaksi</label>
                    <div class="input-group">
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control"
                            value="<?= date('Y-m-t') ?>">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3" style="height: 80px;">
                    <label for="tanggal_akhir">Pilih jenis laporan</label>
                    <select class="form-select" id="jenis_laporan" name="jenis_laporan">
                        <option value=""> -- Pilih laporan yang ingin di print -- </option>
                        <option value="1"> Setoran </option>
                        <option value="2"> Penarikan </option>
                        <option value="3" selected> Setoran dan Penarikan </option>
                    </select>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center">
            <button type="submit" id="tombol_cetak" class="btn btn-primary">
                Cetak laporan
                <i class="fa fa-print"></i>
            </button>
        </div>
    </div>
    <?= form_close() ?>
</div>
<script>
    $(document).ready(function () {
        $('#form_laporan').submit(function (e) {
            e.preventDefault();
            Swal.fire({
                title: "Cetak data?",
                text: `Yakin ingin cetak data dari <?= $simpanan->no_rekening ?>`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya!",
                cancelButtonText: "Tidak",
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = $(this).serialize();
                    const url = "<?= base_url('simpanan/print_laporan') ?>" + "?" + formData;
                    window.open(url, '_blank');
                }
            });
        });
    });
</script>