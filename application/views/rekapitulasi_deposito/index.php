<section class="section">
    <div class="card">
        <div class="card-body">
            <div class="row mb-4 align-items-end">
                <div class="col-md-3">
                    <label for="bulan" class="form-label">Bulan</label>
                    <select class="form-select" id="bulan" name="bulan">
                        <option value="all" <?= ('all' == $selected_month) ? 'selected' : '' ?>>Semua Bulan</option>
                        <?php
                        $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                        $current_year_server = date('Y');
                        $current_month_server = date('n');

                        foreach ($months as $num => $name) {
                            $show_month = true;
                            if ($selected_year == $current_year_server && $num > $current_month_server) {
                                $show_month = false;
                            }
                            if ($selected_year > $current_year_server) {
                                $show_month = false;
                            }
                            if ($show_month) {
                                $option_selected = ($num == $selected_month) ? ' selected' : '';
                                echo '<option value="' . $num . '"' . $option_selected . '>' . $name . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tahun" class="form-label">Tahun</label>
                    <select class="form-select" id="tahun" name="tahun">
                        <?php foreach ($years as $year) : ?>
                            <option value="<?= $year ?>" <?= ($year == $selected_year) ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="filterBtn" class="btn btn-primary w-100">Tampilkan</button>
                </div>
            </div>

            <div class="recap-summary text-center my-5">
                <h5>Rekapitulasi Deposito untuk Bulan</h5>
                <h1 id="recapMonthYear" class="display-4 text-primary fw-bold mb-3">...</h1>

                <div class="total-balance-card" style="border-radius: 15px; overflow: hidden; border: 1px solid #dee2e6;">
                    <div class="row g-0">
                        <div class="col-md-6 p-4 bg-light-success">
                            <h6 class="text-muted">Total Saldo Pokok Nasabah</h6>
                            <h3 id="totalSaldoAwal" class="fw-bold">Rp 0</h3>
                        </div>
                        <div class="col-md-6 p-4 bg-light-info">
                            <h6 class="text-muted">Total Bunga Bulan Ini</h6>
                            <h3 id="totalBungaBulanIni" class="fw-bold">Rp 0</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-5">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 id="detailTitle" class="mb-0"><i class="fa fa-users"></i> Detail Saldo Nasabah</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="rekapTable" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center">No.</th>
                                    <th>No. Rekening</th>
                                    <th>Nama Nasabah</th>
                                    <th class="text-end">Saldo Awal Bulan</th>
                                    <th class="text-end">Bunga Bulan Ini</th>
                                    <th class="text-end">Saldo Akhir Bulan</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        var table = $('#rekapTable').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [],
            "ajax": {
                "url": "<?= site_url('rekapitulasi_deposito/fetch_rekapitulasi') ?>",
                "type": "POST",
                "data": function(d) {
                    d.bulan = $('#bulan').val();
                    d.tahun = $('#tahun').val();
                },
                "dataSrc": function(json) {
                    // ✅ MODIFIED: Logic to handle dynamic titles
                    const selectedMonthValue = $('#bulan').val();
                    const monthName = $('#bulan option:selected').text();
                    const year = $('#tahun').val();

                    if (selectedMonthValue === 'all') {
                        // Update titles for the "Entire Year" view
                        $('#recapMonthYear').text('Tahun ' + year);
                        $('#detailTitle').html('<i class="fa fa-users"></i> Detail Saldo Nasabah - Tahun ' + year);
                        $('#totalBungaBulanIni').siblings('h6').text('Total Bunga Tahun Ini');
                    } else {
                        // Update titles for the "Monthly" view
                        $('#recapMonthYear').text(monthName + ' ' + year);
                        $('#detailTitle').html('<i class="fa fa-users"></i> Detail Saldo Nasabah - ' + monthName + ' ' + year);
                        $('#totalBungaBulanIni').siblings('h6').text('Total Bunga Bulan Ini');
                    }

                    // Update summary cards with data from the backend
                    $('#totalSaldoAwal').text(json.total_saldo_awal);
                    $('#totalBungaBulanIni').text(json.total_bunga_bulan_ini);

                    return json.data;
                }
            },
            "columnDefs": [{
                "targets": [0, 5],
                "orderable": false,
            }]
        });

        $('#filterBtn').on('click', function() {
            table.ajax.reload();
        });

        $('#filterBtn').trigger('click');
    });
</script>