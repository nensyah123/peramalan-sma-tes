<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_penyewaan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_kendaraan')
                ->constrained('kendaraan')
                ->onDelete('cascade');
            $table->string('nama_penyewa', 255);
            $table->date('tgl_pinjam');
            $table->date('tgl_kembali')->nullable();
            $table->enum('status', ['Disewa', 'Dikembalikan'])->default('Disewa');
            $table->string('kondisi')->nullable(); // ← TAMBAHAN
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_penyewaan');
    }
};
