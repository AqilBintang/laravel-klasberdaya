<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backup snapshot hasil assessment lengkap (untuk export/arsip)
        Schema::create('assessment_backups', function (Blueprint $table) {
            $table->id();
            $table->string('assessment_title', 255);
            $table->string('periode', 20);
            $table->integer('version_number')->default(1);
            $table->integer('tahun')->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->integer('total_score')->default(0);
            $table->integer('max_possible_total')->default(0);
            $table->string('status_akhir', 20)->nullable();  // Hasil klasifikasi akhir
            $table->json('snapshot');                         // Seluruh data snapshot
            $table->timestamps();

            $table->unique(
                ['assessment_title', 'periode', 'kecamatan', 'version_number'],
                'assessment_backups_unique'
            );
        });

        // Key-value store untuk konfigurasi dan pengaturan landing page
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->json('value');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Audit log untuk semua aksi penting
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('action', [
                'USER_LOGIN',
                'USER_CREATED',
                'USER_UPDATED',
                'USER_DEACTIVATED',
                'ASSESSMENT_SUBMITTED',
                'ASSESSMENT_VALIDATED',
                'ROLE_CHANGED',
            ]);
            $table->unsignedBigInteger('user_id')->nullable(); // User yang melakukan aksi (null = system)
            $table->string('target_id', 100)->nullable();      // ID objek yang diaffect
            $table->string('target_type', 50)->nullable();     // Tipe objek: user, assessment, dst
            $table->json('details')->nullable();               // Detail tambahan
            $table->json('metadata')->nullable();              // Metadata request (IP, user agent)
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index('user_id');
            $table->index('created_at');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('assessment_backups');
    }
};
