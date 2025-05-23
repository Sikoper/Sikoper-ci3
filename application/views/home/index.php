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
                        <h6 class="font-extrabold mb-0">10</h6>
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
                        <h6 class="font-extrabold mb-0">100</h6>
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
                        <h6 class="font-extrabold mb-0">5</h6>
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
                        <h6 class="font-extrabold mb-0">11</h6>
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
                <div id="chart-profile-visit" style="min-height: 315px;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    const options = {
        chart: {
            type: 'bar',
            height: 300
        },
        series: [{
                name: 'Jumlah Setoran',
                data: [20, 25, 18, 22, 30, 28, 24, 26, 29, 23, 27, 31],
                color: '#007bff'
            },
            {
                name: 'Jumlah Penarikan',
                data: [15, 18, 12, 20, 22, 19, 17, 16, 21, 20, 18, 25],
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

    const chart = new ApexCharts(document.querySelector("#chart-profile-visit"), options);
    chart.render();
</script>