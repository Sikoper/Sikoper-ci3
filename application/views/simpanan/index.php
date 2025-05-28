<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <button class="btn btn-primary" onclick="window.location='<?= base_url('simpanan/add') ?>'">
                <i class="fa fa-plus-circle"></i> Tambah Data
            </button>
        </h4>
    </div>
    <div class="card-body">
        <div class="dataTable-wrapper dataTable-loading no-footer sortable searchable fixed-columns">
            <div class="dataTable-container">
                <table class="table table-striped dataTable-table" id="tabel_simpanan">
                    <thead>
                        <tr>
                            <th class="text-center" width="40px">No</th>
                            <th>Nama Nasabah</th>
                            <th>Nomer Rekening</th>
                            <th>Nomer Telepon</th>
                            <th>Jenis Tabungan</th>
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
<script>
    table = $('#tabel_simpanan').DataTable({
        responsive: true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [],
        autoWidth: false,

        "ajax": {
            "url": "<?= site_url('simpanan/fetchData') ?>",
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

    function deleteItem(id, nama) {
        Swal.fire({
            title: "Hapus data ini?",
            html: `Yakin ingin menghapus rekening dari <strong>${nama}</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('simpanan/delete') ?>",
                    data: {
                        id: id
                    },
                    dataType: "json",
                    success: function(response) {
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
                    error: function(xhr, thrownError) {
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                    }
                });
            }
        });
    }

    function print(id, nama) {
        Swal.fire({
            title: "Print data ini?",
            html: `Yakin ingin print data dari <strong>${nama}</strong>?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
        }).then((result) => {
            if (result.isConfirmed) {
                window.open("<?= base_url('simpanan/print_nasabah?id=') ?>" + id, "_blank");
                window.location.reload();
            }
        });
    }
</script>