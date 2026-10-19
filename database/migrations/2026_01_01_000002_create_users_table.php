<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password_hash', 255);       // passwordHash dari Prisma
            $table->enum('role', ['SUPER_ADMIN', 'ADMIN', 'VALIDATOR', 'USER'])->default('USER');
            // Legacy string columns (dipertahankan untuk kompatibilitas data lama)
            $table->string('kabupaten', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            // Relational FK ke wilayah
            $table->unsignedBigInteger('kabupaten_id')->nullable();
            $table->unsignedBigInteger('kecamatan_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();                       // created_at & updated_at

            // Password reset (diperlukan Laravel auth)
            $table->rememberToken();

            $table->foreign('kabupaten_id')->references('id')->on('wilayah')->onDelete('set null');
            $table->foreign('kecamatan_id')->references('id')->on('wilayah')->onDelete('set null');
        });

        // Password reset tokens — diperlukan Laravel Breeze
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
