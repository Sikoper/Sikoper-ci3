<div class="row">
    <div class="col-6 col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body px-4 py-4-5">
                <div class="row">
                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                        <div class="stats-icon text-white purple mb-2">
                            <i class="fa fa-user fa-fw"></i>
                        </div>
                    </div>
                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                        <h6 class="text-muted font-semibold">Jumlah Pegawai</h6>
                        <h6 class="font-extrabold mb-0" id="jumlah_pegawai"></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body px-4 py-4-5">
                <div class="row">
                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                        <div class="stats-icon text-white blue mb-2">
                            <i class="fa fa-users fa-fw"></i>
                        </div>
                    </div>
                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                        <h6 class="text-muted font-semibold">Jumlah Nasabah</h6>
                        <h6 class="font-extrabold mb-0" id="jumlah_nasabah"></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body px-4 py-4-5">
                <div class="row">
                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                        <div class="stats-icon text-white green mb-2">
                            <i class="fa fa-money-check fa-fw"></i>
                        </div>
                    </div>
                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                        <h6 class="text-muted font-semibold">Setoran Baru</h6>
                        <h6 class="font-extrabold mb-0" id="setoran_baru"></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body px-4 py-4-5">
                <div class="row">
                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                        <div class="stats-icon text-white red mb-2">
                            <i class="fa fa-credit-card fa-fw"></i>
                        </div>
                    </div>
                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                        <h6 class="text-muted font-semibold">Penarikan</h6>
                        <h6 class="font-extrabold mb-0" id="penarikan_baru"></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>Jumlah Transaksi per Bulan</h4>
            </div>
            <div class="card-body">
                <div id="transaksi_bulanan" style="min-height: 315px;"></div>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets') ?>/vendors/apexcharts/apexcharts.min.js"></script>
<script>
    $(document).ready(function() {
        $.ajax({
            type: "POST",
            "url": "<?= site_url('dashboard/fetchData') ?>",
            dataType: "json",
            success: function(response) {
                const options = {
                    chart: {
                        type: 'bar',
                        height: 300
                    },
                    series: [{
                            name: 'Jumlah Setoran',
                            data: response.setoran,
                            color: '#007bff'
                        },
                        {
                            name: 'Jumlah Penarikan',
                            data: response.penarikan,
                            color: '#dc3545'
                        }
                    ],
                    xaxis: {
                        categories: [
                            'Januari', 'Februari', 'Maret', 'April',
                            'Mei', 'Juni', 'Juli', 'Agustus',
                            'September', 'Oktober', 'November', 'Desember'
                        ],
                        title: {
                            text: 'Bulan'
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Jumlah Transaksi'
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '50%',
                            endingShape: 'rounded'
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: val => `${val} Transaksi`
                        }
                    },
                    dataLabels: {
                        enabled: true
                    }
                };

                $('#jumlah_pegawai').text(response.jumlah_pegawai);
                $('#jumlah_nasabah').text(response.jumlah_nasabah);
                $('#setoran_baru').text(response.setoran_baru);
                $('#penarikan_baru').text(response.penarikan_baru);

                const chart = new ApexCharts($("#transaksi_bulanan")[0], options);
                chart.render();
            }
        });
    });
</script>