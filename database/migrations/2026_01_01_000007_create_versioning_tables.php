<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracking perubahan indikator antar versi assessment
        Schema::create('indicator_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('version_id');
            $table->unsignedBigInteger('indicator_id')->nullable(); // Null jika indikator dihapus
            $table->enum('change_type', ['ADDED', 'MODIFIED', 'REMOVED']);
            $table->json('old_value')->nullable();   // Snapshot nilai lama
            $table->json('new_value')->nullable();   // Snapshot nilai baru
            $table->boolean('requires_resubmit')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('version_id')->references('id')->on('assessment_versions')->onDelete('restrict');
            $table->foreign('indicator_id')->references('id')->on('assessment_indicators')->onDelete('set null');
        });

        // Status pengisian assessment per user
        Schema::create('user_assessment_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assessment_id');
            $table->integer('current_version')->default(1);
            $table->integer('latest_version')->default(1);
            $table->enum('status', [
                'NOT_STARTED',
                'IN_PROGRESS',
                'SUBMITTED',
                'HAS_UPDATE',
                'NEEDS_REVISION',
                'RESUBMITTED',
            ])->default('NOT_STARTED');
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'assessment_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assessment_id')->references('id')->on('assessments')->onDelete('cascade');
        });

        // Backup draft jawaban (untuk migrasi data saat assessment diupdate)
        Schema::create('assessment_draft_backups', function (Blueprint $table) {
            $table->string('id', 36)->primary();    // UUID
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assessment_id');
            $table->integer('version_number');
            $table->json('answers_json');            // Backup jawaban lengkap dalam JSON
            $table->string('reason', 100)->nullable(); // Alasan backup: migration, manual, dsb
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'assessment_id']);
            $table->index('created_at');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assessment_id')->references('id')->on('assessments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_draft_backups');
        Schema::dropIfExists('user_assessment_status');
        Schema::dropIfExists('indicator_changes');
    }
};
