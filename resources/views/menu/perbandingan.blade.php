@extends('layouts.app')

@section('content')

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h3 mb-0 text-gray-800">Riwayat Peramalan TES</h1>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert">
  {{ session('success') }}
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
<script>
  setTimeout(function() {
    var alert = document.getElementById('success-alert');
    if (alert) {
      alert.classList.remove('show');
      setTimeout(function() {
        alert.remove();
      }, 150);
    }
  }, 3000);
</script>
@endif

<!-- History Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Riwayat Peramalan</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kendaraan</th>
                        <th>Periode Peramalan</th>
                        <th>Tanggal Dibuat</th>
                        <th>MAD</th>
                        <th>MSE</th>
                        <th>MAPE</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riwayat as $index => $item)
                    <tr>
                         <td>{{ $index + 1 }}</td>
                         <td>{{ $item->kendaraan->nama_kendaraan ?? '-' }}</td>
                         <td>{{ $item->periode_label }}</td>
                         <td>{{ $item->created_at->format('d-m-Y') }}</td>
                         <td>{{ $item->mad }}</td>
                         <td>{{ $item->mse }}</td>
                         <td>{{ $item->mape }}%</td>
                         <td>
                            <button class="btn btn-info btn-sm" onclick="showDetail({{ $item->id }})" title="Lihat">
                                <i class="fas fa-eye"></i> Lihat
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="showComparison({{ $item->id }})" title="Pembanding">
                                <i class="fas fa-chart-line"></i> Pembanding
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete({{ $item->id }})" title="Hapus">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                            <form id="delete-form-{{ $item->id }}" action="{{ route('perbandingan.destroy', $item->id) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                         </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Container for Dynamic Cards -->
<div id="dynamicCardContainer"></div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data riwayat ini akan dihapus secara permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        })
    }

    // Variable to hold chart instances to destroy them before creating new ones
    var currentChart = null;

    function showDetail(id) {
        // Fetch detail data
        $.ajax({
            url: '/perbandingan/' + id,
            type: 'GET',
            success: function(response) {
                renderDetailCard(response);
            },
            error: function(err) {
                alert('Gagal mengambil data detail.');
                console.error(err);
            }
        });
    }

    function showComparison(id) {
        // Fetch comparison data
        $.ajax({
            url: '/perbandingan/' + id + '/compare',
            type: 'GET',
            success: function(response) {
                renderComparisonCard(response);
            },
            error: function(err) {
                alert('Gagal mengambil data perbandingan.');
                console.error(err);
            }
        });
    }

    function renderDetailCard(data) {
        var html = `
        <div class="card shadow mb-4" id="detailCard">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Detail Peramalan — ${data.kendaraan} (${data.periode})</h6>
                <button type="button" class="close" onclick="$('#detailCard').remove()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4">
                    <div class="col-md-4">
                        <h4 class="font-weight-bold">${data.mad}</h4>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">MAD</div>
                    </div>
                    <div class="col-md-4">
                        <h4 class="font-weight-bold">${data.mse}</h4>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">MSE</div>
                    </div>
                    <div class="col-md-4">
                        <h4 class="font-weight-bold">${data.mape}%</h4>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">MAPE</div>
                    </div>
                </div>
                <p class="text-center">${data.summary}</p>
            </div>
        </div>`;

        $('#dynamicCardContainer').html(html);
        // Scroll to card
        $('html, body').animate({
            scrollTop: $("#dynamicCardContainer").offset().top - 100
        }, 500);
    }

    function renderComparisonCard(data) {
        var html = `
        <div class="card shadow mb-4" id="comparisonCard">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Perbandingan TES vs SMA</h6>
                <button type="button" class="close" onclick="$('#comparisonCard').remove()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="card-body">
                <div class="chart-area mb-4">
                    <canvas id="compChartCanvas"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead class="thead-light">
                            <tr>
                                <th>Metode</th>
                                <th>MAD</th>
                                <th>MSE</th>
                                <th>MAPE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>TES</td>
                                <td>${data.accuracy.tes.mad}</td>
                                <td>${data.accuracy.tes.mse}</td>
                                <td>${data.accuracy.tes.mape}%</td>
                            </tr>
                            <tr>
                                <td>SMA</td>
                                <td>${data.accuracy.sma.mad}</td>
                                <td>${data.accuracy.sma.mse}</td>
                                <td>${data.accuracy.sma.mape}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info text-center mt-3">
                    ${data.conclusion}
                </div>
            </div>
        </div>`;

        $('#dynamicCardContainer').html(html);

        // Render Chart
        var ctx = document.getElementById("compChartCanvas");
        if (currentChart) {
             currentChart.destroy();
        }

        currentChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.chart.labels,
                datasets: [
                    {
                        label: "Aktual",
                        lineTension: 0.3,
                        borderColor: "rgba(78, 115, 223, 1)",
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(78, 115, 223, 1)",
                        data: data.chart.actual,
                        fill: false
                    },
                    {
                        label: "TES",
                        lineTension: 0.3,
                        borderColor: "rgba(246, 194, 62, 1)", // Yellow
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(246, 194, 62, 1)",
                        data: data.chart.tes,
                        fill: false,
                        borderDash: [5, 5]
                    },
                    {
                        label: "SMA",
                        lineTension: 0.3,
                        borderColor: "rgba(28, 200, 138, 1)", // Green
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(28, 200, 138, 1)",
                        data: data.chart.sma,
                        fill: false,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    mode: 'index',
                    intersect: false
                }
            }
        });

        // Scroll to card
        $('html, body').animate({
            scrollTop: $("#dynamicCardContainer").offset().top - 100
        }, 500);
    }
</script>
@endpush
