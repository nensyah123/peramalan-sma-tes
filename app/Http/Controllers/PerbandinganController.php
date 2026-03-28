<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use App\Models\TransaksiPenyewaan;
use Illuminate\Http\Request;

class PerbandinganController extends Controller
{
    public function index()
    {
        $riwayat = PeramalanTes::latest()->get();
        return view('menu.perbandingan', compact('riwayat'));
    }

    public function show($id)
    {
        $peramalan = PeramalanTes::findOrFail($id);

        $kualitas = 'kurang akurat (> 50%)';
        if ($peramalan->mape < 10)     $kualitas = 'sangat baik (< 10%)';
        elseif ($peramalan->mape < 20) $kualitas = 'baik (10-20%)';
        elseif ($peramalan->mape < 50) $kualitas = 'wajar (20-50%)';

        return response()->json([
            'merk'       => $peramalan->merk,
            'mad'        => $peramalan->mad,
            'mse'        => $peramalan->mse,
            'mape'       => $peramalan->mape,
            'created_at' => $peramalan->created_at->format('Y-m-d'),
            'summary'    => "Hasil peramalan TES untuk {$peramalan->merk} disimpan pada {$peramalan->created_at->format('Y-m-d')}. Nilai MAPE sebesar {$peramalan->mape}% menunjukkan tingkat akurasi yang {$kualitas}."
        ]);
    }

