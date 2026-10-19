<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WilayahSeeder extends Seeder
{
    /**
     * Seed data wilayah Jawa Tengah dari file SQL.
     *
     * Membaca 2 file SQL dari folder db/ proyek sumber:
     *   - wilayah_level_1_2_jateng.sql  → provinsi & kab/kota (dengan koordinat)
     *   - wilayah_jateng.sql            → kecamatan (kode + nama saja)
     *
     * Hanya memproses kode wilayah yang diawali '33' (Jawa Tengah).
     * Level ditentukan dari panjang kode:
     *   2  → Provinsi
     *   5  → Kabupaten/Kota
     *   8  → Kecamatan
     */
    public function run(): void
    {
        // ── Cari path file SQL ──────────────────────────────────────────────────
        // Coba di sebelah folder laravel (sibling) atau di path absolut kustom.
        $baseDir = $this->findSqlDirectory();

        if (!$baseDir) {
            $this->command->error(
                'Folder db/ tidak ditemukan. Salin file SQL ke database/sql/ atau set ' .
                'env WILAYAH_SQL_DIR ke path yang benar.'
            );
            return;
        }

        $level12File  = $baseDir . DIRECTORY_SEPARATOR . 'wilayah_level_1_2_jateng.sql';
        $wilayahFile  = $baseDir . DIRECTORY_SEPARATOR . 'wilayah_jateng.sql';

        // ── Hapus data Jawa Tengah yang sudah ada ─────────────────────────────
        $this->command->info('Menghapus data Jawa Tengah yang sudah ada...');
        // Nonaktifkan foreign key check sementara agar self-referencing tidak error
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Wilayah::where('kode', 'like', '33%')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Parse kedua file ─────────────────────────────────────────────────
        $rows = [];

        if (file_exists($level12File)) {
            $this->command->info("Membaca: {$level12File}");
            $rows = array_merge($rows, $this->parseLevel12File($level12File));
        } else {
            $this->command->warn("File tidak ditemukan: {$level12File}");
        }

        if (file_exists($wilayahFile)) {
            $this->command->info("Membaca: {$wilayahFile}");
            $rows = array_merge($rows, $this->parseWilayahFile($wilayahFile));
        } else {
            $this->command->warn("File tidak ditemukan: {$wilayahFile}");
        }

        if (empty($rows)) {
            $this->command->error('Tidak ada data yang berhasil diparsing.');
            return;
        }

        // ── Deduplikasi: file level_1_2 prioritas (ada koordinat) ─────────────
        $unique = [];
        foreach ($rows as $row) {
            if (!isset($unique[$row['kode']])) {
                $unique[$row['kode']] = $row;
            }
        }

        // ── Urutkan: parent dulu baru anak (sortir berdasar panjang kode) ─────
        usort($unique, function ($a, $b) {
            $la = strlen($a['kode']);
            $lb = strlen($b['kode']);
            if ($la !== $lb) return $la - $lb;
            return strcmp($a['kode'], $b['kode']);
        });

        // ── Insert ke database ─────────────────────────────────────────────────
        $this->command->info('Menyimpan ' . count($unique) . ' wilayah ke database...');

        $kodeToId = [];  // kode => id setelah insert

        foreach ($unique as $row) {
            $level    = $this->detectLevel($row['kode']);
            if (!$level) continue;  // skip level desa dsb.

            $parentId = null;
            if ($level > 1) {
                // Parent kode: buang 3 karakter terakhir (titik + 2 digit)
                $parentKode = substr($row['kode'], 0, -3);
                $parentId   = $kodeToId[$parentKode] ?? null;
                if (!$parentId) {
                    $this->command->warn("Parent tidak ditemukan untuk kode: {$row['kode']}");
                }
            }

            $wilayah = Wilayah::create([
                'kode'     => $row['kode'],
                'nama'     => $row['nama'],
                'level'    => $level,
                'parent_id' => $parentId,
                'lat'      => $row['lat'] ?? null,
                'lng'      => $row['lng'] ?? null,
                'luas'     => $row['luas'] ?? null,
                'penduduk' => $row['penduduk'] ?? null,
                'path'     => $row['path'] ?? null,
                'status'   => $row['status'] ?? null,
            ]);

            $kodeToId[$row['kode']] = $wilayah->id;
        }

        $this->command->info('✓ Seeded ' . count($kodeToId) . ' wilayah Jawa Tengah.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: cari folder db/
    // ─────────────────────────────────────────────────────────────────────────

    private function findSqlDirectory(): ?string
    {
        // 1. Env override
        if ($dir = env('WILAYAH_SQL_DIR')) {
            return rtrim($dir, '/\\');
        }

        // 2. database/sql/ di dalam project Laravel ini
        $candidates = [
            database_path('sql'),
            // 3. Sibling folder dari project Laravel (untuk monorepo)
            dirname(base_path()) . DIRECTORY_SEPARATOR . 'klasifikasi-berdaya-dispermades' . DIRECTORY_SEPARATOR . 'db',
            // 4. Path absolut umum untuk development
            base_path('db'),
        ];

        foreach ($candidates as $path) {
            if (is_dir($path)) return $path;
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: parse wilayah_level_1_2_jateng.sql
    // Format kolom: kode, nama, ibukota, lat, lng, elv, tz, luas, penduduk, path, status
    // ─────────────────────────────────────────────────────────────────────────

    private function parseLevel12File(string $filePath): array
    {
        $rows    = [];
        $content = file_get_contents($filePath);
        $lines   = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, '(')) continue;

            // Hapus koma trailing
            if (str_ends_with($line, ',')) {
                $line = substr($line, 0, -1);
            }

            $values = $this->parseCsvLine(substr($line, 1, -1));
            if (count($values) < 2) continue;

            $kode = trim($values[0], "' ");
            if (!str_starts_with($kode, '33')) continue;
            if (!$this->detectLevel($kode)) continue;

            $parseNum = fn($v) => ($v === null || strtoupper(trim($v, "' ")) === 'NULL')
                ? null
                : (is_numeric(trim($v, "' ")) ? (float) trim($v, "' ") : null);

            $rows[] = [
                'kode'     => $kode,
                'nama'     => trim($values[1] ?? '', "' "),
                'lat'      => $parseNum($values[3] ?? null),
                'lng'      => $parseNum($values[4] ?? null),
                'luas'     => $parseNum($values[7] ?? null),
                'penduduk' => $parseNum($values[8] ?? null),
                'path'     => ($values[9] ?? null) && strtoupper(trim($values[9], "' ")) !== 'NULL'
                    ? trim($values[9], "' ")
                    : null,
                'status'   => $parseNum($values[10] ?? null),
            ];
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: parse wilayah_jateng.sql
    // Format kolom: kode, nama
    // ─────────────────────────────────────────────────────────────────────────

    private function parseWilayahFile(string $filePath): array
    {
        $rows    = [];
        $content = file_get_contents($filePath);
        $lines   = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, '(')) continue;

            if (str_ends_with($line, ',')) {
                $line = substr($line, 0, -1);
            }

            $values = $this->parseCsvLine(substr($line, 1, -1));
            if (count($values) < 2) continue;

            $kode = trim($values[0], "' ");
            if (!str_starts_with($kode, '33')) continue;
            if (!$this->detectLevel($kode)) continue;

            $rows[] = [
                'kode' => $kode,
                'nama' => trim($values[1] ?? '', "' "),
            ];
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: tentukan level berdasarkan panjang kode
    // Hanya proses level 1 (provinsi), 2 (kab/kota), 3 (kecamatan)
    // ─────────────────────────────────────────────────────────────────────────

    private function detectLevel(string $kode): ?int
    {
        return match (strlen($kode)) {
            2  => 1,  // Provinsi      "33"
            5  => 2,  // Kab/Kota      "33.01"
            8  => 3,  // Kecamatan     "33.01.01"
            default => null,
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: parse satu baris CSV dengan respek tanda kutip tunggal
    // ─────────────────────────────────────────────────────────────────────────

    private function parseCsvLine(string $line): array
    {
        $values     = [];
        $current    = '';
        $inQuote    = false;
        $escapeNext = false;
        $len        = strlen($line);

        for ($i = 0; $i < $len; $i++) {
            $ch = $line[$i];

            if ($escapeNext) {
                $current   .= $ch;
                $escapeNext = false;
                continue;
            }

            if ($ch === '\\') {
                $escapeNext = true;
                continue;
            }

            if ($ch === "'") {
                $inQuote = !$inQuote;
                continue;
            }

            if ($ch === ',' && !$inQuote) {
                $values[] = $current;
                $current  = '';
                continue;
            }

            $current .= $ch;
        }

        $values[] = $current;
        return $values;
    }
}
