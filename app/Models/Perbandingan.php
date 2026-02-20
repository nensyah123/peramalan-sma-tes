<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Perbandingan extends Model
{
    use HasFactory;

    protected $table = 'perbandingan';

    protected $fillable = [
        'id_kendaraan',
        'periode_sma',
        'alpha',
        'beta',
        'gamma',
        'durasi_prediksi',
        'mad_sma',
        'mse_sma',
        'mape_sma',
        'mad_tes',
        'mse_tes',
        'mape_tes',
        'metode_terbaik',
        'data_perbandingan',
    ];
    protected $casts = [
        'data_perbandingan' => 'array',
        'alfa' => 'double',
        'beta' => 'double',
        'gamma' => 'double',
        'mad_sma' => 'double',
        'mse_sma' => 'double',
        'mape_sma' => 'double',
        'mad_tes' => 'double',
        'mse_tes' => 'double',
        'mape_tes' => 'double',
    ];

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan', 'id');
    }

}
