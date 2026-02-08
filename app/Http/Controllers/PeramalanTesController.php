<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use Illuminate\Http\Request;
use App\Models\PemakaianKendaraan;
use Barryvdh\DomPDF\Facade\Pdf;

class PeramalanTesController extends Controller
{
    public function index()
    {
        $kendaraans = Kendaraan::all();
        $riwayat = PeramalanTes::with('kendaraan')->latest()->get();
        return view('menu.peramalan_tes', compact('kendaraans', 'riwayat'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'durasi_prediksi' => 'required|integer|min:1',
            'alpha' => 'required|numeric|min:0|max:1',
            'beta' => 'required|numeric|min:0|max:1',
            'gamma' => 'required|numeric|min:0|max:1',
        ]);

        $id_kendaraan = $request->id_kendaraan;
        $durasi = $request->durasi_prediksi;
        $alpha = $request->alpha;
        $beta = $request->beta;
        $gamma = $request->gamma;
        $L = 12; // Panjang Musim (Data Bulanan => 12 bulan)

        // Ambil Data Historis
        $dataPemakaian = PemakaianKendaraan::where('id_kendaraan', $id_kendaraan)
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        // Cek kecukupan data (Minimal L untuk inisialisasi)
        if ($dataPemakaian->count() < $L) {
            return back()->with('error', 'Data historis harus minimal ' . $L . ' bulan untuk metode TES (Musiman Tahunan).');
        }

        $resultTable = [];
        $d = [];
        foreach ($dataPemakaian as $pem) {
            $d[] = [
                'bulan' => $pem->bulan,
                'tahun' => $pem->tahun,
                'aktual' => $pem->jumlah_transaksi,
                'bulan_tahun' => $this->getMonthName($pem->bulan) . ' ' . $pem->tahun
            ];
        }

        // --- Inisialisasi ---
        $nData = count($d);
        $seasonals = [];

        // Hitung rata-rata musim pertama
        $avgFirstSeason = 0;
        for ($i = 0; $i < $L; $i++) $avgFirstSeason += $d[$i]['aktual'];
        $avgFirstSeason /= $L;

        // Inisialisasi Musiman (Model Aditif: Data - Rata-rata)
        for ($i = 0; $i < $L; $i++) {
            $seasonals[] = $d[$i]['aktual'] - $avgFirstSeason;
        }

        // Inisialisasi Level dan Trend pada t=L
        $level = $avgFirstSeason;
        $trend = 0;

        // Jika data cukup (minimal 2 tahun), hitung trend awal
        if ($nData >= 2 * $L) {
            $sumSecond = 0;
            for ($i = $L; $i < 2 * $L; $i++) $sumSecond += $d[$i]['aktual'];
            $avgSecond = $sumSecond / $L;
            $trend = ($avgSecond - $avgFirstSeason) / $L;
        }

        // Simpan Riwayat Parameter
        $seasonal_indices = $seasonals;

        // Isi data tampilan awal untuk periode inisialisasi (L pertama)
        for ($i = 0; $i < $L; $i++) {
            $resultTable[] = [
                'bulan_tahun' => $d[$i]['bulan_tahun'],
                'aktual' => $d[$i]['aktual'],
                'level' => '-',
                'trend' => '-',
                'seasonal' => round($seasonal_indices[$i], 3),
                'prediksi' => '-',
                'error' => '-',
                'error_sqr' => '-',
                'ape' => '-'
            ];
        }

        // Parameter saat ini
        $currLevel = $level;
        $currTrend = $trend;

        $total_error_abs = 0;
        $total_error_sqr = 0;
        $total_ape = 0;
        $count_error = 0;

        // --- Iterasi Peramalan (Mulai dari t = L) ---
        for ($i = $L; $i < $nData; $i++) {
            $prevLevel = $currLevel;
            $prevTrend = $currTrend;
            $seasonalIndexLoc = $i - $L;
            $prevSeasonal = $seasonal_indices[$seasonalIndexLoc];

            // 1. Prediksi (Aditif)
            $prediksi = $prevLevel + $prevTrend + $prevSeasonal; // Additive
            $prediksi = round($prediksi, 2);
            $aktual = $d[$i]['aktual'];

            // 2. Metrik Error
            $error_abs = abs($aktual - $prediksi);
            $error_sqr = pow($error_abs, 2);
            $ape = ($aktual != 0) ? ($error_abs / $aktual) * 100 : 0;

            $total_error_abs += $error_abs;
            $total_error_sqr += $error_sqr;
            $total_ape += $ape;
            $count_error++;

            // 3. Pembaruan Parameter (Aditif)

            // Level Baru
            $newLevel = $alpha * ($aktual - $prevSeasonal) + (1 - $alpha) * ($prevLevel + $prevTrend);

            // Trend Baru
            $newTrend = $beta * ($newLevel - $prevLevel) + (1 - $beta) * $prevTrend;

            // Musiman Baru
            $newSeasonal = $gamma * ($aktual - $newLevel) + (1 - $gamma) * $prevSeasonal;

            // Update nilai saat ini
            $currLevel = $newLevel;
            $currTrend = $newTrend;
            $seasonal_indices[] = $newSeasonal;

            $resultTable[] = [
                'bulan_tahun' => $d[$i]['bulan_tahun'],
                'aktual' => $aktual,
                'level' => round($newLevel, 2),
                'trend' => round($newTrend, 2),
                'seasonal' => round($newSeasonal, 3),
                'prediksi' => $prediksi,
                'error' => number_format($error_abs, 2),
                'error_sqr' => number_format($error_sqr, 2),
                'ape' => number_format($ape, 2)
            ];
        }

        // --- Agregat Metrik Error ---
        $mae = ($count_error > 0) ? round($total_error_abs / $count_error, 2) : 0;
        $mse = ($count_error > 0) ? round($total_error_sqr / $count_error, 2) : 0;
        $mape = ($count_error > 0) ? round($total_ape / $count_error, 2) : 0;

        // --- Peramalan Masa Depan ---
        $lastMonth = $d[$nData - 1]['bulan'];
        $lastYear = $d[$nData - 1]['tahun'];

        for ($h = 1; $h <= $durasi; $h++) {
            // Update Tanggal
            $lastMonth++;
            if ($lastMonth > 12) {
                $lastMonth = 1;
                $lastYear++;
            }

            // Cari indeks musiman yang relevan
            $s_idx = ($nData + $h - 1) - $L;
            while ($s_idx >= count($seasonal_indices)) {
                $s_idx -= $L;
            }
            if ($s_idx < 0) {
                $s_idx = ($s_idx % $L + $L) % $L;
            }

            $S_proj = $seasonal_indices[$s_idx];

            // Prediksi Masa Depan (Aditif)
            $predFuture = ($currLevel + $h * $currTrend) + $S_proj;
            $predFuture = round($predFuture, 2);

            $resultTable[] = [
                'bulan_tahun' => $this->getMonthName($lastMonth) . ' ' . $lastYear,
                'aktual' => '-',
                'level' => '-',
                'trend' => '-',
                'seasonal' => '-',
                'prediksi' => $predFuture,
                'error' => '-',
                'error_sqr' => '-',
                'ape' => '-'
            ];
        }

        // Persiapan Grafik
        $chartLabels = [];
        $actualData = [];
        $predictedData = [];

        foreach ($resultTable as $row) {
            $chartLabels[] = $row['bulan_tahun'];
            $actualData[] = ($row['aktual'] !== '-' && $row['aktual'] !== null) ? $row['aktual'] : null;
            $predictedData[] = ($row['prediksi'] !== '-' && $row['prediksi'] !== null) ? $row['prediksi'] : null;
        }

        // Persiapan Data untuk Tampilan
        $kendaraans = Kendaraan::all();
        $riwayat = PeramalanTes::with('kendaraan')->latest()->get();

        return view('menu.peramalan_tes', compact(
            'kendaraans',
            'riwayat',
            'mae',
            'mse',
            'mape',
            'chartLabels',
            'actualData',
            'predictedData',
            'resultTable',
            'id_kendaraan',
            'durasi',
            'alpha',
            'beta',
            'gamma'
        ))->with('showResult', true)->with('input', $request->all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_kendaraan' => 'required',
            'alpha' => 'required',
            'beta' => 'required',
            'gamma' => 'required',
            'durasi_prediksi' => 'required',
            'mae' => 'required',
            'mse' => 'required',
            'mape' => 'required',
            'data_peramalan' => 'required', // json string
        ]);

