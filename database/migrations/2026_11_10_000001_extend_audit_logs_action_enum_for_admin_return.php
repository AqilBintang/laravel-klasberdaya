<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah dua nilai enum baru pada audit_logs.action untuk mencatat aksi admin
 * mengembalikan hasil verifikasi tim teknis:
 *   - ADMIN_RETURNED_REVERIFY   : kembalikan ke antrian validator (verifikasi ulang)
 *   - ADMIN_RETURNED_REVISION    : kembalikan ke kecamatan untuk diperbaiki
 *
 * MySQL  : ubah definisi enum (menambah nilai di akhir aman).
 * SQLite : enum disimpan sebagai VARCHAR + CHECK constraint. Lingkungan testing
 *          tidak meng-insert aksi ini, jadi no-op agar migration tetap berjalan
 *          tanpa harus rebuild tabel (SQLite tidak mendukung ALTER MODIFY).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE audit_logs MODIFY action ENUM(
                'USER_LOGIN',
                'USER_CREATED',
                'USER_UPDATED',
                'USER_DEACTIVATED',
                'ASSESSMENT_SUBMITTED',
                'ASSESSMENT_VALIDATED',
                'ROLE_CHANGED',
                'ADMIN_RETURNED_REVERIFY',
                'ADMIN_RETURNED_REVISION'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Catatan: akan gagal bila masih ada baris dengan nilai baru.
            // Hapus baris tersebut terlebih dahulu jika perlu rollback.
            DB::statement("ALTER TABLE audit_logs MODIFY action ENUM(
                'USER_LOGIN',
                'USER_CREATED',
                'USER_UPDATED',
                'USER_DEACTIVATED',
                'ASSESSMENT_SUBMITTED',
                'ASSESSMENT_VALIDATED',
                'ROLE_CHANGED'
            ) NOT NULL");
        }
    }
};
