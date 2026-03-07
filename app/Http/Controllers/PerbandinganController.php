<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use App\Models\PemakaianKendaraan;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PerbandinganController extends Controller
{
    /**
     * Menampilkan Halaman Riwayat Peramalan.
     */
    public function index()
    {
        $riwayat = PeramalanTes::with('kendaraan')->latest()->get();
        return view('menu.perbandingan', compact('riwayat'));
    }

    /**
     * Menampilkan Detail Peramalan TES (Fitur Lihat).
     */
    public function show($id)
    {
        $peramalan = PeramalanTes::with('kendaraan')->findOrFail($id);

        $kualitas = 'kurang akurat (> 50%)';
        if ($peramalan->mape < 10)      $kualitas = 'sangat baik (< 10%)';
        elseif ($peramalan->mape < 20)  $kualitas = 'baik (10-20%)';
        elseif ($peramalan->mape < 50)  $kualitas = 'wajar (20-50%)';

        return response()->json([
            'kendaraan'  => $peramalan->kendaraan->nama_kendaraan,
            'periode'    => $peramalan->periode_label,
            'mad'        => $peramalan->mad,
            'mse'        => $peramalan->mse,
            'mape'       => $peramalan->mape,
            'created_at' => $peramalan->created_at->format('Y-m-d'),
            'summary'    => "Hasil peramalan TES untuk {$peramalan->kendaraan->nama_kendaraan} pada periode {$peramalan->periode_label}, disimpan pada {$peramalan->created_at->format('Y-m-d')}. Nilai MAPE sebesar {$peramalan->mape}% menunjukkan tingkat akurasi yang {$kualitas}."
        ]);
    }

    /**
     * Melakukan Perbandingan TES (Grid Search 729 kombinasi) vs SMA.
     * TES dihitung ulang dengan parameter optimal dari data historis.
     */
    public function compare($id)
    {
        $peramalanTes = PeramalanTes::with('kendaraan')->findOrFail($id);

        // Ambil data historis asli dari database (bukan dari JSON tersimpan)
        $dataPemakaian = PemakaianKendaraan::where('id_kendaraan', $peramalanTes->id_kendaraan)
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        $L = 12; // Panjang musim bulanan

        // Bangun array data
        $d = [];
        foreach ($dataPemakaian as $pem) {
            $d[] = [
                'bulan'       => $pem->bulan,
                'tahun'       => $pem->tahun,
                'aktual'      => $pem->jumlah_transaksi,
                'bulan_tahun' => $this->getMonthName($pem->bulan) . ' ' . $pem->tahun,
            ];
        }

        $nData   = count($d);
        $durasi  = $peramalanTes->durasi_prediksi;

        // ---- GRID SEARCH: 729 Kombinasi (alpha x beta x gamma) ----
        $optimal = $this->gridSearch($d, $L);
        $alpha   = $optimal['alpha'];
        $beta    = $optimal['beta'];
        $gamma   = $optimal['gamma'];

        // ---- HITUNG TES DENGAN PARAMETER OPTIMAL ----
        $tesResult = $this->hitungTESLengkap($d, $alpha, $beta, $gamma, $L, $durasi);

        // ---- HITUNG SMA PERIODE 3 BULAN ----
        $periode_sma = 3;
        $dataAktual  = [];
        foreach ($d as $row) {
            $dataAktual[] = [
                'aktual'      => $row['aktual'],
                'bulan_tahun' => $row['bulan_tahun'],
            ];
        }
        $smaResult = $this->calculateSMA($dataAktual, $periode_sma, $durasi);

        // ---- SIAPKAN DATA CHART ----
        $chartLabels    = [];
        $actualData     = [];
        $tesPredictions = [];

        foreach ($tesResult['table'] as $row) {
            $chartLabels[]    = $row['bulan_tahun'];
            $actualData[]     = ($row['aktual'] !== '-' && $row['aktual'] !== null) ? $row['aktual'] : null;
            $tesPredictions[] = ($row['prediksi'] !== '-' && $row['prediksi'] !== null) ? $row['prediksi'] : null;
        }

        $smaPredictions = $smaResult['chart']['predicted'];

        // Sesuaikan panjang array SMA agar sama dengan TES (padding null jika perlu)
        while (count($smaPredictions) < count($chartLabels)) {
            $smaPredictions[] = null;
        }
        $smaPredictions = array_slice($smaPredictions, 0, count($chartLabels));

        // ---- KESIMPULAN/PERBANDINGAN ----
        $mape_tes = $tesResult['metrics']['mape'];
        $mape_sma = $smaResult['metrics']['mape'];
        $better   = ($mape_sma < $mape_tes) ? 'SMA' : 'TES';
        $betterMape = ($better === 'SMA') ? $mape_sma : $mape_tes;

        return response()->json([
            'chart' => [
                'labels' => $chartLabels,
                'actual' => $actualData,
                'tes'    => $tesPredictions,
                'sma'    => $smaPredictions,
            ],
            'accuracy' => [
                'tes' => [
                    'alpha' => $alpha,
                    'beta'  => $beta,
                    'gamma' => $gamma,
                    'mad'   => $tesResult['metrics']['mad'],
                    'mse'   => $tesResult['metrics']['mse'],
                    'mape'  => $mape_tes,
                ],
                'sma' => $smaResult['metrics'],
            ],
            'conclusion' => "Berdasarkan Grid Search (729 kombinasi), parameter optimal TES: α={$alpha}, β={$beta}, γ={$gamma}. Metode <strong>{$better}</strong> lebih akurat dengan MAPE {$betterMape}%."
        ]);
    }

    /**
     * Menghapus Riwayat Peramalan.
     */
    public function destroy($id)
    {
        PeramalanTes::findOrFail($id)->delete();
        return redirect()->route('perbandingan.index')->with('success', 'Riwayat peramalan berhasil dihapus.');
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
    // HELPER: Hitung TES — hanya metrik (untuk Grid Search)
    // =========================================================
    private function hitungTESMetrik(array $d, float $alpha, float $beta, float $gamma, int $L): array
    {
        $nData = count($d);
        if ($nData < $L) return ['mape' => PHP_INT_MAX, 'mad' => PHP_INT_MAX, 'mse' => PHP_INT_MAX];

        // Inisialisasi
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

        $seasonal_indices    = $seasonals;
        $total_error_abs     = 0;
        $total_error_sqr     = 0;
        $total_ape           = 0;
        $count_error         = 0;

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

            $currLevel           = $newLevel;
            $currTrend           = $newTrend;
            $seasonal_indices[]  = $newSeasonal;
        }

        if ($count_error === 0) return ['mape' => PHP_INT_MAX, 'mad' => PHP_INT_MAX, 'mse' => PHP_INT_MAX];

        return [
            'mad'  => round($total_error_abs / $count_error, 2),
            'mse'  => round($total_error_sqr / $count_error, 2),
            'mape' => round($total_ape / $count_error, 2),
        ];
    }

    // =========================================================
    // HELPER: Hitung TES lengkap (tabel + prediksi masa depan)
    // =========================================================
    private function hitungTESLengkap(array $d, float $alpha, float $beta, float $gamma, int $L, int $durasi): array
    {
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
        $table            = [];

        // Periode inisialisasi
        for ($i = 0; $i < $L; $i++) {
            $table[] = [
                'bulan_tahun' => $d[$i]['bulan_tahun'],
                'aktual'      => $d[$i]['aktual'],
                'prediksi'    => '-',
                'error'       => '-',
            ];
        }

        $total_error_abs = 0;
        $total_error_sqr = 0;
        $total_ape       = 0;
        $count_error     = 0;

        // Iterasi TES
        for ($i = $L; $i < $nData; $i++) {
            $prevLevel    = $currLevel;
            $prevTrend    = $currTrend;
            $prevSeasonal = $seasonal_indices[$i - $L];

            $prediksi  = round($prevLevel + $prevTrend + $prevSeasonal, 2);
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

            $table[] = [
                'bulan_tahun' => $d[$i]['bulan_tahun'],
                'aktual'      => $aktual,
                'prediksi'    => $prediksi,
                'error'       => number_format($error_abs, 2),
            ];
        }

        $mad  = ($count_error > 0) ? round($total_error_abs / $count_error, 2) : 0;
        $mse  = ($count_error > 0) ? round($total_error_sqr / $count_error, 2) : 0;
        $mape = ($count_error > 0) ? round($total_ape / $count_error, 2) : 0;

        // Prediksi masa depan
        $lastMonth = $d[$nData - 1]['bulan'];
        $lastYear  = $d[$nData - 1]['tahun'];

        for ($h = 1; $h <= $durasi; $h++) {
            $lastMonth++;
            if ($lastMonth > 12) { $lastMonth = 1; $lastYear++; }

            $s_idx = ($nData + $h - 1) - $L;
            while ($s_idx >= count($seasonal_indices)) $s_idx -= $L;
            if ($s_idx < 0) $s_idx = ($s_idx % $L + $L) % $L;

            $predFuture = round(($currLevel + $h * $currTrend) + $seasonal_indices[$s_idx], 2);

            $table[] = [
                'bulan_tahun' => $this->getMonthName($lastMonth) . ' ' . $lastYear,
                'aktual'      => '-',
                'prediksi'    => $predFuture,
                'error'       => '-',
            ];
        }

        return [
            'table'   => $table,
            'metrics' => ['mad' => $mad, 'mse' => $mse, 'mape' => $mape],
        ];
    }

    // =========================================================
    // HELPER: Hitung SMA Periode 3 Bulan
    // =========================================================
    private function calculateSMA(array $data, int $periode, int $durasi): array
    {
        $nData           = count($data);
        $total_error_abs = 0;
        $total_error_sqr = 0;
        $total_ape       = 0;
        $count_error     = 0;
        $predictions     = [];

        for ($i = 0; $i < $nData; $i++) {
            $prediksi = null;
            if ($i >= $periode) {
                $sum = 0;
                for ($k = 1; $k <= $periode; $k++) $sum += $data[$i - $k]['aktual'];
                $prediksi = round($sum / $periode, 2);

                $aktual = $data[$i]['aktual'];
                $err    = abs($aktual - $prediksi);

                $total_error_abs += $err;
                $total_error_sqr += pow($err, 2);
                $total_ape       += ($aktual != 0) ? ($err / $aktual) * 100 : 0;
                $count_error++;
            }
            $predictions[] = $prediksi;
        }

        $mad  = ($count_error > 0) ? round($total_error_abs / $count_error, 2) : 0;
        $mse  = ($count_error > 0) ? round($total_error_sqr / $count_error, 2) : 0;
        $mape = ($count_error > 0) ? round($total_ape / $count_error, 2) : 0;

        // Prediksi masa depan SMA (sliding window)
        $dataExtended = array_column($data, 'aktual');

        for ($j = 0; $j < $durasi; $j++) {
            $len = count($dataExtended);
            $sum = 0;
            for ($k = 1; $k <= $periode; $k++) $sum += $dataExtended[$len - $k];
            $predFuture     = round($sum / $periode, 2);
            $predictions[]  = $predFuture;
            $dataExtended[] = $predFuture;
        }

        return [
            'metrics' => ['mad' => $mad, 'mse' => $mse, 'mape' => $mape],
            'chart'   => ['predicted' => $predictions],
        ];
    }

    // =========================================================
    // HELPER: Nama Bulan
    // =========================================================
    private function getMonthName(int $monthNum): string
    {
        return \DateTime::createFromFormat('!m', $monthNum)->format('F');
    }
}
