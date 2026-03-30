@extends('layouts.app')

@section('content')

{{--
    ================================================================
    ALERT: Pesan sukses
    ================================================================
    Muncul otomatis dari session('success') yang dikirim controller.
    Akan menghilang sendiri setelah 3 detik via setTimeout di bawah.
    ================================================================
--}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" id="success-alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<script>
    // Auto-dismiss alert sukses setelah 3 detik
    setTimeout(function () {
        let el = document.getElementById('success-alert');
        if (el) { el.classList.remove('show'); setTimeout(() => el.remove(), 150); }
    }, 3000);
</script>
@endif

{{-- Alert error (misal kendaraan tidak tersedia, sudah dikembalikan, dll) --}}
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif


{{--
    ================================================================
    TAB NAVIGASI — Sewa | Pengembalian
    ================================================================
    Dua tab utama:
      - Tab Sewa        : form tambah + tabel kendaraan sedang disewa
      - Tab Pengembalian: riwayat kendaraan yang sudah dikembalikan
    ================================================================
--}}
<ul class="nav nav-tabs mb-0" id="transaksiTab" role="tablist" style="border-bottom: none;">

    {{-- Tab Sewa --}}
    <li class="nav-item">
        <a class="nav-link active font-weight-bold" id="sewa-tab"
           data-toggle="tab" href="#sewa" role="tab"
           style="border-radius: 10px 10px 0 0; color: #74271f;">
            <i class="fas fa-key mr-1"></i> Sewa
            {{-- Badge jumlah kendaraan yang sedang disewa --}}
            <span class="badge badge-primary ml-1">{{ $transaksisSewa->count() }}</span>
        </a>
    </li>

    {{-- Tab Pengembalian --}}
    <li class="nav-item">
        <a class="nav-link font-weight-bold" id="pengembalian-tab"
           data-toggle="tab" href="#pengembalian" role="tab"
           style="border-radius: 10px 10px 0 0; color: #1a7a4a;">
            <i class="fas fa-undo mr-1"></i> Pengembalian
            {{-- Badge jumlah total yang sudah dikembalikan --}}
            <span class="badge badge-success ml-1">{{ $totalDikembalikan }}</span>
        </a>
    </li>

</ul>


<div class="tab-content" id="transaksiTabContent">

    {{--
        ================================================================
        TAB SEWA
        ================================================================
        Berisi dua bagian:
          1. Form untuk menambah transaksi sewa baru
          2. Tabel kendaraan yang sedang dalam status "Disewa"
        ================================================================
    --}}
    <div class="tab-pane fade show active" id="sewa" role="tabpanel">

        {{-- ── Form Tambah Transaksi ────────────────────────────────── --}}
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 0 14px 14px 14px;">
            <div class="card-body p-3">

                <h6 class="font-weight-bold mb-3" style="font-size: 1rem; color: #74271f;">
                    <i class="fas fa-key mr-1"></i> Form Transaksi Penyewaan
                </h6>

                <form action="{{ route('transaksi_penyewaan.store') }}" method="POST">
                    @csrf
                    <div class="form-row">

                        {{--
                            Dropdown Kendaraan untuk FORM TAMBAH.
                            Menggunakan $kendaraans → hanya Tersedia.
                            Berbeda dengan modal Edit yang pakai $semuaKendaraans.
                        --}}
                        <div class="form-group col-md-4 mb-2">
                            <label style="font-size: 0.82rem; color: #555;">Kendaraan</label>
                            <select class="form-control form-control-sm" name="id_kendaraan" required>
                                <option value="">-- Pilih Kendaraan --</option>
                                @foreach($kendaraans as $k)
                                    <option value="{{ $k->id }}">
                                        {{ $k->merk }}{{ $k->plat ? ' — ' . $k->plat : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 0.72rem;">
                                Hanya menampilkan kendaraan <strong>Tersedia</strong>
                            </small>
                        </div>

                        {{-- Input Nama Penyewa --}}
                        <div class="form-group col-md-3 mb-2">
                            <label style="font-size: 0.82rem; color: #555;">Nama Penyewa</label>
                            <input type="text" class="form-control form-control-sm"
                                   name="nama_penyewa" placeholder="Nama penyewa..." required>
                        </div>

                        {{-- Input Tanggal Pinjam (default hari ini) --}}
                        <div class="form-group col-md-3 mb-2">
                            <label style="font-size: 0.82rem; color: #555;">Tanggal Pinjam</label>
                            <input type="date" class="form-control form-control-sm"
                                   name="tgl_pinjam" value="{{ date('Y-m-d') }}" required>
                        </div>

                        {{-- Tombol Simpan --}}
                        <div class="form-group col-md-2 mb-2">
                            <label style="font-size: 0.82rem; color: transparent;">Aksi</label>
                            <button type="submit"
                                    class="btn btn-sm text-white font-weight-bold w-100 d-block"
                                    style="background: linear-gradient(135deg, #74271f, #c0392b);
                                           border: none; border-radius: 8px; height: 31px;">
                                <i class="fas fa-save mr-1"></i> Simpan
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- ── Tabel Kendaraan Sedang Disewa ───────────────────────── --}}
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px; overflow: hidden;">
            <div class="card-body p-3">

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="font-weight-bold mb-0" style="font-size: 1rem; color: #74271f;">
                        Data Kendaraan Sedang Disewa
                        <span class="badge badge-secondary ml-2" style="font-size: 0.75rem;">
                            Total: {{ $transaksisSewa->count() }}
                        </span>
                    </h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="dataTableSewa" width="100%" style="font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #dee2e6;">
                                <th width="4%"   style="color: #74271f; font-size: 0.8rem;">No</th>
                                <th             style="color: #74271f; font-size: 0.8rem;">Kendaraan</th>
                                <th             style="color: #74271f; font-size: 0.8rem;">No. Plat</th>
                                <th             style="color: #74271f; font-size: 0.8rem;">Nama Penyewa</th>
                                <th             style="color: #74271f; font-size: 0.8rem;">Tgl Pinjam</th>
                                <th             style="color: #74271f; font-size: 0.8rem;">Status</th>
                                <th width="10%" style="color: #74271f; font-size: 0.8rem;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksisSewa as $t)
                            <tr>
                                {{-- Nomor urut diisi oleh JavaScript (drawCallback DataTables) --}}
                                <td class="row-num-sewa"></td>
                                <td><strong>{{ $t->kendaraan->merk ?? '-' }}</strong></td>
                                <td>{{ $t->kendaraan->plat ?? '-' }}</td>
                                <td>{{ $t->nama_penyewa }}</td>
                                <td>{{ $t->tgl_pinjam->format('d M Y') }}</td>
                                <td>
                                    <span class="badge badge-primary px-2 py-1">
                                        <i class="fas fa-key mr-1"></i>Disewa
                                    </span>
                                </td>
                                <td>
                                    {{-- Tombol Kembalikan: membuka modal konfirmasi pengembalian --}}
                                    <button class="btn btn-success btn-sm btn-circle"
                                            onclick="kembalikanTransaksi({{ $t->id }})"
                                            title="Kembalikan">
                                        <i class="fas fa-undo-alt"></i>
                                    </button>

                                    {{--
                                        Tombol Edit: memanggil fungsi editTransaksi()
                                        dengan data transaksi saat ini sebagai argumen.
                                        Data ini akan mengisi field-field di modal Edit.
                                    --}}
                                    <button class="btn btn-warning btn-sm btn-circle"
                                            onclick="editTransaksi(
                                                {{ $t->id }},
                                                {{ $t->id_kendaraan }},
                                                '{{ addslashes($t->nama_penyewa) }}',
                                                '{{ $t->tgl_pinjam->format('Y-m-d') }}'
                                            )"
                                            title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Tidak ada data transaksi sewa.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    {{--
        ================================================================
        TAB PENGEMBALIAN — Server-side DataTables
        ================================================================
        Tabel ini KOSONG saat render pertama.
        Data diisi otomatis oleh AJAX dari route getDikembalikanData.
        ================================================================
    --}}
    <div class="tab-pane fade" id="pengembalian" role="tabpanel">
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px; overflow: hidden;">
            <div class="card-body p-3">

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="font-weight-bold mb-0" style="font-size: 1rem; color: #1a7a4a;">
                        Riwayat Pengembalian
                        <span class="badge badge-secondary ml-2" style="font-size: 0.75rem;">
                            Total: {{ $totalDikembalikan }}
                        </span>
                    </h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="dataTableKembali" width="100%" style="font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #dee2e6;">
                                <th width="4%" style="color: #1a7a4a; font-size: 0.8rem;">No</th>
                                <th style="color: #1a7a4a; font-size: 0.8rem;">Kendaraan</th>
                                <th style="color: #1a7a4a; font-size: 0.8rem;">No. Plat</th>
                                <th style="color: #1a7a4a; font-size: 0.8rem;">Nama Penyewa</th>
                                <th style="color: #1a7a4a; font-size: 0.8rem;">Tgl Pinjam</th>
                                <th style="color: #1a7a4a; font-size: 0.8rem;">Tgl Kembali Aktual</th>
                                <th style="color: #1a7a4a; font-size: 0.8rem;">Kondisi</th>
                                <th style="color: #1a7a4a; font-size: 0.8rem;">Status</th>
                                <th width="8%" style="color: #1a7a4a; font-size: 0.8rem;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Diisi otomatis oleh AJAX server-side DataTables --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>


