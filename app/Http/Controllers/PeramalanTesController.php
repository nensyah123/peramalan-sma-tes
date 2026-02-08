<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use Illuminate\Http\Request;
use App\Models\PemakaianKendaraan;

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
}
