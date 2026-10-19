<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('version_id')->default(1);
            $table->string('code', 10);             // Kode kategori: "A", "B", dst
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->json('scoring_rule')->nullable(); // Custom threshold JSON
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique: satu assessment+version tidak boleh punya kode duplikat
            $table->unique(['assessment_id', 'version_id', 'code'], 'assessment_cat_assessment_version_code_key');

            $table->foreign('assessment_id')->references('id')->on('assessments')->onDelete('cascade');
            $table->foreign('version_id')->references('id')->on('assessment_versions')->onDelete('restrict');
        });

        Schema::create('assessment_indicators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('version_id')->default(1);
            $table->integer('number');              // Nomor urut indikator dalam kategori
            $table->text('indicator');              // Teks indikator
            $table->integer('max_score')->default(4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique: satu assessment+version+category tidak boleh punya nomor urut duplikat
            $table->unique(
                ['assessment_id', 'version_id', 'category_id', 'number'],
                'assessment_ind_assessment_version_cat_num_key'
            );

            $table->foreign('assessment_id')->references('id')->on('assessments')->onDelete('restrict');
            $table->foreign('category_id')->references('id')->on('assessment_categories')->onDelete('restrict');
            $table->foreign('version_id')->references('id')->on('assessment_versions')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_indicators');
        Schema::dropIfExists('assessment_categories');
    }
};
