<?php

namespace Database\Seeders;

use App\Models\KlasifikasiSnapshot;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * ImportKlasifikasiSnapshot
 *
 * Membaca file rekapitulasi xlsx dan mengisi tabel klasifikasi_snapshots.
 *
 * Kolom xlsx (1-indexed):
 *  1  = No
 *  2  = Kabupaten/Kota
 *  3  = Kecamatan
 *  4  = Skor RPPA                              → skor_a
 *  5  = Status RPPA                            → status_a
 *  6  = Skor Perlindungan Lansia & Disabilitas → skor_b
 *  7  = Status Perlindungan Lansia & Disabilitas → status_b
 *  8  = Skor Taruna Karya Mandiri              → skor_c
 *  9  = Status Taruna Karya Mandiri            → status_c
 * 10  = Skor Sport Center                      → skor_d
 * 11  = Status Sport Center                    → status_d
 * 12  = Skor Administrasi                      → skor_e
 * 13  = Skor Publikasi                         → skor_f
 * 14  = Verifikasi
 * 15  = Skor Final (Konversi Pembobotan)       → skor_final
 * 16  = Klasifikasi
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=ImportKlasifikasiSnapshot
 */
class ImportKlasifikasiSnapshot extends Seeder
{
    /**
     * Tahun data (sesuaikan jika file berubah).
     */
    private const TAHUN = 2026;

    /**
     * Path ke file xlsx relatif dari base_path().
     * File diletakkan satu level di atas root Laravel.
     */
    private const XLSX_PATH = '/../REKAPITULASI KECAMATAN BERDAYA VERIFIKASI TAHAP 1 DAN 2 update pembaharuan (1).xlsx';

    public function run(): void
    {
        $filePath = base_path() . self::XLSX_PATH;

        if (!file_exists($filePath)) {
            $this->command->error("File tidak ditemukan: {$filePath}");
            $this->command->line('Pastikan file xlsx ada di folder production/ (satu level di atas laravel_klasberdaya/).');
            return;
        }

        $this->command->info('Membaca file xlsx...');

        $spreadsheet = IOFactory::load($filePath);
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        // Baris pertama adalah header, skip
        $dataRows = array_slice($rows, 1);

        $inserted = 0;
        $skipped  = 0;
        $errors   = [];

        $this->command->info('Memproses ' . count($dataRows) . ' baris...');

        foreach ($dataRows as $lineNum => $row) {
            // Skip baris kosong (tidak ada kabupaten atau kecamatan)
            $kabupaten = trim((string) ($row[1] ?? ''));
            $kecamatan = trim((string) ($row[2] ?? ''));

            if ($kabupaten === '' || $kecamatan === '') {
                $skipped++;
                continue;
            }

            $data = [
                'kabupaten'  => $kabupaten,
                'kecamatan'  => $kecamatan,
                'tahun'      => self::TAHUN,
                'skor_a'     => (int) ($row[3]  ?? 0),
                'status_a'   => $this->normalizeStatus($row[4]  ?? null),
                'skor_b'     => (int) ($row[5]  ?? 0),
                'status_b'   => $this->normalizeStatus($row[6]  ?? null),
                'skor_c'     => (int) ($row[7]  ?? 0),
                'status_c'   => $this->normalizeStatus($row[8]  ?? null),
                'skor_d'     => (int) ($row[9]  ?? 0),
                'status_d'   => $this->normalizeStatus($row[10] ?? null),
                'skor_e'     => (int) ($row[11] ?? 0),
                'skor_f'     => (int) ($row[12] ?? 0),
                'verifikasi' => trim((string) ($row[13] ?? '')),
                'skor_final' => (float) ($row[14] ?? 0),
                'klasifikasi'=> $this->normalizeStatus($row[15] ?? null),
                'sumber'     => 'import_xlsx',
            ];

            try {
                KlasifikasiSnapshot::updateOrCreate(
                    [
                        'kabupaten' => $data['kabupaten'],
                        'kecamatan' => $data['kecamatan'],
                        'tahun'     => $data['tahun'],
                    ],
                    $data,
                );
                $inserted++;
            } catch (\Throwable $e) {
                $errors[] = "Baris " . ($lineNum + 2) . " ({$kecamatan}): " . $e->getMessage();
            }
        }

        $this->command->info("✓ Berhasil: {$inserted} baris");
        if ($skipped > 0) {
            $this->command->warn("  Dilewati (kosong): {$skipped} baris");
        }
        if (!empty($errors)) {
            $this->command->error("  Error: " . count($errors) . " baris");
            foreach ($errors as $err) {
                $this->command->line("    - {$err}");
            }
        }
    }

    /**
     * Normalisasi nilai status/klasifikasi:
     * Pastikan hanya Rintisan/Berkembang/Maju/Unggul atau null.
     */
    private function normalizeStatus(mixed $val): ?string
    {
        if ($val === null || trim((string) $val) === '') return null;

        $clean = ucfirst(strtolower(trim((string) $val)));

        return match ($clean) {
            'Rintisan', 'Berkembang', 'Maju', 'Unggul' => $clean,
            default => trim((string) $val), // simpan apa adanya jika tidak dikenali
        };
    }
}
