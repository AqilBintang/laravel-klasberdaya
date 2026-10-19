<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel klasifikasi_snapshots
 *
 * Menyimpan hasil akhir klasifikasi kecamatan yang diimport dari file xlsx
 * (rekapitulasi verifikasi tim teknis). Digunakan sebagai sumber data
 * fallback di KlasifikasiBerdayaController jika belum ada data dari
 * alur assessment reguler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klasifikasi_snapshots', function (Blueprint $table) {
            $table->id();

            // Identitas wilayah (string, tidak FK ke wilayah — agar tidak tergantung data wilayah)
            $table->string('kabupaten', 100);
            $table->string('kecamatan', 100);
            $table->unsignedSmallInteger('tahun');

            // Skor + status per kategori
            $table->unsignedTinyInteger('skor_a')->default(0);
            $table->string('status_a', 30)->nullable();   // Rintisan/Berkembang/Maju/Unggul

            $table->unsignedTinyInteger('skor_b')->default(0);
            $table->string('status_b', 30)->nullable();

            $table->unsignedTinyInteger('skor_c')->default(0);
            $table->string('status_c', 30)->nullable();

            $table->unsignedTinyInteger('skor_d')->default(0);
            $table->string('status_d', 30)->nullable();

            $table->unsignedTinyInteger('skor_e')->default(0);
            $table->unsignedTinyInteger('skor_f')->default(0);

            // Hasil akhir
            $table->decimal('skor_final', 6, 2)->default(0);  // weighted score
            $table->string('klasifikasi', 30)->nullable();     // Rintisan/Berkembang/Maju/Unggul

            // Metadata
            $table->string('verifikasi', 60)->nullable();      // mis. "VERIFIKASI TIM TEKNIS"
            $table->string('sumber', 60)->default('import_xlsx');

            $table->timestamps();

            // Satu kecamatan per kabupaten hanya boleh punya satu snapshot per tahun
            // (pakai kabupaten+kecamatan karena ada nama kecamatan yang sama di kabupaten berbeda)
            $table->unique(['kabupaten', 'kecamatan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klasifikasi_snapshots');
    }
};
