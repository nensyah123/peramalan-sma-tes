<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use Illuminate\Http\Request;

class ManagementKendaraan extends Controller
{
    public function index(Request $request)
    {
        $query = Kendaraan::withCount('transaksiPenyewaan'); // FIX: tambah total transaksi

        if ($request->filled('filter_merk')) {
            $query->where('merk', 'like', '%' . $request->filter_merk . '%');
        }
        if ($request->filled('filter_plat')) {
            $query->where('plat', 'like', '%' . $request->filter_plat . '%');
        }
        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        $kendaraans    = $query->get();
        $allKendaraans = Kendaraan::all();

        $totalTersedia  = $allKendaraans->where('status', 'Tersedia')->count();
        $totalDisewa    = $allKendaraans->where('status', 'Disewa')->count();
        $totalRusak     = $allKendaraans->where('status', 'Rusak')->count();
        $totalDijual    = $allKendaraans->where('status', 'Dijual')->count();
        $totalKendaraan = $allKendaraans->count();

        $merkList = Kendaraan::select('merk')->distinct()->orderBy('merk')->pluck('merk');

        return view('menu.management_kendaraan', compact(
            'kendaraans',
            'totalKendaraan',
            'totalTersedia',
            'totalDisewa',
            'totalRusak',
            'totalDijual',
            'merkList'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'merk'   => 'required|string|max:255',
            'plat'   => 'nullable|string|max:20',
            'status' => 'required|in:Tersedia,Disewa,Rusak,Dijual',
        ]);

        Kendaraan::create([
            'merk'   => $request->merk,
            'plat'   => $request->plat,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('management_kendaraan.index')
            ->with('success', 'Kendaraan berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'merk'   => 'required|string|max:255',
            'plat'   => 'nullable|string|max:20',
            'status' => 'required|in:Tersedia,Disewa,Rusak,Dijual',
        ]);

        $kendaraan = Kendaraan::findOrFail($id);
        $kendaraan->update([
            'merk'   => $request->merk,
            'plat'   => $request->plat,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('management_kendaraan.index')
            ->with('success', 'Kendaraan berhasil diperbarui');
    }

    public function destroy($id)
    {
        Kendaraan::findOrFail($id)->delete();

        return redirect()
            ->route('management_kendaraan.index')
            ->with('success', 'Kendaraan berhasil dihapus');
    }
}
