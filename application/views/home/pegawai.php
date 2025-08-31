<div class="page-content">
    <section class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="mb-3">Silakan gunakan menu di bawah ini untuk mengakses fitur-fitur yang tersedia:</p>

                    <div class="card-body p-4">
                        <h5 class="card-title pb-3 border-bottom mb-4">
                            <i class="bi bi-lightning-charge-fill me-2"></i>Menu Utama Pegawai
                        </h5>

                        <p class="text-muted small fw-bold mb-3">TRANSAKSI KEUANGAN</p>
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= base_url('setoran') ?>" class="btn btn-success btn-lg w-100 p-3">
                                    <i class="bi bi-arrow-down-circle me-2"></i> Setor Tunai
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= base_url('penarikan') ?>" class="btn btn-danger btn-lg w-100 p-3">
                                    <i class="bi bi-arrow-up-circle me-2"></i> Tarik Tunai
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= base_url('Deposito/add') ?>" class="btn btn-success btn-lg w-100 p-3">
                                    <i class="bi bi-arrow-down-circle me-2"></i> Setor Deposito
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= base_url('Pencairan') ?>" class="btn btn-danger btn-lg w-100 p-3">
                                    <i class="bi bi-arrow-up-circle me-2"></i> Cairkan Deposito
                                </a>
                            </div>
                        </div>

                        <hr class="my-4">

                        <p class="text-muted small fw-bold mb-3">PENDAFTARAN & PEMBUKAAN REKENING</p>
                        <div class="row">
                            <div class="col-lg-4 col-md-12 mb-3">
                                <a href="<?= base_url('nasabah/add') ?>" class="btn btn-primary btn-lg w-100 p-3">
                                    <i class="bi bi-person-plus-fill me-2"></i> Tambah Nasabah
                                </a>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <a href="<?= base_url('simpanan/add') ?>" class="btn btn-info btn-lg w-100 p-3">
                                    <i class="bi bi-journal-plus me-2"></i> Buka Tabungan
                                </a>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <a href="<?= base_url('deposito/add') ?>" class="btn btn-secondary btn-lg w-100 p-3">
                                    <i class="bi bi-wallet2 me-2"></i> Buka Deposito
                                </a>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-muted">Jika Anda mengalami kendala, silakan hubungi Administrator.</p>
                </div>
            </div>
        </div>
    </section>
</div>