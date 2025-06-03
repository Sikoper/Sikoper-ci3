<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <?php
            function safe_base64_encode($string)
            {
                return strtr(base64_encode($string), '+/=', '-_.');
            }
            $backUrl = $this->input->get('code') == 1
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
        <input type="hidden" name="id" id="id" <?= $simpanan->id ?>>
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
                    <select class="form-select">
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
        $('#tombol_cetak').click(function(e) {
            e.preventDefault();
            let form = $('#form_laporan')[0];
            let data = new FormData(form);
            Swal.fire({
                title: "Print data ini?",
                html: "Yakin ingin print data dari <strong><?= $simpanan->no_rekening ?></strong>?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes!",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: "<?= base_url('simpanan/print_laporan') ?>",
                        data: data,
                        dataType: "json",
                        success: function(response) {

                        },
                        error: function(xhr, thrownError) {
                            alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                        }
                    });
                }
            });
        });
    });
</script>