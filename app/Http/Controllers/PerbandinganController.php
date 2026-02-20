<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PerbandinganController extends Controller
{
    /**
     * Menampilkan Halaman Riwayat Peramalan.
     * Mengambil data dari tabel PeramalanTes.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $riwayat = PeramalanTes::with('kendaraan')->latest()->get();
        return view('menu.perbandingan', compact('riwayat'));
    }

    /**
     * Menampilkan Detail Peramalan TES (Fitur Lihat).
     * Mengembalikan data JSON untuk ditampilkan pada modal/card.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $peramalan = PeramalanTes::with('kendaraan')->findOrFail($id);

        // Logic sederhana untuk pesan summary berdasarkan nilai MAPE
        $kualitas = 'kurang akurat (> 50%)';
        if ($peramalan->mape < 10) $kualitas = 'sangat baik (< 10%)';
        elseif ($peramalan->mape < 20) $kualitas = 'baik (10-20%)';
        elseif ($peramalan->mape < 50) $kualitas = 'wajar (20-50%)';

        return response()->json([
            'kendaraan' => $peramalan->kendaraan->nama_kendaraan,
            'periode' => $peramalan->periode_label,
            'mad' => $peramalan->mad,
            'mse' => $peramalan->mse,
            'mape' => $peramalan->mape,
            'created_at' => $peramalan->created_at->format('Y-m-d'),
            'summary' => "Hasil peramalan TES untuk {$peramalan->kendaraan->nama_kendaraan} pada periode {$peramalan->periode_label}, disimpan pada {$peramalan->created_at->format('Y-m-d')}. Nilai MAPE sebesar {$peramalan->mape}% menunjukkan tingkat akurasi yang {$kualitas}."
        ]);
    }

    /**
     * Melakukan Perbandingan dengan metode SMA (Fitur Pembanding).
     * Menghitung SMA (Simple Moving Average) secara on-the-fly menggunakan data historis TES.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function compare($id)
    {
        $peramalanTes = PeramalanTes::with('kendaraan')->findOrFail($id);

        // Ambil Data dari JSON
        $dataTes = $peramalanTes->data_peramalan;
        if (is_string($dataTes)) {
            $dataTes = json_decode($dataTes, true);
        }

        // 1. Ekstrak Data Aktual untuk perhitungan SMA
        $dataAktual = [];
        foreach ($dataTes as $row) {
            if ($row['aktual'] !== '-' && $row['aktual'] !== null) {
                $dataAktual[] = [
                    'aktual' => $row['aktual'],
                    'bulan_tahun' => $row['bulan_tahun']
                ];
            }
        }

        // 2. Parameter SMA Hardcoded (n=3)
        $periode_sma = 3;
        $durasi_prediksi = $peramalanTes->durasi_prediksi;

        // 3. Hitung SMA
        $smaResult = $this->calculateSMA($dataAktual, $periode_sma, $durasi_prediksi);

        // 4. Siapkan Data Chart & Compare
        $tesPredictions = [];
        $chartLabels = [];
        $actualData = [];

        foreach ($dataTes as $row) {
            $chartLabels[] = $row['bulan_tahun'];
            $actualData[] = ($row['aktual'] !== '-' && $row['aktual'] !== null) ? $row['aktual'] : null;
            $tesPredictions[] = ($row['prediksi'] !== '-' && $row['prediksi'] !== null) ? $row['prediksi'] : null;
        }

        $smaPredictions = $smaResult['chart']['predicted'];

        // 5. Tentukan Metode Terbaik
        $mape_sma = $smaResult['metrics']['mape'];
        $mape_tes = $peramalanTes->mape;
        $better = ($mape_sma < $mape_tes) ? 'SMA' : 'TES';

        return response()->json([
            'chart' => [
                'labels' => $chartLabels,
                'actual' => $actualData,
                'tes' => $tesPredictions,
                'sma' => $smaPredictions
            ],
            'accuracy' => [
                'sma' => $smaResult['metrics'],
                'tes' => [
                    'mad' => $peramalanTes->mad,
                    'mse' => $peramalanTes->mse,
                    'mape' => $peramalanTes->mape
                ]
            ],
            'conclusion' => "Berdasarkan nilai MAPE, metode <strong>$better</strong> lebih akurat dengan MAPE " . ($better == 'SMA' ? $mape_sma : $mape_tes) . "%."
        ]);
    }

    /**
     * Menghapus Riwayat Peramalan.
     * 
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        PeramalanTes::findOrFail($id)->delete();
        return redirect()->route('perbandingan.index')->with('success', 'Riwayat peramalan berhasil dihapus.');
    }

    // --- Helper Functions ---

    /**
     * Hitung SMA dan Metrik Akurasi.
     * Menggunakan metode n-moving average.
     */
    private function calculateSMA($data, $periode, $durasi)
    {
        $nData = count($data);
        $total_error_abs = 0;
        $total_error_sqr = 0;
        $total_ape = 0;
        $count_error = 0;
        $predictions = [];

        // Hitung pada Data Historis
        for ($i = 0; $i < $nData; $i++) {
            $prediksi = null;
            if ($i >= $periode) {
                $sum = 0;
                for ($k = 1; $k <= $periode; $k++) {
                    $sum += $data[$i - $k]['aktual'];
                }
                $prediksi = round($sum / $periode, 2);

                $aktual = $data[$i]['aktual'];
                $err = abs($aktual - $prediksi);
                $total_error_abs += $err;
                $total_error_sqr += pow($err, 2);
                $total_ape += ($aktual != 0) ? ($err / $aktual) * 100 : 0;
                $count_error++;
            }
            $predictions[] = $prediksi;
        }

        $mad = ($count_error > 0) ? round($total_error_abs / $count_error, 2) : 0;
        $mse = ($count_error > 0) ? round($total_error_sqr / $count_error, 2) : 0;
        $mape = ($count_error > 0) ? round($total_ape / $count_error, 2) : 0;

        // Peramalan Masa Depan (Sliding Window)
        $dataExtended = [];
        foreach ($data as $row) $dataExtended[] = $row['aktual'];
        $predictionsFuture = [];

        for ($j = 0; $j < $durasi; $j++) {
            $len = count($dataExtended);
            $sum = 0;
            for ($k = 1; $k <= $periode; $k++) {
                $sum += $dataExtended[$len - $k];
            }
            $predFuture = round($sum / $periode, 2);
            $predictionsFuture[] = $predFuture;
            $dataExtended[] = $predFuture; // Gunakan prediksi sebagai input periode berikutnya
        }

        return [
            'metrics' => ['mad' => $mad, 'mse' => $mse, 'mape' => $mape],
            'chart' => ['predicted' => array_merge($predictions, $predictionsFuture)]
        ];
    }
}
