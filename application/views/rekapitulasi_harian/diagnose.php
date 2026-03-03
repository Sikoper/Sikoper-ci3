<section class="section">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-search"></i> Diagnostik Data Import</h4>
            <a href="<?= base_url('rekapitulasi_harian') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-<?= count($ghost_setoran) > 0 ? 'danger' : 'success' ?>">
                        <div class="card-body text-center">
                            <h1 class="display-4 fw-bold text-<?= count($ghost_setoran) > 0 ? 'danger' : 'success' ?>">
                                <?= count($ghost_setoran) ?>
                            </h1>
                            <h6>Ghost Setoran</h6>
                            <small class="text-muted">Saldo awal yang masuk sebagai setoran</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-<?= count($name_mismatches) > 0 ? 'warning' : 'success' ?>">
                        <div class="card-body text-center">
                            <h1
                                class="display-4 fw-bold text-<?= count($name_mismatches) > 0 ? 'warning' : 'success' ?>">
                                <?= count($name_mismatches) ?>
                            </h1>
                            <h6>Nama Tidak Cocok</h6>
                            <small class="text-muted">Nama di simpanan ≠ nama di nasabah</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-<?= count($duplicate_nasabah) > 0 ? 'info' : 'success' ?>">
                        <div class="card-body text-center">
                            <h1
                                class="display-4 fw-bold text-<?= count($duplicate_nasabah) > 0 ? 'info' : 'success' ?>">
                                <?= count($duplicate_nasabah) ?>
                            </h1>
                            <h6>Nasabah Duplikat</h6>
                            <small class="text-muted">Nama yang sama, ID berbeda</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Clear Banner -->
            <?php if (count($ghost_setoran) == 0 && count($name_mismatches) == 0 && count($duplicate_nasabah) == 0): ?>
                <div class="alert alert-success text-center py-4">
                    <h3><i class="bi bi-check-circle-fill"></i> Semua Data Bersih!</h3>
                    <p class="mb-0">Tidak ditemukan ghost setoran, nama tidak cocok, atau nasabah duplikat.</p>
                </div>
            <?php endif; ?>

            <!-- Ghost Setoran Section -->
            <?php if (count($ghost_setoran) > 0): ?>
                <div class="card border-danger mb-4">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Ghost Setoran (
                            <?= count($ghost_setoran) ?>)
                        </h5>
                        <button class="btn btn-light btn-sm" id="btn-fix-ghost" onclick="fixGhostSetoran()">
                            <i class="bi bi-wrench"></i> Auto-Fix
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>No</th>
                                        <th>No Rekening</th>
                                        <th>Nama Nasabah</th>
                                        <th>Tanggal</th>
                                        <th class="text-end">Jumlah (Ghost)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ghost_setoran as $i => $row): ?>
                                        <tr>
                                            <td>
                                                <?= $i + 1 ?>
                                            </td>
                                            <td><code><?= $row->no_rekening ?></code></td>
                                            <td>
                                                <?= $row->nama_nasabah ?>
                                            </td>
                                            <td>
                                                <?= $row->tanggal_setoran ?>
                                            </td>
                                            <td class="text-end text-danger fw-bold">Rp
                                                <?= number_format($row->jumlah_setoran, 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Name Mismatch Section -->
            <?php if (count($name_mismatches) > 0): ?>
                <div class="card border-warning mb-4">
                    <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-person-exclamation"></i> Nama Tidak Cocok (
                            <?= count($name_mismatches) ?>)
                        </h5>
                        <button class="btn btn-dark btn-sm" id="btn-fix-names" onclick="fixNameMismatches()">
                            <i class="bi bi-wrench"></i> Sync Nama
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>No</th>
                                        <th>No Rekening</th>
                                        <th>Nama dari Excel (Benar)</th>
                                        <th></th>
                                        <th>Nama di Nasabah (Lama)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($name_mismatches as $i => $row): ?>
                                        <tr>
                                            <td>
                                                <?= $i + 1 ?>
                                            </td>
                                            <td><code><?= $row->no_rekening ?></code></td>
                                            <td class="text-success fw-bold">
                                                <?= $row->nama_di_simpanan ?>
                                            </td>
                                            <td class="text-center"><i class="bi bi-arrow-right"></i></td>
                                            <td class="text-danger">
                                                <?= $row->nama_di_nasabah ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Duplicate Nasabah Section -->
            <?php if (count($duplicate_nasabah) > 0): ?>
                <div class="card border-info mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Nasabah Duplikat (
                            <?= count($duplicate_nasabah) ?>)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Jumlah Duplikat</th>
                                        <th>ID Nasabah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($duplicate_nasabah as $i => $row): ?>
                                        <tr>
                                            <td>
                                                <?= $i + 1 ?>
                                            </td>
                                            <td class="fw-bold">
                                                <?= $row->nama ?>
                                            </td>
                                            <td><span class="badge bg-info">
                                                    <?= $row->jumlah ?>x
                                                </span></td>
                                            <td><code><?= $row->ids ?></code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<script>
    function fixGhostSetoran() {
        if (!confirm('PERINGATAN: Ini akan menghapus semua ghost setoran dan menghitung ulang saldo. Lanjutkan?')) return;

        var btn = document.getElementById('btn-fix-ghost');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';

        $.ajax({
            url: '<?= site_url("rekapitulasi_harian/fix_ghost_setoran") ?>',
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Berhasil!\n- Ghost setoran dihapus: ' + response.deleted + '\n- Saldo dihitung ulang: ' + response.recalculated + ' akun');
                    location.reload();
                } else {
                    alert('Error: ' + (response.error || 'Unknown'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-wrench"></i> Auto-Fix';
                }
            },
            error: function () {
                alert('Terjadi kesalahan');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-wrench"></i> Auto-Fix';
            }
        });
    }

    function fixNameMismatches() {
        if (!confirm('Ini akan update nama nasabah (lama) agar cocok dengan nama dari Excel (benar). Lanjutkan?')) return;

        var btn = document.getElementById('btn-fix-names');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';

        $.ajax({
            url: '<?= site_url("rekapitulasi_harian/fix_name_mismatches") ?>',
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Berhasil! ' + response.message);
                    location.reload();
                } else {
                    alert('Error: ' + (response.error || 'Unknown'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-wrench"></i> Sync Nama';
                }
            },
            error: function () {
                alert('Terjadi kesalahan');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-wrench"></i> Sync Nama';
            }
        });
    }
</script>
