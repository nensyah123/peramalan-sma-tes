@extends('layouts.app')

@section('content')

{{-- ================= PILIH KENDARAAN ================= --}}
<div class="card shadow mb-4" style="border-radius:15px; border:none;">
    <div class="card-header py-3"
         style="border-radius:15px 15px 0 0; background:#fff; border-bottom:2px solid #f1f1f1;">
        <h6 class="m-0 font-weight-bold" style="color:#BE4132;">
            <i class="fas fa-car mr-2"></i> Pilih Kendaraan
        </h6>
    </div>
    <div class="card-body p-4">
        <form method="GET" action="{{ url('/analisis-armada') }}">
            @if($filterStatus)
                <input type="hidden" name="filter_status" value="{{ $filterStatus }}">
            @endif
            <div class="form-row align-items-center">

                <div class="form-group col-md-3 mb-0">
                    <label class="font-weight-bold" style="font-size:0.85rem;">Nama Kendaraan</label>
                    <select class="form-control" name="id_kendaraan"
                            style="border-radius:8px;" onchange="this.form.submit()">
                        @foreach($kendaraans as $k)
                            @if(!$kendaraanFiltered || in_array($k->id, $kendaraanFiltered))
                            <option value="{{ $k->id }}" {{ $selectedId == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kendaraan }}
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                @if($selectedAnalisis)
                <div class="col-md-9">
                    <label class="font-weight-bold d-block" style="font-size:0.85rem;">
                        Informasi Kapasitas
                    </label>
                    <div class="d-flex flex-wrap" style="gap:8px;">

                        <div class="px-3 py-2 text-center" style="background:#fdf0ee; border-radius:10px; min-width:105px;">
                            <div style="font-size:0.68rem; color:#888;">Unit Stok Tetap</div>
                            <div class="font-weight-bold" style="font-size:1.15rem; color:#BE4132;">
                                {{ $selectedAnalisis['unit_tersedia'] }} unit
                            </div>
                        </div>

                        <div class="px-3 py-2 text-center" style="background:#e8f5e9; border-radius:10px; min-width:105px;">
                            <div style="font-size:0.68rem; color:#888;">Rata-rata/Bulan</div>
                            <div class="font-weight-bold" style="font-size:1.15rem; color:#2e7d32;">
                                {{ $selectedAnalisis['rata_rata'] }}
                            </div>
                        </div>

                        <div class="px-3 py-2 text-center" style="background:#e3f2fd; border-radius:10px; min-width:105px;">
                            <div style="font-size:0.68rem; color:#888;">Kapasitas 1 Unit</div>
                            <div class="font-weight-bold" style="font-size:1.15rem; color:#1565c0;">
                                {{ $selectedAnalisis['kapasitas_1_unit'] }}
                            </div>
                        </div>

                        <div class="px-3 py-2 text-center" style="background:#f3e5f5; border-radius:10px; min-width:105px;">
                            <div style="font-size:0.68rem; color:#888;">Kapasitas Total</div>
                            <div class="font-weight-bold" style="font-size:1.15rem; color:#6a1b9a;">
                                {{ $selectedAnalisis['kapasitas_total'] }}
                            </div>
                        </div>

                        <div class="px-3 py-2 text-center" style="background:#fff8e1; border-radius:10px; min-width:105px;">
                            <div style="font-size:0.68rem; color:#888;">Threshold (80%)</div>
                            <div class="font-weight-bold" style="font-size:1.15rem; color:#e65100;">
                                {{ $selectedAnalisis['threshold_waspada'] }}
                            </div>
                        </div>

                        <div class="px-3 py-2 text-center" style="background:#f5f5f5; border-radius:10px; min-width:105px;">
                            <div style="font-size:0.68rem; color:#888;">Data Historis</div>
                            <div class="font-weight-bold" style="font-size:1.15rem; color:#424242;">
                                {{ $selectedAnalisis['jumlah_bulan'] }} bulan
                            </div>
                        </div>

                    </div>
                </div>
                @endif

            </div>
        </form>
    </div>
</div>

@if($selectedAnalisis)

