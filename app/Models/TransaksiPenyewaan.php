<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransaksiPenyewaan extends Model
{
    use HasFactory;

    protected $table = 'transaksi_penyewaan';

    protected $fillable = [
        'id_kendaraan',
        'nama_penyewa',
        'tgl_pinjam',
        'tgl_kembali',
        'status',
        'kondisi',
    ];

    protected $casts = [
        'tgl_pinjam'  => 'date',
        'tgl_kembali' => 'date',
    ];

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan', 'id');
    }
}
