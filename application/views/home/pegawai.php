<div class="page-content">
    <section class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="mb-3">Silakan gunakan menu di bawah ini untuk mengakses fitur-fitur yang tersedia:</p>

                    <div class="row">
                        <!-- Transaksi Buttons -->
                        <div class="col-md-6 mb-3">
                            <h5>Transaksi</h5>
                            <a href="<?= base_url('setoran') ?>" class="btn btn-success mb-2 w-100">
                                <i class="bi bi-plus-circle"></i> Setoran Tunai
                            </a>
                            <a href="<?= base_url('penarikan') ?>" class="btn btn-danger mb-2 w-100">
                                <i class="bi bi-dash-circle"></i> Penarikan Tunai
                            </a>
                            <a href="<?= base_url('pencairan') ?>" class="btn btn-warning mb-2 w-100">
                                <i class="bi bi-cash-coin"></i> Pencairan Deposito
                            </a>
                            <a href="<?= base_url('bunga') ?>" class="btn btn-primary mb-2 w-100">
                                <i class="bi bi-percent"></i> Proses Bunga
                            </a>
                        </div>

                        <!-- Data Master Buttons -->
                        <div class="col-md-6 mb-3">
                            <h5>Data Master</h5>
                            <a href="<?= base_url('nasabah') ?>" class="btn btn-outline-dark mb-2 w-100">
                                <i class="bi bi-people-fill"></i> Data Nasabah
                            </a>
                            <a href="<?= base_url('simpanan') ?>" class="btn btn-outline-primary mb-2 w-100">
                                <i class="bi bi-wallet2"></i> Data Tabungan
                            </a>
                            <a href="<?= base_url('deposito') ?>" class="btn btn-outline-success mb-2 w-100">
                                <i class="bi bi-bank"></i> Data Deposito
                            </a>
                        </div>
                    </div>

                    <p class="mt-4 text-muted">Jika Anda mengalami kendala, silakan hubungi Administrator.</p>
                </div>
            </div>
        </div>
    </section>
</div>