<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <button class="btn btn-primary" id="btnPembungaan">
                <i class="fa fa-credit-card"></i>
                Pembungaan
            </button>
        </h4>
    </div>
    <div class="card-body">
        <div class="dataTable-wrapper dataTable-loading no-footer sortable searchable fixed-columns">
            <div class="dataTable-container">
                <table class="table table-striped dataTable-table" id="tabel_bunga">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Rekening</th>
                            <th>Nasabah</th>
                            <th>Tanggal bunga</th>
                            <th>Jumlah bunga</th>
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
    table = $('#tabel_bunga').DataTable({
        responsive: true,
        "destroy": true,
        "processing": true,
        "serverSide": true,
        "order": [],
        autoWidth: false,

        "ajax": {
            "url": "<?= site_url('bunga/fetchData') ?>",
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
            html: `Yakin ingin menghapus bunga dari no. rekening: <strong>${nama}</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('bunga/delete') ?>",
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

    $(document).ready(function() {
        $('#btnPembungaan').click(function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: "<?= base_url('bunga/run_bunga') ?>",
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
                            icon: "error"
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
        });
    });
</script>