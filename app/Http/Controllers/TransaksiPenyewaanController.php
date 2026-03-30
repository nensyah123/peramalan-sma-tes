<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\TransaksiPenyewaan;
use Illuminate\Http\Request;

class TransaksiPenyewaanController extends Controller
{
    /**
     * ================================================================
     * INDEX — Halaman utama Transaksi Penyewaan
     * ================================================================
     * Mengirim dua variabel kendaraan ke view:
     *   1. $kendaraans       → hanya kendaraan berstatus "Tersedia"
     *                          (dipakai di form TAMBAH transaksi baru)
     *   2. $semuaKendaraans  → SEMUA kendaraan tanpa filter status
     *                          (dipakai di modal EDIT transaksi,
     *                           agar kendaraan yg sedang "Disewa"
     *                           tetap muncul sebagai pilihan)
     *
     * ⚠️  INI PERBAIKAN UTAMA:
     *     Sebelumnya hanya ada $kendaraans (filter Tersedia saja),
     *     sehingga kendaraan yang sedang disewa tidak tampil di
     *     dropdown modal Edit. Sekarang ditambah $semuaKendaraans.
     * ================================================================
     */
    public function index(Request $request)
    {
        // ── Untuk form TAMBAH: hanya kendaraan yang siap disewa ──────
        $kendaraans = Kendaraan::where('status', 'Tersedia')->get();

        // ── Untuk modal EDIT: semua kendaraan (Tersedia + Disewa + Rusak) ──
        // Ini yang memperbaiki bug: kendaraan berstatus "Disewa" tetap
        // muncul di dropdown saat user mengedit transaksi yang sudah ada.
        $semuaKendaraans = Kendaraan::all();

        // ── Query data transaksi yang berstatus "Disewa" ─────────────
        $querySewa = TransaksiPenyewaan::with('kendaraan')->where('status', 'Disewa');

        // Filter opsional: cari berdasarkan nama penyewa
        if ($request->filled('filter_nama_sewa')) {
            $querySewa->where('nama_penyewa', 'like', '%' . $request->filter_nama_sewa . '%');
        }

        // Filter opsional: cari berdasarkan tanggal pinjam
        if ($request->filled('filter_tgl_pinjam_sewa')) {
            $querySewa->whereDate('tgl_pinjam', $request->filter_tgl_pinjam_sewa);
        }

        // Ambil data sewa, urutkan terbaru di atas
        $transaksisSewa = $querySewa->orderBy('tgl_pinjam', 'desc')->get();

        // Hanya ambil jumlah untuk badge tab Pengembalian.
        // Data detail tab pengembalian diload via AJAX server-side DataTables.
        $totalDikembalikan = TransaksiPenyewaan::where('status', 'Dikembalikan')->count();

        return view('menu.transaksi_penyewaan', compact(
            'kendaraans',         // form tambah: hanya Tersedia
            'semuaKendaraans',    // modal edit: semua kendaraan ← BARU
            'transaksisSewa',
            'totalDikembalikan'
        ));
    }

    /**
     * ================================================================
     * GET DIKEMBALIKAN DATA — Server-side DataTables untuk Tab Pengembalian
     * ================================================================
     * Dipanggil via AJAX oleh DataTables (bukan akses langsung browser).
     * Mengembalikan JSON dengan struktur yang diharapkan DataTables.
     *
     * Kolom yang dikembalikan (index array):
     *   0 = No (nomor urut)      5 = Tgl Kembali
     *   1 = Kendaraan            6 = Kondisi  (badge HTML)
     *   2 = No. Plat             7 = Status   (badge HTML)
     *   3 = Nama Penyewa         8 = Aksi     (tombol hapus)
     *   4 = Tgl Pinjam
     * ================================================================
     */
    public function getDikembalikanData(Request $request)
    {
        // Query dasar: hanya transaksi berstatus Dikembalikan
        $query = TransaksiPenyewaan::with('kendaraan')
                    ->where('status', 'Dikembalikan');

        // ── Filter pencarian global dari input DataTables ─────────────
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

        // Total sebelum filter (untuk recordsTotal di response DataTables)
        $total    = TransaksiPenyewaan::where('status', 'Dikembalikan')->count();

        // Total setelah filter (untuk recordsFiltered di response DataTables)
        $filtered = $query->count();

        // ── Sorting berdasarkan kolom yang diklik user di header tabel ─
        $orderCol = $request->input('order.0.column', 4); // default kolom 4 = Tgl Pinjam
        $orderDir = $request->input('order.0.dir', 'desc');

        // Mapping index kolom DataTables → nama kolom database
        $colMap = [
            1 => 'id_kendaraan',
            2 => 'id_kendaraan',
            3 => 'nama_penyewa',
            4 => 'tgl_pinjam',
            5 => 'tgl_kembali',
        ];
        $orderBy = $colMap[$orderCol] ?? 'tgl_pinjam';
        $query->orderBy($orderBy, $orderDir);

        // ── Pagination: ambil data sesuai halaman yang diminta ─────────
        $data = $query->skip((int) $request->start)
                      ->take((int) $request->length)
                      ->get();

        // ── Mapping data ke format array untuk DataTables ──────────────
        $rows = $data->map(function ($t, $i) use ($request) {
            $no   = (int) $request->start + $i + 1;
            $merk = $t->kendaraan->merk ?? '-';
            $plat = $t->kendaraan->plat ?? '-';

            // Badge kondisi kendaraan saat dikembalikan
            $kondisi = $t->kondisi === 'Rusak'
                ? '<span class="badge badge-danger px-2 py-1">
                       <i class="fas fa-tools mr-1"></i>Rusak
                   </span>'
                : '<span class="badge badge-info px-2 py-1">
                       <i class="fas fa-check-circle mr-1"></i>Baik
                   </span>';

            // Badge status selalu "Dikembalikan" di tab ini
            $status = '<span class="badge badge-success px-2 py-1">
                           <i class="fas fa-check mr-1"></i>Dikembalikan
                       </span>';

            // Tombol hapus — pakai class + data-id untuk event delegation di JS.
            // TIDAK pakai onclick agar tidak konflik dengan scope JS global.
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

        // Kembalikan response JSON sesuai format yang diharapkan DataTables
        return response()->json([
            'draw'            => (int) $request->draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->values(),
        ]);
    }

    /**
     * ================================================================
     * STORE — Simpan transaksi sewa baru
     * ================================================================
     * Validasi input → cek status kendaraan → buat transaksi →
     * ubah status kendaraan menjadi "Disewa".
     * ================================================================
     */
    public function store(Request $request)
    {
        // Validasi input dari form tambah
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'nama_penyewa' => 'required|string|max:255',
            'tgl_pinjam'   => 'required|date',
        ]);

        // Double-check: pastikan kendaraan masih Tersedia
        // (bisa saja sudah disewa orang lain di tab yang berbeda)
        $kendaraan = Kendaraan::findOrFail($request->id_kendaraan);
        if ($kendaraan->status !== 'Tersedia') {
            return back()->with('error', 'Kendaraan tidak tersedia untuk disewa.');
        }

        // Buat record transaksi baru
        TransaksiPenyewaan::create([
            'id_kendaraan' => $request->id_kendaraan,
            'nama_penyewa' => $request->nama_penyewa,
            'tgl_pinjam'   => $request->tgl_pinjam,
            'tgl_kembali'  => null,     // belum dikembalikan
            'status'       => 'Disewa',
            'kondisi'      => null,     // kondisi diisi saat pengembalian
        ]);

        // Tandai kendaraan sebagai Disewa
        $kendaraan->update(['status' => 'Disewa']);

        return redirect()->route('transaksi_penyewaan.index')
            ->with('success', 'Transaksi sewa berhasil ditambahkan.');
    }

