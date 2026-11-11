<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Tambah KABUPATEN ke ENUM role di tabel users ───────────
        // MySQL tidak bisa langsung ALTER ENUM — harus lewat MODIFY COLUMN
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('SUPER_ADMIN','ADMIN','VALIDATOR','USER','KABUPATEN') NOT NULL DEFAULT 'USER'");

        // ── 2. Buat tabel kabupaten_approvals ──────────────────────────
        // Satu baris per (kecamatan_user_id, assessment_id, periode).
        // Kabupaten memberi keputusan: APPROVED atau NEEDS_REVISION.
        // Setelah APPROVED, assessment bisa dilihat validator tim teknis.
        Schema::create('kabupaten_approvals', function (Blueprint $table) {
            $table->id();
            // User kecamatan yang menyubmit assessment
            $table->unsignedBigInteger('kecamatan_user_id');
            // Assessment yang dimaksud
            $table->unsignedBigInteger('assessment_id');
            // Periode assessment (misal "2024")
            $table->string('periode', 20);
            // User kabupaten yang memberi keputusan
            $table->unsignedBigInteger('kabupaten_user_id')->nullable();
            // Keputusan kabupaten
            $table->enum('status', ['PENDING', 'APPROVED', 'NEEDS_REVISION'])->default('PENDING');
            // Catatan opsional dari kabupaten
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Satu keputusan per (kecamatan, assessment, periode)
            $table->unique(['kecamatan_user_id', 'assessment_id', 'periode'], 'ka_unique');

            $table->foreign('kecamatan_user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('assessment_id')->references('id')->on('assessments')->onDelete('restrict');
            $table->foreign('kabupaten_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kabupaten_approvals');

        // Kembalikan ENUM tanpa KABUPATEN
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('SUPER_ADMIN','ADMIN','VALIDATOR','USER') NOT NULL DEFAULT 'USER'");
    }
};
