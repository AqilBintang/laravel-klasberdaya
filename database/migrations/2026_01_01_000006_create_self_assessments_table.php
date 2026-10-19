<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('indicator_id');
            $table->unsignedBigInteger('submitted_by_id');
            $table->string('periode', 20);
            $table->text('description');             // Narasi/penjelasan jawaban
            $table->integer('score');               // Skor yang diberikan
            $table->string('supporting_doc', 500)->nullable(); // Path dokumen pendukung
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'VALIDATED', 'REJECTED'])->default('DRAFT');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Satu user hanya bisa submit sekali per indikator per periode
            $table->unique(['indicator_id', 'submitted_by_id', 'periode']);

            $table->foreign('indicator_id')->references('id')->on('assessment_indicators')->onDelete('restrict');
            $table->foreign('submitted_by_id')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('assessment_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('self_assessment_id');
            $table->unsignedBigInteger('validator_id');
            $table->enum('status', ['APPROVED', 'REJECTED', 'REVISION_NEEDED']);
            $table->integer('validated_score')->nullable(); // Skor yang ditetapkan validator
            $table->text('notes')->nullable();
            $table->timestamp('validated_at')->useCurrent();

            $table->foreign('self_assessment_id')->references('id')->on('self_assessments')->onDelete('cascade');
            $table->foreign('validator_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_validations');
        Schema::dropIfExists('self_assessments');
    }
};
