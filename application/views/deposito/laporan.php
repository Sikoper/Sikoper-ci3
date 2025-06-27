<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <?php
            function safe_base64_encode($string)
            {
                return strtr(base64_encode($string), '+/=', '-_.');
            }
            $backUrl = $this->input->get('code') == 1
                ? site_url('deposito/detail/' . safe_base64_encode($deposito->no_rekening))
                : site_url('deposito');
            ?>
            <a href="<?= $backUrl ?>" class="btn btn-warning">
                <i class="fa fa-backward"></i> Kembali
            </a>
        </h4>
    </div>
    <?= form_open('', ['id' => 'form_laporan']) ?>
    <div class="card-body">
        <input type="text" name="id" id="id" value="<?= $deposito->id ?>">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="form-group mb-3" style="height: 80px;">
                    <label for="tanggal_mulai">Tanggal mulai transaksi</label>
                    <div class="input-group">
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3" style="height: 80px;">
                    <label for="tanggal_akhir">Tanggal akhir transaksi</label>
                    <div class="input-group">
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control">
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
                        <option value="3"> Setoran dan Penarikan </option>
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
    $(document).ready(function() {
        $('#form_laporan').submit(function(e) {
            e.preventDefault();

            // Confirm dialog
            Swal.fire({
                title: "Cetak data?",
                text: `Yakin ingin cetak data dari <?= $deposito->no_rekening ?>`,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya!",
                cancelButtonText: "Tidak",
            }).then((result) => {
                if (result.isConfirmed) {
                    // Get form data as query params
                    const formData = $(this).serialize();

                    // Build the URL to open in a new tab with query params
                    const url = "<?= base_url('deposito/print_laporan') ?>" + "?" + formData;

                    // Open in new tab
                    window.open(url, '_blank');
                }
            });
        });
    });
</script>