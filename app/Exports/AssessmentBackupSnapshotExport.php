<?php

namespace App\Exports;

use App\Models\AssessmentBackup;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Export backup data lengkap (snapshot) satu kecamatan untuk satu periode.
 *
 * Format identik dengan CSV backup-balapulang-2026-2026-10-19.csv:
 *   Baris 1 : Judul Assessment | <title>
 *   Baris 2 : Periode | <periode> | Tahun | <tahun>
 *   Baris 3 : Kabupaten/Kota | <kab> | Kecamatan | <kec>
 *   Baris 4 : Status Akhir | <status> | Total Skor | <total>/<maks>
 *   Baris 5 : kosong
 *   Per kategori:
 *     - Kategori <kode> | <nama>
 *     - Total Skor Kategori | <skor>/<maks> | Klasifikasi | <klas>
 *     - kosong
 *     - No | Indikator | Deskripsi | Skor | Skor Validasi | Skor Efektif | Dokumen Pendukung
 *     - <baris indikator...>
 *     - kosong
 *   Terakhir:
 *     - Ringkasan
 *     - Total Skor Akhir | <total>/<maks> | Klasifikasi Akhir | <status>
 *
 * Satu sheet per assessment dalam backup.
 */
class AssessmentBackupSnapshotExport implements Export, WithMultipleSheets
{
    private ?string $periode;
    private ?string $kecamatan;

    public function __construct(?string $periode = null, ?string $kecamatan = null)
    {
        $this->periode   = $periode;
        $this->kecamatan = $kecamatan;
    }

    public function sheets(): array
    {
        $query = AssessmentBackup::query()->orderBy('assessment_title');

        if ($this->periode) {
            $query->where('periode', $this->periode);
        }
        if ($this->kecamatan) {
            $query->where('kecamatan', $this->kecamatan);
        }

        $backups = $query->get();

        if ($backups->isEmpty()) {
            return [new AssessmentBackupSnapshotSheetExport('Tidak Ada Data', [])];
        }

        return $backups->map(function ($backup) {
            $snapshot  = is_array($backup->snapshot)
                ? $backup->snapshot
                : json_decode(json_encode($backup->snapshot), true);
            $sheetName = $this->safeSheetName($backup->assessment_title ?? 'Sheet');
            return new AssessmentBackupSnapshotSheetExport($sheetName, $snapshot ?? []);
        })->all();
    }

    public static function filename(?string $periode = null, ?string $kecamatan = null): string
    {
        $stamp         = now()->format('Y-m-d');
        $safeKecamatan = $kecamatan ? strtolower(preg_replace('/[^a-z0-9_-]+/i', '-', $kecamatan)) : 'semua';
        return "backup-{$safeKecamatan}-{$stamp}.xlsx";
    }

    private function safeSheetName(string $name): string
    {
        $cleaned = preg_replace('/[\[\]\*\/\\\\\?\:]/', '-', $name);
        return mb_substr(trim($cleaned), 0, 31);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Sheet untuk satu backup entry (satu assessment per sheet)
// ─────────────────────────────────────────────────────────────────────────────

class AssessmentBackupSnapshotSheetExport implements FromArray, WithEvents, WithTitle
{
    private string $sheetName;
    private array  $snapshot;

    public function __construct(string $sheetName, array $snapshot)
    {
        $this->sheetName = $sheetName;
        $this->snapshot  = $snapshot;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function array(): array
    {
        if (empty($this->snapshot)) {
            return [['Tidak ada data backup untuk filter ini.']];
        }

        $s    = $this->snapshot;
        $rows = [];

        $total = $s['totalScore']       ?? 0;
        $maks  = $s['maxPossibleTotal'] ?? 0;

        // ── Header info (baris 1–4) ──────────────────────────────
        $rows[] = ['Judul Assessment', $s['assessmentTitle'] ?? '', '', '', '', '', ''];
        $rows[] = ['Periode', $s['periode'] ?? '', 'Tahun', $s['tahun'] ?? '', '', '', ''];
        $rows[] = ['Kabupaten/Kota', $s['kabupaten'] ?? '', 'Kecamatan', $s['kecamatan'] ?? '', '', '', ''];
        $rows[] = ['Status Akhir', $s['statusAkhir'] ?? '', 'Total Skor', "{$total}/{$maks}", '', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];

        // ── Per kategori ─────────────────────────────────────────
        $categories = $s['categories'] ?? [];
        usort($categories, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        foreach ($categories as $cat) {
            $catTotal = $cat['totalScore'] ?? 0;
            $catMaks  = $cat['maxScore']   ?? 0;
            $klas     = $cat['klasifikasi'] ?? '';

            // Header kategori
            $rows[] = ["Kategori {$cat['code']}", $cat['name'] ?? '', '', '', '', '', ''];
            $rows[] = ['Total Skor Kategori', "{$catTotal}/{$catMaks}", 'Klasifikasi', $klas, '', '', ''];
            $rows[] = ['', '', '', '', '', '', ''];

            // Header kolom indikator
            $rows[] = ['No', 'Indikator', 'Deskripsi', 'Skor', 'Skor Validasi', 'Skor Efektif', 'Dokumen Pendukung'];

            // Baris data indikator
            foreach ($cat['indicators'] ?? [] as $ind) {
                $rows[] = [
                    $ind['number']         ?? '',
                    $ind['indicator']      ?? '',
                    $ind['description']    ?? '',
                    $ind['score']          ?? 0,
                    $ind['validatedScore'] ?? '',
                    $ind['effectiveScore'] ?? 0,
                    $ind['supportingDoc']  ?? '',
                ];
            }

            $rows[] = ['', '', '', '', '', '', ''];
        }

        // ── Ringkasan ─────────────────────────────────────────────
        $rows[] = ['Ringkasan', '', '', '', '', '', ''];
        $rows[] = ['Total Skor Akhir', "{$total}/{$maks}", 'Klasifikasi Akhir', $s['statusAkhir'] ?? '', '', '', ''];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Lebar kolom (A=label/no, B=konten/indikator, C=deskripsi, D=skor, E=skor val, F=skor efektif, G=dokumen)
                $sheet->getColumnDimension('A')->setWidth(22);
                $sheet->getColumnDimension('B')->setWidth(55);
                $sheet->getColumnDimension('C')->setWidth(55);
                $sheet->getColumnDimension('D')->setWidth(8);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(14);
                $sheet->getColumnDimension('G')->setWidth(45);

                // Style 4 baris info header
                $sheet->getStyle('A1:D4')->applyFromArray([
                    'font'      => ['bold' => true],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
                    'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
                ]);

                // Freeze baris 5 (setelah header info)
                $sheet->freezePane('A5');

                // Wrap text seluruh sheet
                $lastRow = $sheet->getHighestRow();
                $lastCol = $sheet->getHighestColumn();
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }
}
