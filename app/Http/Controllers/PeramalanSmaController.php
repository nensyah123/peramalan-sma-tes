<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanSma;
use App\Models\PemakaianKendaraan;
use Illuminate\Http\Request;

class PeramalanSmaController extends Controller
{
    public function index()
    {
        $kendaraans = Kendaraan::all();
        $riwayat = PeramalanSma::with('kendaraan')->latest()->get();
        return view('menu.peramalan_sma', compact('kendaraans', 'riwayat'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'periode' => 'required|integer|min:1',
            'durasi_prediksi' => 'required|integer|min:1',
        ]);

        $id_kendaraan = $request->id_kendaraan;
        $periode = $request->periode; // n
        $durasi = $request->durasi_prediksi;

        // Ambil Data Historis
        $dataPemakaian = PemakaianKendaraan::where('id_kendaraan', $id_kendaraan)
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        if ($dataPemakaian->count() < $periode) {
            return back()->with('error', 'Data historis tidak cukup untuk melakukan peramalan dengan periode (n) = ' . $periode);
        }

        $resultTable = [];
        $d = [];
        foreach ($dataPemakaian as $idx => $pem) {
            $d[] = [
                'bulan' => $pem->bulan,
                'tahun' => $pem->tahun,
                'aktual' => $pem->jumlah_transaksi,
                'bulan_tahun' => $this->getMonthName($pem->bulan) . ' ' . $pem->tahun
            ];
        }

        $total_error_abs = 0;
        $total_error_sqr = 0;
        $total_ape = 0;
        $count_error = 0;

        // 1. Perhitungan SMA pada Data Historis (Pengujian)
        for ($i = 0; $i < count($d); $i++) {
            $prediksi = null;
            $error_abs = null;
            $error_sqr = null;
            $ape = null;

            // Hitung jika data historis sebelumnya mencukupi (>= n)
            if ($i >= $periode) {
                $sum = 0;
                for ($k = 1; $k <= $periode; $k++) {
                    $sum += $d[$i - $k]['aktual'];
                }
                $prediksi = $sum / $periode;
                $prediksi = round($prediksi, 2);

                $aktual = $d[$i]['aktual'];

                // Hitung Error
                $error_abs = abs($aktual - $prediksi);
                $error_sqr = pow($error_abs, 2);
                $ape = ($aktual != 0) ? ($error_abs / $aktual) * 100 : 0;

                $total_error_abs += $error_abs;
                $total_error_sqr += $error_sqr;
                $total_ape += $ape;
                $count_error++;
            }

            $d[$i]['prediksi'] = $prediksi;
            $d[$i]['error_abs'] = $error_abs;
            $d[$i]['error_sqr'] = $error_sqr;
            $d[$i]['ape'] = $ape;

            $resultTable[] = [
                'bulan_tahun' => $d[$i]['bulan_tahun'],
                'aktual' => $d[$i]['aktual'],
                'prediksi' => $prediksi,
                'error' => $error_abs !== null ? number_format($error_abs, 2) : '-',
                'error_sqr' => $error_sqr !== null ? number_format($error_sqr, 2) : '-',
                'ape' => $ape !== null ? number_format($ape, 2) : '-',
            ];
        }

        // Hitung Rata-rata Error
        $mae = ($count_error > 0) ? round($total_error_abs / $count_error, 2) : 0;
        $mse = ($count_error > 0) ? round($total_error_sqr / $count_error, 2) : 0;
        $mape = ($count_error > 0) ? round($total_ape / $count_error, 2) : 0;

        // 2. Peramalan Masa Depan
        $tempData = $d;
        $lastMonth = $d[count($d) - 1]['bulan'];
        $lastYear = $d[count($d) - 1]['tahun'];

        for ($j = 0; $j < $durasi; $j++) {
            // Update Tanggal
            $lastMonth++;
            if ($lastMonth > 12) {
                $lastMonth = 1;
                $lastYear++;
            }

            // Hitung Prediksi Masa Depan
            // Mengambil n nilai terakhir dari tempData (bisa campuran aktual dan prediksi sebelumnya)
            $len = count($tempData);
            $sum = 0;
            for ($k = 1; $k <= $periode; $k++) {
                $idx = $len - $k;
                // Gunakan aktual jika ada, jika tidak gunakan hasil prediksi sebelumnya
                if (isset($tempData[$idx]['aktual'])) {
                    $val = $tempData[$idx]['aktual'];
                } else {
                    $val = $tempData[$idx]['prediksi'];
                }
                $sum += $val;
            }
            $predFuture = round($sum / $periode, 2);

            $newRow = [
                'bulan' => $lastMonth,
                'tahun' => $lastYear,
                'bulan_tahun' => $this->getMonthName($lastMonth) . ' ' . $lastYear,
                'aktual' => null, // Masa depan
                'prediksi' => $predFuture,
                'error_abs' => null,
                'error_sqr' => null,
                'ape' => null
            ];

            $tempData[] = $newRow;
            $resultTable[] = [
                'bulan_tahun' => $newRow['bulan_tahun'],
                'aktual' => '-',
                'prediksi' => $predFuture,
                'error' => '-',
                'error_sqr' => '-',
                'ape' => '-'
            ];
        }

        // Persiapan Data Grafik
        $chartLabels = [];
        $actualData = [];
        $predictedData = [];

        foreach ($resultTable as $row) {
            $chartLabels[] = $row['bulan_tahun'];
            $actualData[] = $row['aktual'] !== '-' ? $row['aktual'] : null;
            $predictedData[] = $row['prediksi'] !== '-' ? $row['prediksi'] : null;
        }

        // Variabel untuk disimpan (JSON)
        $data_peramalan = [
            'metrics' => ['mae' => $mae, 'mse' => $mse, 'mape' => $mape],
            'table' => $resultTable,
            'chart' => ['labels' => $chartLabels, 'actual' => $actualData, 'predicted' => $predictedData]
        ];

        // Variabel untuk modal view (string JSON)
        $data_json = json_encode($data_peramalan);

        $kendaraans = Kendaraan::all();
        $riwayat = PeramalanSma::with('kendaraan')->latest()->get();

        return view('menu.peramalan_sma', compact(
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
            'periode',
            'durasi',
            'data_peramalan',
            'data_json'
        ))->with('showResult', true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'periode' => 'required',
            'durasi_prediksi' => 'required',
            'mae' => 'required',
            'mse' => 'required',
            'mape' => 'required',
            'data_peramalan' => 'required', // JSON string
        ]);

        PeramalanSma::create([
            'id_kendaraan' => $request->id_kendaraan,
            'periode_sma' => $request->periode,
            'durasi_prediksi' => $request->durasi_prediksi,
            'mae' => $request->mae,
            'mse' => $request->mse,
            'mape' => $request->mape,
            'data_peramalan' => json_decode($request->data_peramalan, true)
        ]);

        return redirect()->route('peramalan_sma.index')->with('success', 'Hasil peramalan berhasil disimpan.');
    }

    public function destroy($id)
    {
        PeramalanSma::findOrFail($id)->delete();
        return redirect()->route('peramalan_sma.index')->with('success', 'Riwayat peramalan berhasil dihapus.');
    }

    private function getMonthName($monthNum)
    {
        $dateObj   = \DateTime::createFromFormat('!m', $monthNum);
        return $dateObj->format('F');
    }
}
