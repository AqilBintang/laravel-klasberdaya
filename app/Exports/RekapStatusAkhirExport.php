<?php

namespace App\Exports;

use App\Helpers\Scoring;
use App\Models\AssessmentVersion;
use App\Models\SelfAssessment;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Rekap Status Akhir — identik dengan Next.js rekap-status-akhir route.
 *
 * Multi-sheet: setiap kabupaten = satu sheet.
 * Kolom per sheet:
 *   Judul Assessment | Periode | Kecamatan | Skor A | Skor B | ... | Total Skor | Skor Maks | Klasifikasi Akhir
 *
 * Logic:
 *   - Ambil semua SelfAssessment VALIDATED (filter periode opsional)
 *   - Group by (submittedById, assessmentId, periode, versionId)
 *   - Filter group yang indicatorCount == total indicator version
 *   - Pilih versionNumber terbaru per (userId, assessmentId, periode)
 *   - Hitung statusAkhir via weighted scoring
 *   - Group final rows per kabupaten → masing-masing jadi sheet
 */
class RekapStatusAkhirExport implements Export, WithMultipleSheets
{
    private ?string $periode;

    public function __construct(?string $periode = null)
    {
        $this->periode = $periode;
    }

    public function sheets(): array
    {
        $rows = $this->buildRows();

        // Group by kabupaten
        $byKabupaten = [];
        foreach ($rows as $row) {
            $kab = $row['kabupaten'] ?? 'Tidak Diketahui';
            $byKabupaten[$kab][] = $row;
        }

        if (empty($byKabupaten)) {
            return [new RekapStatusAkhirSheetExport('Tidak Ada Data', [], [])];
        }

        // Collect all category headers in order (union across all rows)
        $catHeaders = []; // code => display label
        foreach ($rows as $row) {
            foreach ($row['categoryScores'] as $cat) {
                if (!isset($catHeaders[$cat['code']])) {
                    $catHeaders[$cat['code']] = "Skor {$cat['code']} - {$cat['name']}";
                }
            }
        }

        $sheets = [];
        foreach ($byKabupaten as $kab => $kabRows) {
            $sheets[] = new RekapStatusAkhirSheetExport($kab, $kabRows, $catHeaders);
        }

        return $sheets;
    }

    // ─────────────────────────────────────────────────────────
    // Build data (port dari buildRekapStatusAkhir Next.js)
    // ─────────────────────────────────────────────────────────

    private function buildRows(): array
    {
        $query = SelfAssessment::with([
                'submittedBy' => fn ($q) => $q->select(['id', 'name', 'kabupaten_id', 'kecamatan_id', 'kabupaten', 'kecamatan'])
                    ->with(['kabupatenWilayah:id,nama', 'kecamatanWilayah:id,nama']),
                'indicator.version:id,version_number',
                'indicator.category.assessment:id,title',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->limit(1)->select(['self_assessment_id', 'validated_score']),
            ])
            ->where('status', 'VALIDATED');

        if ($this->periode) {
            $query->where('periode', $this->periode);
        }

        $entries = $query->orderBy('submitted_by_id')
            ->orderBy('periode')
            ->get();

        if ($entries->isEmpty()) {
            return [];
        }

        // Jumlah indikator per version
        $versionIds = $entries->map(fn ($e) => $e->indicator->version_id)->unique();
        $indicatorCountsByVersion = AssessmentVersion::whereIn('id', $versionIds)
            ->withCount('indicators')
            ->get()
            ->keyBy('id')
            ->map(fn ($v) => $v->indicators_count);

        // Group by (userId, assessmentId, periode, versionId)
        $map = [];
        foreach ($entries as $e) {
            $cat          = $e->indicator->category;
            $assessmentId = $cat->assessment_id;
            $effScore     = $e->validations->first()?->validated_score ?? $e->score;
            $key          = "{$e->submitted_by_id}_{$assessmentId}_{$e->periode}_{$e->indicator->version_id}";

            if (!isset($map[$key])) {
                $map[$key] = [
                    'userId'          => $e->submitted_by_id,
                    'userName'        => $e->submittedBy->name,
                    'kabupaten'       => $e->submittedBy->kabupatenWilayah?->nama ?? $e->submittedBy->kabupaten ?? null,
                    'kecamatan'       => $e->submittedBy->kecamatanWilayah?->nama ?? $e->submittedBy->kecamatan ?? null,
                    'assessmentId'    => $assessmentId,
                    'assessmentTitle' => $cat->assessment->title,
                    'periode'         => $e->periode,
                    'versionId'       => $e->indicator->version_id,
                    'versionNumber'   => $e->indicator->version->version_number,
                    'indicatorCount'  => 0,
                    'totalScore'      => 0,
                    'maxPossibleTotal'=> 0,
                    'catMap'          => [],
                ];
            }

            $map[$key]['indicatorCount']   += 1;
            $map[$key]['totalScore']        += $effScore;
            $map[$key]['maxPossibleTotal']  += $e->indicator->max_score;

            $code = $cat->code;
            if (!isset($map[$key]['catMap'][$code])) {
                $map[$key]['catMap'][$code] = [
                    'code' => $code, 'name' => $cat->name,
                    'order' => $cat->order, 'score' => 0, 'maxScore' => 0,
                ];
            }
            $map[$key]['catMap'][$code]['score']    += $effScore;
            $map[$key]['catMap'][$code]['maxScore'] += $e->indicator->max_score;
        }

        // Filter & pilih versi terbaru per (userId, assessmentId, periode)
        $latestValidated = [];
        foreach ($map as $group) {
            $expected = $indicatorCountsByVersion->get($group['versionId']);
            if ($expected === null || $group['indicatorCount'] !== $expected) {
                continue;
            }
            $subKey  = "{$group['userId']}_{$group['assessmentId']}_{$group['periode']}";
            $current = $latestValidated[$subKey] ?? null;
            if (!$current || $group['versionNumber'] > $current['versionNumber']) {
                $latestValidated[$subKey] = $group;
            }
        }

        // Build final rows
        return array_map(function ($group) {
            $catScores = array_values($group['catMap']);
            usort($catScores, fn ($a, $b) => $a['order'] <=> $b['order']);
            $statusAkhir = Scoring::getStatusAkhir($group['totalScore'], $group['maxPossibleTotal'], array_map(
                fn ($c) => ['code' => $c['code'], 'score' => $c['score'], 'maxScore' => $c['maxScore']],
                $catScores
            ));

            return [
                'assessmentTitle'  => $group['assessmentTitle'],
                'periode'          => $group['periode'],
                'kecamatan'        => $group['kecamatan'],
                'kabupaten'        => $group['kabupaten'],
                'categoryScores'   => $catScores,
                'totalScore'       => $group['totalScore'],
                'maxPossibleTotal' => $group['maxPossibleTotal'],
                'statusAkhir'      => $statusAkhir,
            ];
        }, array_values($latestValidated));
    }

