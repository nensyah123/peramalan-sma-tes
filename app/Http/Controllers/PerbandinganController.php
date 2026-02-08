<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Perbandingan;
use Illuminate\Http\Request;
use App\Models\PemakaianKendaraan;

class PerbandinganController extends Controller
{
    public function index()
    {
        $kendaraans = Kendaraan::all();
        $riwayat = Perbandingan::with('kendaraan')->latest()->get();
        return view('menu.perbandingan', compact('kendaraans', 'riwayat'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'periode_sma' => 'required|integer|min:1',
            'durasi_prediksi' => 'required|integer|min:1',
            'alpha' => 'required|numeric|min:0|max:1',
            'beta' => 'required|numeric|min:0|max:1',
            'gamma' => 'required|numeric|min:0|max:1',
        ]);

        $id_kendaraan = $request->id_kendaraan;
        $periode_sma = $request->periode_sma;
        $durasi = $request->durasi_prediksi;
        $alpha = $request->alpha;
        $beta = $request->beta;
        $gamma = $request->gamma;

        // Ambil Data Historis
        $dataPemakaian = PemakaianKendaraan::where('id_kendaraan', $id_kendaraan)
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        if ($dataPemakaian->count() < 12) {
            return back()->with('error', 'Data historis minimal 12 bulan diperlukan untuk perbandingan (karena metode TES membutuhkan 1 siklus tahunan).');
        }

        // Lakukan Perhitungan
        $smaResult = $this->calculateSMA($dataPemakaian, $periode_sma, $durasi);
        $tesResult = $this->calculateTES($dataPemakaian, $alpha, $beta, $gamma, $durasi);

        // Tentukan Metode Terbaik (berdasarkan MAPE terendah)
        $metode_terbaik = ($smaResult['metrics']['mape'] < $tesResult['metrics']['mape']) ? 'SMA' : 'TES';

        // Siapkan Data Grafik
        $chartLabels = $smaResult['chart']['labels'];
        $actualData = $smaResult['chart']['actual'];
        $smaData = $smaResult['chart']['predicted'];
        $tesData = $tesResult['chart']['predicted'];

        $result = [
            'sma' => ['mae' => $smaResult['metrics']['mae'], 'mse' => $smaResult['metrics']['mse'], 'mape' => $smaResult['metrics']['mape'], 'data' => $smaData],
            'tes' => ['mae' => $tesResult['metrics']['mae'], 'mse' => $tesResult['metrics']['mse'], 'mape' => $tesResult['metrics']['mape'], 'data' => $tesData],
            'chart' => ['labels' => $chartLabels, 'actual' => $actualData],
            'best' => $metode_terbaik
        ];

        /* Variabel untuk Tampilan */
        $mae_sma = $smaResult['metrics']['mae'];
        $mse_sma = $smaResult['metrics']['mse'];
        $mape_sma = $smaResult['metrics']['mape'];

        $mae_tes = $tesResult['metrics']['mae'];
        $mse_tes = $tesResult['metrics']['mse'];
        $mape_tes = $tesResult['metrics']['mape'];

        $kendaraans = Kendaraan::all();
        $riwayat = Perbandingan::with('kendaraan')->latest()->get();

        return view('menu.perbandingan', compact(
            'kendaraans',
            'riwayat',
            'mae_sma',
            'mse_sma',
            'mape_sma',
            'mae_tes',
            'mse_tes',
            'mape_tes',
            'metode_terbaik',
            'chartLabels',
            'actualData',
            'smaData',
            'tesData',
            'result'
        ))->with('showResult', true)->with('input', $request->all());
    }

