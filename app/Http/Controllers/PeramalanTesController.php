<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use App\Models\TransaksiPenyewaan;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PeramalanTesController extends Controller
{
    public function index()
    {
        $merks = Kendaraan::select('merk')->distinct()->orderBy('merk')->pluck('merk');
        return view('menu.peramalan_tes', compact('merks'));
    }

    public function riwayat()
    {
        $riwayat = PeramalanTes::latest()->get();
        return view('menu.riwayat_tes', compact('riwayat'));
    }

    // ===== OPTIMASI α, β, γ via Railway API (Python FastAPI) =====
    // Menggantikan exec/shell_exec python yang tidak bisa jalan di Vercel
    // karena Vercel tidak support Python runtime dan filesystem-nya read-only
    private function getOptimalParams(array $values): array
    {
        // Kirim data historis ke Python API yang di-deploy di Railway
        $response = \Illuminate\Support\Facades\Http::timeout(60)
            ->post('https://python-optimasi-api-production.up.railway.app/optimasi', [
                'values' => $values,
            ]);

        // Jika request gagal (network error, server down, dll)
        if ($response->failed()) {
            throw new \Exception('Gagal koneksi ke Python API: ' . $response->body());
        }

        $result = $response->json();

        // Validasi response harus mengandung alpha
        if (!$result || !isset($result['alpha'])) {
            throw new \Exception('Response tidak valid dari Python API');
        }

        // Return array berisi alpha, beta, gamma yang sudah dioptimasi
        return $result;
    }

    // ===== TAHAP 1: INISIALISASI MANUAL =====
    // Menghitung nilai awal Level (L0), Trend (b0), dan Seasonal (S0)
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
    // Menghitung nilai Level, Trend, Seasonal, Prediksi, dan Error
    // menggunakan metode Holt-Winters Triple Exponential Smoothing
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

        // Ambil nilai inisialisasi awal
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

            // Baris 1-11: periode inisialisasi, semua kolom kosong (-)
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

            // Baris 12 (Desember tahun pertama): tampilkan nilai inisialisasi awal
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

            // Baris 13+: iterasi Holt-Winters
            $St_s = $S[$i % $s]; // Ambil nilai seasonal periode lalu

            // Forecast periode ini menggunakan L, b, dan S sebelumnya
            $Ft = $L + $b + $St_s;
            $Ft = round($Ft, 2);

            // Update Level menggunakan alpha
            $L_new = $alpha * ($aktual - $St_s) + (1 - $alpha) * ($L + $b);

            // Update Trend menggunakan beta
            $b_new = $beta * ($L_new - $L) + (1 - $beta) * $b;

            // Update Seasonal menggunakan gamma
            $S[$i % $s] = $gamma * ($aktual - $L_new) + (1 - $gamma) * $St_s;

            $L = $L_new;
            $b = $b_new;

            // Hitung error (MAD, MSE, MAPE)
            $err = abs($aktual - $Ft);
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
                'prediksi'    => $Ft,
                'error'       => round($err,      4),
                'error_sqr'   => round($err ** 2, 4),
                'ape'         => round($ape,      4),
            ];
        }

        // Hitung metrik akurasi keseluruhan
        $mad  = $count > 0 ? round($totalMad  / $count, 4) : 0;
        $mse  = $count > 0 ? round($totalMse  / $count, 4) : 0;
        $mape = $count > 0 ? round($totalMape / $count, 4) : 0;

        // ===== TAHAP 4: FORECAST KE DEPAN =====
        // Lanjutkan prediksi sesuai durasi yang diminta
        $lastBulan = $bulanData[$n - 1]['bulan'];
        $lastTahun = $bulanData[$n - 1]['tahun'];

        for ($h = 1; $h <= $durasi; $h++) {
            $lastBulan++;
            if ($lastBulan > 12) { $lastBulan = 1; $lastTahun++; }

            // Gunakan indeks seasonal yang sesuai
            $idx = ($n - $s + $h - 1) % $s;
            $Ft  = $L + $b * $h + $S[$idx];
            $Ft  = round(max(0, $Ft), 2); // Pastikan tidak negatif

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

    // ===== PROSES UTAMA PERAMALAN =====
    public function process(Request $request)
    {
        $request->validate([
            'merk'            => 'required|string',
            'durasi_prediksi' => 'required|integer|min:1',
        ]);

        $merk   = $request->merk;
        $durasi = (int) $request->durasi_prediksi;
        $s      = 12; // Periode musiman = 12 bulan

        // Ambil semua ID kendaraan berdasarkan merk
        $ids = Kendaraan::where('merk', $merk)->pluck('id');

        if ($ids->isEmpty()) {
            return back()->with('error', 'Merk kendaraan tidak ditemukan.');
        }

        // Exclude bulan berjalan agar data tidak terpotong
        $bulanSekarang = (int) date('m');
        $tahunSekarang = (int) date('Y');

        // Ambil data historis penyewaan per bulan
        $raw = TransaksiPenyewaan::selectRaw('MONTH(tgl_pinjam) as bulan, YEAR(tgl_pinjam) as tahun, COUNT(*) as total')
            ->whereIn('id_kendaraan', $ids)
            ->where(function($query) use ($bulanSekarang, $tahunSekarang) {
                $query->whereYear('tgl_pinjam', '<', $tahunSekarang)
                      ->orWhere(function($q) use ($bulanSekarang, $tahunSekarang) {
                          $q->whereYear('tgl_pinjam', '=', $tahunSekarang)
                            ->whereMonth('tgl_pinjam', '<', $bulanSekarang);
                      });
            })
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        // Minimal 12 data untuk TES
        if ($raw->count() < $s) {
            return back()->with('error', 'Data historis harus minimal ' . $s . ' bulan untuk metode TES.');
        }

        $values    = [];
        $labels    = [];
        $bulanData = [];

        foreach ($raw as $row) {
            $values[]    = (float) $row->total;
            $labels[]    = $this->getMonthName((int)$row->bulan) . ' ' . $row->tahun;
            $bulanData[] = ['bulan' => (int)$row->bulan, 'tahun' => (int)$row->tahun];
        }

        // ===== STEP 1: Dapatkan α, β, γ optimal dari Railway Python API =====
        try {
            $params = $this->getOptimalParams($values);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $alpha = $params['alpha'];
        $beta  = $params['beta'];
        $gamma = $params['gamma'];

        // ===== STEP 2: Hitung TES secara manual di PHP =====
        $result = $this->hitungTESManual(
            $values, $labels, $bulanData,
            $alpha, $beta, $gamma,
            $durasi, $s
        );

        $mad         = $result['mad'];
        $mse         = $result['mse'];
        $mape        = $result['mape'];
        $resultTable = $result['result_table'];

        // Siapkan data untuk chart
        $chartLabels   = [];
        $actualData    = [];
        $predictedData = [];

        foreach ($resultTable as $row) {
            $chartLabels[]   = $row['bulan_tahun'];
            $actualData[]    = ($row['aktual'] !== '-') ? (int) $row['aktual'] : null;
            $predictedData[] = ($row['prediksi'] !== '-') ? (float) $row['prediksi'] : null;
        }

        $merks = Kendaraan::select('merk')->distinct()->orderBy('merk')->pluck('merk');

        return view('menu.peramalan_tes', compact(
            'merks',
            'mad', 'mse', 'mape',
            'chartLabels', 'actualData', 'predictedData',
            'resultTable',
            'merk', 'durasi',
            'alpha', 'beta', 'gamma'
        ))->with('showResult', true);
    }

    // ===== SIMPAN HASIL PERAMALAN =====
    public function store(Request $request)
    {
        $request->validate([
            'merk'            => 'required|string',
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
            'merk'            => $request->merk,
            'alfa'            => $request->alpha,
            'beta'            => $request->beta,
            'gamma'           => $request->gamma,
            'durasi_prediksi' => $request->durasi_prediksi,
            'mad'             => $request->mad,
            'mse'             => $request->mse,
            'mape'            => $request->mape,
            'data_peramalan'  => json_decode($request->data_peramalan, true),
        ]);

        return redirect()->route('peramalan_tes.riwayat')
            ->with('success', 'Hasil peramalan TES berhasil disimpan.');
    }

    // ===== HAPUS RIWAYAT PERAMALAN =====
    public function destroy($id)
    {
        PeramalanTes::findOrFail($id)->delete();
        return redirect()->route('peramalan_tes.riwayat')
            ->with('success', 'Riwayat peramalan berhasil dihapus.');
    }

    // ===== HELPER: Konversi nomor bulan ke nama bulan =====
    private function getMonthName($monthNum)
    {
        return \DateTime::createFromFormat('!m', $monthNum)->format('M');
    }

    // ===== EXPORT PDF =====
    public function exportPdf($id)
    {
        $peramalan = PeramalanTes::findOrFail($id);

        $data = $peramalan->data_peramalan;
        if (is_string($data)) $data = json_decode($data, true);
        $table = is_array($data) ? $data : [];
        if (isset($data['table'])) $table = $data['table'];

        $metrics = [
            'mad'  => $peramalan->mad,
            'mse'  => $peramalan->mse,
            'mape' => $peramalan->mape,
        ];

        $labels = []; $actuals = []; $predicteds = [];
        foreach ($table as $row) {
            $labels[]     = $row['bulan_tahun'];
            $actuals[]    = ($row['aktual'] !== '-') ? $row['aktual'] : null;
            $predicteds[] = ($row['prediksi'] !== '-') ? $row['prediksi'] : null;
        }

        // Hitung batas grafik SVG
        $allValues = array_merge(
            array_filter($actuals,    fn($v) => $v !== null),
            array_filter($predicteds, fn($v) => $v !== null)
        );
        $minY    = empty($allValues) ? 0   : min($allValues);
        $maxY    = empty($allValues) ? 100 : max($allValues);
        $padding = ($maxY - $minY) * 0.1 ?: 10;
        $minY    = max(0, $minY - $padding);
        $maxY   += $padding;

        // Konfigurasi dimensi SVG
        $svgWidth     = 1000; $svgHeight     = 300;
        $paddingLeft  = 50;   $paddingBottom = 30;
        $graphWidth   = $svgWidth  - $paddingLeft;
        $graphHeight  = $svgHeight - $paddingBottom;
        $count        = count($labels);
        $stepX        = $graphWidth / max(1, $count - 1);

        // Fungsi konversi nilai ke koordinat Y
        $getY = function ($val) use ($graphHeight, $minY, $maxY) {
            if ($val === null) return null;
            $ratio = ($val - $minY) / max(1, $maxY - $minY);
            return $graphHeight - ($ratio * $graphHeight);
        };

        // Buat titik-titik untuk polyline
        $actualPoints = []; $predictedPoints = [];
        foreach ($labels as $i => $label) {
            $x     = $paddingLeft + ($i * $stepX);
            $yAct  = $getY($actuals[$i]    ?? null);
            if ($yAct  !== null) $actualPoints[]    = "$x,$yAct";
            $yPred = $getY($predicteds[$i] ?? null);
            if ($yPred !== null) $predictedPoints[] = "$x,$yPred";
        }

        // Bangun SVG untuk grafik di PDF
        $svgContent  = '<svg width="'.$svgWidth.'" height="'.$svgHeight.'" xmlns="http://www.w3.org/2000/svg">';
        $svgContent .= '<rect x="0" y="0" width="'.$svgWidth.'" height="'.$svgHeight.'" fill="none" stroke="#f0f0f0" stroke-width="1"/>';
        for ($i = 0; $i <= 4; $i++) {
            $y   = ($svgHeight - $paddingBottom) - ($i * ($svgHeight - $paddingBottom - 20) / 4);
            $val = $minY + ($i * ($maxY - $minY) / 4);
            $svgContent .= '<line x1="'.$paddingLeft.'" y1="'.$y.'" x2="'.$svgWidth.'" y2="'.$y.'" stroke="#e6e6e6" stroke-width="1" stroke-dasharray="4"/>';
            $svgContent .= '<text x="'.($paddingLeft-5).'" y="'.($y+3).'" font-size="10" fill="#888" text-anchor="end">'.number_format($val,0).'</text>';
        }
        $svgContent .= '<polyline points="'.implode(' ',$actualPoints).'"    fill="none" stroke="#4e73df" stroke-width="2"/>';
        $svgContent .= '<polyline points="'.implode(' ',$predictedPoints).'" fill="none" stroke="#1cc88a" stroke-width="2" stroke-dasharray="5,5"/>';
        $skip = ($count > 12) ? ceil($count / 12) : 1;
        foreach ($labels as $i => $label) {
            $x = $paddingLeft + ($i * $stepX);
            if ($i == 0 || $i == $count - 1 || $i % $skip == 0) {
                $svgContent .= '<text x="'.$x.'" y="'.($svgHeight-10).'" font-size="9" fill="#666" text-anchor="middle">'.$label.'</text>';
            }
        }
        $svgContent .= '<rect x="'.($svgWidth-150).'" y="10" width="10" height="10" fill="#4e73df"/>';
        $svgContent .= '<text x="'.($svgWidth-135).'" y="19" font-size="11" fill="#333">Data Aktual</text>';
        $svgContent .= '<rect x="'.($svgWidth-70).'"  y="10" width="10" height="10" fill="none" stroke="#1cc88a" stroke-width="2" stroke-dasharray="4"/>';
        $svgContent .= '<text x="'.($svgWidth-55).'"  y="19" font-size="11" fill="#333">Prediksi</text>';
        $svgContent .= '</svg>';

        // Encode SVG ke base64 untuk di-embed di PDF
        $chartImage = 'data:image/svg+xml;base64,' . base64_encode($svgContent);

        $pdf = Pdf::loadView('pdf.peramalan_tes', compact('peramalan', 'metrics', 'table', 'chartImage'));
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions(['dpi' => 150, 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        return $pdf->download('laporan_tes_' . $peramalan->created_at->format('YmdHis') . '.pdf');
    }
}