{{--
    ================================================================
    MODAL KEMBALIKAN
    ================================================================
    Muncul saat tombol "Kembalikan" diklik di tabel sewa.
    User mengisi tanggal kembali aktual & kondisi kendaraan.
    Action form diisi dinamis oleh JavaScript (fungsi kembalikanTransaksi).
    ================================================================
--}}
<div class="modal fade" id="kembalikanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-0"
                 style="background: linear-gradient(135deg, #1a7a4a, #27ae60);">
                <h5 class="modal-title text-white font-weight-bold">
                    <i class="fas fa-undo-alt mr-2"></i>Konfirmasi Pengembalian
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="kembalikanForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size: 0.85rem;">
                        <i class="fas fa-info-circle mr-1"></i>
                        Status kendaraan akan otomatis berubah sesuai kondisi.
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size: 0.85rem;">
                            Tanggal Pengembalian Aktual
                        </label>
                        <input type="date" class="form-control"
                               name="tgl_kembali_aktual"
                               value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size: 0.85rem;">
                            Kondisi Kendaraan
                        </label>
                        <select class="form-control" name="kondisi" required>
                            <option value="Baik">Baik → Kendaraan jadi Tersedia</option>
                            <option value="Rusak">Rusak → Kendaraan jadi Rusak</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white font-weight-bold"
                            style="background: linear-gradient(135deg, #1a7a4a, #27ae60); border: none;">
                        <i class="fas fa-check mr-1"></i> Konfirmasi Kembali
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{--
    ================================================================
    MODAL EDIT TRANSAKSI
    ================================================================
    Muncul saat tombol Edit (pensil) diklik di tabel sewa.
    Field diisi otomatis oleh JavaScript (fungsi editTransaksi).

    ⚠️  PERBAIKAN UTAMA ADA DI SINI:
    Dropdown Kendaraan sekarang menggunakan $semuaKendaraans
    (bukan $kendaraans), sehingga kendaraan yang sedang "Disewa"
    tetap muncul sebagai pilihan saat user ingin mengedit transaksi.
    ================================================================
--}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-0"
                 style="background: linear-gradient(135deg, #74271f, #c0392b);">
                <h5 class="modal-title text-white font-weight-bold">
                    <i class="fas fa-pen mr-2"></i>Edit Transaksi
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">

                    {{--
                        ⚠️  FIX: Gunakan $semuaKendaraans bukan $kendaraans
                        ───────────────────────────────────────────────────
                        $kendaraans  → hanya Tersedia (untuk form tambah)
                        $semuaKendaraans → semua status (untuk modal edit)

                        Tanpa ini, kendaraan yang sedang "Disewa" tidak akan
                        muncul di dropdown, sehingga value dari JS tidak cocok
                        dan dropdown terlihat kosong / tidak terseleksi.
                    --}}

