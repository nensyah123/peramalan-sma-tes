<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanSma;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PemakaianKendaraan;

class PeramalanSmaController extends Controller
{
    public function index()
    {
        $kendaraans = Kendaraan::all();
        $riwayat = PeramalanSma::with('kendaraan')->latest()->get();
        return view('menu.peramalan_sma', compact('kendaraans', 'riwayat'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'periode' => 'required|integer|min:1',
            'durasi_prediksi' => 'required|integer|min:1',
        ]);

        $id_kendaraan = $request->id_kendaraan;
        $periode = $request->periode; // n
        $durasi = $request->durasi_prediksi;

        // Ambil Data Historis
        $dataPemakaian = PemakaianKendaraan::where('id_kendaraan', $id_kendaraan)
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        if ($dataPemakaian->count() < $periode) {
            return back()->with('error', 'Data historis tidak cukup untuk melakukan peramalan dengan periode (n) = ' . $periode);
        }

        $resultTable = [];
        $d = [];
        foreach ($dataPemakaian as $idx => $pem) {
            $d[] = [
                'bulan' => $pem->bulan,
                'tahun' => $pem->tahun,
                'aktual' => $pem->jumlah_transaksi,
                'bulan_tahun' => $this->getMonthName($pem->bulan) . ' ' . $pem->tahun
            ];
        }

        $total_error_abs = 0;
        $total_error_sqr = 0;
        $total_ape = 0;
        $count_error = 0;

        // 1. Perhitungan SMA pada Data Historis (Pengujian)
        for ($i = 0; $i < count($d); $i++) {
            $prediksi = null;
            $error_abs = null;
            $error_sqr = null;
            $ape = null;

            // Hitung jika data historis sebelumnya mencukupi (>= n)
            if ($i >= $periode) {
                $sum = 0;
                for ($k = 1; $k <= $periode; $k++) {
                    $sum += $d[$i - $k]['aktual'];
                }
                $prediksi = $sum / $periode;
                $prediksi = round($prediksi, 2);

                $aktual = $d[$i]['aktual'];

                // Hitung Error
                $error_abs = abs($aktual - $prediksi);
                $error_sqr = pow($error_abs, 2);
                $ape = ($aktual != 0) ? ($error_abs / $aktual) * 100 : 0;

                $total_error_abs += $error_abs;
                $total_error_sqr += $error_sqr;
                $total_ape += $ape;
                $count_error++;
            }

            $d[$i]['prediksi'] = $prediksi;
            $d[$i]['error_abs'] = $error_abs;
            $d[$i]['error_sqr'] = $error_sqr;
            $d[$i]['ape'] = $ape;

            $resultTable[] = [
                'bulan_tahun' => $d[$i]['bulan_tahun'],
                'aktual' => $d[$i]['aktual'],
                'prediksi' => $prediksi,
                'error' => $error_abs !== null ? number_format($error_abs, 2) : '-',
                'error_sqr' => $error_sqr !== null ? number_format($error_sqr, 2) : '-',
                'ape' => $ape !== null ? number_format($ape, 2) : '-',
            ];
        }

        // Hitung Rata-rata Error
        $mad = ($count_error > 0) ? round($total_error_abs / $count_error, 2) : 0;
        $mse = ($count_error > 0) ? round($total_error_sqr / $count_error, 2) : 0;
        $mape = ($count_error > 0) ? round($total_ape / $count_error, 2) : 0;

        // 2. Peramalan Masa Depan
        $tempData = $d;
        $lastMonth = $d[count($d) - 1]['bulan'];
        $lastYear = $d[count($d) - 1]['tahun'];

        for ($j = 0; $j < $durasi; $j++) {
            // Update Tanggal
            $lastMonth++;
            if ($lastMonth > 12) {
                $lastMonth = 1;
                $lastYear++;
            }

            // Hitung Prediksi Masa Depan
            // Mengambil n nilai terakhir dari tempData (bisa campuran aktual dan prediksi sebelumnya)
            $len = count($tempData);
            $sum = 0;
            for ($k = 1; $k <= $periode; $k++) {
                $idx = $len - $k;
                // Gunakan aktual jika ada, jika tidak gunakan hasil prediksi sebelumnya
                if (isset($tempData[$idx]['aktual'])) {
                    $val = $tempData[$idx]['aktual'];
                } else {
                    $val = $tempData[$idx]['prediksi'];
                }
                $sum += $val;
            }
            $predFuture = round($sum / $periode, 2);

            $newRow = [
                'bulan' => $lastMonth,
                'tahun' => $lastYear,
                'bulan_tahun' => $this->getMonthName($lastMonth) . ' ' . $lastYear,
                'aktual' => null, // Masa depan
                'prediksi' => $predFuture,
                'error_abs' => null,
                'error_sqr' => null,
                'ape' => null
            ];

            $tempData[] = $newRow;
            $resultTable[] = [
                'bulan_tahun' => $newRow['bulan_tahun'],
                'aktual' => '-',
                'prediksi' => $predFuture,
                'error' => '-',
                'error_sqr' => '-',
                'ape' => '-'
            ];
        }

        // Persiapan Data Grafik
        $chartLabels = [];
        $actualData = [];
        $predictedData = [];

        foreach ($resultTable as $row) {
            $chartLabels[] = $row['bulan_tahun'];
            $actualData[] = $row['aktual'] !== '-' ? $row['aktual'] : null;
            $predictedData[] = $row['prediksi'] !== '-' ? $row['prediksi'] : null;
        }

        // Variabel untuk disimpan (JSON)
        $data_peramalan = [
            'metrics' => ['mad' => $mad, 'mse' => $mse, 'mape' => $mape],
            'table' => $resultTable,
            'chart' => ['labels' => $chartLabels, 'actual' => $actualData, 'predicted' => $predictedData]
        ];

        // Variabel untuk modal view (string JSON)
        $data_json = json_encode($data_peramalan);

        $kendaraans = Kendaraan::all();
        $riwayat = PeramalanSma::with('kendaraan')->latest()->get();

        return view('menu.peramalan_sma', compact(
            'kendaraans',
            'riwayat',
            'mad',
            'mse',
            'mape',
            'chartLabels',
            'actualData',
            'predictedData',
            'resultTable',
            'id_kendaraan',
            'periode',
            'durasi',
            'data_peramalan',
            'data_json'
        ))->with('showResult', true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'periode' => 'required',
            'durasi_prediksi' => 'required',
            'mad' => 'required',
            'mse' => 'required',
            'mape' => 'required',
            'data_peramalan' => 'required', // JSON string
        ]);

