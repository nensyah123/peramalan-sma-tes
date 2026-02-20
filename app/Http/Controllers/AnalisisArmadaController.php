<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use Illuminate\Http\Request;

class AnalisisArmadaController extends Controller
{
  /**
   * Menampilkan halaman analisis armada.
   * Mengitung kebutuhan armada berdasarkan prediksi transaksi bulan depan.
   *
   * @return \Illuminate\View\View
   */
  public function index()
  {
    $kendaraans = Kendaraan::all();
    $analisis = [];

    $summary = [
      'cukup' => 0,
      'kurang' => 0,
      'berlebih' => 0
    ];

    $chartData = [
      'labels' => [],
      'unit_tersedia' => [],
      'kebutuhan_prediksi' => []
    ];

    foreach ($kendaraans as $kendaraan) {
      $lastTes = PeramalanTes::where('id_kendaraan', $kendaraan->id)->latest()->first();

      $prediksiBulanDepan = 0;
      $periodeLabel = '-';

      if ($lastTes) {
        $data = $lastTes->data_peramalan;
        if (is_string($data)) {
          $data = json_decode($data, true);
        }

        // Cari prediksi periode pertama di masa depan
        foreach ($data as $row) {
          $isActualEmpty = ($row['aktual'] === '-' || $row['aktual'] === null);
          $isPredictionExist = ($row['prediksi'] !== '-' && $row['prediksi'] !== null);

          if ($isActualEmpty && $isPredictionExist) {
            $prediksiBulanDepan = floatval($row['prediksi']);
            $periodeLabel = $row['bulan_tahun'];
            break;
          }
        }
      }

      // Hitung Kebutuhan Unit Harian
      // Rumus: Total Prediksi Bulan (n) / 30 hari
      // Dibulatkan ke atas (ceil) karena unit tidak bisa pecahan
      $kebutuhan = $prediksiBulanDepan > 0 ? ceil($prediksiBulanDepan / 30) : 0;

      $selisih = $kendaraan->unit - $kebutuhan;
      $status = '';

      // Tentukan Status Armada
      if ($selisih < 0) {
        $status = 'Kurang';
        $summary['kurang']++;
      } elseif ($selisih >= 2) {
        $status = 'Berlebih';
        $summary['berlebih']++;
      } else {
        $status = 'Cukup';
        $summary['cukup']++;
      }

      $analisis[] = [
        'kendaraan' => $kendaraan->nama_kendaraan,
        'unit_tersedia' => $kendaraan->unit,
        'prediksi_transaksi' => $prediksiBulanDepan,
        'prediksi_kebutuhan_unit' => $kebutuhan,
        'periode' => $periodeLabel,
        'selisih' => ($selisih > 0 ? '+' : '') . $selisih,
        'status' => $status
      ];

      // Siapkan data untuk chart
      $chartData['labels'][] = $kendaraan->nama_kendaraan;
      $chartData['unit_tersedia'][] = $kendaraan->unit;
      $chartData['kebutuhan_prediksi'][] = $kebutuhan;
    }

    return view('menu.analisis_armada', compact('analisis', 'summary', 'chartData'));
  }
}