    /**
     * ================================================================
     * KEMBALIKAN — Proses pengembalian kendaraan
     * ================================================================
     * Mengubah status transaksi → Dikembalikan,
     * mengisi tgl_kembali & kondisi,
     * lalu mengubah status kendaraan sesuai kondisinya:
     *   - Baik  → Tersedia (bisa disewa lagi)
     *   - Rusak → Rusak    (perlu perbaikan dulu)
     * ================================================================
     */
    public function kembalikan(Request $request, $id)
    {
        $request->validate([
            'tgl_kembali_aktual' => 'required|date',
            'kondisi'            => 'required|in:Baik,Rusak',
        ]);

        $transaksi = TransaksiPenyewaan::findOrFail($id);

        // Cegah pengembalian ganda (idempoten)
        if ($transaksi->status === 'Dikembalikan') {
            return back()->with('error', 'Kendaraan sudah dikembalikan sebelumnya.');
        }

        // Tandai transaksi sebagai selesai
        $transaksi->update([
            'status'      => 'Dikembalikan',
            'tgl_kembali' => $request->tgl_kembali_aktual,
            'kondisi'     => $request->kondisi,
        ]);

        // Ubah status kendaraan berdasarkan kondisi saat kembali
        $statusBaru = $request->kondisi === 'Rusak' ? 'Rusak' : 'Tersedia';
        Kendaraan::findOrFail($transaksi->id_kendaraan)->update(['status' => $statusBaru]);

        return redirect()->route('transaksi_penyewaan.index')
            ->with('success', 'Kendaraan berhasil dikembalikan.')
            ->with('active_tab', 'pengembalian'); // otomatis buka tab Pengembalian
    }

    /**
     * ================================================================
     * UPDATE — Edit data transaksi yang sedang berjalan
     * ================================================================
     * Hanya mengubah: id_kendaraan, nama_penyewa, tgl_pinjam.
     * Status & kondisi tidak diubah di sini (ada method kembalikan).
     * ================================================================
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:kendaraan,id',
            'nama_penyewa' => 'required|string|max:255',
            'tgl_pinjam'   => 'required|date',
        ]);

        $transaksi = TransaksiPenyewaan::findOrFail($id);

        // Hanya update field yang diizinkan dari form edit
        $transaksi->update($request->only(['id_kendaraan', 'nama_penyewa', 'tgl_pinjam']));

        return redirect()->route('transaksi_penyewaan.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * ================================================================
     * DESTROY — Hapus data transaksi
     * ================================================================
     * Mendukung dua skenario pemanggilan:
     *   1. Request biasa (form POST + _method=DELETE dari modal view)
     *      → redirect dengan session success (halaman reload otomatis)
     *   2. Request AJAX (Accept: application/json)
     *      → return JSON {success, message}
     *
     * Side-effect: Jika transaksi masih "Disewa", status kendaraan
     * dikembalikan ke "Tersedia" secara otomatis.
     * ================================================================
     */
    public function destroy(Request $request, $id)
    {
        $transaksi = TransaksiPenyewaan::findOrFail($id);

        // Jika kendaraan masih berstatus Disewa, bebaskan kembali
        if ($transaksi->status === 'Disewa') {
            Kendaraan::where('id', $transaksi->id_kendaraan)
                     ->update(['status' => 'Tersedia']);
        }

        $transaksi->delete();

        // Jika dipanggil via AJAX (misal dari DataTables tab pengembalian)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.',
            ]);
        }

        // Jika dipanggil via form biasa (modal konfirmasi hapus)
        return redirect()->route('transaksi_penyewaan.index')
            ->with('success', 'Data berhasil dihapus.')
            ->with('active_tab', 'pengembalian'); // otomatis buka tab Pengembalian
    }
}
