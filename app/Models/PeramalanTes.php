<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PeramalanTes extends Model
{
    use HasFactory;

    protected $table = 'peramalan_tes';

    protected $fillable = [
        'id_kendaraan',
        'alfa',
        'beta',
        'gamma',
        'durasi_prediksi',
        'mad',
        'mse',
        'mape',
        'data_peramalan',
    ];

    /**
     * Casting data JSON agar menjadi Array PHP secara otomatis.
     */
    protected $casts = [
        'data_peramalan' => 'array',
        'alfa' => 'double',
        'beta' => 'double',
        'gamma' => 'double',
        'mad' => 'double',
        'mse' => 'double',
        'mape' => 'double',
    ];

    /**
     * Relasi: Hasil peramalan ini merujuk pada satu kendaraan.
     */
    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class, 'id_kendaraan', 'id');
    }

    public function getPeriodeLabelAttribute()
    {
        $data = $this->data_peramalan;
        // Check if data is array (already casted) or string
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!empty($data) && is_array($data)) {
            // Find the first predicted row (where 'prediksi' is not '-')
            // Or just take the last few rows which are the future predictions
            // Based on 'durasi_prediksi' field

            // Let's assume the last 'durasi_prediksi' items are the forecast
            // We need to parse the month/year from them

            // Actually, we can just look for the range of dates in the 'future' part
            // But structurally, the data_peramalan typically holds the entire table including history + forecast

            // Let's look for the first row where 'aktual' is '-' or null, which indicates prediction
            $firstPred = null;
            $lastPred = null;

            foreach ($data as $row) {
                if (($row['aktual'] === '-' || $row['aktual'] === null) && ($row['prediksi'] !== '-' && $row['prediksi'] !== null)) {
                    if (!$firstPred) $firstPred = $row['bulan_tahun'];
                    $lastPred = $row['bulan_tahun'];
                }
            }

            if ($firstPred && $lastPred) {
                return "$firstPred - $lastPred";
            }
        }

        return '-';
    }
}
