<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 13)->unique();       // Kode wilayah: 33, 33.01, 33.01.01
            $table->string('nama', 100);                // Nama wilayah
            $table->integer('level');                   // 1: Provinsi, 2: Kab/Kota, 3: Kecamatan, 4: Desa
            $table->unsignedBigInteger('parent_id')->nullable(); // Null untuk provinsi
            $table->double('lat')->nullable();           // Latitude
            $table->double('lng')->nullable();           // Longitude
            $table->double('luas')->nullable();          // Luas area (km2)
            $table->double('penduduk')->nullable();      // Jumlah penduduk
            $table->longText('path')->nullable();        // GeoJSON / polygon boundaries
            $table->integer('status')->nullable();       // Status kode wilayah

            $table->foreign('parent_id')->references('id')->on('wilayah')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