<div class="form-group">
                        <label class="font-weight-bold" style="font-size: 0.85rem;">Kendaraan</label>
                        <input type="hidden" id="edit_kendaraan_hidden" name="id_kendaraan" required>
                        <select class="form-control" id="edit_kendaraan" name="id_kendaraan_display" disabled>
                            @foreach($semuaKendaraans as $k)
                                <option value="{{ $k->id }}">
                                    {{ $k->merk }}{{ $k->plat ? ' — ' . $k->plat : '' }}
                                    {{-- Tampilkan status jika bukan Tersedia, sebagai info tambahan --}}
                                    @if($k->status !== 'Tersedia')
                                        ({{ $k->status }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>


                    {{-- Input Nama Penyewa — diisi JS saat modal dibuka --}}
                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size: 0.85rem;">Nama Penyewa</label>
                        <input type="text" class="form-control"
                               id="edit_nama_penyewa" name="nama_penyewa" required>
                    </div>

                    {{-- Input Tanggal Pinjam — diisi JS saat modal dibuka --}}
                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size: 0.85rem;">Tanggal Pinjam</label>
                        <input type="date" class="form-control"
                               id="edit_tgl_pinjam" name="tgl_pinjam" required>
                    </div>

                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white font-weight-bold"
                            style="background: linear-gradient(135deg, #74271f, #c0392b); border: none;">
                        <i class="fas fa-save mr-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{--
    ================================================================
    MODAL KONFIRMASI HAPUS PENGEMBALIAN
    ================================================================
    Form hapus diletakkan DI DALAM modal (bukan inline di tabel)
    agar bisa dipakai ulang untuk semua baris.
    Action form diisi secara dinamis oleh JavaScript
    saat tombol hapus di DataTables diklik.
    ================================================================
--}}
<div class="modal fade" id="hapusKembaliModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-0"
                 style="background: linear-gradient(135deg, #c0392b, #e74c3c);">
                <h5 class="modal-title text-white font-weight-bold" style="font-size: 0.95rem;">
                    <i class="fas fa-trash mr-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center py-3">
                <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2 d-block"></i>
                <p class="mb-1" style="font-size: 0.88rem; font-weight: 600;">
                    Yakin ingin menghapus data ini?
                </p>
                <p class="text-muted mb-0" style="font-size: 0.78rem;">
                    Data yang dihapus tidak dapat dikembalikan.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Batal
                </button>
                {{-- Action diisi JS, method DELETE via spoofing _method --}}
                <form id="formHapusKembali" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm font-weight-bold">
                        <i class="fas fa-trash mr-1"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


