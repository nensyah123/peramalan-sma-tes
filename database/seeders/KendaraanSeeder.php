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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Kendaraan::create([
            'nama_kendaraan' => 'Xenia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Kendaraan::create([
            'nama_kendaraan' => 'Ertiga',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Kendaraan::create([
            'nama_kendaraan' => 'Innova',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
