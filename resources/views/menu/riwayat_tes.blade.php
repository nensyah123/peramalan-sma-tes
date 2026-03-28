@extends('layouts.app')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<script>
    setTimeout(function() {
        var el = document.getElementById('success-alert');
        if (el) { el.classList.remove('show'); setTimeout(function(){ el.remove(); }, 150); }
    }, 3000);
</script>
@endif

{{-- ===== HEADER ===== --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="font-weight-bold mb-0" style="color:#74271f;">
        <i class="fas fa-history mr-1"></i> Riwayat Peramalan TES
    </h6>
    <a href="{{ route('peramalan_tes.index') }}" class="btn btn-sm text-white font-weight-bold"
       style="background:linear-gradient(135deg,#74271f,#c0392b);border:none;border-radius:8px;">
        <i class="fas fa-plus mr-1"></i> Buat Peramalan Baru
    </a>
</div>

{{-- ===== TABEL RIWAYAT ===== --}}
<div class="card shadow-sm border-0 mb-4" style="border-radius:14px;overflow:hidden;">
    <div class="card-body p-3">
        @if($riwayat->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <p class="text-muted">Belum ada riwayat peramalan tersimpan.</p>
            <a href="{{ route('peramalan_tes.index') }}" class="btn btn-sm text-white font-weight-bold"
               style="background:linear-gradient(135deg,#74271f,#c0392b);border:none;border-radius:8px;">
                <i class="fas fa-plus mr-1"></i> Buat Peramalan Pertama
            </a>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable" width="100%" cellspacing="0" style="font-size:0.85rem;">
                <thead>
                    <tr style="border-bottom:2px solid #dee2e6;">
                        <th width="5%" style="color:#74271f;font-size:0.8rem;">No</th>
                        <th style="color:#74271f;font-size:0.8rem;">Merk</th>
                        <th style="color:#74271f;font-size:0.8rem;">α</th>
                        <th style="color:#74271f;font-size:0.8rem;">β</th>
                        <th style="color:#74271f;font-size:0.8rem;">γ</th>
                        <th style="color:#74271f;font-size:0.8rem;">Periode</th>
                        <th style="color:#74271f;font-size:0.8rem;">MAD</th>
                        <th style="color:#74271f;font-size:0.8rem;">MSE</th>
                        <th style="color:#74271f;font-size:0.8rem;">MAPE</th>
                        <th style="color:#74271f;font-size:0.8rem;">Tanggal</th>
                        <th width="15%" style="color:#74271f;font-size:0.8rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riwayat as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $item->merk }}</strong></td>
                        <td>{{ $item->alfa }}</td>
                        <td>{{ $item->beta }}</td>
                        <td>{{ $item->gamma }}</td>
                        <td>{{ $item->durasi_prediksi }} bln</td>
                        <td>{{ $item->mad }}</td>
                        <td>{{ $item->mse }}</td>
                        <td>
                            {{ $item->mape }}%
                            @if($item->mape <= 10)
                                <span class="badge badge-success">Sangat Baik</span>
                            @elseif($item->mape <= 20)
                                <span class="badge badge-primary">Baik</span>
                            @elseif($item->mape <= 50)
                                <span class="badge badge-warning">Cukup</span>
                            @else
                                <span class="badge badge-danger">Kurang</span>
                            @endif
                        </td>
                        <td>{{ $item->created_at->format('d M Y') }}</td>
                        <td class="text-nowrap">
                            <button class="btn btn-info btn-sm btn-circle" title="Detail Iterasi"
                                onclick='showDetail(@json($item))'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-warning btn-sm btn-circle" title="Perbandingan TES vs SMA"
                                onclick='showPerbandingan(@json($item))'>
                                <i class="fas fa-balance-scale"></i>
                            </button>
                            <a href="{{ route('peramalan_tes.export_pdf', $item->id) }}"
                               class="btn btn-secondary btn-sm btn-circle" title="Export PDF" target="_blank">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <form action="{{ route('peramalan_tes.destroy', $item->id) }}"
                                  method="POST" class="d-inline"
                                  onsubmit="return confirm('Hapus riwayat ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm btn-circle" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ===== MODAL DETAIL ITERASI ===== --}}
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#1a6b9a,#2980b9);">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-table mr-1"></i>
                    Detail Iterasi TES — <span id="detail_merk_title"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap mb-3 p-2 rounded" style="background:#f8f9fc;font-size:0.85rem;gap:12px;">
                    <div><strong>Merk:</strong> <strong id="detail_merk"></strong></div>
                    <div><strong>α:</strong> <span id="detail_alpha"></span></div>
                    <div><strong>β:</strong> <span id="detail_beta"></span></div>
                    <div><strong>γ:</strong> <span id="detail_gamma"></span></div>
                    <div><strong>Durasi:</strong> <span id="detail_durasi"></span> bulan</div>
                    <div><strong>MAD:</strong> <span id="detail_mad"></span></div>
                    <div><strong>MSE:</strong> <span id="detail_mse"></span></div>
                    <div><strong>MAPE:</strong> <span id="detail_mape"></span> <span id="detail_mape_badge"></span></div>
                </div>
                <h6 class="font-weight-bold mb-2" style="color:#74271f;">
                    <i class="fas fa-chart-line mr-1"></i> Grafik Aktual vs Prediksi
                </h6>
                <div class="chart-area mb-4" style="height:250px;">
                    <canvas id="detailChart"></canvas>
                </div>
                <h6 class="font-weight-bold mb-2" style="color:#74271f;">
                    <i class="fas fa-table mr-1"></i> Tabel Iterasi Lengkap
                </h6>
                <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-bordered table-sm table-striped" style="font-size:0.78rem;">
                        <thead class="thead-dark" style="position:sticky;top:0;">
                            <tr>
                                <th>No</th><th>Bulan/Tahun</th><th>Aktual</th>
                                <th>Level</th><th>Trend</th><th>Seasonal</th>
                                <th>Prediksi</th><th>Error</th><th>APE (%)</th>
                            </tr>
                        </thead>
                        <tbody id="detail_table_body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ===== MODAL PERBANDINGAN ===== --}}
<div class="modal fade" id="perbandinganModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#92400e,#d97706);">
                <h5 class="modal-title font-weight-bold text-white">
                    <i class="fas fa-balance-scale mr-1"></i>
                    Perbandingan TES vs SMA — <span id="perb_merk_title"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap mb-3 p-2 rounded" style="background:#f8f9fc;font-size:0.85rem;gap:12px;">
                    <div><strong>Merk:</strong> <strong id="perb_merk"></strong></div>
                    <div><strong>α:</strong> <span id="perb_alpha"></span></div>
                    <div><strong>β:</strong> <span id="perb_beta"></span></div>
                    <div><strong>γ:</strong> <span id="perb_gamma"></span></div>
                    {{-- FIX 1: Periode SMA diubah dari 3 bulan ke 12 bulan --}}
                    <div><strong>Periode SMA:</strong> 12 bulan</div>
                </div>

                <div id="perb_loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">Memproses perbandingan...</p>
                </div>

                <div id="perb_content" style="display:none;">
                    <h6 class="font-weight-bold mb-2" style="color:#74271f;">
                        <i class="fas fa-chart-line mr-1"></i> Grafik Aktual vs TES vs SMA
                    </h6>
                    <div class="chart-area mb-4" style="height:280px;">
                        <canvas id="perbChart"></canvas>
                    </div>
                    <h6 class="font-weight-bold mb-2" style="color:#74271f;">
                        <i class="fas fa-ruler-combined mr-1"></i> Tabel Perbandingan Akurasi
                    </h6>
                    <table class="table table-hover text-center mb-3" style="font-size:0.85rem;">
                        <thead>
                            <tr style="border-bottom:2px solid #dee2e6;">
                                <th style="color:#74271f;">Metrik</th>
                                <th style="color:#74271f;">TES Additif</th>
                                {{-- FIX 2: Header kolom SMA diubah dari "3 periode" ke "m=12" --}}
                                <th style="color:#74271f;">SMA (m=12)</th>
                                <th style="color:#74271f;">Lebih Baik</th>
                            </tr>
                        </thead>
                        <tbody id="perb_table_body"></tbody>
                    </table>
                    <div id="perb_conclusion" class="alert text-center font-weight-bold"></div>
                </div>

                <div id="perb_error" class="alert alert-danger" style="display:none;">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    <span id="perb_error_msg">Gagal memproses data.</span>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#dataTable').DataTable({ order: [[9, 'desc']] });
});

