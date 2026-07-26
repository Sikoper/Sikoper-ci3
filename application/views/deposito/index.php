<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <button class="btn btn-primary" onclick="window.location='<?= base_url('deposito/add') ?>'">
                <i class="fa fa-plus-circle"></i> Buka Deposito Baru
            </button>
            <?php if ($this->session->userdata('level') == 'Admin'): ?>
                <button class="btn btn-success ms-2" onclick="window.location='<?= base_url('deposito/import') ?>'">
                    <i class="fa fa-upload"></i> Import Excel
                </button>
            <?php endif; ?>
        </h4>
    </div>
    <div class="card-body">
        <div class="dataTable-wrapper dataTable-loading no-footer sortable searchable fixed-columns">
            <div class="dataTable-container">
                <table class="table table-striped dataTable-table" id="tabel_deposito">
                    <thead>
                        <tr>
                            <th class="text-center" width="40px">No</th>
                            <th>Nama Nasabah</th>
                            <th>Nomer Rekening</th>
                            <th>Nomer Telepon</th>
                            <th>Jumlah Saldo</th>
                            <th>Status</th>
                            <th>Aksi</th>
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
    </div>
</div>
<script src="<?= base_url('assets') ?>/vendors/pusher/pusher.min.js"></script>
<script>
    table = $('#tabel_deposito').DataTable({
        responsive: true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [],
        autoWidth: false,

        "ajax": {
            "url": "<?= site_url('deposito/fetchData') ?>",
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
            "targets": 1,
            "width": "15%"
        },
        {
            "targets": 2,
            "width": "10%"
        },
        {
            "targets": 3,
            "orderable": false,
            "width": "5%"
        },
        {
            "targets": 5,
            "width": "10%",
        },
        {
            "targets": 6,
            "width": "15%",
            "orderable": false
        },
        {
            "targets": 7,
            "orderable": false,
            "width": "15%"
        }
        ],
    });

    function deleteItem(id, nama) {
        Swal.fire({
            title: "Hapus data ini?",
            html: `Yakin ingin menghapus rekening dari <strong>${nama}</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('deposito/delete') ?>",
                    data: {
                        id: id
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                title: "Success!",
                                text: response.success,
                                icon: "success"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "Error!",
                                text: response.error,
                                icon: "warning"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        }
                    },
                    error: function (xhr, thrownError) {
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                    }
                });
            }
        });
    }

    function printSertifikat(id, nama) {
        Swal.fire({
            title: "Cetak Sertifikat?",
            html: `Yakin ingin mencetak sertifikat untuk <strong>${nama}</strong>?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#17a2b8",
            confirmButtonText: "Ya, Cetak!",
            cancelButtonText: "Batal",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("<?= base_url('deposito/print_sertifikat/') ?>" + id, "_blank");
            }
        });
    }

    Pusher.logToConsole = false;

    var pusher = new Pusher('a110c1ed036f6f6bf451', {
        cluster: 'ap1'
    });

    var channel = pusher.subscribe('deposito-channel');
    channel.bind('deposito-event', function (data) {
        table.ajax.reload(null, false);
    });
</script>