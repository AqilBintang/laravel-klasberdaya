<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah nilai VALIDATED ke enum status di tabel user_assessment_status
        DB::statement("
            ALTER TABLE `user_assessment_status`
            MODIFY COLUMN `status` ENUM(
                'NOT_STARTED',
                'IN_PROGRESS',
                'SUBMITTED',
                'HAS_UPDATE',
                'NEEDS_REVISION',
                'RESUBMITTED',
                'VALIDATED'
            ) NOT NULL DEFAULT 'NOT_STARTED'
        ");
    }

    public function down(): void
    {
        // Kembalikan ke enum tanpa VALIDATED
        // (hanya aman jika tidak ada baris dengan status = VALIDATED)
        DB::statement("
            ALTER TABLE `user_assessment_status`
            MODIFY COLUMN `status` ENUM(
                'NOT_STARTED',
                'IN_PROGRESS',
                'SUBMITTED',
                'HAS_UPDATE',
                'NEEDS_REVISION',
                'RESUBMITTED'
            ) NOT NULL DEFAULT 'NOT_STARTED'
        ");
    }
};
