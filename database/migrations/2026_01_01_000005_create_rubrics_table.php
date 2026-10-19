<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_rubrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->string('title', 255);            // Judul rubrik penilaian
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('assessment_id')->references('id')->on('assessments')->onDelete('restrict');
        });

        Schema::create('rubric_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rubric_id');
            $table->unsignedBigInteger('indicator_id'); // Relasi ke assessment_indicators
            $table->text('score1');                  // Deskripsi skor 1
            $table->text('score2');                  // Deskripsi skor 2
            $table->text('score3');                  // Deskripsi skor 3
            $table->text('score4');                  // Deskripsi skor 4
            $table->timestamps();

            $table->unique(['rubric_id', 'indicator_id']);

            $table->foreign('rubric_id')->references('id')->on('assessment_rubrics')->onDelete('restrict');
            $table->foreign('indicator_id')->references('id')->on('assessment_indicators')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_items');
        Schema::dropIfExists('assessment_rubrics');
    }
};