{{-- ================= TABEL ANALISIS ================= --}}
{{-- Gaya tabel mengikuti halaman Data Kendaraan: bersih, kolom ringkas --}}
<div class="card shadow mb-4" style="border-radius:15px; border:none;">
    <div class="card-header py-3"
         style="border-radius:15px 15px 0 0; background:#fff; border-bottom:2px solid #f1f1f1;">
        <h6 class="m-0 font-weight-bold" style="color:#BE4132;">
            <i class="fas fa-table mr-2"></i>
            Detail Analisis — {{ $selectedAnalisis['kendaraan'] }}
        </h6>
    </div>
    <div class="card-body p-4">
        @if(count($selectedAnalisis['bulan_prediksi']) > 0)

        <div class="table-responsive">
            <table class="table table-hover" id="dataTable" width="100%" style="font-size:0.88rem;">
                <thead>
                    <tr>
                        <th class="text-center" style="width:50px;">No</th>
                        <th style="min-width:110px;">Periode</th>
                        <th style="min-width:130px;">Seasonal</th>
                        <th class="text-center" style="min-width:90px;">Prediksi</th>
                        <th class="text-center" style="min-width:110px;">Unit Dipinjam</th>
                        <th class="text-center" style="min-width:100px;">Utilisasi</th>
                        <th class="text-center" style="min-width:100px;">Status</th>
                        <th style="min-width:220px;">Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selectedAnalisis['bulan_prediksi'] as $i => $b)
                    <tr style="{{ $b['is_peak'] ? 'background:#f6fff8;' : ($b['is_low'] ? 'background:#fafafa;' : '') }}">

                        {{-- No — bulat abu seperti tabel Data Kendaraan --}}
                        <td class="text-center align-middle">
                            <span style="display:inline-flex; align-items:center; justify-content:center;
                                         width:28px; height:28px; border-radius:50%;
                                         background:#f0f0f0; color:#555; font-size:0.8rem; font-weight:600;">
                                {{ $i + 1 }}
                            </span>
                        </td>

                        {{-- Periode --}}
                        <td class="align-middle font-weight-bold">{{ $b['periode'] }}</td>

                        {{-- Seasonal — badge ringkas, tanpa prefix "Peak -" --}}
                        <td class="align-middle">
                            @if($b['is_peak'])
                                <span class="badge badge-pill px-2 py-1"
                                      style="background:#e8f5e9; color:#2e7d32; font-size:0.75rem;">
                                    🌟 {{ $b['seasonal_short'] }}
                                </span>
                            @elseif($b['is_low'])
                                <span class="badge badge-pill px-2 py-1"
                                      style="background:#f5f5f5; color:#757575; font-size:0.75rem;">
                                    📉 Low Season
                                </span>
                            @else
                                <span class="badge badge-pill px-2 py-1"
                                      style="background:#e3f2fd; color:#1565c0; font-size:0.75rem;">
                                    ⚪ Normal
                                </span>
                            @endif
                        </td>

                        {{-- Prediksi transaksi dari TES --}}
                        <td class="text-center align-middle font-weight-bold">
                            {{ $b['forecast'] }}
                        </td>

                        {{-- Unit dipinjam — angka paling penting --}}
                        <td class="text-center align-middle">
                            @if($b['unit_pinjam'] > 0)
                                <span class="font-weight-bold" style="color:#c62828; font-size:0.95rem;">
                                    {{ $b['unit_pinjam'] }} unit
                                </span>
                            @else
                                <span style="color:#2e7d32; font-size:0.85rem;">— tidak perlu</span>
                            @endif
                        </td>

                        {{-- % Utilisasi + progress bar --}}
                        <td class="text-center align-middle" style="min-width:90px;">
                            @php $u = $b['utilisasi']; @endphp
                            <span class="font-weight-bold" style="font-size:0.88rem;
                                  color:{{ $u > 100 ? '#c62828' : ($u > 80 ? '#e65100' : '#2e7d32') }};">
                                {{ $u }}%
                            </span>
                            <div style="height:5px; border-radius:4px; background:#eee; margin-top:4px; position:relative;">
                                <div style="height:5px; border-radius:4px;
                                            width:{{ min(100, $u) }}%;
                                            background:{{ $u > 100 ? '#c62828' : ($u > 80 ? '#f6c23e' : '#1cc88a') }};">
                                </div>
                            </div>
                        </td>

                        {{-- Status badge --}}
                        <td class="text-center align-middle">
                            <span class="badge badge-pill px-3 py-1"
                                  style="background:{{ $b['status']['bg_color'] }};
                                         color:{{ $b['status']['text_color'] }};
                                         font-weight:600; font-size:0.8rem;">
                                {{ $b['status']['icon'] }} {{ $b['status']['label'] }}
                            </span>
                        </td>

                        {{-- Rekomendasi --}}
                        <td class="align-middle" style="font-size:0.82rem; color:#444;">
                            {{ $b['rekomendasi'] }}
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @else
        <div class="alert alert-warning text-center mb-0" style="border-radius:10px;">
            <i class="fas fa-exclamation-circle mr-2"></i>
            Belum ada data peramalan untuk kendaraan ini.
            Silakan lakukan peramalan di menu <strong>Metode Peramalan</strong> terlebih dahulu.
        </div>
        @endif
    </div>
</div>

{{-- ================= GRAFIK ================= --}}
<div class="card shadow mb-4" style="border-radius:15px; border:none;">
    <div class="card-header py-3"
         style="border-radius:15px 15px 0 0; background:#fff; border-bottom:2px solid #f1f1f1;">
        <h6 class="m-0 font-weight-bold" style="color:#BE4132;">
            <i class="fas fa-chart-bar mr-2"></i>
            Grafik Analisis Armada — {{ $selectedAnalisis['kendaraan'] }}
        </h6>
    </div>
    <div class="card-body p-4">
        <div class="row">
            <div class="col-md-8 mb-3">
                <p class="text-muted mb-2" style="font-size:0.8rem;">
                    Unit stok tetap vs unit yang perlu dipinjam per bulan
                </p>
                <div style="height:260px;">
                    <canvas id="analisisChart"></canvas>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <p class="text-muted mb-2" style="font-size:0.8rem;">
                    % Utilisasi stok
                    <span style="color:#c62828;">━</span> Kritis (100%)
                    <span style="color:#f6c23e;">━</span> Waspada (80%)
                </p>
                <div style="height:260px;">
                    <canvas id="utilisasiChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@endif

