<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kendaraan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('merk', 100);
            $table->string('plat', 20)->nullable();
            $table->enum('status', ['Tersedia', 'Disewa', 'Rusak', 'Dijual'])->default('Tersedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kendaraan');
    }
};
