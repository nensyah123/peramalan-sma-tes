<!DOCTYPE html>
<html>
<head>
    <title>Laporan Peramalan SMA</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #333; }
        .header { margin-bottom: 25px; border-bottom: 2px solid #4e73df; padding-bottom: 10px; }
        h2 { margin: 0 0 10px 0; color: #2e3e50; }
        .info-table { width: 100%; margin-bottom: 20px; border: none; }
        .info-table td { border: none; padding: 4px 0; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9pt; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; text-align: center; }
        table.data-table th { background-color: #f8f9fc; color: #4e73df; font-weight: bold; }
        
        .chart-container { width: 100%; text-align: center; margin-bottom: 20px; padding: 10px; border: 1px solid #eee; background-color: #fff; page-break-inside: avoid; }
        

    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Peramalan SMA</h2>
    </div>
    
    <table class="info-table">
        <tr>
            <td width="20%"><strong>Kendaraan</strong></td>
            <td>: {{ $peramalan->kendaraan->nama_kendaraan }}</td>
        </tr>
        <tr>
            <td><strong>Periode (n)</strong></td>
            <td>: {{ $peramalan->periode_sma }}</td>
        </tr>
        <tr>
            <td><strong>Durasi Prediksi</strong></td>
            <td>: {{ $peramalan->durasi_prediksi }} Bulan</td>
        </tr>
        <tr>
            <td><strong>Metode</strong></td>
            <td>: SMA (Simple Moving Average)</td>
        </tr>
    </table>

    <h4 style="margin-bottom: 10px; border-left: 4px solid #4e73df; padding-left: 8px;">Tabel Data</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th>Bulan/Tahun</th>
                <th>Data Aktual</th>
                <th>Data Prediksi</th>
                <th>Error (Abs)</th>
                <th>Error^2</th>
                <th>APE (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($table as $row)
            <tr>
                <td>{{ $row['bulan_tahun'] ?? '-' }}</td>
                <td>{{ $row['aktual'] ?? '-' }}</td>
                <td>{{ $row['prediksi'] ?? '-' }}</td>
                <td>{{ $row['error'] ?? '-' }}</td>
                <td>{{ $row['error_sqr'] ?? '-' }}</td>
                <td>{{ isset($row['ape']) && $row['ape'] !== '-' ? $row['ape'] . '%' : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="page-break-inside: avoid;">
        <h4 style="margin-bottom: 10px; border-left: 4px solid #1cc88a; padding-left: 8px;">Grafik & Metrik</h4>
        
        <!-- SVG Chart -->
        <div class="chart-container">
            <img src="{{ $chartImage }}" style="width: 100%; height: auto;">
            <div style="font-size: 10px; color: #888; margin-top: 5px;">Perbandingan Data Aktual vs Prediksi</div>
        </div>
        
        <!-- Metrics -->
        <div style="margin-top: 20px;">
            <table class="data-table" style="width: 50%; margin: 0 auto;">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>MAE</td><td>{{ $metrics['mae'] }}</td></tr>
                    <tr><td>MSE</td><td>{{ $metrics['mse'] }}</td></tr>
                    <tr><td>MAPE</td><td>{{ $metrics['mape'] }}%</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div style="text-align: right; font-size: 9px; color: #aaa; margin-top: 30px;">
        Dicetak pada: {{ date('d-m-Y H:i') }}
    </div>

</body>
</html>
