<!DOCTYPE html>
<html>
<head>
    <title>Laporan Peramalan TES</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #333; }
        .header { margin-bottom: 25px; border-bottom: 2px solid #4e73df; padding-bottom: 10px; }
        h2 { margin: 0 0 5px 0; color: #2e3e50; }
        .subtitle { color: #888; font-size: 9pt; margin: 0; }
        .info-table { width: 100%; margin-bottom: 20px; border: none; }
        .info-table td { border: none; padding: 4px 0; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 8pt; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 4px; text-align: center; }
        table.data-table th { background-color: #f8f9fc; color: #4e73df; font-weight: bold; }
        .chart-container { width: 100%; text-align: center; margin-bottom: 20px; padding: 10px; border: 1px solid #eee; background-color: #fff; page-break-inside: avoid; }
        .section-title { margin-bottom: 10px; border-left: 4px solid #4e73df; padding-left: 8px; }
        .future-row { background-color: #f0fff8; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Laporan Peramalan Triple Exponential Smoothing</h2>
        <p class="subtitle">CV Mitra Sempurna Jaya Trans — Sistem Prediksi Permintaan Armada</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="25%"><strong>Merk Kendaraan</strong></td>
            <td>: <strong>{{ $peramalan->merk }}</strong></td>
        </tr>
        <tr>
            <td><strong>Alpha (α)</strong></td>
            <td>: {{ $peramalan->alfa }}</td>
        </tr>
        <tr>
            <td><strong>Beta (β)</strong></td>
            <td>: {{ $peramalan->beta }}</td>
        </tr>
        <tr>
            <td><strong>Gamma (γ)</strong></td>
            <td>: {{ $peramalan->gamma }}</td>
        </tr>
        <tr>
            <td><strong>Durasi Prediksi</strong></td>
            <td>: {{ $peramalan->durasi_prediksi }} Bulan</td>
        </tr>
        <tr>
            <td><strong>Metode</strong></td>
            <td>: TES (Triple Exponential Smoothing / Holt-Winters)</td>
        </tr>
        <tr>
            <td><strong>Tanggal Laporan</strong></td>
            <td>: {{ $peramalan->created_at->format('d M Y') }}</td>
        </tr>
    </table>

    <h4 class="section-title">Grafik Aktual vs Prediksi</h4>
    <div class="chart-container">
        <img src="{{ $chartImage }}" style="width: 100%; height: auto;">
        <div style="font-size: 9px; color: #888; margin-top: 5px;">
            Garis Biru = Data Aktual &nbsp;|&nbsp; Garis Hijau (putus-putus) = Prediksi TES
        </div>
    </div>

    <h4 class="section-title">Metrik Akurasi</h4>
    <table class="data-table" style="width: 60%; margin-bottom: 20px;">
        <thead>
            <tr>
                <th>Metrik</th>
                <th>Nilai</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>MAD</strong></td>
                <td>{{ $metrics['mad'] }}</td>
                <td>Rata-rata selisih absolut</td>
            </tr>
            <tr>
                <td><strong>MSE</strong></td>
                <td>{{ $metrics['mse'] }}</td>
                <td>Sensitivitas error besar</td>
            </tr>
            <tr>
                <td><strong>MAPE</strong></td>
                <td>{{ $metrics['mape'] }}%</td>
                <td>
                    @if($metrics['mape'] <= 10) Sangat Baik
                    @elseif($metrics['mape'] <= 20) Baik
                    @elseif($metrics['mape'] <= 50) Cukup
                    @else Kurang @endif
                </td>
            </tr>
        </tbody>
    </table>

    <h4 class="section-title">Tabel Iterasi TES</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Bulan/Tahun</th>
                <th>Aktual</th>
                <th>Level</th>
                <th>Trend</th>
                <th>Seasonal</th>
                <th>Prediksi</th>
                <th>Error</th>
                <th>APE (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($table as $i => $row)
            <tr class="{{ ($row['aktual'] ?? '-') === '-' ? 'future-row' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td>{{ $row['bulan_tahun'] ?? '-' }}</td>
                <td>{{ $row['aktual'] ?? '-' }}</td>
                <td>{{ $row['level'] ?? '-' }}</td>
                <td>{{ $row['trend'] ?? '-' }}</td>
                <td>{{ $row['seasonal'] ?? '-' }}</td>
                <td><strong>{{ $row['prediksi'] ?? '-' }}</strong></td>
                <td>{{ $row['error'] ?? '-' }}</td>
                <td>{{ isset($row['ape']) && $row['ape'] !== '-' ? $row['ape'].'%' : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="text-align: right; font-size: 9px; color: #aaa; margin-top: 20px; border-top: 1px solid #eee; padding-top: 8px;">
        Dicetak pada: {{ date('d M Y, H:i') }} &nbsp;|&nbsp; Sistem Prediksi Permintaan Armada — MSJ Trans
    </div>

</body>
</html>
