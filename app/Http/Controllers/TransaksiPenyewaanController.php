<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\TransaksiPenyewaan;
use Illuminate\Http\Request;

class TransaksiPenyewaanController extends Controller
{
    public function index(Request $request)
    {
        $kendaraans = Kendaraan::where('status', 'Tersedia')->get();

        $querySewa = TransaksiPenyewaan::with('kendaraan')->where('status', 'Disewa');

        if ($request->filled('filter_nama_sewa')) {
            $querySewa->where('nama_penyewa', 'like', '%' . $request->filter_nama_sewa . '%');
        }
        if ($request->filled('filter_tgl_pinjam_sewa')) {
            $querySewa->whereDate('tgl_pinjam', $request->filter_tgl_pinjam_sewa);
        }

        $transaksisSewa = $querySewa->orderBy('tgl_pinjam', 'desc')->get();

        // Hanya ambil jumlah untuk badge — data diload via AJAX server-side
        $totalDikembalikan = TransaksiPenyewaan::where('status', 'Dikembalikan')->count();

        return view('menu.transaksi_penyewaan', compact(
            'kendaraans',
            'transaksisSewa',
            'totalDikembalikan'
        ));
    }

    /* ================================================================
       SERVER-SIDE DATATABLE — Tab Pengembalian
       ----------------------------------------------------------------
       Kolom yang dikembalikan (index array):
         0 = No (nomor urut)      5 = Tgl Kembali
         1 = Kendaraan            6 = Kondisi  (badge HTML)
         2 = No. Plat             7 = Status   (badge HTML)
         3 = Nama Penyewa         8 = Aksi     (tombol hapus)
         4 = Tgl Pinjam

       Tombol hapus menggunakan class "btn-hapus-pengembalian" dan
       data-id, sehingga dapat ditangkap event delegation di JS.
       TIDAK menggunakan onclick/confirmDelete agar tidak konflik.
       ================================================================ */
    public function getDikembalikanData(Request $request)
    {
        $query = TransaksiPenyewaan::with('kendaraan')
                    ->where('status', 'Dikembalikan');

        // Filter pencarian global
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('nama_penyewa', 'like', "%$search%")
                  ->orWhereHas('kendaraan', function ($qq) use ($search) {
                      $qq->where('merk', 'like', "%$search%")
                         ->orWhere('plat', 'like', "%$search%");
                  })
                  ->orWhere('tgl_pinjam',  'like', "%$search%")
                  ->orWhere('tgl_kembali', 'like', "%$search%");
            });
        }

        $total    = TransaksiPenyewaan::where('status', 'Dikembalikan')->count();
        $filtered = $query->count();

        // Sorting
        $orderCol = $request->input('order.0.column', 4);
        $orderDir = $request->input('order.0.dir', 'desc');
        $colMap   = [
            1 => 'id_kendaraan',
            2 => 'id_kendaraan',
            3 => 'nama_penyewa',
            4 => 'tgl_pinjam',
            5 => 'tgl_kembali',
        ];
        $orderBy = $colMap[$orderCol] ?? 'tgl_pinjam';
        $query->orderBy($orderBy, $orderDir);

        // Pagination
        $data = $query->skip((int) $request->start)
                      ->take((int) $request->length)
                      ->get();

        $rows = $data->map(function ($t, $i) use ($request) {
            $no   = (int) $request->start + $i + 1;
            $merk = $t->kendaraan->merk ?? '-';
            $plat = $t->kendaraan->plat ?? '-';

            // Badge kondisi
            $kondisi = $t->kondisi === 'Rusak'
                ? '<span class="badge badge-danger px-2 py-1">
                       <i class="fas fa-tools mr-1"></i>Rusak
                   </span>'
                : '<span class="badge badge-info px-2 py-1">
                       <i class="fas fa-check-circle mr-1"></i>Baik
                   </span>';

            // Badge status
            $status = '<span class="badge badge-success px-2 py-1">
                           <i class="fas fa-check mr-1"></i>Dikembalikan
                       </span>';

            /*
             * TOMBOL HAPUS
             * -------------
             * Menggunakan class "btn-hapus-pengembalian" dan data-id.
             * TIDAK memakai onclick/confirmDelete karena fungsi tersebut
             * tidak terdefinisi di view dan menyebabkan error JS.
             * Event delegation di view akan menangkap klik tombol ini,
             * mengisi action form modal, lalu menampilkan modal konfirmasi.
             */
            $hapus = '<button type="button"
                              class="btn btn-danger btn-sm btn-circle btn-hapus-pengembalian"
                              data-id="' . $t->id . '"
                              title="Hapus">
                          <i class="fas fa-trash"></i>
                      </button>';

            return [
                $no,
                '<strong>' . e($merk) . '</strong>',
                '<span style="white-space:nowrap;">' . e($plat) . '</span>',
                e($t->nama_penyewa),
                '<span style="white-space:nowrap;">'
                    . ($t->tgl_pinjam  ? $t->tgl_pinjam->format('d M Y')  : '-')
                    . '</span>',
                '<span style="white-space:nowrap;">'
                    . ($t->tgl_kembali ? $t->tgl_kembali->format('d M Y') : '-')
                    . '</span>',
                $kondisi,
                $status,
                $hapus,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->values(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'nama_penyewa' => 'required|string|max:255',
            'tgl_pinjam'   => 'required|date',
        ]);

        $kendaraan = Kendaraan::findOrFail($request->id_kendaraan);
        if ($kendaraan->status !== 'Tersedia') {
            return back()->with('error', 'Kendaraan tidak tersedia untuk disewa.');
        }

        TransaksiPenyewaan::create([
            'id_kendaraan' => $request->id_kendaraan,
            'nama_penyewa' => $request->nama_penyewa,
            'tgl_pinjam'   => $request->tgl_pinjam,
            'tgl_kembali'  => null,
            'status'       => 'Disewa',
            'kondisi'      => null,
        ]);

        $kendaraan->update(['status' => 'Disewa']);

        return redirect()->route('transaksi_penyewaan.index')
            ->with('success', 'Transaksi sewa berhasil ditambahkan.');
    }

    public function kembalikan(Request $request, $id)
    {
        $request->validate([
            'tgl_kembali_aktual' => 'required|date',
            'kondisi'            => 'required|in:Baik,Rusak',
        ]);

        $transaksi = TransaksiPenyewaan::findOrFail($id);

        if ($transaksi->status === 'Dikembalikan') {
            return back()->with('error', 'Kendaraan sudah dikembalikan sebelumnya.');
        }

        $transaksi->update([
            'status'      => 'Dikembalikan',
            'tgl_kembali' => $request->tgl_kembali_aktual,
            'kondisi'     => $request->kondisi,
        ]);

        $statusBaru = $request->kondisi === 'Rusak' ? 'Rusak' : 'Tersedia';
        Kendaraan::findOrFail($transaksi->id_kendaraan)->update(['status' => $statusBaru]);

        return redirect()->route('transaksi_penyewaan.index')
            ->with('success', 'Kendaraan berhasil dikembalikan.')
            ->with('active_tab', 'pengembalian');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'nama_penyewa' => 'required|string|max:255',
            'tgl_pinjam'   => 'required|date',
        ]);

        $transaksi = TransaksiPenyewaan::findOrFail($id);
        $transaksi->update($request->only(['id_kendaraan', 'nama_penyewa', 'tgl_pinjam']));

        return redirect()->route('transaksi_penyewaan.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    /* ================================================================
       DESTROY — Hapus data transaksi
       ----------------------------------------------------------------
       Mendukung dua skenario:
         1. Request biasa (form POST + _method=DELETE dari modal view)
            → redirect dengan session success (halaman reload otomatis)
         2. Request AJAX (Accept: application/json)
            → return JSON {success, message}

       Controller ini sengaja dibuat fleksibel agar bisa dipanggil
       dari keduanya tanpa perlu dua method terpisah.
       ================================================================ */
    public function destroy(Request $request, $id)
    {
        $transaksi = TransaksiPenyewaan::findOrFail($id);

        // Jika masih Disewa, kembalikan status kendaraan ke Tersedia
        if ($transaksi->status === 'Disewa') {
            Kendaraan::where('id', $transaksi->id_kendaraan)
                     ->update(['status' => 'Tersedia']);
        }

        $transaksi->delete();

        // Jika request mengharapkan JSON (AJAX), kembalikan JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.',
            ]);
        }

        // Jika request biasa (form submit dari modal), redirect
        return redirect()->route('transaksi_penyewaan.index')
            ->with('success', 'Data berhasil dihapus.')
            ->with('active_tab', 'pengembalian');
    }
}
