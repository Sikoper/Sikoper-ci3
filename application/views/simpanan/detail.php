<div class="row">
    <!-- Simpanan Info Card (Top) -->
    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white"><i class="fa fa-id-card"></i> Informasi Simpanan</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th>No. Rekening</th>
                        <td>: <?= $simpanan->no_rekening ?></td>
                    </tr>
                    <tr>
                        <th>Nasabah</th>
                        <td>: <?= $nasabah->nama_lengkap ?? '-' ?></td>
                    </tr>
                    <tr>
                        <th>Pegawai yang mendata</th>
                        <td>: <?= $pegawai->nama_lengkap ?? '-' ?></td>
                    </tr>
                    <tr>
                        <th>Jenis Tabungan</th>
                        <td>: <?= $jenis->nama ?? '-' ?></td>
                    </tr>
                    <tr>
                        <th>Total Simpanan</th>
                        <td>: Rp <?= number_format($simpanan->jumlah_simpanan, 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Simpanan</th>
                        <td>: <?= date('d-m-Y', strtotime($simpanan->tanggal_simpanan)) ?></td>
                    </tr>
                    <tr>
                        <th>Durasi</th>
                        <td>: <?= format_durasi($simpanan->durasi) ?></td>
                    </tr>
                    <tr>
                        <th>Ahli Waris</th>
                        <td>: <?= $simpanan->nama_ahli_waris ?> (<?= $simpanan->hubungan_ahli_waris ?> dari deposan), <?= $simpanan->telp_ahli_waris ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            : <span class="badge 
                                <?= $simpanan->status == 'aktif' ? 'bg-success' : ($simpanan->status == 'nonaktif' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                <?= ucfirst($simpanan->status) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Detail Simpanan Table (Bottom) -->
    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white"><i class="fa fa-list"></i> Detail Simpanan</h5>
                <button class="btn btn-primary" onclick="window.location='<?= base_url('setoran') ?>'"><i class="fa fa-circle-plus"></i> Tambah Data</button>
            </div>
            <div class="card-body">
                <div class="dataTable-wrapper dataTable-loading no-footer sortable searchable fixed-columns">
                    <div class="dataTable-container">
                        <table class="table table-bordered table-striped" id="detail_simpanan">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Pegawai</th>
                                    <th>#</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="dataTable-bottom">
                        <ul class="pagination pagination-primary float-end dataTable-pagination">

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    table = $('#detail_simpanan').DataTable({
        responsive: true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [],
        autoWidth: false,

        "ajax": {
            "url": "<?= site_url('setoran/fetchData') ?>",
            "type": "POST"
        },

        "columns": [{
                "type": "string"
            },
            {
                "type": "string"
            },
            {
                "type": "string"
            },
            {
                "type": "string"
            },
            {
                "orderable": false
            }
        ],

        "columnDefs": [{
                "targets": 0,
                "orderable": false,
                "width": "5%"
            },
            {
                "targets": 4,
                "orderable": false,
                "width": "15%"
            }
        ],
    });
</script>