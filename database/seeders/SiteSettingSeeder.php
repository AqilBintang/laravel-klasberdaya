<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'landing_banner' => [
                'slides' => [
                    ['id' => 'banner-1', 'imageUrl' => '', 'alt' => 'Banner 1'],
                    ['id' => 'banner-2', 'imageUrl' => '', 'alt' => 'Banner 2'],
                    ['id' => 'banner-3', 'imageUrl' => '', 'alt' => 'Banner 3'],
                ],
            ],

            'landing_tentang_platform' => [
                'heading'     => 'Apa itu Klasifikasi Berdaya?',
                'description' => 'Klasifikasi Kecamatan Berdaya adalah sistem penilaian mandiri berbasis indikator yang mengukur kapasitas dan kinerja kecamatan dalam menjalankan program pemberdayaan masyarakat di Jawa Tengah.',
                'points'      => [
                    'Penilaian dilakukan oleh kecamatan secara mandiri melalui self-assessment',
                    'Setiap indikator divalidasi oleh tim admin untuk menjamin akurasi data',
                    'Hasil klasifikasi dipublikasikan secara transparan kepada masyarakat',
                    'Data digunakan sebagai dasar pengambilan kebijakan pemberdayaan wilayah',
                ],
            ],
        ];

        foreach ($settings as $key => $value) {
            $exists = SiteSetting::find($key);
            if (!$exists) {
                SiteSetting::create(['key' => $key, 'value' => $value]);
                $this->command->info("✓ Site setting: {$key}");
            } else {
                $this->command->warn("- Site setting sudah ada: {$key} (dilewati)");
            }
        }
    }
}