@push('scripts')

<style>
    /* ── DataTables: Input Pencarian ─────────────────────────────── */
    #dataTableSewa_wrapper .dataTables_filter input,
    #dataTableKembali_wrapper .dataTables_filter input {
        border: 1px solid #dee2e6; border-radius: 6px;
        padding: 4px 10px; font-size: 0.82rem;
        outline: none; transition: border-color 0.2s;
    }
    #dataTableSewa_wrapper .dataTables_filter input:focus {
        border-color: #74271f;
        box-shadow: 0 0 0 2px rgba(116, 39, 31, 0.1);
    }
    #dataTableKembali_wrapper .dataTables_filter input:focus {
        border-color: #1a7a4a;
        box-shadow: 0 0 0 2px rgba(26, 122, 74, 0.1);
    }

    /* ── DataTables: Label & Select ──────────────────────────────── */
    #dataTableSewa_wrapper .dataTables_filter label,
    #dataTableKembali_wrapper .dataTables_filter label,
    #dataTableSewa_wrapper .dataTables_length label,
    #dataTableKembali_wrapper .dataTables_length label {
        font-size: 0.82rem; color: #555; font-weight: 600;
    }
    #dataTableSewa_wrapper .dataTables_length select,
    #dataTableKembali_wrapper .dataTables_length select {
        border: 1px solid #dee2e6; border-radius: 6px;
        padding: 3px 8px; font-size: 0.82rem;
    }

    /* ── DataTables: Info ────────────────────────────────────────── */
    #dataTableSewa_wrapper .dataTables_info,
    #dataTableKembali_wrapper .dataTables_info {
        font-size: 0.80rem; color: #888; padding-top: 8px;
    }
    #dataTableSewa_wrapper,
    #dataTableKembali_wrapper { font-size: 0.85rem; margin-top: 0 !important; }

    /* ── DataTables: Paginasi ────────────────────────────────────── */
    #dataTableSewa_wrapper .dataTables_paginate .paginate_button,
    #dataTableKembali_wrapper .dataTables_paginate .paginate_button {
        font-size: 0.80rem !important; border-radius: 6px !important;
        padding: 3px 9px !important; margin: 0 2px !important;
        border: 1px solid #dee2e6 !important; color: #555 !important;
    }
    #dataTableSewa_wrapper .dataTables_paginate .paginate_button:hover,
    #dataTableSewa_wrapper .dataTables_paginate .paginate_button.current {
        background: #74271f !important; border-color: #74271f !important; color: #fff !important;
    }
    #dataTableKembali_wrapper .dataTables_paginate .paginate_button:hover,
    #dataTableKembali_wrapper .dataTables_paginate .paginate_button.current {
        background: #1a7a4a !important; border-color: #1a7a4a !important; color: #fff !important;
    }

    /* ── DataTables: Processing indicator ───────────────────────── */
    #dataTableKembali_wrapper .dataTables_processing {
        background: rgba(255,255,255,0.9);
        border: 1px solid #dee2e6; border-radius: 8px;
        padding: 10px 20px; font-size: 0.85rem;
        color: #1a7a4a; font-weight: 600;
    }

    /* ── Mencegah teks tanggal & plat terpotong ──────────────────── */
    #dataTableKembali td:nth-child(3),
    #dataTableSewa    td:nth-child(3),
    #dataTableKembali td:nth-child(5),
    #dataTableKembali td:nth-child(6),
    #dataTableSewa    td:nth-child(5) { white-space: nowrap; }
