<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <button class="btn btn-primary" onclick="window.location='<?= base_url('users/add') ?>'">
                <i class="fa fa-plus-circle"></i> Tambah Data
            </button>
        </h4>
    </div>
    <div class="card-body">
        <div class="dataTable-wrapper dataTable-loading no-footer sortable searchable fixed-columns">
            <div class="dataTable-container">
                <table class="table table-striped dataTable-table" id="tabel_users">
                    <thead>
                        <tr>
                            <th>Nomor</a></th>
                            <th>Nama</a></th>
                            <th>Username</a></th>
                            <th>Level</a></th>
                            <th>#</a></th>
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
    table = $('#tabel_users').DataTable({
        responsive: true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [],
        autoWidth: false,

        "ajax": {
            "url": "<?= site_url('users/fetchData') ?>",
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
                "targets": 2,
                "width": "25%"
            },
            {
                "targets": 4,
                "orderable": false,
                "width": "20%"
            }
        ],
    });

    function deleteItem(id, nama) {
        Swal.fire({
            title: "Hapus data ini?",
            html: `Yakin ingin menghapus data dari <strong>${nama}</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('users/delete') ?>",
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
                        }
                    },
                    error: function(xhr, thrownError) {
                        alert(xhr.status + "\n" + xhr.responseText + "\n" + thrownError);
                    }
                });
            }
        });
    }
</script>