    public static function filename(?string $periode = null): string
    {
        $stamp = now()->format('Y-m-d');
        $safe  = $periode
            ? preg_replace('/[^a-z0-9_-]+/i', '-', $periode)
            : 'semua';
        return "rekap-status-akhir-{$safe}-{$stamp}.xlsx";
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Sheet per kabupaten
// ─────────────────────────────────────────────────────────────────────────────

class RekapStatusAkhirSheetExport implements FromArray, WithEvents, WithTitle
{
    private string $kabupaten;
    private array  $rows;
    private array  $catHeaders; // code => display label

    public function __construct(string $kabupaten, array $rows, array $catHeaders)
    {
        $this->kabupaten  = $kabupaten;
        $this->rows       = $rows;
        $this->catHeaders = $catHeaders;
    }

    public function title(): string
    {
        $name = preg_replace('/[\\\\\/\*\?\[\]\:]/u', '-', $this->kabupaten);
        return mb_substr($name, 0, 31);
    }

    public function array(): array
    {
        // Header row
        $header = ['Judul Assessment', 'Periode', 'Kecamatan'];
        foreach ($this->catHeaders as $label) {
            $header[] = $label;
        }
        $header[] = 'Total Skor';
        $header[] = 'Skor Maksimum';
        $header[] = 'Klasifikasi Akhir';

        $data = [$header];

        foreach ($this->rows as $row) {
            $line = [
                $row['assessmentTitle'],
                $row['periode'],
                $row['kecamatan'] ?? '-',
            ];

            foreach (array_keys($this->catHeaders) as $code) {
                $found = null;
                foreach ($row['categoryScores'] as $cat) {
                    if ($cat['code'] === $code) { $found = $cat; break; }
                }
                $line[] = $found ? $found['score'] : 0;
            }

            $line[] = $row['totalScore'];
            $line[] = $row['maxPossibleTotal'];
            $line[] = $row['statusAkhir'] ?? '-';

            $data[] = $line;
        }

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Lebar kolom
                $sheet->getColumnDimension('A')->setWidth(40);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(30);

                // Kolom kategori + total
                $colIdx = 4; // D dan seterusnya (1-indexed)
                foreach ($this->catHeaders as $_) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $sheet->getColumnDimension($col)->setWidth(14);
                    $colIdx++;
                }
                // Total Skor, Skor Maks, Klasifikasi
                foreach ([12, 14, 20] as $w) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $sheet->getColumnDimension($col)->setWidth($w);
                    $colIdx++;
                }

                // Style header
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx - 1);
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBDD7EE']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
                ]);

                // Freeze header row
                $sheet->freezePane('A2');

                // Wrap text
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }
}
