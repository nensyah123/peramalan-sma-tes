<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use Illuminate\Http\Request;
use App\Models\PemakaianKendaraan;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Statistik Utama (3 card)
        $countKendaraan = Kendaraan::count();
        $totalTransaksi = PemakaianKendaraan::sum('jumlah_transaksi');
        $countTes       = PeramalanTes::count();

        // 2. Grafik Donat
        $vehicles    = Kendaraan::all();
        $donutLabels = [];
        $donutData   = [];

        foreach ($vehicles as $v) {
            $sum = PemakaianKendaraan::where('id_kendaraan', $v->id)->sum('jumlah_transaksi');
            if ($sum > 0) {
                $donutLabels[] = trim($v->merk . ' ' . $v->nama_kendaraan);
                $donutData[]   = $sum;
            }
        }

        // 3. Grafik Area
        $selectedVehicleId = $request->query('vehicle_id');
        $selectedVehicle   = $selectedVehicleId
            ? Kendaraan::find($selectedVehicleId)
            : $vehicles->first();

        $lineLabels  = [];
        $lineData    = [];
        $vehicleName = $selectedVehicle
            ? trim($selectedVehicle->merk . ' ' . $selectedVehicle->nama_kendaraan)
            : 'Data Kosong';

        if ($selectedVehicle) {
            $transaksis = PemakaianKendaraan::where('id_kendaraan', $selectedVehicle->id)
                ->orderBy('tahun', 'asc')
                ->orderBy('bulan', 'asc')
                ->get();

            foreach ($transaksis as $t) {
                $dateObj      = \DateTime::createFromFormat('!m', $t->bulan);
                $monthName    = $dateObj->format('M');
                $lineLabels[] = $monthName . ' ' . $t->tahun;
                $lineData[]   = $t->jumlah_transaksi;
            }
        }

        return view('menu.dashboard', compact(
            'countKendaraan',
            'totalTransaksi',
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