    private function calculateSMA($data, $periode, $durasi)
    {
        $d = [];
        foreach ($data as $pem) {
            $d[] = ['aktual' => $pem->jumlah_transaksi, 'bulan' => $pem->bulan, 'tahun' => $pem->tahun];
        }

        $total_error_abs = 0;
        $total_error_sqr = 0;
        $total_ape = 0;
        $count_error = 0;
        $predictions = [];

        for ($i = 0; $i < count($d); $i++) {
            $prediksi = null;
            // Hitung SMA jika data mencukupi sesuai periode
            if ($i >= $periode) {
                $sum = 0;
                for ($k = 1; $k <= $periode; $k++) $sum += $d[$i - $k]['aktual'];
                $prediksi = round($sum / $periode, 2);

                $aktual = $d[$i]['aktual'];
                $err = abs($aktual - $prediksi);
                $total_error_abs += $err;
                $total_error_sqr += pow($err, 2);
                $total_ape += ($aktual != 0) ? ($err / $aktual) * 100 : 0;
                $count_error++;
            }
            $predictions[] = $prediksi;
        }

        $mae = ($count_error > 0) ? round($total_error_abs / $count_error, 2) : 0;
        $mse = ($count_error > 0) ? round($total_error_sqr / $count_error, 2) : 0;
        $mape = ($count_error > 0) ? round($total_ape / $count_error, 2) : 0;

        // Persiapan Data Grafik
        $chartLabels = [];
        $actualChart = [];
        $predChart = [];

        foreach ($d as $i => $row) {
            $chartLabels[] = $this->getMonthName($row['bulan']) . ' ' . $row['tahun'];
            $actualChart[] = $row['aktual'];
            $predChart[] = $predictions[$i];
        }

        // --- Peramalan Masa Depan ---
        // Kita perlu menyalin data aktual ke array sementara untuk perhitungan prediksi berlanjut
        for ($i = 0; $i < count($d); $i++) {
            $d[$i]['prediksi'] = $predictions[$i];
        }

        $lastMonth = $d[count($d) - 1]['bulan'];
        $lastYear = $d[count($d) - 1]['tahun'];

        for ($j = 0; $j < $durasi; $j++) {
            $lastMonth++;
            if ($lastMonth > 12) {
                $lastMonth = 1;
                $lastYear++;
            }

            $len = count($d);
            $sum = 0;
            // Ambil N data terakhir (bisa berupa aktual atau hasil prediksi sebelumnya)
            for ($k = 1; $k <= $periode; $k++) {
                $idx = $len - $k;
                $val = isset($d[$idx]['aktual']) ? $d[$idx]['aktual'] : $d[$idx]['prediksi'];
                $sum += $val;
            }
            $predFuture = round($sum / $periode, 2);

            // Tambahkan hasil prediksi ke array data untuk iterasi berikutnya
            $newRow = [
                'bulan' => $lastMonth,
                'tahun' => $lastYear,
                'aktual' => null,
                'prediksi' => $predFuture
            ];
            $d[] = $newRow;

            $chartLabels[] = $this->getMonthName($lastMonth) . ' ' . $lastYear;
            $actualChart[] = null;
            $predChart[] = $predFuture;
        }

        return [
            'metrics' => ['mae' => $mae, 'mse' => $mse, 'mape' => $mape],
            'chart' => ['labels' => $chartLabels, 'actual' => $actualChart, 'predicted' => $predChart]
        ];
    }

