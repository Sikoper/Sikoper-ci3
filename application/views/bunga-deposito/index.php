<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:transparent; z-index:9999; pointer-events:all;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center; backdrop-filter:blur(3px); padding:1rem 2rem; border-radius:1rem;">
        <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
        <p style="margin-top: 1rem; color:#333;">Memproses perhitungan bunga deposito...</p>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h4 class="card-title">
            <?php if ($level == 'Admin'): ?>
                <button class="btn btn-success" id="btnPembungaanDeposito">
                    <i class="fa fa-credit-card"></i>
                    Hitung Bunga Deposito
                </button>
            <?php endif; ?>
        </h4>
        <div class="row mb-3">
            <div class="col-md-3">
                <label>Dari Tanggal</label>
                <input type="date" id="start_date" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-3">
                <label>Sampai Tanggal</label>
                <input type="date" id="end_date" class="form-control" value="<?= date('Y-m-t') ?>">
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button id="filterBtn" class="btn btn-info form-control">Filter</button>
            </div>
            <div class="col-md-3 text-end">
                <h5>Total Bunga Deposito: <span id="total_bunga_display">Rp 0</span></h5>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="dataTable-wrapper dataTable-loading no-footer sortable searchable fixed-columns">
            <div class="dataTable-container">
                <table class="table table-striped dataTable-table" id="tabel_bunga">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nasabah</th>
                            <th>No. Rekening</th>
                            <th>Tanggal bunga</th>
                            <th>Jumlah bunga</th>
                            <th>Presentase Bunga</th>
                            <?php if ($level == 'Admin'): ?>
                                <th>#</th>
                            <?php endif; ?>
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
    function setDefaultDateRange() {
        const today = new Date();
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

        // Format to yyyy-mm-dd
        const formatDate = (date) => {
            let m = String(date.getMonth() + 1).padStart(2, '0');
            let d = String(date.getDate()).padStart(2, '0');
            return `${date.getFullYear()}-${m}-${d}`;
        };

        $('#start_date').val(formatDate(startOfMonth));
        $('#end_date').val(formatDate(today));
    }

    $(document).ready(function() {
        const userLevel = '<?= $this->session->userdata('level') ?>';

        const columns = [{
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
        ];

        // Add admin-only column
        if (userLevel === 'Admin') {
            columns.push({
                "orderable": false
            });
        }

        const columnDefs = [{
                "targets": 0,
                "orderable": false,
                "width": "5%"
            },
            {
                "targets": 5,
                "orderable": false,
                "width": "15%"
            }
        ];

        // Add a columnDef for the last column if admin
        if (userLevel === 'admin') {
            columnDefs.push({
                "targets": 7,
                "orderable": false,
                "width": "10%",
                "className": "text-center"
            });
        }

        var table = $('#tabel_bunga').DataTable({
            responsive: true,
            destroy: true,
            processing: true,
            serverSide: true,
            order: [],
            autoWidth: false,

            ajax: {
                url: "<?= site_url('bunga_deposito/fetchBungaDeposito') ?>",
                type: "POST",
                data: function(d) {
                    d.start_date = $('#start_date').val() || '';
                    d.end_date = $('#end_date').val() || '';
                },
                dataSrc: function(json) {
                    const total = json.total_bunga !== undefined ? json.total_bunga : '0';
                    $('#total_bunga_display').text('Rp ' + total);
                    return json.data || [];
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    $('#total_bunga_display').text('Rp 0');
                }
            },

            columns: columns,
            columnDefs: columnDefs
        });

        $('#filterBtn').on('click', function() {
            table.ajax.reload(null, false);
        });
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
                    url: "<?= base_url('bunga_deposito/delete') ?>",
                    data: {
                        id: id
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            Swal.fire("Success!", response.success, "success").then(() => {
                                window.location.reload();
                            });
                        } else if (response.error) {
                            Swal.fire("Gagal", response.error, "error");
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
        $('#btnPembungaanDeposito').click(function() {
            $.ajax({
                type: "POST",
                url: "<?= site_url('bunga_deposito/run_bunga_deposito') ?>",
                dataType: "json",
                processData: false,
                contentType: false,
                cache: false,
                beforeSend: function() {
                    $('#btnPembungaanDeposito').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i>');
                    $('#loadingOverlay').fadeIn();
                },
                complete: function() {
                    $('#btnPembungaanDeposito').prop('disabled', false).html('Hitung Bunga Deposito');
                    $('#loadingOverlay').fadeOut();
                },
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
                    } else if (response.error) {
                        Swal.fire({
                            title: "Error!",
                            text: response.error,
                            icon: "error"
                        }).then((result) => {
                            if (result.isConfirmed) {}
                        });
                    } else {
                        Swal.fire({
                            title: "Tidak ada!",
                            text: response.empty,
                            icon: "warning"
                        }).then((result) => {
                            if (result.isConfirmed) {}
                        });
                    }
                },
                error: function(xhr, status, error) {
                    alert("Error: " + xhr.responseText);
                }
            });
        });
    });
</script>