        PeramalanTes::create([
            'id_kendaraan' => $request->id_kendaraan,
            'alfa' => $request->alpha,
            'beta' => $request->beta,
            'gamma' => $request->gamma,
            'durasi_prediksi' => $request->durasi_prediksi,
            'mae' => $request->mae,
            'mse' => $request->mse,
            'mape' => $request->mape,
            'data_peramalan' => json_decode($request->data_peramalan, true)
        ]);

        return redirect()->route('peramalan_tes.index')->with('success', 'Hasil peramalan TES berhasil disimpan.');
    }

    public function destroy($id)
    {
        PeramalanTes::findOrFail($id)->delete();
        return redirect()->route('peramalan_tes.index')->with('success', 'Riwayat peramalan berhasil dihapus.');
    }

    private function getMonthName($monthNum)
    {
        $dateObj   = \DateTime::createFromFormat('!m', $monthNum);
        return $dateObj->format('F');
    }

    public function exportPdf($id)
    {
        $peramalan = PeramalanTes::with('kendaraan')->findOrFail($id);

        // 1. Ambil Metrics
        $metrics = [
            'mae' => $peramalan->mae,
            'mse' => $peramalan->mse,
            'mape' => $peramalan->mape
        ];

        // 2. Ambil Data Tabel
        $data = $peramalan->data_peramalan;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // TES biasanya nyimpan langsung array tabel di data_peramalan
        // Tapi kita handle jika struktur beda (meski di controller store cuma json_encode($resultTable))
        $table = is_array($data) ? $data : [];
        if (isset($data['table'])) {
            $table = $data['table'];
        }

        // 3. Siapkan Data Chart
        $labels = [];
        $actuals = [];
        $predicteds = [];

        foreach ($table as $row) {
            $labels[] = $row['bulan_tahun'];
            $actuals[] = ($row['aktual'] !== '-' && $row['aktual'] !== null) ? $row['aktual'] : null;
            $predicteds[] = ($row['prediksi'] !== '-' && $row['prediksi'] !== null) ? $row['prediksi'] : null;
        }

        // --- SVG Generation Logic (Sama dengan SMA) ---

        $allValues = array_merge(
            array_filter($actuals, fn($v) => $v !== null),
            array_filter($predicteds, fn($v) => $v !== null)
        );
        $minY = empty($allValues) ? 0 : min($allValues);
        $maxY = empty($allValues) ? 100 : max($allValues);

        // Padding Y
        $padding = ($maxY - $minY) * 0.1;
        if ($padding == 0) $padding = 10;
        $minY = max(0, $minY - $padding);
        $maxY = $maxY + $padding;

        // Dimensions
        $svgWidth = 1000;
        $svgHeight = 300;
        $paddingLeft = 50;
        $paddingBottom = 30;
        $graphWidth = $svgWidth - $paddingLeft;
        $graphHeight = $svgHeight - $paddingBottom;

        $count = count($labels);
        $stepX = $graphWidth / max(1, $count - 1);

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

        // Build SVG String
        $svgContent = '<svg width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '" xmlns="http://www.w3.org/2000/svg">';
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

        // X Labels with Skipping
        $skip = 1;
        if ($count > 12) {
            $skip = ceil($count / 12);
        }

        foreach ($labels as $i => $label) {
            $x = $paddingLeft + ($i * $stepX);
            $y = $svgHeight - 10;
            if ($i == 0 || $i == $count - 1 || $i % $skip == 0) {
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

        $pdf = Pdf::loadView('pdf.peramalan_tes', compact('peramalan', 'metrics', 'table', 'chartImage'));
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions(['dpi' => 150, 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        return $pdf->download('laporan_tes_' . $peramalan->created_at->format('YmdHis') . '.pdf');
    }
}
