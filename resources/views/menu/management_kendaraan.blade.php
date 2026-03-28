@extends('layouts.app')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" id="success-alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<script>
setTimeout(function() {
    let a = document.getElementById('success-alert');
    if(a){ a.classList.remove('show'); setTimeout(()=>a.remove(),150); }
}, 3000);
</script>
@endif

<div class="row mb-2">

    {{-- FORM TAMBAH --}}
    <div class="col-xl-8 col-lg-7 mb-2 d-flex">
        <div class="card shadow-sm border-0 w-100" style="border-radius:14px;">
            <div class="card-body p-3">
                <h6 class="font-weight-bold mb-2" style="font-size:0.9rem;color:#74271f;">
                    Tambah Kendaraan
                </h6>
                <form action="{{ route('management_kendaraan.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-6 mb-2">
                            <label style="font-size:0.82rem;color:#555;">Merk Kendaraan</label>
                            <input type="text" class="form-control form-control-sm" name="merk"
                                   placeholder="Contoh: Avanza" required>
                        </div>
                        <div class="form-group col-md-6 mb-2">
                            <label style="font-size:0.82rem;color:#555;">No. Plat</label>
                            <input type="text" class="form-control form-control-sm" name="plat"
                                   placeholder="Contoh: S 1234 AA">
                        </div>
                    </div>
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-10 mb-0">
                            <label style="font-size:0.82rem;color:#555;">Status</label>
                            <select class="form-control form-control-sm" name="status" required>
                                <option value="Tersedia">Tersedia</option>
                                <option value="Disewa">Disewa</option>
                                <option value="Rusak">Rusak</option>
                                <option value="Dijual">Dijual</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2 mb-0">
                            <button type="submit" class="btn btn-sm text-white font-weight-bold w-100"
                                    style="background:linear-gradient(135deg,#74271f,#c0392b);border:none;border-radius:8px;">
                                <i class="fas fa-save mr-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- GRAFIK STATUS --}}
    <div class="col-xl-4 col-lg-5 mb-2 d-flex">
        <div class="card shadow-sm border-0 w-100" style="border-radius:14px;">
            <div class="card-body p-3">
                <h6 class="font-weight-bold mb-1" style="font-size:0.85rem;color:#74271f;">
                    <i class="fas fa-chart-pie mr-1"></i> Status Armada
                </h6>
                <div style="height:160px;">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="row text-center mt-2">
                    <div class="col-6 mb-1">
                        <div class="py-1 rounded" style="background:#1cc88a20;">
                            <div class="font-weight-bold" style="font-size:1.1rem;color:#1cc88a;">{{ $totalTersedia }}</div>
                            <small class="text-muted" style="font-size:0.7rem;">Tersedia</small>
                        </div>
                    </div>
                    <div class="col-6 mb-1">
                        <div class="py-1 rounded" style="background:#4e73df20;">
                            <div class="font-weight-bold" style="font-size:1.1rem;color:#4e73df;">{{ $totalDisewa }}</div>
                            <small class="text-muted" style="font-size:0.7rem;">Disewa</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="py-1 rounded" style="background:#e74a3b20;">
                            <div class="font-weight-bold" style="font-size:1.1rem;color:#e74a3b;">{{ $totalRusak }}</div>
                            <small class="text-muted" style="font-size:0.7rem;">Rusak</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="py-1 rounded" style="background:#85879620;">
                            <div class="font-weight-bold" style="font-size:1.1rem;color:#858796;">{{ $totalDijual }}</div>
                            <small class="text-muted" style="font-size:0.7rem;">Dijual</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- TABEL DATA KENDARAAN --}}
<div class="d-flex align-items-center justify-content-between mb-2">
    <h6 class="font-weight-bold mb-0" style="color:#74271f;">
        Data Kendaraan
        <span class="badge badge-secondary ml-1" style="font-size:0.72rem;font-weight:500;">{{ $totalKendaraan }} unit</span>
    </h6>
