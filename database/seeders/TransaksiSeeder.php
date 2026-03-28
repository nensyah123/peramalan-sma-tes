<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransaksiPenyewaan;
use App\Models\PemakaianKendaraan;
use Carbon\Carbon;

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        // Data bulanan per kendaraan (id_kendaraan => [bulan, tahun, jumlah])
        $data = [
            // AVANZA (id=1)
            [1, 1, 2023, 89],  [1, 2, 2023, 94],  [1, 3, 2023, 112],
            [1, 4, 2023, 180], [1, 5, 2023, 116],  [1, 6, 2023, 200],
            [1, 7, 2023, 145], [1, 8, 2023, 101],  [1, 9, 2023, 115],
            [1, 10, 2023, 115],[1, 11, 2023, 85],  [1, 12, 2023, 250],
            [1, 1, 2024, 105], [1, 2, 2024, 110],  [1, 3, 2024, 112],
            [1, 4, 2024, 195], [1, 5, 2024, 114],  [1, 6, 2024, 193],
            [1, 7, 2024, 135], [1, 8, 2024, 108],  [1, 9, 2024, 119],
            [1, 10, 2024, 113],[1, 11, 2024, 96],  [1, 12, 2024, 255],
            [1, 1, 2025, 108], [1, 2, 2025, 101],  [1, 3, 2025, 250],

            // XENIA (id=2)
            [2, 1, 2023, 55],  [2, 2, 2023, 88],   [2, 3, 2023, 104],
            [2, 4, 2023, 112], [2, 5, 2023, 71],   [2, 6, 2023, 102],
            [2, 7, 2023, 75],  [2, 8, 2023, 89],   [2, 9, 2023, 69],
            [2, 10, 2023, 80], [2, 11, 2023, 50],  [2, 12, 2023, 140],
            [2, 1, 2024, 60],  [2, 2, 2024, 80],   [2, 3, 2024, 95],
            [2, 4, 2024, 120], [2, 5, 2024, 79],   [2, 6, 2024, 110],
            [2, 7, 2024, 85],  [2, 8, 2024, 92],   [2, 9, 2024, 66],
            [2, 10, 2024, 98], [2, 11, 2024, 65],  [2, 12, 2024, 145],
            [2, 1, 2025, 82],  [2, 2, 2025, 75],   [2, 3, 2025, 150],

            // ERTIGA (id=3)
            [3, 1, 2023, 63],  [3, 2, 2023, 85],   [3, 3, 2023, 111],
            [3, 4, 2023, 150], [3, 5, 2023, 100],  [3, 6, 2023, 150],
            [3, 7, 2023, 122], [3, 8, 2023, 115],  [3, 9, 2023, 100],
            [3, 10, 2023, 85], [3, 11, 2023, 70],  [3, 12, 2023, 195],
            [3, 1, 2024, 75],  [3, 2, 2024, 88],   [3, 3, 2024, 109],
            [3, 4, 2024, 140], [3, 5, 2024, 110],  [3, 6, 2024, 165],
            [3, 7, 2024, 120], [3, 8, 2024, 95],   [3, 9, 2024, 104],
            [3, 10, 2024, 91], [3, 11, 2024, 75],  [3, 12, 2024, 200],
            [3, 1, 2025, 74],  [3, 2, 2025, 105],  [3, 3, 2025, 107],

            // INNOVA (id=4)
            [4, 1, 2023, 101], [4, 2, 2023, 180],  [4, 3, 2023, 150],
            [4, 4, 2023, 225], [4, 5, 2023, 150],  [4, 6, 2023, 200],
            [4, 7, 2023, 175], [4, 8, 2023, 180],  [4, 9, 2023, 101],
            [4, 10, 2023, 155],[4, 11, 2023, 98],  [4, 12, 2023, 230],
            [4, 1, 2024, 100], [4, 2, 2024, 180],  [4, 3, 2024, 185],
            [4, 4, 2024, 199], [4, 5, 2024, 170],  [4, 6, 2024, 196],
            [4, 7, 2024, 177], [4, 8, 2024, 188],  [4, 9, 2024, 180],
            [4, 10, 2024, 144],[4, 11, 2024, 100], [4, 12, 2024, 220],
            [4, 1, 2025, 105], [4, 2, 2025, 190],  [4, 3, 2025, 265],
        ];

        foreach ($data as [$idKendaraan, $bulan, $tahun, $jumlah]) {
            // Hitung jumlah hari dalam bulan tersebut
            $jumlahHari = Carbon::create($tahun, $bulan, 1)->daysInMonth;

            // Bagi transaksi secara acak ke hari-hari dalam bulan
            $hariList = range(1, $jumlahHari);
            shuffle($hariList);

            // Ambil sejumlah $jumlah hari (boleh duplikat kalau transaksi > hari)
            $transaksiHari = [];
            for ($i = 0; $i < $jumlah; $i++) {
                $transaksiHari[] = $hariList[$i % $jumlahHari];
            }
            sort($transaksiHari);

            // Buat transaksi harian
            foreach ($transaksiHari as $hari) {
                $tglPinjam  = Carbon::create($tahun, $bulan, $hari);
                $tglKembali = $tglPinjam->copy()->addDays(rand(1, 3));

                TransaksiPenyewaan::create([
                    'id_kendaraan' => $idKendaraan,
                    'tgl_pinjam'   => $tglPinjam->toDateString(),
                    'tgl_kembali'  => $tglKembali->toDateString(),
                    'status'       => 'selesai',
                ]);
            }

            // Rekap otomatis ke pemakaian_kendaraan
            PemakaianKendaraan::updateOrCreate(
                [
                    'id_kendaraan' => $idKendaraan,
                    'bulan'        => $bulan,
                    'tahun'        => $tahun,
                ],
                [
                    'jumlah_transaksi' => $jumlah,
                ]
            );
        }
    }
}
