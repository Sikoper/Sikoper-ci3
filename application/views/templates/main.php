<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sikoper</title>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/css/bootstrap.css">

    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/css/app.css">
    <link rel="shortcut icon" href="<?= base_url('assets') ?>/images/favicon.svg" type="image/x-icon">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendors/datatables/datatables.min.css">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendors/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendors/select2/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<script src="<?= base_url('assets') ?>/vendors/jquery/jquery.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="<?= base_url('assets') ?>/vendors/datatables/datatables.min.js"></script>
<script src="<?= base_url('assets') ?>/vendors/sweetalert2/sweetalert2.all.min.js"></script>
<script src="<?= base_url('assets') ?>/vendors/autonumeric.js/autoNumeric.js"></script>
<script src="<?= base_url('assets') ?>/vendors/select2/js/select2.min.js"></script>

<body>
    <?php
    $level = $this->session->userdata('level');
    $nama = $this->session->userdata('nama');
    ?>
    <div id="app">
        <div id="sidebar" class="active">
            <div class="sidebar-wrapper active">
                <div class="sidebar-header">
                    <div class="d-flex justify-content-between">
                        <div class="logo">
                            <a href="index.html">Sikoper</a>
                        </div>
                        <div class="toggler">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>
                <div class="sidebar-menu">
                    <ul class="menu">
                        <li class="sidebar-title">Menu</li>

                        <li class="sidebar-item <?= $this->uri->segment(1) == '' || $this->uri->segment(1) == 'dashboard' ? 'active' : '' ?>">
                            <a href="<?= base_url('/') ?>" class='sidebar-link'>
                                <i class="bi bi-grid-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <?php if ($level == 'Admin' || $level == 'Pegawai' || $level == 'Direktur'): ?>
                            <li class="sidebar-item has-sub <?= in_array($this->uri->segment(1), ['setoran', 'penarikan', 'bunga']) ? 'active' : '' ?>">
                                <a href="#" class='sidebar-link'>
                                    <i class="bi bi-cash-stack"></i>
                                    <span>Transaksi</span>
                                </a>
                                <ul class="submenu <?= in_array($this->uri->segment(1), ['setoran', 'penarikan', 'bunga']) ? 'active' : '' ?>">
                                    <li class="submenu-item <?= $this->uri->segment(1) == 'setoran' ? 'active' : '' ?>">
                                        <a href="<?= base_url('setoran') ?>">Setoran Tunai</a>
                                    </li>
                                    <li class="submenu-item <?= $this->uri->segment(1) == 'penarikan' ? 'active' : '' ?>">
                                        <a href="<?= base_url('penarikan') ?>">Penarikan Tunai</a>
                                    </li>
                                    <li class="submenu-item <?= $this->uri->segment(1) == 'bunga' ? 'active' : '' ?>">
                                        <a href="<?= base_url('bunga') ?>">Proses Bunga</a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <?php if ($level == 'Admin' || $level == 'Pegawai' || $level == 'Direktur'): ?>
                            <li class="sidebar-item has-sub <?= in_array($this->uri->segment(1), ['simpanan', 'jenis_tabungan', 'nasabah', 'pegawai']) ? 'active' : '' ?>">
                                <a href="#" class='sidebar-link'>
                                    <i class="bi bi-server"></i>
                                    <span>Data Master</span>
                                </a>
                                <ul class="submenu <?= in_array($this->uri->segment(1), ['simpanan', 'jenis_tabungan', 'nasabah', 'pegawai']) ? 'active' : '' ?>">
                                    <?php if ($level == 'Admin' || $level == 'Pegawai' || $level == 'Direktur'): ?>
                                        <li class="submenu-item <?= $this->uri->segment(1) == 'simpanan' ? 'active' : '' ?>">
                                            <a href="<?= base_url('simpanan') ?>">Data Simpanan</a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($level == 'Admin' || $level == 'Direktur'): ?>
                                        <li class="submenu-item <?= $this->uri->segment(1) == 'jenis_tabungan' ? 'active' : '' ?>">
                                            <a href="<?= base_url('jenis_tabungan') ?>">Produk Simpanan</a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($level == 'Admin' || $level == 'Pegawai'): ?>
                                        <li class="submenu-item <?= $this->uri->segment(1) == 'nasabah' ? 'active' : '' ?>">
                                            <a href="<?= base_url('nasabah') ?>">Data Nasabah</a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($level == 'Admin' || $level == 'Direktur'): ?>
                                        <li class="submenu-item <?= $this->uri->segment(1) == 'pegawai' ? 'active' : '' ?>">
                                            <a href="<?= base_url('pegawai') ?>">Data Pegawai</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <?php if ($level == 'Admin'): ?>
                            <li class="sidebar-item has-sub <?= in_array($this->uri->segment(1), ['users']) ? 'active' : '' ?>">
                                <a href="#" class='sidebar-link'>
                                    <i class="bi bi-person-badge-fill"></i>
                                    <span>Administrasi</span>
                                </a>
                                <ul class="submenu <?= in_array($this->uri->segment(1), ['users']) ? 'active' : '' ?>">
                                    <li class="submenu-item <?= $this->uri->segment(1) == 'users' ? 'active' : '' ?>">
                                        <a href="<?= base_url('users') ?>">Pengguna Sistem</a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
            </div>
        </div>
        <div id="main" class='layout-navbar'>
            <header class='mb-3'>
                <nav class="navbar navbar-expand navbar-light ">
                    <div class="container-fluid">
                        <a href="#" class="burger-btn d-block">
                            <i class="bi bi-justify fs-3"></i>
                        </a>

                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                            aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                                <li class="nav-item dropdown me-3">
                                    <a class="nav-link active dropdown-toggle" href="#" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class='bi bi-bell bi-sub fs-4 text-gray-600'></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <h6 class="dropdown-header">Notifications</h6>
                                        </li>
                                        <li>
                                            <a class="dropdown-item">
                                                No notification available
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                            <div class="dropdown">
                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-menu d-flex">
                                        <div class="user-name text-end me-3">
                                            <h6 class="mb-0 text-gray-600"><?= $nama ?></h6>
                                            <p class="mb-0 text-sm text-gray-600"><?= $level ?></p>
                                        </div>
                                        <div class="user-img d-flex align-items-center">
                                            <div class="avatar avatar-md">
                                                <img src="<?= base_url('assets') ?>/images/faces/1.jpg">
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                    <li>
                                        <a class="dropdown-item" href="#"><i class="icon-mid bi bi-person me-2"></i> My
                                            Profile
                                        </a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="#" id="logout">
                                            <i class="icon-mid bi bi-box-arrow-left me-2"></i> Log out
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>
            <div id="main-content">

                <div class="page-heading">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-12 col-md-6 order-md-1 order-last">
                                <h3>{judul}</h3>
                            </div>
                        </div>
                    </div>
                    <section class="section">
                        {isi}
                    </section>
                </div>

                <footer>
                    <div class="footer clearfix mb-0 text-muted">
                        <div class="float-start">
                            <p>2025 &copy; Politeknik Negeri Bali</p>
                        </div>
                        <div class="float-end">
                            <p>V 1.0.0</p>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
    <script>
        $('#logout').click(function(e) {
            e.preventDefault();

            Swal.fire({
                icon: "warning",
                title: "Apakah anda yakin ingin logout?",
                showCancelButton: true,
                confirmButtonText: "Ya, logout",
                cancelButtonText: "Batal",
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger m-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "<?= site_url('auth/logout') ?>";
                }
            });
        });
    </script>
    <script src="<?= base_url('assets') ?>/js/main.js"></script>
    <script src="<?= base_url('assets') ?>/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= base_url('assets') ?>/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>

</html>