var detailChartInstance = null;

function showDetail(item) {
    $('#detail_merk').text(item.merk ?? '-');
    $('#detail_merk_title').text(item.merk ?? '-');
    $('#detail_alpha').text(item.alfa);
    $('#detail_beta').text(item.beta);
    $('#detail_gamma').text(item.gamma);
    $('#detail_durasi').text(item.durasi_prediksi ?? '-');
    $('#detail_mad').text(item.mad);
    $('#detail_mse').text(item.mse);
    $('#detail_mape').text(item.mape + '%');

    var mape = parseFloat(item.mape);
    $('#detail_mape_badge').html(
        mape <= 10 ? '<span class="badge badge-success">Sangat Baik</span>'
        : mape <= 20 ? '<span class="badge badge-primary">Baik</span>'
        : mape <= 50 ? '<span class="badge badge-warning">Cukup</span>'
        : '<span class="badge badge-danger">Kurang</span>'
    );

    var tbody = $('#detail_table_body');
    tbody.empty();
    var labels = [], actuals = [], predicteds = [];

    if (item.data_peramalan && item.data_peramalan.length > 0) {
        item.data_peramalan.forEach(function(row, i) {
            var isFuture = (row.aktual === '-' || row.aktual === null);
            tbody.append(`<tr class="${isFuture ? 'table-success font-weight-bold' : ''}">
                <td>${i+1}</td>
                <td>${row.bulan_tahun || '-'}</td>
                <td>${row.aktual ?? '-'}</td>
                <td>${row.level ?? '-'}</td>
                <td>${row.trend ?? '-'}</td>
                <td>${row.seasonal ?? '-'}</td>
                <td><strong>${row.prediksi ?? '-'}</strong></td>
                <td>${row.error ?? '-'}</td>
                <td>${row.ape !== undefined && row.ape !== '-' ? row.ape + '%' : '-'}</td>
            </tr>`);
            labels.push(row.bulan_tahun);
            actuals.push(!isFuture ? parseFloat(row.aktual) : null);
            predicteds.push(row.prediksi !== '-' ? parseFloat(row.prediksi) : null);
        });
    } else {
        tbody.append('<tr><td colspan="9" class="text-center text-muted">Tidak ada data.</td></tr>');
    }

    if (detailChartInstance) detailChartInstance.destroy();
    detailChartInstance = new Chart(document.getElementById("detailChart"), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: "Aktual",
                borderColor: "rgba(78,115,223,1)",
                backgroundColor: "rgba(78,115,223,0.05)",
                borderWidth: 2, lineTension: 0.3, pointRadius: 2, pointHitRadius: 10,
                data: actuals,
            }, {
                label: "Prediksi TES",
                borderColor: "rgba(28,200,138,1)",
                backgroundColor: "rgba(28,200,138,0.05)",
                borderWidth: 2, lineTension: 0.3, pointRadius: 2, pointHitRadius: 10,
                data: predicteds,
            }],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                xAxes: [{ ticks: { maxTicksLimit: 10, fontSize: 10 }, gridLines: { display: false } }],
                yAxes: [{ ticks: { maxTicksLimit: 5, fontSize: 10 }, gridLines: { color: "rgb(234,236,244)", borderDash: [2] } }],
            },
            legend: { display: true, position: 'top', labels: { fontSize: 11 } },
            tooltips: { mode: 'index', intersect: false },
        }
    });

    $('#detailModal').modal('show');
}

