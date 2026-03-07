@extends('layouts.app')

@section('content')

<style>
    :root {
        --msj-red:       #74271f;
        --msj-red-light: #be4132;
        --msj-dark:      #2d2d2d;
    }

    /* ===== PAGE HEADING ===== */
    .dashboard-heading {
        border-left: 4px solid var(--msj-red);
        padding-left: 12px;
        margin-bottom: 24px;
    }
    .dashboard-heading h1 {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--msj-dark);
        margin: 0;
    }
    .dashboard-heading small {
        font-size: 0.78rem;
        color: #888;
    }

    /* ===== STAT CARDS ===== */
    .stat-card {
        border: none;
        border-radius: 14px;
        padding: 22px 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #fff;
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        overflow: hidden;
        position: relative;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.18);
    }
    .stat-card .stat-info .stat-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        opacity: 0.85;
        margin-bottom: 6px;
    }
    .stat-card .stat-info .stat-value {
        font-size: 2.1rem;
        font-weight: 800;
        line-height: 1;
    }
    .stat-card .stat-icon {
        font-size: 3rem;
        opacity: 0.22;
    }
    .stat-card::after {
        content: '';
        position: absolute;
        width: 130px; height: 130px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
        right: -35px; top: -35px;
    }

    .card-kendaraan { background: linear-gradient(135deg, #74271f, #be4132); }
    .card-transaksi { background: linear-gradient(135deg, #1a6b3c, #28a86a); }
    .card-tes       { background: linear-gradient(135deg, #b07d10, #f0a500); }

    /* ===== CHART CARDS ===== */
    .chart-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .chart-card .chart-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
    }
    .chart-card .chart-card-header h6 {
        margin: 0;
        font-weight: 700;
        font-size: 0.88rem;
        color: var(--msj-dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .chart-card .chart-card-header h6::before {
        content: '';
        display: inline-block;
        width: 4px; height: 16px;
        background: var(--msj-red);
        border-radius: 2px;
    }
    .chart-card .card-body {
        padding: 20px;
        background: #fff;
    }
</style>


<!-- ===== STAT CARDS (3 card, col-4 each) ===== -->
<div class="row mb-2">

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="stat-card card-kendaraan">
            <div class="stat-info">
                <div class="stat-label">Jumlah Kendaraan</div>
                <div class="stat-value">{{ $countKendaraan }}</div>
                <small style="opacity:.75;font-size:.72rem;">Unit terdaftar</small>
            </div>
            <i class="fas fa-car stat-icon"></i>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="stat-card card-transaksi">
            <div class="stat-info">
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value">{{ number_format($totalTransaksi, 0, ',', '.') }}</div>
                <small style="opacity:.75;font-size:.72rem;">Pemakaian kendaraan</small>
            </div>
            <i class="fas fa-exchange-alt stat-icon"></i>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="stat-card card-tes">
            <div class="stat-info">
                <div class="stat-label">Data Peramalan TES</div>
                <div class="stat-value">{{ $countTes }}</div>
                <small style="opacity:.75;font-size:.72rem;">Triple Exp. Smoothing</small>
            </div>
            <i class="fas fa-chart-area stat-icon"></i>
        </div>
    </div>

</div>

<!-- ===== CHARTS ROW ===== -->
<div class="row">

    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="chart-card">
            <div class="chart-card-header">
                <h6>Data Aktual: {{ $vehicleName }}</h6>
                <div class="dropdown no-arrow">
                    <a class="btn btn-sm btn-outline-secondary dropdown-toggle" href="#"
                        role="button" id="dropdownMenuLink"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-car fa-sm mr-1"></i> Ganti Kendaraan
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                        aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header" style="font-size:.75rem;color:#888;">Pilih Kendaraan:</div>
                        @foreach($vehicles as $v)
                            <a class="dropdown-item" href="{{ url('/?vehicle_id=' . $v->id) }}">
                                <i class="fas fa-car fa-sm mr-2 text-muted"></i>
                                {{ trim($v->merk . ' ' . $v->nama_kendaraan) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="myAreaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="chart-card h-100">
            <div class="chart-card-header">
                <h6>Transaksi per Kendaraan</h6>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div class="chart-pie pt-2 pb-2" style="width:100%;">
                    <canvas id="myPieChart"></canvas>
                </div>
                <div class="mt-3 text-center">
                    <small class="text-muted">Distribusi Transaksi Seluruh Kendaraan</small>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    Chart.defaults.global.defaultFontFamily = 'Nunito, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
    Chart.defaults.global.defaultFontColor = '#858796';

    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(',', '').replace(' ', '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) { var k = Math.pow(10, prec); return '' + Math.round(n * k) / k; };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        if ((s[1] || '').length < prec) { s[1] = s[1] || ''; s[1] += new Array(prec - s[1].length + 1).join('0'); }
        return s.join(dec);
    }

    // LINE CHART
    var ctx = document.getElementById("myAreaChart");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($lineLabels) !!},
            datasets: [{
                label: "Transaksi",
                lineTension: 0.3,
                backgroundColor: "rgba(190, 65, 50, 0.07)",
                borderColor: "#BE4132",
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: "#BE4132",
                pointBorderColor: "#fff",
                pointBorderWidth: 2,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: "#BE4132",
                pointHoverBorderColor: "#fff",
                pointHitRadius: 10,
                data: {!! json_encode($lineData) !!},
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
            scales: {
                xAxes: [{ gridLines: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 7, fontSize: 11 } }],
                yAxes: [{
                    ticks: { maxTicksLimit: 5, padding: 10, fontSize: 11, callback: function(value) { return number_format(value); } },
                    gridLines: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] }
                }],
            },
            legend: { display: false },
            tooltips: {
                backgroundColor: "#fff", bodyFontColor: "#858796", titleFontColor: '#6e707e',
                titleFontSize: 13, borderColor: '#dddfeb', borderWidth: 1,
                xPadding: 15, yPadding: 15, displayColors: false, intersect: false, mode: 'index', caretPadding: 10,
                callbacks: { label: function(tooltipItem, chart) { var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || ''; return datasetLabel + ': ' + number_format(tooltipItem.yLabel); } }
            }
        }
    });

    // DONUT CHART
    var ctxPie = document.getElementById("myPieChart");
    var myPieChart = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($donutLabels) !!},
            datasets: [{
                data: {!! json_encode($donutData) !!},
                backgroundColor: ['#74271f', '#28a86a', '#36b9cc', '#f0a500', '#2e7fd8', '#858796', '#5a5c69'],
                hoverBackgroundColor: ['#be4132', '#1a8a56', '#2c9faf', '#c88800', '#1a5fa8', '#60616f', '#373840'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
                borderWidth: 3,
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: { backgroundColor: "#fff", bodyFontColor: "#858796", borderColor: '#dddfeb', borderWidth: 1, xPadding: 15, yPadding: 15, displayColors: true, caretPadding: 10 },
            legend: { display: true, position: 'bottom', labels: { padding: 16, fontSize: 12, usePointStyle: true } },
            cutoutPercentage: 75,
        },
    });
</script>
@endpush

@endsection
