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
        elseif ($peramalan->mape < 20) $kualitas = 'Akurat (10-20%)';
        elseif ($peramalan->mape < 50) $kualitas = 'Cukup akurat (20-50%)';

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

        // Gunakan nilai TES yang sudah tersimpan
        $mad_tes  = $peramalanTes->mad;
        $mse_tes  = $peramalanTes->mse;
        $mape_tes = $peramalanTes->mape;

        $alpha = $peramalanTes->alfa;
        $beta  = $peramalanTes->beta;
        $gamma = $peramalanTes->gamma;

        // Ambil data_peramalan yang tersimpan untuk chart TES
        $savedData = $peramalanTes->data_peramalan;
        if (is_string($savedData)) $savedData = json_decode($savedData, true);
        $resultTable = is_array($savedData) ? $savedData : [];
        if (isset($savedData['table'])) $resultTable = $savedData['table'];

        $chartLabels    = [];
        $actualData     = [];
        $tesPredictions = [];

        foreach ($resultTable as $row) {
            $chartLabels[]    = $row['bulan_tahun'];
            $actualData[]     = ($row['aktual'] !== '-') ? (float) $row['aktual'] : null;
            $tesPredictions[] = ($row['prediksi'] !== '-') ? (float) $row['prediksi'] : null;
        }

        // ===== Hitung SMA m=12 =====
        // Ambil data aktual dari hasil TES yang tersimpan (bukan query live)
        // agar konsisten dengan data saat peramalan TES disimpan
        $periode_sma = 12;
        $dataAktual  = [];
        foreach ($resultTable as $row) {
            if ($row['aktual'] !== '-') {
                $dataAktual[] = [
                    'aktual'      => (float) $row['aktual'],
                    'bulan_tahun' => $row['bulan_tahun'],
                ];
            }
        }

        $smaResult = $this->calculateSMA($dataAktual, $periode_sma, $durasi);
        $mape_sma  = $smaResult['metrics']['mape'];

        // Sesuaikan panjang array SMA dengan TES
        $smaPredictions = $smaResult['chart']['predicted'];
        while (count($smaPredictions) < count($chartLabels)) $smaPredictions[] = null;
        $smaPredictions = array_slice($smaPredictions, 0, count($chartLabels));

        // Tentukan metode yang lebih baik berdasarkan MAPE
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
            'conclusion' => "Berdasarkan optimasi Statsmodels, parameter optimal TES: α={$alpha}, β={$beta}, γ={$gamma}. Metode <strong>{$better}</strong> lebih akurat dengan MAPE {$betterMape}%.",
            'merk'    => $merk,
            'periode' => $durasi . ' bulan ke depan',
            'alpha'   => $alpha,
            'beta'    => $beta,
            'gamma'   => $gamma,
        ]);
    }

    public function destroy($id)
    {
        PeramalanTes::findOrFail($id)->delete();
        return redirect()->route('perbandingan.index')
            ->with('success', 'Riwayat peramalan berhasil dihapus.');
    }

    // ===== HITUNG SMA m=12 =====
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

        // Forecast ke depan
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

    // ===== HELPER: Konversi nomor bulan ke nama bulan =====
    private function getMonthName(int $monthNum): string
    {
        return \DateTime::createFromFormat('!m', $monthNum)->format('M');
    }
}
