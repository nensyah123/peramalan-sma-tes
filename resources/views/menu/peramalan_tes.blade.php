@extends('layouts.app')

@section('content')

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif

<div class="card shadow-sm border-0 mb-4" style="border-radius:14px;">
    <div class="card-body p-4">

        <form action="{{ route('peramalan_tes.process') }}" method="POST" id="formPeramalan">
            @csrf

            {{-- STEP 1: Pilih Merk --}}
            <div class="mb-3">
                <p class="font-weight-bold mb-2" style="font-size:0.85rem;color:#444;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#74271f;color:#fff;font-size:0.68rem;font-weight:700;margin-right:7px;">1</span>
                    Pilih Merk Kendaraan
                </p>

                @php $merkColors = ['Avanza'=>'#e74c3c','Ertiga'=>'#27ae60','Innova'=>'#2980b9','Xenia'=>'#f39c12']; @endphp
                <div class="row">
                    @foreach($merks as $m)
                    @php $col = $merkColors[$m] ?? '#74271f'; @endphp
                    <div class="col-md-3 col-6 mb-2">
                        <label class="w-100 mb-0" style="cursor:pointer;">
                            <input type="radio" name="merk" value="{{ $m }}"
                                   class="d-none merk-radio"
                                   data-color="{{ $col }}"
                                   {{ isset($merk) && $merk == $m ? 'checked' : '' }} required>
                            <div class="merk-box d-flex align-items-center p-3 border"
                                 style="border-radius:10px;border-width:2px!important;transition:all 0.2s;
                                        {{ isset($merk) && $merk == $m ? "border-color:{$col};background:{$col}12;" : 'border-color:#e9ecef;background:#f8f9fc;' }}">
                                <div class="d-flex align-items-center justify-content-center mr-3"
                                     style="width:36px;height:36px;border-radius:8px;background:{{ $col }}20;">
                                    <i class="fas fa-car" style="color:{{ $col }};font-size:1rem;"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-dark" style="font-size:0.9rem;">{{ $m }}</div>
                                    <small class="text-muted" style="font-size:0.72rem;">Klik untuk pilih</small>
                                </div>
                                <div class="ml-auto merk-check" style="display:{{ isset($merk) && $merk == $m ? 'block' : 'none' }};">
                                    <i class="fas fa-check-circle" style="color:{{ $col }};font-size:1.1rem;"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            <hr class="my-3">

            {{-- STEP 2: Periode Prediksi --}}
            <div class="mb-2">
                <p class="font-weight-bold mb-2" style="font-size:0.85rem;color:#444;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#74271f;color:#fff;font-size:0.68rem;font-weight:700;margin-right:7px;">2</span>
                    Periode Prediksi
                </p>

                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="p-3" style="border-radius:12px;background:#f8f9fc;border:1.5px solid #e9ecef;">
                            <small class="text-muted d-block mb-2">Jumlah bulan ke depan yang akan diprediksi</small>
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <input type="number"
                                       id="durasi_input"
                                       name="durasi_prediksi"
                                       min="1"
                                       value="{{ isset($durasi) ? $durasi : 3 }}"
                                       required
                                       style="width:100px;height:42px;text-align:center;
                                              border:1.5px solid #dee2e6;border-radius:8px;
                                              font-size:1.1rem;font-weight:700;color:#74271f;
                                              background:#fff;outline:none;">
                                <span class="font-weight-bold" style="font-size:0.9rem;color:#555;">bulan</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 text-center mt-3 mt-md-0">
                        <button type="submit" class="btn text-white font-weight-bold px-4 py-2"
                                style="background:linear-gradient(135deg,#74271f,#c0392b);border:none;border-radius:8px;font-size:0.85rem;">
                            <i class="fas fa-cogs mr-1"></i> Proses Peramalan
                        </button>
                        <small class="text-muted d-block mt-1" style="font-size:0.7rem;">
                            <i class="fas fa-info-circle mr-1"></i>Python Statsmodels
                        </small>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

