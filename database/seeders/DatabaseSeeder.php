<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Urutan sesuai prisma/seed.ts:
     * 1. WilayahSeeder   — data wilayah Jawa Tengah (provinsi, kab/kota, kecamatan)
     * 2. UserSeeder      — Super Admin & Admin dari env SUPER_ADMIN_EMAIL / ADMIN_EMAIL
     * 3. SiteSettingSeeder — landing page default (banner, tentang platform)
     */
    public function run(): void
    {
        $this->call([
            WilayahSeeder::class,
            UserSeeder::class,
            SiteSettingSeeder::class,
        ]);
    }
}
