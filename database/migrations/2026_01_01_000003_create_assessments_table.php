<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('periode', 20);
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'REVISION', 'ARCHIVED'])->default('DRAFT');
            $table->integer('current_version')->default(1);
            $table->timestamp('last_major_update_at')->nullable();
            $table->timestamps();
        });

        Schema::create('assessment_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->integer('version_number')->default(1);
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('changes_summary')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['assessment_id', 'version_number']);

            $table->foreign('assessment_id')->references('id')->on('assessments')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_versions');
        Schema::dropIfExists('assessments');
    }
};