{{-- ===== HASIL ===== --}}
@if(isset($showResult) && $showResult)
<div id="result-card">

    {{-- PARAMETER HASIL PYTHON --}}
    <div class="alert border-0 mb-3 py-3 px-4" style="background:#eaf6ff;border-left:4px solid #2980b9!important;">
        <div class="d-flex align-items-center flex-wrap">
            <i class="fas fa-check-circle text-success mr-2 fa-lg"></i>
            <strong class="mr-3">Parameter Optimal (Statsmodels):</strong>
            <span class="mr-3">α = <strong class="text-danger">{{ $alpha }}</strong></span>
            <span class="mr-3">β = <strong class="text-primary">{{ $beta }}</strong></span>
            <span class="mr-3">γ = <strong class="text-success">{{ $gamma }}</strong></span>
            <span class="mr-3">Merk: <strong>{{ $merk }}</strong></span>
            <span>Periode: <strong>{{ $durasi }} bulan</strong></span>
        </div>
    </div>

    {{-- METRIK --}}
    <div class="row mb-3">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;border-left:4px solid #858796!important;">
                <div class="card-body py-3 px-4">
                    <div class="text-xs text-uppercase text-muted font-weight-bold mb-1">MAD</div>
                    <div class="h3 font-weight-bold text-dark mb-1">{{ $mad }}</div>
                    <small class="text-muted">Rata-rata selisih absolut prediksi vs aktual</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;border-left:4px solid #4e73df!important;">
                <div class="card-body py-3 px-4">
                    <div class="text-xs text-uppercase text-muted font-weight-bold mb-1">MSE</div>
                    <div class="h3 font-weight-bold text-dark mb-1">{{ $mse }}</div>
                    <small class="text-muted">Sensitivitas terhadap error besar</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;
                border-left:4px solid {{ $mape<=10?'#1cc88a':($mape<=20?'#4e73df':($mape<=50?'#f6c23e':'#e74a3b')) }}!important;">
                <div class="card-body py-3 px-4">
                    <div class="text-xs text-uppercase text-muted font-weight-bold mb-1">MAPE</div>
                    <div class="d-flex align-items-center mb-1">
                        <span class="h3 font-weight-bold mb-0 mr-2
                            {{ $mape<=10?'text-success':($mape<=20?'text-primary':($mape<=50?'text-warning':'text-danger')) }}">
                            {{ $mape }}%
                        </span>
                        @if($mape<=10)<span class="badge badge-success">Sangat Baik</span>
                        @elseif($mape<=20)<span class="badge badge-primary">Baik</span>
                        @elseif($mape<=50)<span class="badge badge-warning">Cukup</span>
                        @else<span class="badge badge-danger">Kurang</span>@endif
                    </div>
                    <small class="text-muted">Rata-rata persentase kesalahan prediksi</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        {{-- GRAFIK --}}
        <div class="col-lg-8 mb-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="font-weight-bold mb-0">
                            <i class="fas fa-chart-line text-primary mr-1"></i>
                            Aktual vs Prediksi — <span class="text-danger">{{ $merk }}</span>
                        </h6>
                        <small class="text-muted">α={{ $alpha }} β={{ $beta }} γ={{ $gamma }}</small>
                    </div>
                    <div style="height:260px;">
                        <canvas id="tesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- PREDIKSI KE DEPAN --}}
        <div class="col-lg-4 mb-3">
            <div class="card border-0 shadow-sm h-100 text-white"
                 style="border-radius:12px;background:linear-gradient(160deg,#1a7a4a,#27ae60);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-calendar-check mr-2 fa-lg"></i>
                        <div>
                            <div class="font-weight-bold" style="font-size:0.9rem;">Hasil Prediksi</div>
                            <small style="opacity:0.8;font-size:0.75rem;">{{ $merk }} — {{ $durasi }} bulan ke depan</small>
                        </div>
                    </div>
                    <div style="max-height:220px;overflow-y:auto;">
                        @foreach($resultTable as $row)
                            @if($row['aktual'] === '-')
                            <div class="py-2 px-2 mb-2 rounded" style="background:rgba(255,255,255,0.15);font-size:0.82rem;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="font-weight-bold" style="font-size:0.85rem;">
                                        <i class="fas fa-calendar-alt mr-1" style="opacity:0.8;"></i>
                                        {{ $row['bulan_tahun'] }}
                                    </span>
                                    <strong style="font-size:1rem;">
                                        {{ round($row['prediksi']) }}
                                        <small style="opacity:0.75;font-size:0.7rem;">kali sewa</small>
                                    </strong>
                                </div>
                                <small style="opacity:0.85;font-size:0.72rem;display:block;line-height:1.4;">
                                    Kendaraan <strong>{{ $merk }}</strong> diprediksi akan disewa sekitar
                                    <strong>{{ round($row['prediksi']) }} kali</strong> pada bulan {{ $row['bulan_tahun'] }}.
                                </small>
                            </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-2 pt-2" style="border-top:1px solid rgba(255,255,255,0.2);font-size:0.7rem;opacity:0.8;">
                        <i class="fas fa-info-circle mr-1"></i>
                        Nilai prediksi berdasarkan pola data historis menggunakan metode Triple Exponential Smoothing (TES).
                        Gunakan sebagai acuan persiapan armada.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL ITERASI --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-header bg-white border-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h6 class="font-weight-bold mb-0">
                <i class="fas fa-table text-primary mr-1"></i> Tabel Iterasi TES
            </h6>
            <span class="badge badge-light border">{{ count($resultTable) }} baris</span>
        </div>
        <div class="card-body p-3">

            {{-- REVISI: kotak keterangan kolom tanpa icon tanda tanya --}}
            <div class="mb-3 p-3" style="background:#f8f9fc;border-radius:10px;border:1px solid #e9ecef;">
                <p class="font-weight-bold mb-2" style="font-size:0.8rem;color:#444;">
                    <i class="fas fa-info-circle text-primary mr-1"></i> Keterangan Kolom Tabel
                </p>
                <div class="row" style="font-size:0.78rem;color:#555;">
                    <div class="col-md-4 mb-1">
                        <strong>Aktual</strong> — Jumlah sewa nyata yang terjadi pada bulan tersebut.
                    </div>
                    <div class="col-md-4 mb-1">
                        <strong>Level</strong> — Nilai rata-rata permintaan sewa yang sudah diperhalus (tanpa tren & musiman).
                    </div>
                    <div class="col-md-4 mb-1">
                        <strong>Trend</strong> — Kecenderungan naik/turun permintaan dari bulan ke bulan.
                    </div>
                    <div class="col-md-4 mb-1">
                        <strong>Seasonal</strong> — Pola musiman berulang tiap tahun (misal: ramai saat liburan/lebaran).
                    </div>
                    <div class="col-md-4 mb-1">
                        <strong>Prediksi</strong> — Perkiraan jumlah sewa hasil perhitungan TES (Level + Trend + Seasonal).
                    </div>
                    <div class="col-md-4 mb-1">
                        <strong>Error</strong> — Selisih aktual vs prediksi. Semakin kecil = semakin akurat.
                    </div>
                    <div class="col-md-12 mb-0 mt-1">
                        <strong>APE%</strong> — Persentase kesalahan prediksi per bulan:
                        <span class="badge badge-success ml-1">Sangat Baik &lt;10%</span>
                        <span class="badge badge-primary ml-1">Baik 10–20%</span>
                        <span class="badge badge-warning ml-1">Cukup 20–50%</span>
                        <span class="badge badge-danger ml-1">Kurang &gt;50%</span>
                    </div>
                </div>
            </div>
            {{-- AKHIR REVISI kotak keterangan --}}

            {{-- Legenda warna tabel --}}
            <div class="d-flex align-items-center mb-2" style="gap:16px;font-size:0.78rem;color:#6c757d;">
                <span><span style="display:inline-block;width:12px;height:12px;background:#343a40;border-radius:2px;margin-right:4px;"></span>Data historis aktual</span>
                <span><span style="display:inline-block;width:12px;height:12px;background:#d4edda;border:1px solid #28a745;border-radius:2px;margin-right:4px;"></span>Hasil prediksi ke depan</span>
            </div>

            <div class="table-responsive" style="max-height:340px;overflow-y:auto;">
                <table class="table table-bordered table-sm table-hover mb-0" style="font-size:0.77rem;">
                    <thead class="thead-dark" style="position:sticky;top:0;">
                        <tr>
                            {{-- REVISI: header tabel bersih tanpa icon --}}
                            <th>No</th>
                            <th>Bulan/Tahun</th>
                            <th>Aktual</th>
                            <th>Level</th>
                            <th>Trend</th>
                            <th>Seasonal</th>
                            <th>Prediksi</th>
                            <th>Error</th>
                            <th>APE%</th>
                            {{-- AKHIR REVISI --}}
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resultTable as $i => $row)
                        <tr class="{{ $row['aktual'] === '-' ? 'table-success font-weight-bold' : '' }}">
                            <td>{{ $i+1 }}</td>
                            <td>{{ $row['bulan_tahun'] }}</td>
                            <td>{{ $row['aktual'] }}</td>
                            <td>{{ $row['level'] }}</td>
                            <td>{{ $row['trend'] }}</td>
                            <td>{{ $row['seasonal'] }}</td>
                            <td><strong>{{ $row['prediksi'] }}</strong></td>
                            <td>{{ $row['error'] }}</td>
                            <td>{{ $row['ape'] !== '-' ? $row['ape'].'%' : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        

        </div>
    </div>

    {{-- TOMBOL AKSI --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
            <small class="text-muted">


            </small>
            <div class="d-flex">
                <form action="{{ route('peramalan_tes.store') }}" method="POST" class="mr-2">
                    @csrf
                    <input type="hidden" name="merk"            value="{{ $merk }}">
                    <input type="hidden" name="durasi_prediksi" value="{{ $durasi }}">
                    <input type="hidden" name="alpha"           value="{{ $alpha }}">
                    <input type="hidden" name="beta"            value="{{ $beta }}">
                    <input type="hidden" name="gamma"           value="{{ $gamma }}">
                    <input type="hidden" name="mad"             value="{{ $mad }}">
                    <input type="hidden" name="mse"             value="{{ $mse }}">
                    <input type="hidden" name="mape"            value="{{ $mape }}">
                    <input type="hidden" name="data_peramalan"  value="{{ json_encode($resultTable) }}">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fas fa-save mr-1"></i> Simpan ke Riwayat
                    </button>
                </form>
                <a href="{{ route('peramalan_tes.index') }}" class="btn btn-outline-secondary px-4">
                    <i class="fas fa-redo mr-1"></i> Ulangi
                </a>
            </div>
        </div>
    </div>

</div>
@endif

@endsection

@push('scripts')
<script>
document.querySelectorAll('.merk-radio').forEach(function(radio) {
    function applySelected(r) {
        document.querySelectorAll('.merk-radio').forEach(function(rx) {
            var box   = rx.nextElementSibling;
            var check = box.querySelector('.merk-check');
            box.style.borderColor = '#e9ecef';
            box.style.background  = '#f8f9fc';
            if (check) check.style.display = 'none';
        });
        var box   = r.nextElementSibling;
        var check = box.querySelector('.merk-check');
        var col   = r.dataset.color || '#74271f';
        box.style.borderColor = col;
        box.style.background  = col + '12';
        if (check) check.style.display = 'block';
    }
    radio.addEventListener('change', function() { applySelected(this); });
    if (radio.checked) applySelected(radio);
});

@if(isset($showResult) && $showResult)
new Chart(document.getElementById("tesChart"), {
    type: 'line',
    data: {
        labels: @json($chartLabels),
        datasets: [{
            label: "Aktual",
            lineTension: 0.3,
            backgroundColor: "rgba(78,115,223,0.07)",
            borderColor: "rgba(78,115,223,1)",
            borderWidth: 2, pointRadius: 2, pointHitRadius: 10,
            data: @json($actualData),
        },{
            label: "Prediksi TES",
            lineTension: 0.3,
            backgroundColor: "rgba(28,200,138,0.07)",
            borderColor: "rgba(28,200,138,1)",
            borderWidth: 2, pointRadius: 2, pointHitRadius: 10,
            borderDash: [5,3],
            data: @json($predictedData),
        }],
    },
    options: {
        maintainAspectRatio: false,
        scales: {
            xAxes: [{ gridLines:{display:false}, ticks:{maxTicksLimit:10,fontSize:10} }],
            yAxes: [{ ticks:{maxTicksLimit:5,padding:10,fontSize:10},
                gridLines:{color:"rgb(234,236,244)",drawBorder:false,borderDash:[2]} }],
        },
        legend: { display:true, position:'top', labels:{fontSize:11} },
        tooltips: {
            backgroundColor:"#fff", bodyFontColor:"#858796", titleFontColor:'#6e707e',
            borderColor:'#dddfeb', borderWidth:1,
            xPadding:15, yPadding:15, intersect:false, mode:'index',
        }
    }
});
setTimeout(function(){
    $('html,body').animate({scrollTop:$('#result-card').offset().top - 80},400);
},300);
@endif
</script>
@endpush
