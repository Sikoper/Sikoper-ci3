<section class="section">
    <div class="card">
        <div class="card-header">
        </div>
        <div class="card-body">
            <!-- Date Picker Section -->
            <div class="row justify-content-center mb-4">
                <div class="col-md-4 col-lg-3">
                    <label for="filterTanggal" class="form-label fw-bold">Pilih Tanggal:</label>
                    <input type="date" id="filterTanggal" class="form-control form-control-lg text-center"
                        value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <!-- Summary Section -->
            <div class="recap-summary text-center my-5">
                <h5>Ringkasan Transaksi</h5>
                <h1 id="displayTanggal" class="display-4 text-primary fw-bold mb-3"><?= date('d F Y') ?></h1>
                <div class="row justify-content-center g-3">
                    <!-- Total Setoran Card -->
                    <div class="col-md-5 col-lg-4">
                        <div class="total-balance-card"
                            style="border-radius: 15px; overflow: hidden; border: 1px solid #dee2e6;">
                            <div class="p-4 bg-light-success">
                                <h6 class="text-muted">Total Setoran Hari Ini</h6>
                                <h3 id="totalSetoran" class="fw-bold">Rp 0</h3>
                            </div>
                        </div>
                    </div>
                    <!-- Total Penarikan Card -->
                    <div class="col-md-5 col-lg-4">
                        <div class="total-balance-card"
                            style="border-radius: 15px; overflow: hidden; border: 1px solid #dee2e6;">
                            <div class="p-4 bg-light-danger">
                                <h6 class="text-muted">Total Penarikan Hari Ini</h6>
                                <h3 id="totalPenarikan" class="fw-bold">Rp 0</h3>
                            </div>
                        </div>
                    </div>
                    <!-- Saldo Akhir -->
                    <div class="col-md-10 col-lg-4 mt-lg-3 mt-md-3">
                        <div class="total-balance-card"
                            style="border-radius: 15px; overflow: hidden; border: 1px solid #dee2e6;">
                            <div class="p-4 bg-light-primary">
                                <h6 class="text-muted">Saldo Akhir Hari Ini</h6>
                                <h3 id="saldoAkhir" class="fw-bold">Rp 0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Tables Section -->
            <div class="row mt-5">
                <!-- Deposit Table -->
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-box-arrow-in-down me-2 text-success"></i>Detail Setoran
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="setoranTable" class="table table-striped table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="text-center">No.</th>
                                            <th>Waktu</th>
                                            <th class="text-end">Jumlah</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Withdrawal Table -->
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-box-arrow-up me-2 text-danger"></i>Detail Penarikan</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="penarikanTable" class="table table-striped table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="text-center">No.</th>
                                            <th>Waktu</th>
                                            <th class="text-end">Jumlah</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    $(document).ready(function () {

        /**
         * Parses currency strings (e.g., "Rp 1.500.000") into numbers.
         */
        function parseCurrency(string) {
            // Remove all non-digit characters and parse as an integer.
            // Returns 0 if the input is invalid.
            return parseInt(String(string).replace(/\D/g, ''), 10) || 0;
        }

        /**
         * Calculates the closing balance and updates the summary card.
         */
        function updateSaldoAkhir() {
            const totalSetoran = parseCurrency($('#totalSetoran').text());
            const totalPenarikan = parseCurrency($('#totalPenarikan').text());
            const saldoAkhir = totalSetoran - totalPenarikan;

            // Format the result as Indonesian Rupiah and update the display.
            const formattedSaldo = 'Rp ' + saldoAkhir.toLocaleString('id-ID');
            $('#saldoAkhir').text(formattedSaldo);
        }

        /**
         * Formats date to Indonesian format (dd MMMM yyyy)
         */
        function formatTanggalIndonesia(dateString) {
            const bulan = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            const date = new Date(dateString);
            const day = date.getDate().toString().padStart(2, '0');
            const month = bulan[date.getMonth()];
            const year = date.getFullYear();
            return `${day} ${month} ${year}`;
        }

        /**
         * Updates the display date header
         */
        function updateDisplayTanggal() {
            const tanggal = $('#filterTanggal').val();
            $('#displayTanggal').text(formatTanggalIndonesia(tanggal));
        }

        // Initialize DataTable for Deposits (Setoran)
        var setoranTable = $('#setoranTable').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [],
            "ajax": {
                "url": "<?= site_url('rekapitulasi_harian/fetch_setoran_data') ?>",
                "type": "POST",
                "data": function (d) {
                    d.tanggal = $('#filterTanggal').val();
                },
                "dataSrc": function (json) {
                    $('#totalSetoran').text(json.total_setoran);
                    updateSaldoAkhir(); // Recalculate the final balance
                    return json.data;
                },
                "error": function (xhr, error, thrown) {
                    console.error("Setoran AJAX Error:", xhr.responseText);
                }
            },
            "columnDefs": [{
                "targets": [0],
                "orderable": false,
                "className": "text-center"
            }, {
                "targets": [2],
                "className": "text-end"
            }],
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
            }
        });

        // Initialize DataTable for Withdrawals (Penarikan)
        var penarikanTable = $('#penarikanTable').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [],
            "ajax": {
                "url": "<?= site_url('rekapitulasi_harian/fetch_penarikan_data') ?>",
                "type": "POST",
                "data": function (d) {
                    d.tanggal = $('#filterTanggal').val();
                },
                "dataSrc": function (json) {
                    $('#totalPenarikan').text(json.total_penarikan);
                    updateSaldoAkhir(); // Recalculate the final balance
                    return json.data;
                },
                "error": function (xhr, error, thrown) {
                    console.error("Penarikan AJAX Error:", xhr.responseText);
                }
            },
            "columnDefs": [{
                "targets": [0],
                "orderable": false,
                "className": "text-center"
            }, {
                "targets": [2],
                "className": "text-end"
            }],
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
            }
        });

        // Handle date change event
        $('#filterTanggal').on('change', function () {
            updateDisplayTanggal();
            setoranTable.ajax.reload();
            penarikanTable.ajax.reload();
        });
    });
</script>
