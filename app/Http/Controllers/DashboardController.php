<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\PeramalanTes;
use App\Models\TransaksiPenyewaan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Statistik Utama
        $countKendaraan   = Kendaraan::count();
        $totalTransaksi   = TransaksiPenyewaan::count();
        $countTes         = PeramalanTes::count();

        // 2. Status real-time
        $countTersedia    = Kendaraan::where('status', 'Tersedia')->count();
        $countDisewa      = Kendaraan::where('status', 'Disewa')->count();
        $countPenyewaUnik = TransaksiPenyewaan::distinct('nama_penyewa')->count('nama_penyewa');

        // 3. Grafik Donat - Total transaksi per Merk
        $merks       = Kendaraan::select('merk')->distinct()->pluck('merk');
        $donutLabels = [];
        $donutData   = [];

        foreach ($merks as $merk) {
            $ids   = Kendaraan::where('merk', $merk)->pluck('id');
            $total = TransaksiPenyewaan::whereIn('id_kendaraan', $ids)->count();

            if ($total > 0) {
                $donutLabels[] = $merk;
                $donutData[]   = $total;
            }
        }

        // 4. Grafik Area - Transaksi Bulanan per Kendaraan
        $vehicles          = Kendaraan::all();
        $selectedVehicleId = $request->query('vehicle_id');
        $selectedVehicle   = $selectedVehicleId
            ? Kendaraan::find($selectedVehicleId)
            : $vehicles->first();

        $lineLabels  = [];
        $lineData    = [];
        $vehicleName = $selectedVehicle
            ? $selectedVehicle->merk . ($selectedVehicle->plat ? ' — ' . $selectedVehicle->plat : '')
            : 'Data Kosong';

        if ($selectedVehicle) {
            $transaksis = TransaksiPenyewaan::selectRaw(
                    'MONTH(tgl_pinjam) as bulan, YEAR(tgl_pinjam) as tahun, COUNT(*) as total'
                )
                ->where('id_kendaraan', $selectedVehicle->id)
                ->groupBy('tahun', 'bulan')
                ->orderBy('tahun', 'asc')
                ->orderBy('bulan', 'asc')
                ->get();

            foreach ($transaksis as $t) {
                $monthName    = \DateTime::createFromFormat('!m', $t->bulan)->format('M');
                $lineLabels[] = $monthName . ' ' . $t->tahun;
                $lineData[]   = $t->total;
            }
        }

        return view('menu.dashboard', compact(
            'countKendaraan',
            'totalTransaksi',
            'countTes',
            'countTersedia',
            'countDisewa',
            'countPenyewaUnik',
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