</div>
<div class="card shadow-sm border-0 mb-2" style="border-radius:14px;">
    <div class="card-body p-3">

        {{-- Filter Status --}}
        <div class="d-flex align-items-center mb-3 flex-wrap" style="gap:6px;">
            <small class="text-muted font-weight-bold mr-1">Filter Status:</small>
            <button class="btn btn-sm filter-status active" data-status=""
                    style="border-radius:20px;font-size:0.78rem;background:#74271f;color:#fff;border:none;padding:3px 12px;">
                Semua
            </button>
            <button class="btn btn-sm filter-status" data-status="Tersedia"
                    style="border-radius:20px;font-size:0.78rem;background:#f0fdf4;color:#1cc88a;border:1px solid #1cc88a;padding:3px 12px;">
                <i class="fas fa-check-circle mr-1"></i>Tersedia
            </button>
            <button class="btn btn-sm filter-status" data-status="Disewa"
                    style="border-radius:20px;font-size:0.78rem;background:#eff3ff;color:#4e73df;border:1px solid #4e73df;padding:3px 12px;">
                <i class="fas fa-key mr-1"></i>Disewa
            </button>
            <button class="btn btn-sm filter-status" data-status="Rusak"
                    style="border-radius:20px;font-size:0.78rem;background:#fff5f5;color:#e74a3b;border:1px solid #e74a3b;padding:3px 12px;">
                <i class="fas fa-tools mr-1"></i>Rusak
            </button>
            <button class="btn btn-sm filter-status" data-status="Dijual"
                    style="border-radius:20px;font-size:0.78rem;background:#f8f9fa;color:#858796;border:1px solid #858796;padding:3px 12px;">
                <i class="fas fa-tag mr-1"></i>Dijual
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="dataTable" width="100%" style="font-size:0.85rem;">
                <thead>
                    <tr style="border-bottom:2px solid #dee2e6;">
                        {{-- Kolom id tersembunyi untuk sorting --}}
                        <th style="display:none;">id</th>
                        <th width="5%"  style="color:#74271f;font-size:0.8rem;">No</th>
                        <th            style="color:#74271f;font-size:0.8rem;">Merk Kendaraan</th>
                        <th            style="color:#74271f;font-size:0.8rem;">No. Plat</th>
                        <th            style="color:#74271f;font-size:0.8rem;">Total Transaksi</th>
                        <th            style="color:#74271f;font-size:0.8rem;">Status</th>
                        <th style="display:none;">status_filter</th>
                        <th            style="color:#74271f;font-size:0.8rem;">Tanggal Dibuat</th>
                        <th width="12%" style="color:#74271f;font-size:0.8rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $badgeMap = [
                        'Tersedia' => ['color' => 'success',   'icon' => 'check-circle'],
                        'Disewa'   => ['color' => 'primary',   'icon' => 'key'],
                        'Rusak'    => ['color' => 'danger',    'icon' => 'tools'],
                        'Dijual'   => ['color' => 'secondary', 'icon' => 'tag'],
                    ];
                    @endphp
                    @forelse($kendaraans as $k)
                    @php $b = $badgeMap[$k->status] ?? ['color'=>'secondary','icon'=>'circle']; @endphp
                    <tr>
                        {{-- Kolom id tersembunyi --}}
                        <td style="display:none;">{{ $k->id }}</td>
                        <td></td>{{-- nomor diisi drawCallback --}}
                        <td><strong>{{ $k->merk }}</strong></td>
                        <td>{{ $k->plat ?? '-' }}</td>
                        <td>{{ $k->transaksi_penyewaan_count }}</td>
                        <td>
                            <span class="badge badge-{{ $b['color'] }} px-2 py-1">
                                <i class="fas fa-{{ $b['icon'] }} mr-1"></i>{{ $k->status }}
                            </span>
                        </td>
                        <td style="display:none;">{{ $k->status }}</td>
                        <td>{{ $k->created_at->format('d M Y') }}</td>
                        <td>
                            <button class="btn btn-warning btn-sm btn-circle"
                                onclick="editKendaraan({{ $k->id }},'{{ addslashes($k->merk) }}','{{ addslashes($k->plat) }}','{{ $k->status }}')"
                                title="Edit">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form id="delete-form-{{ $k->id }}"
                                  action="{{ route('management_kendaraan.destroy', $k->id) }}"
                                  method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm btn-circle"
                                        onclick="confirmDelete({{ $k->id }})" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-car fa-2x mb-2 d-block"></i>
                            Tidak ada data kendaraan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header text-white border-0"
                 style="background:linear-gradient(135deg,#74271f,#c0392b);">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-pen mr-2"></i>Edit Kendaraan
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" style="font-size:0.85rem;">Merk Kendaraan</label>
                            <input type="text" class="form-control" id="edit_merk" name="merk" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold" style="font-size:0.85rem;">No. Plat</label>
                            <input type="text" class="form-control" id="edit_plat" name="plat">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size:0.85rem;">Status</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="Tersedia">Tersedia</option>
                            <option value="Disewa">Disewa</option>
                            <option value="Rusak">Rusak</option>
                            <option value="Dijual">Dijual</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white font-weight-bold"
                            style="background:linear-gradient(135deg,#74271f,#c0392b);border:none;">
                        <i class="fas fa-save mr-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {

    var table = $('#dataTable').DataTable({
        // FIX: sort by kolom id (index 0, tersembunyi) ascending = urut sesuai database
        order      : [[0, 'asc']],
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
        columnDefs : [
            { visible: false, targets: [0] },               // sembunyikan kolom id
            { orderable: false, searchable: false, targets: [1] }, // No — tidak bisa sort
            { orderable: false, targets: [5, 8] },          // Status badge & Aksi
            { visible: false, targets: [6] }                // sembunyikan status_filter
        ],
        // Nomor urut otomatis per halaman
        drawCallback: function() {
            var api  = this.api();
            var info = api.page.info();
            api.column(1, { page: 'current' }).nodes().each(function(cell, i) {
                cell.innerHTML = info.start + i + 1;
            });
        }
    });

    // Filter status via kolom tersembunyi index 6
    $('.filter-status').on('click', function() {
        $('.filter-status').css({
            'background'  : '',
            'color'       : '',
            'border'      : '',
            'font-weight' : 'normal'
        }).removeClass('active');

        $(this).addClass('active').css({
            'background'  : '#74271f',
            'color'       : '#fff',
            'border'      : 'none',
            'font-weight' : 'bold'
        });

        var status = $(this).data('status');
        if (status === '') {
            table.column(6).search('').draw();
        } else {
            table.column(6).search('^' + status + '$', true, false).draw();
        }
    });

});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Tersedia', 'Disewa', 'Rusak', 'Dijual'],
        datasets: [{
            data: [{{ $totalTersedia }}, {{ $totalDisewa }}, {{ $totalRusak }}, {{ $totalDijual }}],
            backgroundColor: ['#1cc88a','#4e73df','#e74a3b','#858796'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        maintainAspectRatio: false,
        cutoutPercentage: 65,
        legend: { display: true, position: 'bottom', labels: { fontSize: 10, boxWidth: 12 } },
        tooltips: {
            backgroundColor: '#fff', bodyFontColor: '#858796',
            titleFontColor: '#6e707e', borderColor: '#dddfeb', borderWidth: 1,
        }
    }
});

function confirmDelete(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data tidak bisa dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#74271f',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('delete-form-' + id).submit();
    });
}

function editKendaraan(id, merk, plat, status) {
    let url = "{{ route('management_kendaraan.update', ':id') }}";
    url = url.replace(':id', id);
    document.getElementById('editForm').action   = url;
    document.getElementById('edit_merk').value   = merk !== 'null' ? merk : '';
    document.getElementById('edit_plat').value   = plat !== 'null' ? plat : '';
    document.getElementById('edit_status').value = status;
    $('#editModal').modal('show');
}
</script>
@endpush