</style>

<script>
$(document).ready(function () {

    /*
     * ================================================================
     * DATATABLES — Tabel Sewa (client-side)
     * ================================================================
     * Data sudah di-render oleh Blade (server render biasa),
     * DataTables hanya menambahkan fitur sort, search, pagination.
     * ================================================================
     */
    if (!$.fn.DataTable.isDataTable('#dataTableSewa')) {
        $('#dataTableSewa').DataTable({
            order      : [[4, 'desc']],   // default sort: kolom Tgl Pinjam desc
            pageLength : 10,
            lengthMenu : [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'Semua']],
            language   : {
                search       : "🔍 Cari:",
                lengthMenu   : "Tampilkan _MENU_ data",
                info         : "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                infoEmpty    : "Tidak ada data",
                infoFiltered : "(difilter dari _MAX_ total data)",
                zeroRecords  : "Data tidak ditemukan",
                paginate     : { first: "«", last: "»", next: "›", previous: "‹" }
            },
            // Kolom No (0), Status (5), Aksi (6) tidak bisa di-sort
            columnDefs  : [{ orderable: false, targets: [0, 5, 6] }],

            // Isi nomor urut setelah DataTables selesai render (drawCallback)
            drawCallback: function () {
                var info  = this.api().page.info();
                var start = info.start; // nomor awal di halaman ini
                $('#dataTableSewa tbody .row-num-sewa').each(function (i) {
                    $(this).text(start + i + 1);
                });
            }
        });
    }

    /*
     * ================================================================
     * DATATABLES — Tabel Pengembalian (server-side AJAX)
     * ================================================================
     * Server-side berarti SETIAP ganti halaman / cari / sort,
     * DataTables kirim AJAX ke route getDikembalikanData.
     * Controller yang handle query, filter, pagination, lalu
     * kembalikan JSON. Ini lebih efisien untuk data yang banyak.
     * ================================================================
     */
    if (!$.fn.DataTable.isDataTable('#dataTableKembali')) {
        $('#dataTableKembali').DataTable({
            processing : true,   // tampilkan indikator loading saat AJAX berjalan
            serverSide : true,   // aktifkan mode server-side
            ajax       : '{{ route("transaksi_penyewaan.dikembalikan_data") }}',
            columns    : [
                { data: 0, orderable: false }, /* No (tidak bisa sort)   */
                { data: 1 },                   /* Kendaraan              */
                { data: 2 },                   /* No. Plat               */
                { data: 3 },                   /* Nama Penyewa           */
                { data: 4 },                   /* Tgl Pinjam             */
                { data: 5 },                   /* Tgl Kembali Aktual     */
                { data: 6, orderable: false }, /* Kondisi (badge HTML)   */
                { data: 7, orderable: false }, /* Status (badge HTML)    */
                { data: 8, orderable: false }, /* Aksi (tombol hapus)    */
            ],
            order      : [[4, 'desc']],   // default sort: Tgl Pinjam desc
            pageLength : 10,
            lengthMenu : [[10, 25, 50, 100], [10, 25, 50, 100]],
            language   : {
                search       : "🔍 Cari:",
                lengthMenu   : "Tampilkan _MENU_ data",
                info         : "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                infoEmpty    : "Tidak ada data",
                infoFiltered : "(difilter dari _MAX_ total data)",
                zeroRecords  : "Data tidak ditemukan",
                processing   : '<i class="fas fa-spinner fa-spin mr-1"></i> Memuat data...',
                paginate     : { first: "«", last: "»", next: "›", previous: "‹" }
            }
        });
    }

    /*
     * Auto-buka tab Pengembalian jika ada session atau query param active_tab.
     * Ini dipakai setelah redirect dari kembalikan() atau destroy()
     * agar user langsung melihat hasilnya di tab yang benar.
     */
    @if(session('active_tab') === 'pengembalian' || request('active_tab') === 'pengembalian')
        $('#pengembalian-tab').tab('show');
    @endif


    /*
     * ================================================================
     * EVENT DELEGATION — Tombol Hapus di Tab Pengembalian
     * ================================================================
     * Mengapa HARUS pakai $(document).on() dan bukan onclick biasa?
     *
     * Tab pengembalian pakai server-side DataTables: setiap ganti
     * halaman / search, baris lama DIHAPUS dari DOM dan baris baru
     * DIBUAT ulang dari respons AJAX. Artinya tombol tidak ada saat
     * halaman pertama dimuat → event listener biasa tidak pernah
     * ter-attach ke elemen tersebut.
     *
     * $(document).on('click', selector, fn) bekerja berbeda:
     * ia mendengarkan klik di level document (selalu ada),
     * lalu mengecek apakah elemen yang diklik cocok selector.
     * Ini bekerja untuk elemen yang dibuat kapan pun, termasuk
     * setelah AJAX DataTables selesai render baris baru.
     *
     * Alur lengkap:
     *   1. User klik tombol .btn-hapus-pengembalian
     *   2. Handler baca data-id dari tombol
     *   3. Buat URL route destroy dengan ID yang benar
     *   4. Isi action #formHapusKembali dengan URL tersebut
     *   5. Tampilkan modal konfirmasi hapus
     *   6. User klik "Hapus" → form submit → controller destroy()
     *   7. Controller redirect + session success → halaman reload
     * ================================================================
     */
    $(document).on('click', '.btn-hapus-pengembalian', function () {
        var id  = $(this).data('id');
        var url = "{{ route('transaksi_penyewaan.destroy', ':id') }}"
                      .replace(':id', id);

        // Set action form modal dengan URL yang mengandung ID yang benar
        $('#formHapusKembali').attr('action', url);

        // Tampilkan modal konfirmasi
        $('#hapusKembaliModal').modal('show');
    });

    // Bersihkan action form saat modal ditutup (cancel / klik X)
    // Ini mencegah action tersisa dari klik sebelumnya
    $('#hapusKembaliModal').on('hidden.bs.modal', function () {
        $('#formHapusKembali').attr('action', '');
    });

});


