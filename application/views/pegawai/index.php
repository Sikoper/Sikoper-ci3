<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <button class="btn btn-primary" onclick="window.location='<?= base_url('pegawai/add') ?>'">
                <i class="fa fa-plus-circle"></i> Tambah Data
            </button>
        </h4>
    </div>
    <div class="card-body">
        <table class="table table-striped dataTable-table" id="tabel_pegawai">
            <thead>
                <tr>
                    <th class="text-center" width="40px">No</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    <th>Nomer Telepon</th>
                    <th>Jabatan</th>
                    <th>#</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
    <div class="dataTable-bottom">
        <ul class="pagination pagination-primary float-end dataTable-pagination">

        </ul>
    </div>
</div>
<script>
    table = $('#tabel_pegawai').DataTable({
        responsive: true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [],
        autoWidth: false,

        "ajax": {
            "url": "<?= site_url('pegawai/fetchData') ?>",
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
                "targets": 5,
                "orderable": false,
                "width": "15%"
            }
        ],
    });
</script>