{{-- ================= LEGENDA STATUS ================= --}}
<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="card border-0 shadow-sm"
             style="border-radius:12px; border-left:4px solid #1cc88a !important;">
            <div class="card-body py-3 px-4">
                <div class="font-weight-bold mb-1" style="color:#2e7d32; font-size:0.9rem;">
                    ✅ Aman — Utilisasi ≤ 80%
                </div>
                <div style="font-size:0.8rem; color:#666;">
                    Stok tetap lebih dari cukup. Tidak perlu tindakan.
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card border-0 shadow-sm"
             style="border-radius:12px; border-left:4px solid #f6c23e !important;">
            <div class="card-body py-3 px-4">
                <div class="font-weight-bold mb-1" style="color:#e65100; font-size:0.9rem;">
                    ⚠️ Waspada — Utilisasi 80–100%
                </div>
                <div style="font-size:0.8rem; color:#666;">
                    Stok mepet. Koordinasi dengan mitra rental untuk berjaga-jaga.
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card border-0 shadow-sm"
             style="border-radius:12px; border-left:4px solid #e74a3b !important;">
            <div class="card-body py-3 px-4">
                <div class="font-weight-bold mb-1" style="color:#c62828; font-size:0.9rem;">
                    🔴 Kritis — Utilisasi > 100%
                </div>
                <div style="font-size:0.8rem; color:#666;">
                    Stok tidak cukup. Wajib pinjam unit sesuai kolom "Unit Dipinjam".
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    @if($selectedAnalisis && count($selectedAnalisis['bulan_prediksi']) > 0)

    $('#dataTable').DataTable({
        pageLength: 10,
        order: [],
        language: { paginate: { previous: 'Prev', next: 'Next' } }
    });

    var labels       = @json($chartData['labels']);
    var unitPinjam   = @json($chartData['unit_pinjam']);
    var unitTersedia = @json($chartData['unit_tersedia']);
    var utilisasi    = @json($chartData['utilisasi']);

    // ── Bar chart: stok tetap vs unit dipinjam ──
    new Chart(document.getElementById("analisisChart"), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Unit Stok Tetap",
                    backgroundColor: "rgba(78,115,223,0.75)",
                    borderColor: "#4e73df",
                    borderWidth: 1,
                    data: unitTersedia,
                },
                {
                    label: "Unit Perlu Dipinjam",
                    backgroundColor: "rgba(190,65,50,0.8)",
                    borderColor: "#BE4132",
                    borderWidth: 1,
                    data: unitPinjam,
                }
            ],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                xAxes: [{ gridLines: { display: false }, maxBarThickness: 40 }],
                yAxes: [{
                    ticks: { min: 0, stepSize: 1, padding: 8 },
                    gridLines: { color: "rgb(234,236,244)", drawBorder: false, borderDash: [2] }
                }],
            },
            legend: { display: true, position: 'bottom' },
            tooltips: {
                mode: 'index', intersect: false,
                backgroundColor: "#fff",
                bodyFontColor: "#858796",
                titleFontColor: '#6e707e',
                borderColor: '#dddfeb', borderWidth: 1,
                xPadding: 12, yPadding: 12,
                callbacks: {
                    afterBody: function(items) {
                        var p = items[1] ? items[1].yLabel : 0;
                        return p > 0
                            ? ['', '⚠ Pinjam ' + p + ' unit dari rental lain']
                            : ['', '✅ Stok sendiri mencukupi'];
                    }
                }
            },
        }
    });

    // ── Line chart: % utilisasi ──
    var pointColors = utilisasi.map(function(u) {
        return u > 100 ? '#c62828' : (u > 80 ? '#e65100' : '#1cc88a');
    });

    new Chart(document.getElementById("utilisasiChart"), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: "% Utilisasi",
                data: utilisasi,
                borderColor: "#BE4132",
                backgroundColor: "rgba(190,65,50,0.07)",
                pointBackgroundColor: pointColors,
                pointRadius: 5,
                borderWidth: 2,
                fill: true,
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                xAxes: [{ gridLines: { display: false }, ticks: { fontSize: 10 } }],
                yAxes: [{
                    ticks: { min: 0, padding: 8, callback: function(v) { return v + '%'; } },
                    gridLines: { color: "rgb(234,236,244)", borderDash: [2] }
                }],
            },
            legend: { display: false },
            tooltips: {
                callbacks: {
                    label: function(item) {
                        var u = item.yLabel;
                        var s = u > 100 ? '🔴 Kritis' : (u > 80 ? '⚠️ Waspada' : '✅ Aman');
                        return ' ' + u + '% — ' + s;
                    }
                }
            }
        }
    });

    @endif
});
</script>
@endpush