        PeramalanSma::create([
            'id_kendaraan' => $request->id_kendaraan,
            'periode_sma' => $request->periode,
            'durasi_prediksi' => $request->durasi_prediksi,
            'mad' => $request->mad,
            'mse' => $request->mse,
            'mape' => $request->mape,
            'data_peramalan' => json_decode($request->data_peramalan, true)
        ]);

        return redirect()->route('peramalan_sma.index')->with('success', 'Hasil peramalan berhasil disimpan.');
    }

    public function destroy($id)
    {
        PeramalanSma::findOrFail($id)->delete();
        return redirect()->route('peramalan_sma.index')->with('success', 'Riwayat peramalan berhasil dihapus.');
    }

    public function exportPdf($id)
    {
        $peramalan = PeramalanSma::with('kendaraan')->findOrFail($id);

        // 1. Ambil Metrics dari kolom database (bukan dari JSON)
        $metrics = [
            'mad' => $peramalan->mad,
            'mse' => $peramalan->mse,
            'mape' => $peramalan->mape
        ];

        // 2. Ambil Data Tabel & Chart
        $data = $peramalan->data_peramalan;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // Cek struktur data (apakah format baru 'table'/'chart' atau format lama hanya array tabel)
        $table = [];
        $chartData = [];

        if (isset($data['table'])) {
            // Format Baru (Full Structure)
            $table = $data['table'];
            $chartData = $data['chart'];
        } else {
            // Format Lama (Langsung Array Tabel)
            $table = $data;

            // Rekonstruksi Data Chart dari Tabel
            $labels = [];
            $actuals = [];
            $predicteds = [];

            foreach ($table as $row) {
                $labels[] = $row['bulan_tahun'];
                $actuals[] = ($row['aktual'] !== '-' && $row['aktual'] !== null) ? $row['aktual'] : null;
                $predicteds[] = ($row['prediksi'] !== '-' && $row['prediksi'] !== null) ? $row['prediksi'] : null;
            }

            $chartData = [
                'labels' => $labels,
                'actual' => $actuals,
                'predicted' => $predicteds
            ];
        }

        // 3. Prepare Chart Data for SVG
        $labels = $chartData['labels'];
        $actuals = $chartData['actual'];
        $predicteds = $chartData['predicted'];

        // Calculate Min/Max for Y-axis scaling
        $allValues = array_merge(
            array_filter($actuals, fn($v) => $v !== null),
            array_filter($predicteds, fn($v) => $v !== null)
        );
        $minY = empty($allValues) ? 0 : min($allValues);
        $maxY = empty($allValues) ? 100 : max($allValues);
        // Add padding
        $padding = ($maxY - $minY) * 0.1;
        if ($padding == 0) $padding = 10;
        $minY = max(0, $minY - $padding);
        $maxY = $maxY + $padding;

        // SVG Dimensions
        $svgWidth = 1000;
        $svgHeight = 300;
        $paddingLeft = 50;
        $paddingBottom = 30;
        $graphWidth = $svgWidth - $paddingLeft;
        $graphHeight = $svgHeight - $paddingBottom;

        $count = count($labels);
        $stepX = $graphWidth / max(1, $count - 1);

        // Function to get Y coordinate
        $getY = function ($val) use ($graphHeight, $minY, $maxY) {
            if ($val === null) return null;
            $range = max(1, $maxY - $minY);
            $ratio = ($val - $minY) / $range;
            return $graphHeight - ($ratio * $graphHeight);
        };

        $actualPoints = [];
        $predictedPoints = [];

        foreach ($labels as $i => $label) {
            $x = $paddingLeft + ($i * $stepX);

            $yAct = $getY($actuals[$i] ?? null);
            if ($yAct !== null) $actualPoints[] = "$x,$yAct";

            $yPred = $getY($predicteds[$i] ?? null);
            if ($yPred !== null) $predictedPoints[] = "$x,$yPred";
        }

        // Generate SVG String
        $svgContent = '<svg width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '" xmlns="http://www.w3.org/2000/svg">';
        // Background & Border
        $svgContent .= '<rect x="0" y="0" width="' . $svgWidth . '" height="' . $svgHeight . '" fill="none" stroke="#f0f0f0" stroke-width="1" />';

        // Grid Lines
        for ($i = 0; $i <= 4; $i++) {
            $y = ($svgHeight - $paddingBottom) - ($i * ($svgHeight - $paddingBottom - 20) / 4);
            $val = $minY + ($i * ($maxY - $minY) / 4);
            $svgContent .= '<line x1="' . $paddingLeft . '" y1="' . $y . '" x2="' . $svgWidth . '" y2="' . $y . '" stroke="#e6e6e6" stroke-width="1" stroke-dasharray="4" />';
            $svgContent .= '<text x="' . ($paddingLeft - 5) . '" y="' . ($y + 3) . '" font-family="sans-serif" font-size="10" fill="#888" text-anchor="end">' . number_format($val, 0) . '</text>';
        }

        // Lines
        $actPts = implode(' ', $actualPoints);
        $predPts = implode(' ', $predictedPoints);
        $svgContent .= '<polyline points="' . $actPts . '" fill="none" stroke="#4e73df" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />';
        $svgContent .= '<polyline points="' . $predPts . '" fill="none" stroke="#1cc88a" stroke-width="2" stroke-dasharray="5,5" stroke-linecap="round" stroke-linejoin="round"/>';

        // X Labels
        // Determine skip factor to avoid overlapping
        $skip = 1;
        if ($count > 12) {
            $skip = ceil($count / 12);
        }

        foreach ($labels as $i => $label) {
            $x = $paddingLeft + ($i * $stepX);
            $y = $svgHeight - 10;

            // Logic to show label: always first, always last, and respecting skip factor
            if ($i == 0 || $i == $count - 1 || $i % $skip == 0) {
                // Optional: Shorten label if needed (e.g. "January 2023" -> "Jan 23")
                // Assuming format might be "Month YYYY" or "M YYYY"
                // Let's try to keep it as is but rely on skipping. 
                // If you want to shorten:
                // $shortLabel = substr($label, 0, 3) . ' ' .  substr($label, -2);
                $svgContent .= '<text x="' . $x . '" y="' . $y . '" font-family="sans-serif" font-size="9" fill="#666" text-anchor="middle">' . $label . '</text>';
            }
        }

        // Legend
        $svgContent .= '<rect x="' . ($svgWidth - 150) . '" y="10" width="10" height="10" fill="#4e73df" />';
        $svgContent .= '<text x="' . ($svgWidth - 135) . '" y="19" font-family="sans-serif" font-size="11" fill="#333">Data Aktual</text>';
        $svgContent .= '<rect x="' . ($svgWidth - 70) . '" y="10" width="10" height="10" fill="none" stroke="#1cc88a" stroke-width="2" stroke-dasharray="4" />';
        $svgContent .= '<text x="' . ($svgWidth - 55) . '" y="19" font-family="sans-serif" font-size="11" fill="#333">Prediksi</text>';

        $svgContent .= '</svg>';

        $chartImage = 'data:image/svg+xml;base64,' . base64_encode($svgContent);

        $pdf = Pdf::loadView('pdf.peramalan_sma', compact('peramalan', 'metrics', 'table', 'chartImage'));
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions(['dpi' => 150, 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        return $pdf->download('laporan_sma_' . $peramalan->created_at->format('YmdHis') . '.pdf');
    }

    private function getMonthName($monthNum)
    {
        $dateObj   = \DateTime::createFromFormat('!m', $monthNum);
        return $dateObj->format('F');
    }
}