    private function calculateTES($data, $alpha, $beta, $gamma, $durasi)
    {
        $d = [];
        foreach ($data as $pem) {
            $d[] = ['aktual' => $pem->jumlah_transaksi, 'bulan' => $pem->bulan, 'tahun' => $pem->tahun];
        }
        $L = 12; // Panjang Musiman (12 bulan)
        $nData = count($d);

        // --- Inisialisasi ---

        // Hitung rata-rata musim pertama
        $avgFirstSeason = 0;
        for ($i = 0; $i < $L; $i++) $avgFirstSeason += $d[$i]['aktual'];
        $avgFirstSeason /= $L;

        // Inisialisasi Musiman (Model Aditif: Data - Rata-rata)
        $seasonals = [];
        for ($i = 0; $i < $L; $i++) $seasonals[] = $d[$i]['aktual'] - $avgFirstSeason;

        // Inisialisasi Level dan Trend
        $level = $avgFirstSeason;
        $trend = 0;

        // Jika data cukup (minimal 2 tahun), hitung trend awal dengan lebih akurat
        if ($nData >= 2 * $L) {
            $sumSecond = 0;
            for ($i = $L; $i < 2 * $L; $i++) $sumSecond += $d[$i]['aktual'];
            $avgSecond = $sumSecond / $L;
            $trend = ($avgSecond - $avgFirstSeason) / $L;
        }

        $seasonal_indices = $seasonals;
        $currLevel = $level;
        $currTrend = $trend;

        $total_error_abs = 0;
        $total_error_sqr = 0;
        $total_ape = 0;
        $count_error = 0;
        $predictions = array_fill(0, $L, null); // 12 bulan pertama tidak ada prediksi (masa inisialisasi)

        // --- Iterasi Peramalan (Mulai dari bulan ke-13) ---
        for ($i = $L; $i < $nData; $i++) {
            $prevLevel = $currLevel;
            $prevTrend = $currTrend;
            $seasonalIndexLoc = $i - $L;
            $prevSeasonal = $seasonal_indices[$seasonalIndexLoc];

            // 1. Prediksi (Aditif)
            $prediksi = ($prevLevel + $prevTrend) + $prevSeasonal;
            $prediksi = round($prediksi, 2);
            $aktual = $d[$i]['aktual'];

            // Hitung Error
            $err = abs($aktual - $prediksi);
            $total_error_abs += $err;
            $total_error_sqr += pow($err, 2);
            $total_ape += ($aktual != 0) ? ($err / $aktual) * 100 : 0;
            $count_error++;

            // 2. Pembaruan Parameter (Aditif)

            // Level Baru
            $newLevel = $alpha * ($aktual - $prevSeasonal) + (1 - $alpha) * ($prevLevel + $prevTrend);

            // Trend Baru
            $newTrend = $beta * ($newLevel - $prevLevel) + (1 - $beta) * $prevTrend;

            // Musiman Baru
            $newSeasonal = $gamma * ($aktual - $newLevel) + (1 - $gamma) * $prevSeasonal;

            $currLevel = $newLevel;
            $currTrend = $newTrend;
            $seasonal_indices[] = $newSeasonal;

            $predictions[] = $prediksi;
        }

        $mae = ($count_error > 0) ? round($total_error_abs / $count_error, 2) : 0;
        $mse = ($count_error > 0) ? round($total_error_sqr / $count_error, 2) : 0;
        $mape = ($count_error > 0) ? round($total_ape / $count_error, 2) : 0;

        // Persiapan Data Grafik
        $chartLabels = [];
        $actualChart = [];
        $predChart = [];

        foreach ($d as $i => $row) {
            $chartLabels[] = $this->getMonthName($row['bulan']) . ' ' . $row['tahun'];
            $actualChart[] = $row['aktual'];
            $predChart[] = isset($predictions[$i]) ? $predictions[$i] : null;
        }

        // --- Peramalan Masa Depan ---
        $lastMonth = $d[$nData - 1]['bulan'];
        $lastYear = $d[$nData - 1]['tahun'];

        for ($h = 1; $h <= $durasi; $h++) {
            $lastMonth++;
            if ($lastMonth > 12) {
                $lastMonth = 1;
                $lastYear++;
            }

            // Cari indeks musiman yang sesuai (looping ke belakang jika perlu)
            $s_idx = ($nData + $h - 1) - $L;
            while ($s_idx >= count($seasonal_indices)) $s_idx -= $L;
            if ($s_idx < 0) $s_idx = ($s_idx % $L + $L) % $L;

            $S_proj = $seasonal_indices[$s_idx];

            // Prediksi Masa Depan (Aditif)
            $predFuture = ($currLevel + $h * $currTrend) + $S_proj;
            $predFuture = round($predFuture, 2);

            $chartLabels[] = $this->getMonthName($lastMonth) . ' ' . $lastYear;
            $actualChart[] = null;
            $predChart[] = $predFuture;
        }

        return [
            'metrics' => ['mae' => $mae, 'mse' => $mse, 'mape' => $mape],
            'chart' => ['labels' => $chartLabels, 'actual' => $actualChart, 'predicted' => $predChart]
        ];
    }

    private function getMonthName($monthNum)
    {
        $dateObj   = \DateTime::createFromFormat('!m', $monthNum);
        return $dateObj->format('F');
    }

    public function store(Request $request)
    {
        // ... (Validasi sudah dilakukan di method process, namun bisa ditambahkan jika perlu)

        Perbandingan::create([
            'id_kendaraan' => $request->id_kendaraan,
            'periode_sma' => $request->periode_sma,
            'durasi_prediksi' => $request->durasi_prediksi,
            'alpha' => $request->alpha,
            'beta' => $request->beta,
            'gamma' => $request->gamma,
            'mae_sma' => $request->mae_sma,
            'mse_sma' => $request->mse_sma,
            'mape_sma' => $request->mape_sma,
            'mae_tes' => $request->mae_tes,
            'mse_tes' => $request->mse_tes,
            'mape_tes' => $request->mape_tes,
            'metode_terbaik' => $request->metode_terbaik,
            'data_perbandingan' => json_decode($request->data_perbandingan, true)
        ]);

        return redirect()->route('perbandingan.index')->with('success', 'Hasil perbandingan berhasil disimpan.');
    }

    public function destroy($id)
    {
        Perbandingan::findOrFail($id)->delete();
        return redirect()->route('perbandingan.index')->with('success', 'Riwayat perbandingan berhasil dihapus.');
    }
}
