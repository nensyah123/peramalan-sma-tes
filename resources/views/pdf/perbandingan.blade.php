<!DOCTYPE html>
<html>
<head>
    <title>Laporan Perbandingan Metode</title>
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
        
        .badge { padding: 5px 10px; border-radius: 5px; color: #fff; font-size: 8pt; }
        .badge-success { background-color: #1cc88a; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Perbandingan Metode</h2>
    </div>
    
    <table class="info-table">
        <tr>
            <td width="20%"><strong>Kendaraan</strong></td>
            <td>: {{ $perbandingan->kendaraan->nama_kendaraan }}</td>
        </tr>
    </table>

    <h4 style="margin-bottom: 10px; border-left: 4px solid #4e73df; padding-left: 8px;">Metrik Error</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th>Metode</th>
                <th>MAE</th>
                <th>MSE</th>
                <th>MAPE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>SMA</td>
                <td>{{ $perbandingan->mae_sma }}</td>
                <td>{{ $perbandingan->mse_sma }}</td>
                <td>{{ $perbandingan->mape_sma }}%</td>
            </tr>
            <tr>
                <td>TES</td>
                <td>{{ $perbandingan->mae_tes }}</td>
                <td>{{ $perbandingan->mse_tes }}</td>
                <td>{{ $perbandingan->mape_tes }}%</td>
            </tr>
        </tbody>
    </table>

    <div style="page-break-inside: avoid;">
        <h4 style="margin-bottom: 10px; border-left: 4px solid #1cc88a; padding-left: 8px;">Grafik Perbandingan</h4>
        
        <!-- SVG Chart -->
        <div class="chart-container">
            <img src="{{ $chartImage }}" style="width: 100%; height: auto;">
            <div style="font-size: 10px; color: #888; margin-top: 5px;">Perbandingan Data Aktual vs SMA vs TES</div>
        </div>
        
        <!-- Best Method -->
        <div style="text-align: center; margin-top: 20px;">
            <h4>Metode Terbaik: <span class="badge badge-success">{{ $perbandingan->metode_terbaik }}</span></h4>
        </div>
    </div>
    
    <div style="text-align: right; font-size: 9px; color: #aaa; margin-top: 30px;">
        Dicetak pada: {{ date('d-m-Y H:i') }}
    </div>

</body>
</html>
