<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Perbandingan;
use Illuminate\Http\Request;
use App\Models\PemakaianKendaraan;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function exportPdf($id)
    {
        $perbandingan = Perbandingan::with('kendaraan')->findOrFail($id);

        // 1. Ambil Data Chart
        $data = $perbandingan->data_perbandingan;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // Structure: $data['chart']['labels/actual'], $data['sma']['data'], $data['tes']['data']
        $labels = $data['chart']['labels'] ?? [];
        $actuals = $data['chart']['actual'] ?? [];
        $smaData = $data['sma']['data'] ?? [];
        $tesData = $data['tes']['data'] ?? [];

        // --- SVG Generation Logic ---

        $allValues = array_merge(
            array_filter($actuals, fn($v) => $v !== null),
            array_filter($smaData, fn($v) => $v !== null),
            array_filter($tesData, fn($v) => $v !== null)
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
        $smaPoints = [];
        $tesPoints = [];

        foreach ($labels as $i => $label) {
            $x = $paddingLeft + ($i * $stepX);

            $yAct = $getY($actuals[$i] ?? null);
            if ($yAct !== null) $actualPoints[] = "$x,$yAct";

            $ySma = $getY($smaData[$i] ?? null);
            if ($ySma !== null) $smaPoints[] = "$x,$ySma";

            $yTes = $getY($tesData[$i] ?? null);
            if ($yTes !== null) $tesPoints[] = "$x,$yTes";
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
        $smaPts = implode(' ', $smaPoints);
        $tesPts = implode(' ', $tesPoints);

        // Actual - Blue
        $svgContent .= '<polyline points="' . $actPts . '" fill="none" stroke="#4e73df" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />';
        // SMA - Green
        $svgContent .= '<polyline points="' . $smaPts . '" fill="none" stroke="#1cc88a" stroke-width="2" stroke-dasharray="5,5" stroke-linecap="round" stroke-linejoin="round"/>';
        // TES - Yellow
        $svgContent .= '<polyline points="' . $tesPts . '" fill="none" stroke="#f6c23e" stroke-width="2" stroke-dasharray="5,5" stroke-linecap="round" stroke-linejoin="round"/>';

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
        $l1 = $svgWidth - 250;
        $svgContent .= '<rect x="' . $l1 . '" y="10" width="10" height="10" fill="#4e73df" />';
        $svgContent .= '<text x="' . ($l1 + 15) . '" y="19" font-family="sans-serif" font-size="11" fill="#333">Actual</text>';

        $l2 = $l1 + 60;
        $svgContent .= '<rect x="' . $l2 . '" y="10" width="10" height="10" fill="none" stroke="#1cc88a" stroke-width="2" stroke-dasharray="4" />';
        $svgContent .= '<text x="' . ($l2 + 15) . '" y="19" font-family="sans-serif" font-size="11" fill="#333">SMA</text>';

        $l3 = $l2 + 50;
        $svgContent .= '<rect x="' . $l3 . '" y="10" width="10" height="10" fill="none" stroke="#f6c23e" stroke-width="2" stroke-dasharray="4" />';
        $svgContent .= '<text x="' . ($l3 + 15) . '" y="19" font-family="sans-serif" font-size="11" fill="#333">TES</text>';

        $svgContent .= '</svg>';

        $chartImage = 'data:image/svg+xml;base64,' . base64_encode($svgContent);

        $pdf = Pdf::loadView('pdf.perbandingan', compact('perbandingan', 'chartImage'));
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions(['dpi' => 150, 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        return $pdf->download('laporan_perbandingan_' . $perbandingan->created_at->format('YmdHis') . '.pdf');
    }
}
