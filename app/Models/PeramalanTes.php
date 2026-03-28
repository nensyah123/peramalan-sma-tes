<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PeramalanTes extends Model
{
    use HasFactory;

    protected $table = 'peramalan_tes';

    protected $fillable = [
        'merk',
        'alfa',
        'beta',
        'gamma',
        'durasi_prediksi',
        'mad',
        'mse',
        'mape',
        'data_peramalan',
    ];

    protected $casts = [
        'data_peramalan' => 'array',
        'alfa'           => 'double',
        'beta'           => 'double',
        'gamma'          => 'double',
        'mad'            => 'double',
        'mse'            => 'double',
        'mape'           => 'double',
    ];
}
