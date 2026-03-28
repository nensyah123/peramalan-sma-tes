<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peramalan_tes', function (Blueprint $table) {
            $table->id();

            $table->string('merk'); // Avanza, Ertiga, Innova, Xenia

            $table->double('alfa', 8, 4);
            $table->double('beta', 8, 4);
            $table->double('gamma', 8, 4);

            $table->integer('durasi_prediksi');

            $table->double('mad', 15, 8)->nullable()->default(0);
            $table->double('mse', 15, 8)->nullable()->default(0);
            $table->double('mape', 15, 8)->nullable()->default(0);

            $table->json('data_peramalan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peramalan_tes');
    }
};
