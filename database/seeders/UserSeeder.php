<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin ──────────────────────────────────────────
        $superAdminEmail    = env('SUPER_ADMIN_EMAIL');
        $superAdminPassword = env('SUPER_ADMIN_PASSWORD');

        if (!$superAdminEmail || !$superAdminPassword) {
            $this->command->error(
                'SUPER_ADMIN_EMAIL dan SUPER_ADMIN_PASSWORD harus diset di .env'
            );
            return;
        }

        $existing = User::where('email', $superAdminEmail)->first();
        if (!$existing) {
            User::create([
                'name'          => 'Super Administrator',
                'email'         => $superAdminEmail,
                'password_hash' => Hash::make($superAdminPassword),
                'role'          => 'SUPER_ADMIN',
                'is_active'     => true,
            ]);
            $this->command->info("✓ Super Admin dibuat: {$superAdminEmail}");
        } else {
            $this->command->warn("- Super Admin sudah ada: {$superAdminEmail} (dilewati)");
        }

        // ── Admin ────────────────────────────────────────────────
        $adminEmail    = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if (!$adminEmail || !$adminPassword) {
            $this->command->error(
                'ADMIN_EMAIL dan ADMIN_PASSWORD harus diset di .env'
            );
            return;
        }

        $existing = User::where('email', $adminEmail)->first();
        if (!$existing) {
            User::create([
                'name'          => 'Administrator',
                'email'         => $adminEmail,
                'password_hash' => Hash::make($adminPassword),
                'role'          => 'ADMIN',
                'is_active'     => true,
            ]);
            $this->command->info("✓ Admin dibuat: {$adminEmail}");
        } else {
            $this->command->warn("- Admin sudah ada: {$adminEmail} (dilewati)");
        }
    }
}
