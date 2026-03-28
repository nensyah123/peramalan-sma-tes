<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kendaraan extends Model
{
    use HasFactory;

    protected $table = 'kendaraan';

    protected $fillable = [
        'merk',
        'plat',
        'status',
    ];

    public function pemakaian()
    {
        return $this->hasMany(PemakaianKendaraan::class, 'id_kendaraan', 'id');
    }

    public function transaksiPenyewaan()
    {
        return $this->hasMany(TransaksiPenyewaan::class, 'id_kendaraan', 'id');
    }
}