/*
 * ================================================================
 * FUNGSI: Buka modal Kembalikan
 * ================================================================
 * Dipanggil dari onclick di tombol kembalikan di tabel sewa.
 * Mengisi action form dengan URL yang mengandung ID transaksi.
 * ================================================================
 */
function kembalikanTransaksi(id) {
    // Ganti ':id' pada template URL dengan ID transaksi yang diklik
    var url = "{{ route('transaksi_penyewaan.kembalikan', ':id') }}";
    url = url.replace(':id', id);
    document.getElementById('kembalikanForm').action = url;
    $('#kembalikanModal').modal('show');
}


/*
 * ================================================================
 * FUNGSI: Buka modal Edit Transaksi
 * ================================================================
 * Dipanggil dari onclick di tombol edit (pensil) di tabel sewa.
 * Menerima data transaksi lalu mengisi field-field di modal.
 *
 * Parameter:
 *   id           → ID transaksi (untuk URL action form)
 *   idKendaraan  → ID kendaraan yang dipilih (untuk set value dropdown)
 *   namaPenyewa  → Nama penyewa saat ini
 *   tglPinjam    → Tanggal pinjam format Y-m-d (untuk input date)
 *
 * Catatan: dropdown #edit_kendaraan sekarang berisi $semuaKendaraans,
 * sehingga setting .value = idKendaraan pasti menemukan opsi yang cocok,
 * termasuk kendaraan yang sedang berstatus "Disewa".
 * ================================================================
 */
function editTransaksi(id, idKendaraan, namaPenyewa, tglPinjam) {
    // Set URL action form dengan ID transaksi yang akan diedit
    var url = "{{ route('transaksi_penyewaan.update', ':id') }}";
    url = url.replace(':id', id);
    document.getElementById('editForm').action = url;

    // Isi field-field modal dengan data transaksi saat ini
    document.getElementById('edit_kendaraan_hidden').value = idKendaraan;   // set hidden value untuk submit
    document.getElementById('edit_kendaraan').value = idKendaraan;   // pilih kendaraan untuk display
    document.getElementById('edit_nama_penyewa').value = namaPenyewa;   // isi nama
    document.getElementById('edit_tgl_pinjam').value   = tglPinjam;     // isi tanggal

    // Tampilkan modal edit
    $('#editModal').modal('show');
}
</script>

@endpush

@endsection
