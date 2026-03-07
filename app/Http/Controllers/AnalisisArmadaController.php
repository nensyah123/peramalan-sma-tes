<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use Illuminate\Http\Request;
use App\Models\PemakaianKendaraan;

class AnalisisArmadaController extends Controller
{
    // =========================================================
    // LOGIKA ANALISIS ARMADA
    //
    // Konteks bisnis:
    //   - Kantor punya stok tetap di garasi
    //   - Jika demand melebihi stok → pinjam dari rental lain
    //   - Data transaksi = total permintaan aktual (sudah termasuk pinjaman)
    //
    // Alur perhitungan:
    //   1. Rata-rata transaksi/bulan  = total historis ÷ jumlah bulan
    //   2. Kapasitas 1 unit           = rata-rata ÷ jumlah unit stok
    //   3. Kapasitas total stok       = rata-rata (= kap.1unit × unit, matematis sama)
    //   4. Threshold waspada          = 80% × kapasitas total
    //   5. Kelebihan demand           = MAX(0, forecast − kapasitas total)
    //   6. Unit perlu dipinjam        = CEILING(kelebihan ÷ kapasitas 1 unit)
    //   7. % Utilisasi stok           = (forecast ÷ kapasitas total) × 100%
    //   8. Status:
    //        ✅ Aman    → utilisasi ≤ 80%
    //        ⚠️ Waspada → 80% < utilisasi ≤ 100%
    //        🔴 Kritis  → utilisasi > 100% (wajib pinjam)
    // =========================================================

    /**
     * Deteksi info seasonal dari string periode.
     * Mengembalikan label lengkap, nama pendek, dan flag is_peak / is_low.
     *
     * CATATAN: Daftar Lebaran perlu diperbarui tiap tahun karena tanggalnya berpindah.
     */
    private function getSeasonalInfo(string $periodeStr): array
    {
        $str = strtolower(trim($periodeStr));

        // Lebaran — perbarui tiap tahun
        $lebaranKeywords = [
            'april 2023', 'apr 2023', 'apr-23',
            'april 2024', 'apr 2024', 'apr-24',
            'maret 2025', 'mar 2025', 'mar-25', 'march 2025',
        ];
        foreach ($lebaranKeywords as $kw) {
            if (str_contains($str, $kw)) {
                return ['label' => 'Peak - Lebaran', 'short' => 'Lebaran', 'is_peak' => true, 'is_low' => false];
            }
        }

        // Liburan sekolah: Juni & Juli setiap tahun
        if (preg_match('/\b(juni|june|jul)\b/i', $str)) {
            return ['label' => 'Peak - Liburan Sekolah', 'short' => 'Liburan Sekolah', 'is_peak' => true, 'is_low' => false];
        }

        // Natal & Tahun Baru
        if (preg_match('/\b(desember|des|december|dec)\b/i', $str)) {
            return ['label' => 'Peak - Natal/Tahun Baru', 'short' => 'Natal/Tahun Baru', 'is_peak' => true, 'is_low' => false];
        }

        // Low season
        if (preg_match('/\b(januari|jan|january)\b/i', $str)) {
            return ['label' => 'Low Season', 'short' => 'Low Season', 'is_peak' => false, 'is_low' => true];
        }
        if (preg_match('/\b(november|nov)\b/i', $str)) {
            return ['label' => 'Low Season', 'short' => 'Low Season', 'is_peak' => false, 'is_low' => true];
        }

        return ['label' => 'Normal', 'short' => 'Normal', 'is_peak' => false, 'is_low' => false];
    }

    /**
     * Tentukan status armada berdasarkan % utilisasi stok.
     */
    private function getStatus(float $utilisasi): array
    {
        if ($utilisasi <= 80) {
            return ['label' => 'Aman',    'icon' => '✅', 'text_color' => '#2e7d32', 'bg_color' => '#e8f5e9'];
        } elseif ($utilisasi <= 100) {
            return ['label' => 'Waspada', 'icon' => '⚠️', 'text_color' => '#e65100', 'bg_color' => '#fff8e1'];
        } else {
            return ['label' => 'Kritis',  'icon' => '🔴', 'text_color' => '#c62828', 'bg_color' => '#fdecea'];
        }
    }

