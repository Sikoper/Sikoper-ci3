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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
    <script src="<?= base_url('assets') ?>/vendors/jquery/jquery.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="<?= base_url('assets') ?>/vendors/datatables/datatables.min.js"></script>
    <script src="<?= base_url('assets') ?>/vendors/sweetalert2/sweetalert2.all.min.js"></script>
<body>
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

                        <li class="sidebar-item <?= $this->uri->segment(1) == '' ? 'active' : '' ?>">
                            <a href="<?= base_url('/') ?>" class='sidebar-link'>
                                <i class="bi bi-grid-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="sidebar-item <?= $this->uri->segment(1) == 'pegawai' ? 'active' : '' ?>">
                            <a href="<?= base_url('pegawai') ?>" class='sidebar-link'>
                                <i class="fa fa-users"></i>
                                <span>Pegawai</span>
                            </a>
                        </li>

                        <li class="sidebar-item <?= $this->uri->segment(1) == 'users' ? 'active' : '' ?>">
                            <a href="<?= base_url('users') ?>" class='sidebar-link'>
                                <i class="fa fa-user-lock"></i>
                                <span>User</span>
                            </a>
                        </li>

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
                                            <h6 class="mb-0 text-gray-600">John Ducky</h6>
                                            <p class="mb-0 text-sm text-gray-600">Administrator</p>
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

                                    <li><a class="dropdown-item" href="#">
                                            <i class="icon-mid bi bi-box-arrow-left me-2"></i> Logout</a>
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
    <script src="<?= base_url('assets') ?>/js/main.js"></script>
    <script src="<?= base_url('assets') ?>/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= base_url('assets') ?>/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>

</html>