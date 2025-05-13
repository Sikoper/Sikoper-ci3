<div class="card">
    <div class="card-header">
        <button class="btn btn-warning" onclick="window.location='<?= base_url('pegawai') ?>'">
            <i class="fa fa-backward"></i> Kembali
        </button>
    </div>

    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label><strong>NIK:</strong></label>
                <div><?= $pegawai->nik ?></div>
            </div>
            <div class="col-md-6">
                <label><strong>Nama Lengkap:</strong></label>
                <div><?= $pegawai->nama_lengkap ?></div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label><strong>Jenis Kelamin:</strong></label>
                <div><?= $pegawai->jenis_kelamin ?></div>
            </div>
            <div class="col-md-6">
                <label><strong>Tempat, Tanggal Lahir:</strong></label>
                <div><?= $pegawai->tempat_lahir . ', ' . date('d-m-Y', strtotime($pegawai->tanggal_lahir)) ?></div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label><strong>Agama:</strong></label>
                <div><?= $pegawai->agama ?></div>
            </div>
            <div class="col-md-6">
                <label><strong>No. Telepon:</strong></label>
                <div><?= $pegawai->telp ?></div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label><strong>Alamat Lengkap:</strong></label>
                <div><?= $pegawai->alamat ?>, Desa <?= $nama_desa ?>, Kecamatan <?= $nama_kecamatan ?>, <br /> Kabupaten <?= $nama_kabupaten ?>, Provinsi <?= $nama_provinsi ?></div>
            </div>
            <div class="col-md-6">
                <label><strong>Jabatan:</strong></label>
                <div><?= $pegawai->jabatan ?></div>
            </div>
        </div>
        <div class="text-muted">
            <small>Terdaftar sejak: <?= date('d M Y, H:i', strtotime($pegawai->created_at)) ?></small>
        </div>
    </div>