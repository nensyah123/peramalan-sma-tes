<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KendaraanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kendaraan::create([
            'nama_kendaraan' => 'Avanza',
            'unit' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Kendaraan::create([
            'nama_kendaraan' => 'Xenia',
            'unit' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Kendaraan::create([
            'nama_kendaraan' => 'Ertiga',
            'unit' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Kendaraan::create([
            'nama_kendaraan' => 'Innova',
            'unit' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