    /**
     * Buat teks rekomendasi.
     * Menggunakan $seasonal['short'] agar teks lebih bersih (misal "Lebaran" bukan "Peak - Lebaran").
     */
    private function getRekomendasi(string $statusLabel, int $unitPinjam, array $seasonal): string
    {
        $namaMusim = $seasonal['short'];
        $isPeak    = $seasonal['is_peak'];
        $isLow     = $seasonal['is_low'];

        if ($statusLabel === 'Kritis') {
            $teks = "Segera pinjam {$unitPinjam} unit dari rental lain";
            if ($isPeak) $teks .= " (wajar di musim {$namaMusim})";
            return $teks;
        }

        if ($statusLabel === 'Waspada') {
            $teks = "Siapkan rencana pinjaman, koordinasi dengan mitra rental";
            if ($isPeak) $teks .= " — antisipasi peak {$namaMusim}";
            return $teks;
        }

        // Aman
        if ($isLow) return "Stok cukup — pertimbangkan efisiensi armada di {$namaMusim}";
        return "Stok sendiri mencukupi, tidak perlu tindakan";
    }

    // =========================================================
    // HALAMAN UTAMA
    // =========================================================
    public function index(Request $request)
    {
        $kendaraans = Kendaraan::all();

        $statusKendaraan = [];
        $summary = ['aman' => 0, 'waspada' => 0, 'kritis' => 0];

        foreach ($kendaraans as $kendaraan) {

            $dataHistoris = PemakaianKendaraan::where('id_kendaraan', $kendaraan->id)->get();

            if ($dataHistoris->isEmpty()) {
                $statusKendaraan[] = ['id' => $kendaraan->id, 'nama' => $kendaraan->nama_kendaraan, 'status' => 'aman'];
                $summary['aman']++;
                continue;
            }

            $jumlahBulan    = $dataHistoris->count();
            $totalTransaksi = $dataHistoris->sum('jumlah_transaksi');
            $rataRata       = $totalTransaksi / $jumlahBulan;
            $jumlahUnit     = max(1, $kendaraan->unit);
            $kapasitas1Unit = $rataRata / $jumlahUnit;
            $kapasitasTotal = $rataRata;

            if ($kapasitas1Unit <= 0) {
                $statusKendaraan[] = ['id' => $kendaraan->id, 'nama' => $kendaraan->nama_kendaraan, 'status' => 'aman'];
                $summary['aman']++;
                continue;
            }

            $lastTes    = PeramalanTes::where('id_kendaraan', $kendaraan->id)->latest()->first();
            $statusList = [];

            if ($lastTes) {
                $data = $lastTes->data_peramalan;
                if (is_string($data)) $data = json_decode($data, true);

                foreach ($data as $row) {
                    $isAktualKosong = ($row['aktual'] === '-' || $row['aktual'] === null);
                    $isPrediksiAda  = ($row['prediksi'] !== '-' && $row['prediksi'] !== null);
                    if (!$isAktualKosong || !$isPrediksiAda) continue;

                    $utilisasi    = $kapasitasTotal > 0 ? (floatval($row['prediksi']) / $kapasitasTotal) * 100 : 0;
                    $statusList[] = $this->getStatus($utilisasi)['label'];
                }
            }

            // Status dominan: Kritis > Waspada > Aman
            if (in_array('Kritis', $statusList))      $dominan = 'kritis';
            elseif (in_array('Waspada', $statusList)) $dominan = 'waspada';
            else                                       $dominan = 'aman';

            $summary[$dominan]++;
            $statusKendaraan[] = ['id' => $kendaraan->id, 'nama' => $kendaraan->nama_kendaraan, 'status' => $dominan];
        }

        // ── Filter ──
        $filterStatus = $request->get('filter_status');
        $selectedId   = $request->get('id_kendaraan');

        if ($filterStatus && !$selectedId) {
            $found      = collect($statusKendaraan)->firstWhere('status', $filterStatus);
            $selectedId = $found ? $found['id'] : optional($kendaraans->first())->id;
        }
        if (!$selectedId) $selectedId = optional($kendaraans->first())->id;

        $kendaraanFiltered = $filterStatus
            ? collect($statusKendaraan)->where('status', $filterStatus)->pluck('id')->toArray()
            : null;

        // ── Detail kendaraan terpilih ──
        $selectedAnalisis  = null;
        $chartData         = ['labels' => [], 'unit_pinjam' => [], 'unit_tersedia' => [], 'utilisasi' => []];
        $selectedKendaraan = $kendaraans->firstWhere('id', $selectedId);

        if ($selectedKendaraan) {
            $dataHistoris   = PemakaianKendaraan::where('id_kendaraan', $selectedKendaraan->id)->get();
            $jumlahBulan    = $dataHistoris->isNotEmpty() ? $dataHistoris->count() : 0;
            $totalTransaksi = $dataHistoris->isNotEmpty() ? $dataHistoris->sum('jumlah_transaksi') : 0;
            $rataRata       = $jumlahBulan > 0 ? round($totalTransaksi / $jumlahBulan, 2) : 0;
            $jumlahUnit     = max(1, $selectedKendaraan->unit);
            $kapasitas1Unit = $rataRata > 0 ? round($rataRata / $jumlahUnit, 2) : 0;
            $kapasitasTotal = $rataRata;
            $thresholdWaspada = round($kapasitasTotal * 0.8, 2);

            $lastTes       = PeramalanTes::where('id_kendaraan', $selectedKendaraan->id)->latest()->first();
            $bulanPrediksi = [];

            if ($lastTes && $kapasitas1Unit > 0) {
                $data = $lastTes->data_peramalan;
                if (is_string($data)) $data = json_decode($data, true);

                foreach ($data as $row) {
                    $isAktualKosong = ($row['aktual'] === '-' || $row['aktual'] === null);
                    $isPrediksiAda  = ($row['prediksi'] !== '-' && $row['prediksi'] !== null);
                    if (!$isAktualKosong || !$isPrediksiAda) continue;

                    $forecast        = floatval($row['prediksi']);
                    $periode         = $row['bulan_tahun'];
                    $seasonal        = $this->getSeasonalInfo($periode);

                    $kelebihanDemand = max(0, $forecast - $kapasitasTotal);
                    $unitPinjam      = $kelebihanDemand > 0 ? (int) ceil($kelebihanDemand / $kapasitas1Unit) : 0;
                    $utilisasi       = $kapasitasTotal > 0 ? round(($forecast / $kapasitasTotal) * 100, 1) : 0;
                    $status          = $this->getStatus($utilisasi);
                    $rekomendasi     = $this->getRekomendasi($status['label'], $unitPinjam, $seasonal);

                    $bulanPrediksi[] = [
                        'periode'           => $periode,
                        'seasonal_label'    => $seasonal['label'],
                        'seasonal_short'    => $seasonal['short'],
                        'is_peak'           => $seasonal['is_peak'],
                        'is_low'            => $seasonal['is_low'],
                        'forecast'          => round($forecast, 2),
                        'kapasitas_1_unit'  => $kapasitas1Unit,
                        'kapasitas_total'   => round($kapasitasTotal, 2),
                        'threshold_waspada' => $thresholdWaspada,
                        'kelebihan_demand'  => round($kelebihanDemand, 2),
                        'unit_pinjam'       => $unitPinjam,
                        'unit_tersedia'     => $selectedKendaraan->unit,
                        'utilisasi'         => $utilisasi,
                        'status'            => $status,
                        'rekomendasi'       => $rekomendasi,
                    ];

                    $chartData['labels'][]        = $periode;
                    $chartData['unit_pinjam'][]   = $unitPinjam;
                    $chartData['unit_tersedia'][] = $selectedKendaraan->unit;
                    $chartData['utilisasi'][]     = $utilisasi;
                }
            }

            $selectedAnalisis = [
                'kendaraan'         => $selectedKendaraan->nama_kendaraan,
                'unit_tersedia'     => $selectedKendaraan->unit,
                'jumlah_bulan'      => $jumlahBulan,
                'total_transaksi'   => $totalTransaksi,
                'rata_rata'         => $rataRata,
                'kapasitas_1_unit'  => $kapasitas1Unit,
                'kapasitas_total'   => round($kapasitasTotal, 2),
                'threshold_waspada' => $thresholdWaspada,
                'bulan_prediksi'    => $bulanPrediksi,
            ];
        }

        return view('menu.analisis_armada', compact(
            'kendaraans', 'summary', 'selectedAnalisis',
            'chartData', 'selectedId', 'filterStatus',
            'statusKendaraan', 'kendaraanFiltered'
        ));
    }
}