    public function compare($id)
    {
        $peramalanTes = PeramalanTes::findOrFail($id);
        $merk         = $peramalanTes->merk;
        $durasi       = (int) $peramalanTes->durasi_prediksi;
        $s            = 12;

        // Ambil id kendaraan berdasarkan merk
        $ids = Kendaraan::where('merk', $merk)->pluck('id');

        // Exclude bulan berjalan
        $bulanSekarang = (int) date('m');
        $tahunSekarang = (int) date('Y');

        $raw = TransaksiPenyewaan::selectRaw(
                'MONTH(tgl_pinjam) as bulan, YEAR(tgl_pinjam) as tahun, COUNT(*) as total'
            )
            ->whereIn('id_kendaraan', $ids)
            ->where(function ($query) use ($bulanSekarang, $tahunSekarang) {
                $query->whereYear('tgl_pinjam', '<', $tahunSekarang)
                      ->orWhere(function ($q) use ($bulanSekarang, $tahunSekarang) {
                          $q->whereYear('tgl_pinjam', '=', $tahunSekarang)
                            ->whereMonth('tgl_pinjam', '<', $bulanSekarang);
                      });
            })
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        // Susun data
        $values    = [];
        $labels    = [];
        $bulanData = [];

        foreach ($raw as $row) {
            $values[]    = (float) $row->total;
            $labels[]    = $this->getMonthName((int) $row->bulan) . ' ' . $row->tahun;
            $bulanData[] = ['bulan' => (int) $row->bulan, 'tahun' => (int) $row->tahun];
        }

        // ===== STEP 1: Dapatkan α, β, γ optimal dari Python =====
        try {
            $params = $this->getOptimalParams($values);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $alpha = $params['alpha'];
        $beta  = $params['beta'];
        $gamma = $params['gamma'];

        // ===== STEP 2: Hitung TES Manual di PHP =====
        $tesResult = $this->hitungTESManual(
            $values, $labels, $bulanData,
            $alpha, $beta, $gamma,
            $durasi, $s
        );

        $mape_tes = $tesResult['mape'];
        $mad_tes  = $tesResult['mad'];
        $mse_tes  = $tesResult['mse'];

        // ===== STEP 3: Hitung SMA dengan m=12 =====
        // m=12 disesuaikan dengan seasonal period TES
        $periode_sma = 12;
        $dataAktual  = [];
        foreach ($raw as $row) {
            $dataAktual[] = [
                'aktual'      => (float) $row->total,
                'bulan_tahun' => $this->getMonthName((int) $row->bulan) . ' ' . $row->tahun,
            ];
        }
        $smaResult = $this->calculateSMA($dataAktual, $periode_sma, $durasi);
        $mape_sma  = $smaResult['metrics']['mape'];

        // ===== Susun data chart =====
        $chartLabels    = [];
        $actualData     = [];
        $tesPredictions = [];

        foreach ($tesResult['result_table'] as $row) {
            $chartLabels[]    = $row['bulan_tahun'];
            $actualData[]     = ($row['aktual'] !== '-') ? (float) $row['aktual'] : null;
            $tesPredictions[] = ($row['prediksi'] !== '-') ? (float) $row['prediksi'] : null;
        }

        $smaPredictions = $smaResult['chart']['predicted'];
        while (count($smaPredictions) < count($chartLabels)) $smaPredictions[] = null;
        $smaPredictions = array_slice($smaPredictions, 0, count($chartLabels));

        $better     = ($mape_sma < $mape_tes) ? 'SMA' : 'TES';
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
                    'mad'   => $mad_tes,
                    'mse'   => $mse_tes,
                    'mape'  => $mape_tes,
                ],
                'sma' => $smaResult['metrics'],
            ],
            'conclusion' => "Berdasarkan optimasi Statsmodels, parameter optimal TES: α={$alpha}, β={$beta}, γ={$gamma}. Metode <strong>{$better}</strong> lebih akurat dengan MAPE {$betterMape}%."
        ]);
    }

    public function destroy($id)
    {
        PeramalanTes::findOrFail($id)->delete();
        return redirect()->route('perbandingan.index')
            ->with('success', 'Riwayat peramalan berhasil dihapus.');
    }

    // ===== PYTHON: HANYA UNTUK OPTIMASI α, β, γ =====
    private function getOptimalParams(array $values): array
    {
        $input = json_encode(['values' => $values]);

        $tmpFile = storage_path('app/tes_compare_' . time() . '.json');
        file_put_contents($tmpFile, $input);

        $scriptPath = base_path('tes_optimize.py');
        $command    = "python \"$scriptPath\" \"$tmpFile\" 2>&1";
        $outputRaw  = shell_exec($command);

        @unlink($tmpFile);

        $result = json_decode($outputRaw, true);

        if (!$result || !isset($result['alpha'])) {
            throw new \Exception('Gagal menjalankan Python: ' . $outputRaw);
        }

        return $result;
    }

    // ===== TAHAP 1: INISIALISASI MANUAL =====
    private function hitungInisialisasi(array $values, int $s = 12): array
    {
        $n = count($values);

        // Level awal = rata-rata 12 data pertama
        $L0 = array_sum(array_slice($values, 0, $s)) / $s;

        // Trend awal = rata-rata selisih antar musim / s
        $trendSum   = 0;
        $trendCount = 0;
        for ($i = 0; $i < $s; $i++) {
            if (($s + $i) < $n) {
                $trendSum += ($values[$s + $i] - $values[$i]) / $s;
                $trendCount++;
            }
        }
        $b0 = $trendCount > 0 ? $trendSum / $trendCount : 0;

        // Seasonal awal = Yk - L0
        $S0 = [];
        for ($i = 0; $i < $s; $i++) {
            $S0[$i] = $values[$i] - $L0;
        }

        return ['L0' => $L0, 'b0' => $b0, 'S0' => $S0];
    }

    // ===== TAHAP 2 & 3: ITERASI + FORECAST MANUAL =====
    private function hitungTESManual(
        array $values,
        array $labels,
        array $bulanData,
        float $alpha,
        float $beta,
        float $gamma,
        int   $durasi,
        int   $s = 12
    ): array {
        $n = count($values);

        // Inisialisasi
        $init = $this->hitungInisialisasi($values, $s);
        $L    = $init['L0'];
        $b    = $init['b0'];
        $S    = $init['S0'];

        $resultTable = [];
        $totalMad    = 0;
        $totalMse    = 0;
        $totalMape   = 0;
        $count       = 0;

        for ($i = 0; $i < $n; $i++) {
            $aktual = $values[$i];

            // Baris 1-11: kosong semua (periode inisialisasi)
            if ($i < 11) {
                $resultTable[] = [
                    'bulan_tahun' => $labels[$i],
                    'aktual'      => $aktual,
                    'level'       => '-',
                    'trend'       => '-',
                    'seasonal'    => '-',
                    'prediksi'    => '-',
                    'error'       => '-',
                    'error_sqr'   => '-',
                    'ape'         => '-',
                ];
                continue;
            }

            // Baris 12 (Des): tampilkan nilai awal inisialisasi
            if ($i === 11) {
                $resultTable[] = [
                    'bulan_tahun' => $labels[$i],
                    'aktual'      => $aktual,
                    'level'       => round($L, 4),
                    'trend'       => round($b, 4),
                    'seasonal'    => round($S[11], 4),
                    'prediksi'    => '-',
                    'error'       => '-',
                    'error_sqr'   => '-',
                    'ape'         => '-',
                ];
                continue;
            }

            // Baris 13+: iterasi menggunakan rumus Holt-Winters
            $St_s = $S[$i % $s];

            // FIX: Hitung Ft_raw TANPA dibulatkan dulu
            // agar error & APE lebih presisi (sama dengan Excel)
            $Ft_raw = $L + $b + $St_s;

            // Update Level
            $L_new = $alpha * ($aktual - $St_s) + (1 - $alpha) * ($L + $b);

            // Update Trend
            $b_new = $beta * ($L_new - $L) + (1 - $beta) * $b;

            // Update Seasonal
            $S[$i % $s] = $gamma * ($aktual - $L_new) + (1 - $gamma) * $St_s;

            $L = $L_new;
            $b = $b_new;

            // FIX: Hitung Error dari Ft_raw (nilai asli, bukan yang dibulatkan)
            $err = abs($aktual - $Ft_raw);
            $ape = $aktual != 0 ? ($err / $aktual) * 100 : 0;

            $totalMad  += $err;
            $totalMse  += $err ** 2;
            $totalMape += $ape;
            $count++;

            $resultTable[] = [
                'bulan_tahun' => $labels[$i],
                'aktual'      => $aktual,
                'level'       => round($L, 4),
                'trend'       => round($b, 4),
                'seasonal'    => round($S[$i % $s], 4),
                'prediksi'    => round($Ft_raw, 2), // round hanya untuk tampilan
                'error'       => round($err, 4),
                'error_sqr'   => round($err ** 2, 4),
                'ape'         => round($ape, 4),
            ];
        }

        // Hitung metrik akurasi
        $mad  = $count > 0 ? round($totalMad  / $count, 4) : 0;
        $mse  = $count > 0 ? round($totalMse  / $count, 4) : 0;
        $mape = $count > 0 ? round($totalMape / $count, 4) : 0;

        // ===== TAHAP 4: FORECAST KE DEPAN =====
        $lastBulan = $bulanData[$n - 1]['bulan'];
        $lastTahun = $bulanData[$n - 1]['tahun'];

        for ($h = 1; $h <= $durasi; $h++) {
            $lastBulan++;
            if ($lastBulan > 12) { $lastBulan = 1; $lastTahun++; }

            $idx = ($n - $s + $h - 1) % $s;
            $Ft  = $L + $b * $h + $S[$idx];
            $Ft  = round(max(0, $Ft), 2);

            $resultTable[] = [
                'bulan_tahun' => $this->getMonthName($lastBulan) . ' ' . $lastTahun,
                'aktual'      => '-',
                'level'       => '-',
                'trend'       => '-',
                'seasonal'    => '-',
                'prediksi'    => $Ft,
                'error'       => '-',
                'error_sqr'   => '-',
                'ape'         => '-',
            ];
        }

        return [
            'mad'          => $mad,
            'mse'          => $mse,
            'mape'         => $mape,
            'result_table' => $resultTable,
        ];
    }

    // ===== HITUNG SMA m=12 =====
    // m=12 disesuaikan dengan seasonal period TES (12 bulan)
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

        $mad  = ($count_error > 0) ? round($total_error_abs / $count_error, 4) : 0;
        $mse  = ($count_error > 0) ? round($total_error_sqr / $count_error, 4) : 0;
        $mape = ($count_error > 0) ? round($total_ape       / $count_error, 4) : 0;

        // Forecast ke depan menggunakan data aktual + prediksi sebelumnya
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

    private function getMonthName(int $monthNum): string
    {
        return \DateTime::createFromFormat('!m', $monthNum)->format('M');
    }
}
