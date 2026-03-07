@extends('layouts.app')

@section('content')

{{-- Alert Success --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert">
        <span>&times;</span>
    </button>
</div>

<script>
setTimeout(function() {
    let alert = document.getElementById('success-alert');
    if(alert){
        alert.classList.remove('show');
        setTimeout(() => alert.remove(),150);
    }
},3000);
</script>
@endif


<div class="row mb-4">

{{-- ================= FORM TAMBAH ================= --}}
<div class="col-xl-8 col-lg-7">
<div class="card shadow mb-4 h-100">

<div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">Tambah Kendaraan</h6>
</div>

<div class="card-body">

<form action="{{ route('management_kendaraan.store') }}" method="POST">
@csrf

<div class="form-group">
    <label>Nama Kendaraan</label>
    <input type="text"
           class="form-control"
           name="nama_kendaraan"
           placeholder="Masukkan nama kendaraan..."
           required>
</div>

<div class="form-group">
    <label>Jumlah Unit</label>
    <input type="number"
           class="form-control"
           name="unit"
           min="0"
           value="0"
           required>
</div>

<button type="submit" class="btn btn-primary">
    Simpan
</button>

</form>

</div>
</div>
</div>


{{-- ================= CARD TOTAL ================= --}}
<div class="col-xl-4 col-lg-5">
<div class="card shadow h-100 py-2">

<div class="card-body">
<div class="row no-gutters align-items-center h-100">

<div class="col mr-2">
    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
        Total Kendaraan
    </div>

    <div class="h5 mb-0 font-weight-bold text-gray-800">
        {{ $totalKendaraan }}
    </div>
</div>

<div class="col-auto">
    <i class="fas fa-car fa-2x text-gray-300"></i>
</div>

</div>
</div>
</div>
</div>

</div>


{{-- ================= TABEL DATA ================= --}}
<div class="card shadow mb-4">

<div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">Data Kendaraan</h6>
</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered" id="dataTable" width="100%">

<thead>
<tr>
    <th width="5%">No</th>
    <th>Nama Kendaraan</th>
    <th>Unit</th>
    <th>Tanggal Dibuat</th>
    <th width="15%">Aksi</th>
</tr>
</thead>

<tbody>

@foreach($kendaraans as $i => $k)

<tr>

<td>{{ $i+1 }}</td>

<td>{{ $k->nama_kendaraan }}</td>

<td>{{ $k->unit }}</td>

<td>{{ $k->created_at->format('d M Y') }}</td>

<td>

{{-- EDIT --}}
<button class="btn btn-warning btn-sm btn-circle"
onclick="editKendaraan(
    {{ $k->id }},
    '{{ $k->nama_kendaraan }}',
    {{ $k->unit }}
)">
<i class="fas fa-pen"></i>
</button>


{{-- DELETE --}}
<form id="delete-form-{{ $k->id }}"
      action="{{ route('management_kendaraan.destroy',$k->id) }}"
      method="POST"
      class="d-inline">

@csrf
@method('DELETE')

<button type="button"
        class="btn btn-danger btn-sm btn-circle"
        onclick="confirmDelete({{ $k->id }})">

<i class="fas fa-trash"></i>
</button>

</form>

</td>

</tr>

@endforeach

</tbody>

</table>

</div>
</div>
</div>



{{-- ================= MODAL EDIT ================= --}}
<div class="modal fade" id="editModal" tabindex="-1">

<div class="modal-dialog">

<div class="modal-content">

<div class="modal-header">

<h5 class="modal-title">Edit Kendaraan</h5>

<button type="button" class="close" data-dismiss="modal">
    <span>&times;</span>
</button>

</div>


<form id="editForm" method="POST">

@csrf
@method('PUT')

<div class="modal-body">

<div class="form-group">
    <label>Nama Kendaraan</label>
    <input type="text"
           class="form-control"
           id="edit_nama"
           name="nama_kendaraan"
           required>
</div>

<div class="form-group">
    <label>Jumlah Unit</label>
    <input type="number"
           class="form-control"
           id="edit_unit"
           name="unit"
           min="0"
           required>
</div>

</div>


<div class="modal-footer">

<button type="button"
        class="btn btn-secondary"
        data-dismiss="modal">

Batal
</button>

<button type="submit" class="btn btn-primary">
    Update
</button>

</div>

</form>

</div>
</div>
</div>



{{-- ================= SCRIPT ================= --}}
@push('scripts')

<script>

$(document).ready(function() {
    $('#dataTable').DataTable();
});


function confirmDelete(id){

Swal.fire({
    title: 'Apakah Anda yakin?',
    text: "Data tidak bisa dikembalikan!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal'
})
.then((result)=>{

    if(result.isConfirmed){
        document.getElementById('delete-form-'+id).submit();
    }

});

}


function editKendaraan(id,nama,unit){

let url = "{{ route('management_kendaraan.update',':id') }}";
url = url.replace(':id',id);

document.getElementById('editForm').action = url;

document.getElementById('edit_nama').value = nama;
document.getElementById('edit_unit').value = unit;

$('#editModal').modal('show');

}

</script>

@endpush

@endsection
