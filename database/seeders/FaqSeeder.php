<?php

namespace Database\Seeders;

use App\Models\FaqCategory;
use App\Models\FaqItem;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Data FAQ awal — dimigrasi dari hardcode di FaqController lama.
     */
    public function run(): void
    {
        $data = [
            [
                'name'       => 'Umum',
                'sort_order' => 1,
                'items'      => [
                    [
                        'question'   => 'Apa itu Klasifikasi Indeks Kecamatan Berdaya?',
                        'answer'     => 'Klasifikasi Indeks Kecamatan Berdaya adalah sistem penilaian mandiri berbasis indikator yang mengukur kapasitas dan kinerja kecamatan dalam menjalankan program pemberdayaan masyarakat di Jawa Tengah. Setiap kecamatan dinilai dan diklasifikasikan ke dalam empat level: Rintisan, Berkembang, Maju, atau Unggul.',
                        'sort_order' => 1,
                    ],
                    [
                        'question'   => 'Siapa saja yang bisa mengakses platform ini?',
                        'answer'     => 'Platform ini terbuka untuk masyarakat umum yang ingin melihat data klasifikasi kecamatan di Jawa Tengah. Untuk mengisi self-assessment, hanya pihak kecamatan yang memiliki akun terdaftar yang dapat melakukannya.',
                        'sort_order' => 2,
                    ],
                    [
                        'question'   => 'Seberapa sering data klasifikasi diperbarui?',
                        'answer'     => 'Data klasifikasi diperbarui setiap tahun seiring dengan siklus pengisian self-assessment oleh kecamatan. Data yang ditampilkan adalah data tervalidasi oleh tim teknis.',
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'name'       => 'Penilaian & Klasifikasi',
                'sort_order' => 2,
                'items'      => [
                    [
                        'question'   => 'Bagaimana cara kerja sistem penilaian?',
                        'answer'     => 'Setiap kecamatan mengisi self-assessment secara mandiri berdasarkan indikator-indikator yang telah ditentukan. Jawaban kemudian divalidasi oleh tim teknis, dan skor tertimbang dihitung untuk menentukan level klasifikasi kecamatan tersebut.',
                        'sort_order' => 1,
                    ],
                    [
                        'question'   => 'Apa perbedaan antara keempat level klasifikasi?',
                        'answer'     => 'Terdapat empat level: Rintisan (skor ≤ 14,41) — kecamatan yang baru memulai inisiatif pemberdayaan; Berkembang (skor ≤ 29,13) — sudah memiliki sistem dasar; Maju (skor ≤ 43,23) — sistem pemberdayaan berjalan terstruktur; Unggul (skor ≥ 43,24) — mencapai tingkat pemberdayaan optimal dan dapat menjadi percontohan.',
                        'sort_order' => 2,
                    ],
                    [
                        'question'   => 'Apakah hasil klasifikasi bisa berubah setiap tahun?',
                        'answer'     => 'Ya. Klasifikasi dihitung ulang setiap periode penilaian berdasarkan data self-assessment terbaru.',
                        'sort_order' => 3,
                    ],
                    [
                        'question'   => 'Siapa yang memvalidasi hasil penilaian?',
                        'answer'     => 'Validasi dilakukan oleh tim teknis yang berwenang. Setiap jawaban self-assessment yang dikirimkan oleh kecamatan akan ditinjau dan dapat diberikan skor tervalidasi yang berbeda dari skor awal jika diperlukan.',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'name'       => 'Data & Transparansi',
                'sort_order' => 3,
                'items'      => [
                    [
                        'question'   => 'Dari mana data klasifikasi yang ditampilkan berasal?',
                        'answer'     => 'Data berasal dari self-assessment yang diisi langsung oleh pihak kecamatan, kemudian divalidasi oleh tim teknis. Hanya data yang telah tervalidasi yang ditampilkan kepada publik.',
                        'sort_order' => 1,
                    ],
                ],
            ],
        ];

        foreach ($data as $categoryData) {
            $items = $categoryData['items'];
            unset($categoryData['items']);

            $category = FaqCategory::create(array_merge($categoryData, ['is_active' => true]));

            foreach ($items as $itemData) {
                FaqItem::create(array_merge($itemData, [
                    'faq_category_id' => $category->id,
                    'is_active'       => true,
                ]));
            }
        }
    }
}
