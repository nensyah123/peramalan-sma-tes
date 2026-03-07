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
        $riwayat    = PeramalanTes::with('kendaraan')->latest()->get();
        return view('menu.peramalan_tes', compact('kendaraans', 'riwayat'));
    }

    // =========================================================
    // HELPER: Hitung TES — hanya metrik (untuk Grid Search)
    // =========================================================
    private function hitungTESMetrik(array $d, float $alpha, float $beta, float $gamma, int $L): array
    {
        $nData = count($d);
        if ($nData < $L) return ['mape' => PHP_INT_MAX, 'mad' => PHP_INT_MAX, 'mse' => PHP_INT_MAX];

        $avgFirstSeason = 0;
        for ($i = 0; $i < $L; $i++) $avgFirstSeason += $d[$i]['aktual'];
        $avgFirstSeason /= $L;

        $seasonals = [];
        for ($i = 0; $i < $L; $i++) $seasonals[] = $d[$i]['aktual'] - $avgFirstSeason;

        $currLevel = $avgFirstSeason;
        $currTrend = 0;

        if ($nData >= 2 * $L) {
            $sumSecond = 0;
            for ($i = $L; $i < 2 * $L; $i++) $sumSecond += $d[$i]['aktual'];
            $currTrend = ($sumSecond / $L - $avgFirstSeason) / $L;
        }

        $seasonal_indices = $seasonals;
        $total_error_abs  = 0;
        $total_error_sqr  = 0;
        $total_ape        = 0;
        $count_error      = 0;

        for ($i = $L; $i < $nData; $i++) {
            $prevLevel    = $currLevel;
            $prevTrend    = $currTrend;
            $prevSeasonal = $seasonal_indices[$i - $L];

            $prediksi  = $prevLevel + $prevTrend + $prevSeasonal;
            $aktual    = $d[$i]['aktual'];
            $error_abs = abs($aktual - $prediksi);

            $total_error_abs += $error_abs;
            $total_error_sqr += pow($error_abs, 2);
            $total_ape       += ($aktual != 0) ? ($error_abs / $aktual) * 100 : 0;
            $count_error++;

            $newLevel    = $alpha * ($aktual - $prevSeasonal)  + (1 - $alpha) * ($prevLevel + $prevTrend);
            $newTrend    = $beta  * ($newLevel - $prevLevel)   + (1 - $beta)  * $prevTrend;
            $newSeasonal = $gamma * ($aktual - $newLevel)      + (1 - $gamma) * $prevSeasonal;

            $currLevel          = $newLevel;
            $currTrend          = $newTrend;
            $seasonal_indices[] = $newSeasonal;
        }

        if ($count_error === 0) return ['mape' => PHP_INT_MAX, 'mad' => PHP_INT_MAX, 'mse' => PHP_INT_MAX];

        return [
            'mad'  => round($total_error_abs / $count_error, 2),
            'mse'  => round($total_error_sqr / $count_error, 2),
            'mape' => round($total_ape / $count_error, 2),
        ];
    }

    // =========================================================
    // HELPER: Grid Search — 729 kombinasi (0.1 s/d 0.9, step 0.1)
    // Kriteria: MAPE terkecil
    // =========================================================
    private function gridSearch(array $d, int $L): array
    {
        $bestMape  = PHP_INT_MAX;
        $bestAlpha = 0.1;
        $bestBeta  = 0.1;
        $bestGamma = 0.1;

        $steps = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9];

        foreach ($steps as $alpha) {
            foreach ($steps as $beta) {
                foreach ($steps as $gamma) {
                    $result = $this->hitungTESMetrik($d, $alpha, $beta, $gamma, $L);
                    if ($result['mape'] < $bestMape) {
                        $bestMape  = $result['mape'];
                        $bestAlpha = $alpha;
                        $bestBeta  = $beta;
                        $bestGamma = $gamma;
                    }
                }
            }
        }

        return [
            'alpha' => $bestAlpha,
            'beta'  => $bestBeta,
            'gamma' => $bestGamma,
        ];
    }

    // =========================================================
    // PROCESS: Main forecasting TES dengan parameter Grid Search
    // =========================================================
    public function process(Request $request)
    {
        $request->validate([
            'id_kendaraan'    => 'required|exists:kendaraan,id',
            'durasi_prediksi' => 'required|integer|min:1',
        ]);

        $id_kendaraan = $request->id_kendaraan;
        $durasi       = $request->durasi_prediksi;
        $L            = 12;

        $dataPemakaian = PemakaianKendaraan::where('id_kendaraan', $id_kendaraan)
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        if ($dataPemakaian->count() < $L) {
            return back()->with('error', 'Data historis harus minimal ' . $L . ' bulan untuk metode TES (Musiman Tahunan).');
        }

        $d = [];
        foreach ($dataPemakaian as $pem) {
            $d[] = [
                'bulan'       => $pem->bulan,
                'tahun'       => $pem->tahun,
                'aktual'      => $pem->jumlah_transaksi,
                'bulan_tahun' => $this->getMonthName($pem->bulan) . ' ' . $pem->tahun,
            ];
        }

        // ---- GRID SEARCH: 729 kombinasi ----
        $optimal = $this->gridSearch($d, $L);
        $alpha   = $optimal['alpha'];
        $beta    = $optimal['beta'];
        $gamma   = $optimal['gamma'];

        // ---- HITUNG TES DENGAN PARAMETER OPTIMAL ----
        $nData = count($d);

        $avgFirstSeason = 0;
        for ($i = 0; $i < $L; $i++) $avgFirstSeason += $d[$i]['aktual'];
        $avgFirstSeason /= $L;

        $seasonals = [];
        for ($i = 0; $i < $L; $i++) $seasonals[] = $d[$i]['aktual'] - $avgFirstSeason;

        $currLevel = $avgFirstSeason;
        $currTrend = 0;

        if ($nData >= 2 * $L) {
            $sumSecond = 0;
            for ($i = $L; $i < 2 * $L; $i++) $sumSecond += $d[$i]['aktual'];
            $currTrend = ($sumSecond / $L - $avgFirstSeason) / $L;
        }

        $seasonal_indices = $seasonals;
        $resultTable      = [];

        for ($i = 0; $i < $L; $i++) {
            $resultTable[] = [
                'bulan_tahun' => $d[$i]['bulan_tahun'],
                'aktual'      => $d[$i]['aktual'],
                'level'       => '-',
                'trend'       => '-',
                'seasonal'    => round($seasonal_indices[$i], 3),
                'prediksi'    => '-',
                'error'       => '-',
                'error_sqr'   => '-',
                'ape'         => '-',
            ];
        }

        $total_error_abs = 0;
        $total_error_sqr = 0;
        $total_ape       = 0;
        $count_error     = 0;

        for ($i = $L; $i < $nData; $i++) {
            $prevLevel    = $currLevel;
            $prevTrend    = $currTrend;
            $prevSeasonal = $seasonal_indices[$i - $L];

            $prediksi  = round($prevLevel + $prevTrend + $prevSeasonal, 2);
            $aktual    = $d[$i]['aktual'];
            $error_abs = abs($aktual - $prediksi);
            $error_sqr = pow($error_abs, 2);
            $ape       = ($aktual != 0) ? ($error_abs / $aktual) * 100 : 0;

            $total_error_abs += $error_abs;
            $total_error_sqr += $error_sqr;
            $total_ape       += $ape;
            $count_error++;

            $newLevel    = $alpha * ($aktual - $prevSeasonal)  + (1 - $alpha) * ($prevLevel + $prevTrend);
            $newTrend    = $beta  * ($newLevel - $prevLevel)   + (1 - $beta)  * $prevTrend;
            $newSeasonal = $gamma * ($aktual - $newLevel)      + (1 - $gamma) * $prevSeasonal;

            $currLevel          = $newLevel;
            $currTrend          = $newTrend;
            $seasonal_indices[] = $newSeasonal;

            $resultTable[] = [
                'bulan_tahun' => $d[$i]['bulan_tahun'],
                'aktual'      => $aktual,
                'level'       => round($newLevel, 2),
                'trend'       => round($newTrend, 2),
                'seasonal'    => round($newSeasonal, 3),
                'prediksi'    => $prediksi,
                'error'       => number_format($error_abs, 2),
                'error_sqr'   => number_format($error_sqr, 2),
                'ape'         => number_format($ape, 2),
            ];
        }

        $mad  = ($count_error > 0) ? round($total_error_abs / $count_error, 2) : 0;
        $mse  = ($count_error > 0) ? round($total_error_sqr / $count_error, 2) : 0;
        $mape = ($count_error > 0) ? round($total_ape / $count_error, 2) : 0;

        $lastMonth = $d[$nData - 1]['bulan'];
        $lastYear  = $d[$nData - 1]['tahun'];

        for ($h = 1; $h <= $durasi; $h++) {
            $lastMonth++;
            if ($lastMonth > 12) { $lastMonth = 1; $lastYear++; }

            $s_idx = ($nData + $h - 1) - $L;
            while ($s_idx >= count($seasonal_indices)) $s_idx -= $L;
            if ($s_idx < 0) $s_idx = ($s_idx % $L + $L) % $L;

            $predFuture = round(($currLevel + $h * $currTrend) + $seasonal_indices[$s_idx], 2);

            $resultTable[] = [
                'bulan_tahun' => $this->getMonthName($lastMonth) . ' ' . $lastYear,
                'aktual'      => '-',
                'level'       => '-',
                'trend'       => '-',
                'seasonal'    => '-',
                'prediksi'    => $predFuture,
                'error'       => '-',
                'error_sqr'   => '-',
                'ape'         => '-',
            ];
        }

        $chartLabels   = [];
        $actualData    = [];
        $predictedData = [];

        foreach ($resultTable as $row) {
            $chartLabels[]   = $row['bulan_tahun'];
            $actualData[]    = ($row['aktual'] !== '-' && $row['aktual'] !== null) ? $row['aktual'] : null;
            $predictedData[] = ($row['prediksi'] !== '-' && $row['prediksi'] !== null) ? $row['prediksi'] : null;
        }

        $kendaraans = Kendaraan::all();
        $riwayat    = PeramalanTes::with('kendaraan')->latest()->get();

        return view('menu.peramalan_tes', compact(
            'kendaraans', 'riwayat',
            'mad', 'mse', 'mape',
            'chartLabels', 'actualData', 'predictedData',
            'resultTable',
            'id_kendaraan', 'durasi',
            'alpha', 'beta', 'gamma'
        ))->with('showResult', true)->with('input', $request->all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_kendaraan'    => 'required',
            'alpha'           => 'required',
            'beta'            => 'required',
            'gamma'           => 'required',
            'durasi_prediksi' => 'required',
            'mad'             => 'required',
            'mse'             => 'required',
            'mape'            => 'required',
            'data_peramalan'  => 'required',
        ]);

        PeramalanTes::create([
            'id_kendaraan'    => $request->id_kendaraan,
            'alfa'            => $request->alpha,
            'beta'            => $request->beta,
            'gamma'           => $request->gamma,
            'durasi_prediksi' => $request->durasi_prediksi,
            'mad'             => $request->mad,
            'mse'             => $request->mse,
            'mape'            => $request->mape,
            'data_peramalan'  => json_decode($request->data_peramalan, true),
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
        return \DateTime::createFromFormat('!m', $monthNum)->format('F');
    }

    public function exportPdf($id)
    {
        $peramalan = PeramalanTes::with('kendaraan')->findOrFail($id);

        $metrics = [
            'mad'  => $peramalan->mad,
            'mse'  => $peramalan->mse,
            'mape' => $peramalan->mape,
        ];

        $data = $peramalan->data_peramalan;
        if (is_string($data)) $data = json_decode($data, true);

        $table = is_array($data) ? $data : [];
        if (isset($data['table'])) $table = $data['table'];

        $labels = []; $actuals = []; $predicteds = [];
        foreach ($table as $row) {
            $labels[]     = $row['bulan_tahun'];
            $actuals[]    = ($row['aktual'] !== '-' && $row['aktual'] !== null) ? $row['aktual'] : null;
            $predicteds[] = ($row['prediksi'] !== '-' && $row['prediksi'] !== null) ? $row['prediksi'] : null;
        }

        $allValues = array_merge(
            array_filter($actuals, fn($v) => $v !== null),
            array_filter($predicteds, fn($v) => $v !== null)
        );
        $minY    = empty($allValues) ? 0 : min($allValues);
        $maxY    = empty($allValues) ? 100 : max($allValues);
        $padding = ($maxY - $minY) * 0.1 ?: 10;
        $minY    = max(0, $minY - $padding);
        $maxY   += $padding;

        $svgWidth    = 1000; $svgHeight    = 300;
        $paddingLeft = 50;   $paddingBottom = 30;
        $graphWidth  = $svgWidth - $paddingLeft;
        $graphHeight = $svgHeight - $paddingBottom;
        $count       = count($labels);
        $stepX       = $graphWidth / max(1, $count - 1);

        $getY = function ($val) use ($graphHeight, $minY, $maxY) {
            if ($val === null) return null;
            $ratio = ($val - $minY) / max(1, $maxY - $minY);
            return $graphHeight - ($ratio * $graphHeight);
        };

        $actualPoints = []; $predictedPoints = [];
        foreach ($labels as $i => $label) {
            $x     = $paddingLeft + ($i * $stepX);
            $yAct  = $getY($actuals[$i] ?? null);
            if ($yAct !== null) $actualPoints[] = "$x,$yAct";
            $yPred = $getY($predicteds[$i] ?? null);
            if ($yPred !== null) $predictedPoints[] = "$x,$yPred";
        }

        $svgContent  = '<svg width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '" xmlns="http://www.w3.org/2000/svg">';
        $svgContent .= '<rect x="0" y="0" width="' . $svgWidth . '" height="' . $svgHeight . '" fill="none" stroke="#f0f0f0" stroke-width="1" />';

        for ($i = 0; $i <= 4; $i++) {
            $y   = ($svgHeight - $paddingBottom) - ($i * ($svgHeight - $paddingBottom - 20) / 4);
            $val = $minY + ($i * ($maxY - $minY) / 4);
            $svgContent .= '<line x1="' . $paddingLeft . '" y1="' . $y . '" x2="' . $svgWidth . '" y2="' . $y . '" stroke="#e6e6e6" stroke-width="1" stroke-dasharray="4" />';
            $svgContent .= '<text x="' . ($paddingLeft - 5) . '" y="' . ($y + 3) . '" font-family="sans-serif" font-size="10" fill="#888" text-anchor="end">' . number_format($val, 0) . '</text>';
        }

        $svgContent .= '<polyline points="' . implode(' ', $actualPoints) . '" fill="none" stroke="#4e73df" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />';
        $svgContent .= '<polyline points="' . implode(' ', $predictedPoints) . '" fill="none" stroke="#1cc88a" stroke-width="2" stroke-dasharray="5,5" stroke-linecap="round" stroke-linejoin="round"/>';

        $skip = ($count > 12) ? ceil($count / 12) : 1;
        foreach ($labels as $i => $label) {
            $x = $paddingLeft + ($i * $stepX);
            if ($i == 0 || $i == $count - 1 || $i % $skip == 0) {
                $svgContent .= '<text x="' . $x . '" y="' . ($svgHeight - 10) . '" font-family="sans-serif" font-size="9" fill="#666" text-anchor="middle">' . $label . '</text>';
            }
        }

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
