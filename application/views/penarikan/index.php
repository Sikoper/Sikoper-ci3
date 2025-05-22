<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <?php if ($this->session->userdata('level') != 'Direktur') : ?>
                <button class="btn btn-primary" onclick="window.location='<?= base_url('penarikan/add') ?>'">
                    <i class="fa fa-plus-circle"></i> Tambah Data
                </button>
            <?php endif; ?>
        </h4>
    </div>
    <div class="card-body">
        <div class="dataTable-wrapper">
            <div class="dataTable-container">
                <table class="table table-striped" id="tabel_penarikan">
                    <thead>
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th>Nomor Rekening</th>
                            <th>Nama Nasabah</th>
                            <th>Jenis Tabungan</th>
                            <th>Total Penarikan</th>
                            <th width="15%">#</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#tabel_penarikan').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            order: [],
            ajax: {
                url: "<?= site_url('penarikan/fetchData') ?>",
                type: "POST"
            },
            columns: [{
                    data: 'no',
                    orderable: false
                },
                {
                    data: 'no_rekening'
                },
                {
                    data: 'nama_nasabah'
                },
                {
                    data: 'jenis_tabungan'
                },
                {
                    data: 'total_penarikan'
                },
                {
                    data: 'aksi',
                    orderable: false
                }
            ],
            columnDefs: [{
                    targets: 0,
                    className: "text-center"
                },
                {
                    targets: 5,
                    className: "text-center"
                }
            ]
        });
    });

    function deleteItem(id, nama) {
        Swal.fire({
            title: "Hapus data ini?",
            html: `Yakin ingin menghapus data dari <strong>${nama}</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url('penarikan/delete') ?>",
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
                            }).then(() => {
                                $('#tabel_penarikan').DataTable().ajax.reload();
                            });
                        } else {
                            Swal.fire("Gagal", "Data tidak berhasil dihapus.", "error");
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