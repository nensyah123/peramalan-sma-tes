<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use Illuminate\Database\Seeder;

class KendaraanSeeder extends Seeder
{
    public function run(): void
    {
        $kendaraans = [
            // AVANZA (9 unit)
            ['merk' => 'Avanza',     'plat' => 'L 1165 AAH', 'status' => 'Tersedia'],
            ['merk' => 'Avanza',     'plat' => 'L 1468 ACA', 'status' => 'Tersedia'],
            ['merk' => 'Avanza',     'plat' => 'L 1810 BAZ', 'status' => 'Tersedia'],
            ['merk' => 'Avanza',     'plat' => 'L 1131 KX',  'status' => 'Tersedia'],
            ['merk' => 'Avanza New', 'plat' => 'P 1090 LE',  'status' => 'Tersedia'],
            ['merk' => 'Avanza',     'plat' => 'W 1325 ST',  'status' => 'Tersedia'],
            ['merk' => 'Avanza',     'plat' => 'W 1047 TM',  'status' => 'Tersedia'],
            ['merk' => 'Avanza',     'plat' => 'L 1090 V',   'status' => 'Tersedia'],
            ['merk' => 'Avanza',     'plat' => 'L 1092 VJ',  'status' => 'Tersedia'],

            // ERTIGA (4 unit)
            ['merk' => 'Ertiga',     'plat' => 'W 1344 BH',  'status' => 'Tersedia'],
            ['merk' => 'Ertiga',     'plat' => 'P 1078 GG',  'status' => 'Tersedia'],
            ['merk' => 'Ertiga',     'plat' => 'W 1078 TE',  'status' => 'Tersedia'],
            ['merk' => 'Ertiga',     'plat' => 'W 1346 ZO',  'status' => 'Tersedia'],

            // INNOVA (9 unit)
            ['merk' => 'Innova',     'plat' => 'AG 1219 RK', 'status' => 'Tersedia'],
            ['merk' => 'Innova',     'plat' => 'AG 1036 RK', 'status' => 'Tersedia'],
            ['merk' => 'Innova',     'plat' => 'L 1336 AAW', 'status' => 'Tersedia'],
            ['merk' => 'Innova',     'plat' => 'L 1425 BM',  'status' => 'Tersedia'],
            ['merk' => 'Innova New', 'plat' => 'P 1075 KQ',  'status' => 'Tersedia'],
            ['merk' => 'Innova',     'plat' => 'W 1633 QF',  'status' => 'Tersedia'],
            ['merk' => 'Innova',     'plat' => 'W 1419 QR',  'status' => 'Tersedia'],
            ['merk' => 'Innova',     'plat' => 'L 1276 VF',  'status' => 'Tersedia'],
            ['merk' => 'Innova',     'plat' => 'AB 1857 VK', 'status' => 'Tersedia'],

            // XENIA (2 unit)
            ['merk' => 'Xenia',      'plat' => 'Z 1643 AW',  'status' => 'Tersedia'],
            ['merk' => 'Xenia',      'plat' => 'W 1257 SJ',  'status' => 'Tersedia'],
        ];

        foreach ($kendaraans as $k) {
            Kendaraan::create($k);
        }
    }
}
