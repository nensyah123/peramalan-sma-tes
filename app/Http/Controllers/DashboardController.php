<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanSma;
use App\Models\PeramalanTes;
use Illuminate\Http\Request;
use App\Models\PemakaianKendaraan;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Data Kartu (Statistik Utama)
        $countKendaraan = Kendaraan::count();
        $totalTransaksi = PemakaianKendaraan::sum('jumlah_transaksi');
        $countSma = PeramalanSma::count();
        $countTes = PeramalanTes::count();

        // 2. Grafik Donat (Transaksi per Kendaraan)
        $vehicles = Kendaraan::all();
        $donutLabels = [];
        $donutData = [];

        foreach ($vehicles as $v) {
            $sum = PemakaianKendaraan::where('id_kendaraan', $v->id)->sum('jumlah_transaksi');

            // Hanya masukkan jika ada transaksi
            if ($sum > 0) {
                $donutLabels[] = $v->merk . ' ' . $v->nama_kendaraan;
                $donutData[] = $sum;
            }
        }

        // 3. Grafik Area (Data Aktual Transaksi)
        // Filter berdasarkan ID kendaraan (default ke kendaraan pertama)
        $selectedVehicleId = $request->query('vehicle_id');

        if ($selectedVehicleId) {
            $selectedVehicle = Kendaraan::find($selectedVehicleId);
        } else {
            $selectedVehicle = $vehicles->first();
        }

        $lineLabels = [];
        $lineData = [];
        $vehicleName = $selectedVehicle ? ($selectedVehicle->merk . ' ' . $selectedVehicle->nama_kendaraan) : 'Data Kosong';

        if ($selectedVehicle) {
            $transaksis = PemakaianKendaraan::where('id_kendaraan', $selectedVehicle->id)
                ->orderBy('tahun', 'asc')
                ->orderBy('bulan', 'asc')
                ->get();

            foreach ($transaksis as $t) {
                $dateObj   = \DateTime::createFromFormat('!m', $t->bulan);
                $monthName = $dateObj->format('M');
                $lineLabels[] = $monthName . ' ' . $t->tahun;
                $lineData[] = $t->jumlah_transaksi;
            }
        }

        return view('menu.dashboard', compact(
            'countKendaraan',
            'totalTransaksi',
            'countSma',
            'countTes',
            'donutLabels',
            'donutData',
            'lineLabels',
            'lineData',
            'vehicleName',
            'vehicles',
            'selectedVehicle'
        ));
    }
}