var perbChartInstance = null;

function showPerbandingan(item) {
    $('#perb_loading').show();
    $('#perb_content').hide();
    $('#perb_error').hide();
    $('#perb_table_body').empty();

    $('#perb_merk').text(item.merk ?? '-');
    $('#perb_merk_title').text(item.merk ?? '-');
    $('#perb_alpha').text(item.alfa);
    $('#perb_beta').text(item.beta);
    $('#perb_gamma').text(item.gamma);
    $('#perb_export_pdf').attr('href', '/peramalan-tes/export-pdf/' + item.id);

    $('#perbandinganModal').modal('show');

    $.ajax({
        url: '/perbandingan/' + item.id + '/compare',
        method: 'GET',
        success: function(data) {
            $('#perb_alpha').text(data.accuracy.tes.alpha);
            $('#perb_beta').text(data.accuracy.tes.beta);
            $('#perb_gamma').text(data.accuracy.tes.gamma);

            if (perbChartInstance) perbChartInstance.destroy();
            perbChartInstance = new Chart(document.getElementById("perbChart"), {
                type: 'line',
                data: {
                    labels: data.chart.labels,
                    datasets: [{
                        label: "Aktual",
                        borderColor: "rgba(78,115,223,1)",
                        backgroundColor: "rgba(78,115,223,0.05)",
                        borderWidth: 2, lineTension: 0.3, pointRadius: 2,
                        data: data.chart.actual, fill: false,
                    }, {
                        label: "Prediksi TES",
                        borderColor: "rgba(28,200,138,1)",
                        backgroundColor: "rgba(28,200,138,0.05)",
                        borderWidth: 2, lineTension: 0.3, pointRadius: 2,
                        borderDash: [5,3], data: data.chart.tes, fill: false,
                    }, {
                        // FIX 3: Label grafik diubah dari "SMA (3)" ke "SMA (m=12)"
                        label: "SMA (m=12)",
                        borderColor: "rgba(246,194,62,1)",
                        backgroundColor: "rgba(246,194,62,0.05)",
                        borderWidth: 2, lineTension: 0.3, pointRadius: 2,
                        borderDash: [3,3], data: data.chart.sma, fill: false,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{ ticks: { maxTicksLimit: 10, fontSize: 10 }, gridLines: { display: false } }],
                        yAxes: [{ ticks: { maxTicksLimit: 5, fontSize: 10 }, gridLines: { color: "rgb(234,236,244)", borderDash: [2] } }],
                    },
                    legend: { display: true, position: 'top', labels: { fontSize: 11 } },
                    tooltips: { mode: 'index', intersect: false },
                }
            });

            var acc   = data.accuracy;
            var tbody = $('#perb_table_body');
            tbody.empty();

            var metrics = [
                { label: 'MAD',  tes: acc.tes.mad,  sma: acc.sma.mad,  unit: ''  },
                { label: 'MSE',  tes: acc.tes.mse,  sma: acc.sma.mse,  unit: ''  },
                { label: 'MAPE', tes: acc.tes.mape, sma: acc.sma.mape, unit: '%' },
            ];

            metrics.forEach(function(r) {
                var tesBetter = parseFloat(r.tes) <= parseFloat(r.sma);
                tbody.append(`<tr>
                    <td><strong>${r.label}</strong></td>
                    <td class="${tesBetter ? 'text-success font-weight-bold' : ''}">${r.tes}${r.unit}</td>
                    <td class="${!tesBetter ? 'text-success font-weight-bold' : ''}">${r.sma}${r.unit}</td>
                    <td>${tesBetter
                        ? '<span class="badge badge-success">TES</span>'
                        : '<span class="badge badge-warning">SMA</span>'
                    }</td>
                </tr>`);
            });

            var tesMape = parseFloat(acc.tes.mape);
            var smaMape = parseFloat(acc.sma.mape);
            if (tesMape <= smaMape) {
                $('#perb_conclusion')
                    .removeClass('alert-warning').addClass('alert-success')
                    .html('<i class="fas fa-trophy mr-1"></i> ' + data.conclusion);
            } else {
                $('#perb_conclusion')
                    .removeClass('alert-success').addClass('alert-warning')
                    .html('<i class="fas fa-info-circle mr-1"></i> ' + data.conclusion);
            }

            $('#perb_loading').hide();
            $('#perb_content').show();
        },
        error: function(xhr) {
            $('#perb_loading').hide();
            $('#perb_error_msg').text(xhr.responseJSON?.error ?? 'Gagal memproses data.');
            $('#perb_error').show();
        }
    });
}
</script>
@endpush
