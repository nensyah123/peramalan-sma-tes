@extends('layouts.app')

@section('content')

<style>
    /* ============================================================
       DASHBOARD — MSJ TRANS v2
       Halaman utama sistem prediksi permintaan armada
       ============================================================ */

    /* ── Page Header ────────────────────────────────────────────── */
    .dash-page-header {
        margin-bottom: 24px;
    }
    .dash-page-header h4 {
        font-family: 'Syne', sans-serif;
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0 0 2px;
        letter-spacing: -0.3px;
    }
    .dash-page-header p {
        font-size: 0.78rem;
        color: var(--text-muted);
        margin: 0;
    }

    /* ── Stat Cards (kartu statistik berwarna) ───────────────────── */
    .stat-card {
        border-radius: 14px;
        padding: 20px 22px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        color: #fff;
        border: none;
        overflow: hidden;
        position: relative;
        transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow  0.22s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: default;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.10);
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.16);
    }

    /* Lingkaran dekoratif kanan-bawah */
    .stat-card::after {
        content: '';
        position: absolute;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.07);
        right: -30px;
        bottom: -30px;
        pointer-events: none;
    }

    /* Lingkaran dekoratif kanan-atas (lebih kecil) */
    .stat-card::before {
        content: '';
        position: absolute;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        right: 30px;
        top: -20px;
        pointer-events: none;
    }

    /* Konten teks dalam stat card */
    .stat-card .stat-body      { position: relative; z-index: 1; }
    .stat-card .stat-label {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 1.1px;
        text-transform: uppercase;
        opacity: 0.8;
        margin-bottom: 8px;
    }
    .stat-card .stat-value {
        font-family: 'Syne', sans-serif;
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -1px;
    }
    .stat-card .stat-sub {
        font-size: 0.69rem;
        opacity: 0.7;
        margin-top: 6px;
        font-weight: 400;
    }

    /* Ikon di sisi kanan stat card */
    .stat-card .stat-icon-wrap {
        position: relative;
        z-index: 1;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-card .stat-icon-wrap i { font-size: 1.1rem; opacity: 0.9; }

    /* Warna per jenis kartu statistik */
    .sc-kendaraan { background: linear-gradient(135deg, #5c1212 0%, #a82828 100%); } /* merah tua  */
    .sc-transaksi { background: linear-gradient(135deg, #065f46 0%, #059669 100%); } /* hijau      */
    .sc-tes       { background: linear-gradient(135deg, #92400e 0%, #d97706 100%); } /* oranye     */
    .sc-aktif     { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); } /* biru       */
    .sc-rusak     { background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); } /* merah      */
    .sc-dijual    { background: linear-gradient(135deg, #374151 0%, #6b7280 100%); } /* abu-abu    */

    /* ── Chart Cards (kartu grafik putih) ───────────────────────── */
    .chart-card {
        background: #fff;
        border: 1px solid #ece9e5;
        border-radius: 15px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .chart-card:hover { box-shadow: 0 6px 24px rgba(0, 0, 0, 0.09); }

    /* Header chart card (judul + tombol aksi) */
    .chart-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f4f3f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
    }
    .chart-card-header .chart-title {
        font-family: 'Syne', sans-serif;
        font-size: 0.85rem;
        font-weight: 700;
        color: #18181b;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    /* Garis merah vertikal sebelum judul */
    .chart-card-header .chart-title-bar {
        width: 4px;
        height: 16px;
        background: linear-gradient(180deg, #8B1E1E, #c9392b);
        border-radius: 2px;
        flex-shrink: 0;
    }
    .chart-card .card-body { padding: 20px; background: #fff; }

    /* Tombol dropdown pilih kendaraan */
    .chart-dropdown-btn {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.76rem;
        font-weight: 500;
        color: #71717a;
        background: #f4f3f0;
        border: 1px solid #ece9e5;
        border-radius: 8px;
        padding: 5px 10px;
        cursor: pointer;
        transition: all 0.15s;
        display: flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
    }
    .chart-dropdown-btn:hover {
        background: rgba(139, 30, 30, 0.07);
        border-color: rgba(139, 30, 30, 0.2);
        color: #8B1E1E;
        text-decoration: none;
    }

    /* ── Legenda Donut Chart ─────────────────────────────────────── */
    .donut-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px 16px;
        margin-top: 14px;
    }
    .donut-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.78rem;
        color: #52525b;
        font-weight: 500;
    }

    /* Titik warna pada legenda */
    .donut-legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* Wrapper donut chart — lebar maksimal agar tidak terlalu besar */
    .donut-wrapper {
        position: relative;
        width: 100%;
        max-width: 210px;
        margin: 0 auto;
    }
</style>


{{-- ================================================================
     ROW 1 — Statistik Utama: Kendaraan, Transaksi, Data TES
     ================================================================ --}}
<div class="row mb-3">

    {{-- Jumlah total kendaraan terdaftar --}}
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="stat-card sc-kendaraan">
            <div class="stat-body">
                <div class="stat-label">Jumlah Kendaraan</div>
                <div class="stat-value">{{ $countKendaraan }}</div>
                <div class="stat-sub">Unit terdaftar</div>
            </div>
            <div class="stat-icon-wrap">
                <i class="fas fa-car"></i>
            </div>
        </div>
    </div>

    {{-- Total seluruh transaksi penyewaan --}}
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="stat-card sc-transaksi">
            <div class="stat-body">
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value">{{ number_format($totalTransaksi, 0, ',', '.') }}</div>
                <div class="stat-sub">Penyewaan kendaraan</div>
            </div>
            <div class="stat-icon-wrap">
                <i class="fas fa-exchange-alt"></i>
            </div>
        </div>
    </div>

    {{-- Jumlah data peramalan Triple Exponential Smoothing --}}
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="stat-card sc-tes">
            <div class="stat-body">
                <div class="stat-label">Data Peramalan TES</div>
                <div class="stat-value">{{ $countTes }}</div>
                <div class="stat-sub">Triple Exp. Smoothing</div>
            </div>
            <div class="stat-icon-wrap">
                <i class="fas fa-chart-area"></i>
            </div>
        </div>
    </div>

</div>


{{-- ================================================================
     ROW 2 — Status Real-time Armada: Tersedia, Disewa, Penyewa Unik
     ================================================================ --}}
<div class="row mb-4">

    {{-- Armada yang siap/tersedia untuk disewa --}}
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="stat-card sc-aktif">
            <div class="stat-body">
                <div class="stat-label">Armada Tersedia</div>
                <div class="stat-value">{{ $countTersedia }}</div>
                <div class="stat-sub">Siap untuk disewa</div>
            </div>
            <div class="stat-icon-wrap">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>

    {{-- Unit yang sedang dalam penyewaan --}}
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="stat-card sc-rusak">
            <div class="stat-body">
                <div class="stat-label">Sedang Disewa</div>
                <div class="stat-value">{{ $countDisewa }}</div>
                <div class="stat-sub">Unit sedang keluar</div>
            </div>
            <div class="stat-icon-wrap">
                <i class="fas fa-key"></i>
            </div>
        </div>
    </div>

    {{-- Jumlah pelanggan unik (tidak duplikat) --}}
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="stat-card sc-dijual">
            <div class="stat-body">
                <div class="stat-label">Total Penyewa Unik</div>
                <div class="stat-value">{{ number_format($countPenyewaUnik, 0, ',', '.') }}</div>
                <div class="stat-sub">Pelanggan berbeda</div>
            </div>
            <div class="stat-icon-wrap">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

</div>


{{-- ================================================================
     CHARTS — Grafik Garis & Donut
     ================================================================ --}}
<div class="row">

    {{-- ── Grafik Garis: Data Aktual per Kendaraan ─────────────────── --}}
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="chart-card">

            {{-- Header: judul kendaraan terpilih + dropdown ganti kendaraan --}}
            <div class="chart-card-header">
                <h6 class="chart-title">
                    <span class="chart-title-bar"></span>
                    Data Aktual: {{ $vehicleName }}
                </h6>

                <div class="dropdown no-arrow">
                    <a class="chart-dropdown-btn dropdown-toggle"
                       href="#"
                       role="button"
                       data-toggle="dropdown">
                        <i class="fas fa-car" style="font-size:0.72rem;"></i>
                        Ganti Kendaraan
                    </a>

                    {{-- Daftar kendaraan yang bisa dipilih --}}
                    <div class="dropdown-menu dropdown-menu-right animated--fade-in"
                         style="max-height:280px; overflow-y:auto; min-width:200px;">

                        <div class="dropdown-header"
                             style="font-family:'Plus Jakarta Sans',sans-serif; font-size:0.7rem; color:#a1a1aa; padding:8px 12px;">
                            Pilih Kendaraan
                        </div>

                        @foreach($vehicles as $v)
                            <a class="dropdown-item {{ isset($selectedVehicle) && $selectedVehicle->id == $v->id ? 'active' : '' }}"
                               href="{{ url('/?vehicle_id=' . $v->id) }}">
                                <i class="fas fa-car fa-sm mr-2" style="color:#a1a1aa; font-size:0.72rem;"></i>
                                {{ $v->merk }}{{ $v->plat ? ' — ' . $v->plat : '' }}
                            </a>
                        @endforeach

                    </div>
                </div>
            </div>

            {{-- Canvas area untuk Chart.js line chart --}}
            <div class="card-body">
                <div class="chart-area" style="height: 260px;">
                    <canvas id="myAreaChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Donut Chart: Distribusi Transaksi per Merk ───────────────── --}}
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="chart-card h-100">

            <div class="chart-card-header">
                <h6 class="chart-title">
                    <span class="chart-title-bar"></span>
                    Transaksi per Merk
                </h6>
            </div>

            <div class="card-body d-flex flex-column align-items-center justify-content-center">

                {{-- Canvas donut chart --}}
                <div class="donut-wrapper">
                    <canvas id="myPieChart" style="max-height:200px;"></canvas>
                </div>

                {{-- Legenda: hanya tampilkan nama merk (tanpa angka jumlah) --}}
                @php
                    $donutColors = ['#8B1E1E', '#059669', '#d97706', '#2563eb', '#6b7280'];
                @endphp

                <div class="donut-legend mt-3">
                    @foreach($donutLabels as $i => $label)
                        <div class="donut-legend-item">
                            <div class="donut-legend-dot"
                                 style="background: {{ $donutColors[$i % count($donutColors)] }};"></div>
                            <span>{{ $label }}</span>
                            {{-- Angka jumlah transaksi sengaja tidak ditampilkan di sini;
                                 detail tersedia saat hover pada chart --}}
                        </div>
                    @endforeach
                </div>

                <p class="mt-3 text-center"
                   style="font-size:0.7rem; color:#a1a1aa; margin:0;">
                    Distribusi Transaksi per Merk Kendaraan
                </p>

            </div>
        </div>
    </div>

</div>


@push('scripts')
<script>
    /* ================================================================
       Chart.js — Konfigurasi Global
       ================================================================ */
    Chart.defaults.global.defaultFontFamily = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.global.defaultFontColor  = '#71717a';

    /**
     * Helper: format angka dengan pemisah ribuan.
     * Contoh: 9735 → "9.735"
     */
    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(',', '').replace(' ', '');
        var n    = !isFinite(+number)   ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep  = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec  = (typeof dec_point    === 'undefined') ? '.' : dec_point,
            s    = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };

        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }


    /* ================================================================
       LINE CHART — Grafik transaksi aktual kendaraan terpilih
       ================================================================ */
    var ctx = document.getElementById("myAreaChart");

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($lineLabels) !!},
            datasets: [{
                label: "Transaksi",
                lineTension: 0.35,
                backgroundColor: "rgba(139, 30, 30, 0.06)",  /* area bawah garis */
                borderColor: "#a82828",                        /* warna garis      */
                borderWidth: 2.5,
                pointRadius: 3,
                pointBackgroundColor: "#a82828",
                pointBorderColor: "#fff",
                pointBorderWidth: 2,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: "#a82828",
                pointHoverBorderColor: "#fff",
                pointHitRadius: 10,
                data: {!! json_encode($lineData) !!},
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: { padding: { left: 6, right: 16, top: 16, bottom: 0 } },
            scales: {
                xAxes: [{
                    gridLines: { display: false, drawBorder: false },
                    ticks: { maxTicksLimit: 7, fontSize: 11, fontColor: '#a1a1aa' }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        fontSize: 11,
                        fontColor: '#a1a1aa',
                        callback: function(value) { return number_format(value); }
                    },
                    gridLines: {
                        color: "#f4f3f0",
                        zeroLineColor: "#f4f3f0",
                        drawBorder: false,
                        borderDash: [3],
                        zeroLineBorderDash: [3]
                    }
                }],
            },
            legend: { display: false },
            tooltips: {
                backgroundColor: "#fff",
                bodyFontColor: "#3f3f46",
                titleFontColor: '#18181b',
                titleFontSize: 12,
                titleFontFamily: "'Syne', sans-serif",
                bodyFontFamily: "'Plus Jakarta Sans', sans-serif",
                borderColor: '#ece9e5',
                borderWidth: 1,
                xPadding: 14,
                yPadding: 12,
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
                callbacks: {
                    label: function(tooltipItem) {
                        return 'Transaksi: ' + number_format(tooltipItem.yLabel);
                    }
                }
            }
        }
    });


    /* ================================================================
       DONUT CHART — Distribusi transaksi per merk kendaraan
       Detail angka tersedia via tooltip saat hover
       ================================================================ */
    var ctxPie = document.getElementById("myPieChart");

    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($donutLabels) !!},
            datasets: [{
                data: {!! json_encode($donutData) !!},
                backgroundColor:      ['#8B1E1E', '#059669', '#d97706', '#2563eb', '#6b7280'],
                hoverBackgroundColor: ['#a82828', '#047857', '#b45309', '#1d4ed8', '#52525b'],
                hoverBorderColor: "#ffffff",
                borderColor: "#ffffff",
                borderWidth: 3,
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "#fff",
                bodyFontColor: "#3f3f46",
                bodyFontFamily: "'Plus Jakarta Sans', sans-serif",
                borderColor: '#ece9e5',
                borderWidth: 1,
                xPadding: 14,
                yPadding: 12,
                displayColors: true,
                caretPadding: 10,
                callbacks: {
                    /* Tampilkan: Nama Merk — Jumlah (Persentase%) */
                    label: function(tooltipItem, chart) {
                        var dataset = chart.datasets[tooltipItem.datasetIndex];
                        var total   = dataset.data.reduce(function(a, b) { return a + b; }, 0);
                        var value   = dataset.data[tooltipItem.index];
                        var pct     = Math.round((value / total) * 100);
                        return ' ' + chart.labels[tooltipItem.index]
                             + ': ' + number_format(value)
                             + ' (' + pct + '%)';
                    }
                }
            },
            legend: { display: false }, /* legenda ditangani oleh HTML custom di atas */
            cutoutPercentage: 74,       /* ketebalan cincin donut                      */
        },
    });
</script>
@endpush

@endsection
