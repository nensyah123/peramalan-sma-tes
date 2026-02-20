@extends('layouts.app')

@section('content')

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h3 mb-0 text-gray-800">Analisis Armada</h1>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Cukup Card Example -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Armada Cukup</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summary['cukup'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kurang Card Example -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Armada Kurang</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summary['kurang'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Berlebih Card Example -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Armada Berlebih</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $summary['berlebih'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-info-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Area Chart -->
    <div class="col-xl-12 col-lg-12">
        <div class="card shadow mb-4">
            <!-- Card Header - Dropdown -->
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Grafik Unit vs Prediksi Kebutuhan (Bulan Depan)</h6>
            </div>
            <!-- Card Body -->
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="analisisChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTales Example -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Detail Status Armada</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kendaraan</th>
                        <th>Unit Tersedia</th>
                        <th>Prediksi Transaksi/Bulan</th>
                        <th>Kebutuhan Unit/Hari (Avg)</th>
                        <th>Selisih</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analisis as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['kendaraan'] }}</td>
                        <td>{{ $item['unit_tersedia'] }} unit</td>
                        <td>{{ $item['prediksi_transaksi'] }} ({{ $item['periode'] }})</td>
                        <td>{{ ceil($item['prediksi_transaksi'] / 30) }} unit</td>
                        <td class="{{ $item['selisih'] < 0 ? 'text-danger' : ($item['selisih'] > 0 ? 'text-success' : '') }}">
                            {{ $item['selisih'] }}
                        </td>
                        <td>
                            @if($item['status'] == 'Cukup')
                                <span class="badge badge-success">Cukup</span>
                            @elseif($item['status'] == 'Kurang')
                                <span class="badge badge-danger">Kurang</span>
                            @else
                                <span class="badge badge-warning">Berlebih</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card bg-success text-white shadow">
            <div class="card-body">
                Cukup
                <div class="text-white-50 small">Unit tersedia sesuai atau melebihi prediksi permintaan (selisih 0 s/d 1)</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card bg-danger text-white shadow">
            <div class="card-body">
                Kurang
                <div class="text-white-50 small">Unit tidak mencukupi permintaan, perlu penambahan armada</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card bg-warning text-white shadow">
            <div class="card-body">
                Berlebih
                <div class="text-white-50 small">Unit jauh melebihi prediksi (selisih >= 2), pertimbangkan efisiensi armada</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();

        // Chart
        var ctx = document.getElementById("analisisChart");
        var myBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartData['labels']),
                datasets: [{
                    label: "Unit Tersedia",
                    backgroundColor: "#4e73df",
                    hoverBackgroundColor: "#2e59d9",
                    borderColor: "#4e73df",
                    data: @json($chartData['unit_tersedia']),
                }, {
                    label: "Prediksi Kebutuhan Unit",
                    backgroundColor: "#1cc88a", // Green
                    hoverBackgroundColor: "#17a673",
                    borderColor: "#1cc88a",
                    data: @json($chartData['kebutuhan_prediksi']), // Prediksi / 30
                }],
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        time: {
                            unit: 'month'
                        },
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 6
                        },
                        maxBarThickness: 25,
                    }],
                    yAxes: [{
                        ticks: {
                            min: 0,
                            // max: 15000,
                            maxTicksLimit: 5,
                            padding: 10,
                        },
                        gridLines: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }],
                },
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltips: {
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                },
            }
        });
    });
</script>
@endpush
