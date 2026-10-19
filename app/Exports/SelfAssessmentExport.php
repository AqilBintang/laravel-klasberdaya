<?php

namespace App\Exports;

use App\Helpers\Scoring;
use App\Models\Assessment;
use App\Models\SelfAssessment;
use App\Models\User;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Export self-assessment satu kecamatan untuk satu assessment + periode.
 *
 * Format identik dengan Next.js buildSelfAssessmentExport:
 *   Baris 1 : Judul dokumen
 *   Baris 2 : Kecamatan
 *   Baris 3 : Kabupaten/Kota
 *   Baris 4 : kosong
 *   Per kategori:
 *     - Header kategori     (A–F)
 *     - Sub-header NO|INDIKATOR|SELF ASESSMENT (2 baris, merge)
 *     - Baris per indikator
 *     - TOTAL SKOR
 *     - KATEGORI PROGRAM PRIORITAS
 *     - Baris kosong
 *   Grand total + KATEGORI KECAMATAN BERDAYA
 */
class SelfAssessmentExport implements Export, FromArray, WithEvents, WithTitle
{
    private int    $userId;
    private int    $assessmentId;
    private string $periode;

    // Data diisi saat build
    private array  $rows    = [];
    private array  $merges  = [];
    private string $sheetTitle = 'Input Self Asessment';

    public function __construct(int $userId, int $assessmentId, string $periode)
    {
        $this->userId       = $userId;
        $this->assessmentId = $assessmentId;
        $this->periode      = $periode;
    }

    // ─────────────────────────────────────────────────────────
    // Build data rows (port dari buildSelfAssessmentExport)
    // ─────────────────────────────────────────────────────────

    private function build(): void
    {
        if (!empty($this->rows)) return;

        // 1. Info user
        $user = User::with(['kabupatenWilayah', 'kecamatanWilayah'])->findOrFail($this->userId);
        $kecamatanNama = $user->kecamatanWilayah?->nama ?? $user->kecamatan ?? '-';
        $kabupatenNama = $user->kabupatenWilayah?->nama ?? $user->kabupaten ?? '-';

        // 2. Assessment + kategori + indikator
        $assessment = Assessment::with([
            'categories' => fn ($q) => $q->orderBy('order')->with([
                'indicators' => fn ($q2) => $q2->orderBy('number'),
            ]),
        ])->findOrFail($this->assessmentId);

        // 3. Entry self-assessment user
        $entries = SelfAssessment::with([
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->limit(1),
            ])
            ->where('submitted_by_id', $this->userId)
            ->where('periode', $this->periode)
            ->whereHas('indicator.category', fn ($q) => $q->where('assessment_id', $this->assessmentId))
            ->get()
            ->keyBy('indicator_id');

        // 4. Susun rows
        $rows   = [];
        $merges = [];
        $merge  = function (int $r1, int $c1, int $r2, int $c2) use (&$merges) {
            $merges[] = [$r1, $c1, $r2, $c2];
        };

        // Header dokumen (r=0..3)
        $rows[] = ['SELF ASESSMENT KECAMATAN BERDAYA PROVINSI JAWA TENGAH', '', '', '', '', ''];
        $rows[] = ['', 'Kecamatan ', strtoupper($kecamatanNama), '', '', ''];
        $rows[] = ['', 'Kabupaten/Kota', strtoupper($kabupatenNama), '', '', ''];
        $rows[] = ['', '', '', '', '', ''];

        // Baris 2 (r=1): nilai kecamatan → C:F  (kolom idx 2..5)
        $merge(1, 2, 1, 5);
        // Baris 3 (r=2): nilai kabupaten → C:F
        $merge(2, 2, 2, 5);

        $grandTotal = 0;
        $grandMax   = 0;
        $catCodes   = [];

        foreach ($assessment->categories as $cat) {
            $catCodes[] = $cat->code;
            $catScore   = 0;
            $catMax     = 0;

            foreach ($cat->indicators as $ind) {
                /** @var SelfAssessment|null $e */
                $e = $entries->get($ind->id);
                $effectiveScore = $e?->validations->first()?->validated_score ?? $e?->score ?? 0;
                $catScore += $effectiveScore;
                $catMax   += $ind->max_score;
            }

            $grandTotal += $catScore;
            $grandMax   += $catMax;

            $klasifikasi = Scoring::getKlasifikasiPerKategori($cat->code, $catScore);

            // Header kategori (1 baris)
            $rCatHeader = count($rows);
            $rows[] = ["{$cat->code}. {$cat->name}", '', '', '', '', ''];
            $rCatHeader; // tidak di-merge sesuai file asli

            // Sub-header baris atas + bawah
            $rSubH1 = count($rows);
            $rows[] = ['NO', 'INDIKATOR', 'SELF ASESSMENT', '', '', ''];
            $rows[] = ['',   '',          'DESKRIPSI',      'SKOR', 'DOKUMEN PENDUKUNG', ''];

            // NO    → A merge 2 baris
            $merge($rSubH1, 0, $rSubH1 + 1, 0);
            // INDIKATOR → B merge 2 baris
            $merge($rSubH1, 1, $rSubH1 + 1, 1);
            // SELF ASESSMENT → C:E
            $merge($rSubH1, 2, $rSubH1, 4);

            // Baris indikator
            foreach ($cat->indicators as $ind) {
                $e = $entries->get($ind->id);
                $effectiveScore = $e?->validations->first()?->validated_score ?? $e?->score ?? '';
                $rows[] = [
                    $ind->number,
                    $ind->indicator,
                    $e?->description ?? '',
                    $effectiveScore,
                    $e?->supporting_doc ?? '',
                    '',
                ];
            }

            // Total skor kategori — A:C
            $rTotal = count($rows);
            $rows[] = ["TOTAL SKOR {$cat->code}", '', '', $catScore, '', ''];
            $merge($rTotal, 0, $rTotal, 2);

            // Kategori program prioritas — label A:B, nilai C:D
            $rKlas = count($rows);
            $rows[] = ['KATEGORI PROGRAM PRIORITAS ', '', $klasifikasi ?? '-', '', '', ''];
            $merge($rKlas, 0, $rKlas, 1);
            $merge($rKlas, 2, $rKlas, 3);

            // Baris kosong
            $rows[] = ['', '', '', '', '', ''];
        }

        // Grand total
        $categoryScores = [];
        foreach ($assessment->categories as $cat) {
            $catScore = 0;
            $catMax   = 0;
            foreach ($cat->indicators as $ind) {
                $e = $entries->get($ind->id);
                $effectiveScore = $e?->validations->first()?->validated_score ?? $e?->score ?? 0;
                $catScore += $effectiveScore;
                $catMax   += $ind->max_score;
            }
            $categoryScores[] = ['code' => $cat->code, 'score' => $catScore, 'maxScore' => $catMax];
        }
        $statusAkhir = Scoring::getStatusAkhir($grandTotal, $grandMax, $categoryScores);

        $codesStr   = implode(', ', array_slice($catCodes, 0, -1));
        $lastCode   = end($catCodes);
        $grandLabel = "TOTAL SKOR {$codesStr} DAN {$lastCode}";

        $rGrand = count($rows);
        $rows[] = [$grandLabel, '', '', $grandTotal, '', ''];
        $merge($rGrand, 0, $rGrand, 2);

        $rStatus = count($rows);
        $rows[] = ['KATEGORI KECAMATAN BERDAYA', '', $statusAkhir ?? '-', '', '', ''];
        $merge($rStatus, 0, $rStatus, 1);
        $merge($rStatus, 2, $rStatus, 3);

        $this->rows   = $rows;
        $this->merges = $merges;
    }

    // ─────────────────────────────────────────────────────────
    // Interface implementations
    // ─────────────────────────────────────────────────────────

    public function array(): array
    {
        $this->build();
        return $this->rows;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->build();
                $sheet = $event->sheet->getDelegate();

                // Lebar kolom: NO(A), INDIKATOR(B), DESKRIPSI(C), SKOR(D), DOKUMEN(E), spare(F)
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(55);
                $sheet->getColumnDimension('C')->setWidth(60);
                $sheet->getColumnDimension('D')->setWidth(8);
                $sheet->getColumnDimension('E')->setWidth(55);
                $sheet->getColumnDimension('F')->setWidth(5);

                // Terapkan merge cells
                $colMap = ['A','B','C','D','E','F'];
                foreach ($this->merges as [$r1, $c1, $r2, $c2]) {
                    $cellRange = "{$colMap[$c1]}".($r1+1).":{$colMap[$c2]}".($r2+1);
                    $sheet->mergeCells($cellRange);
                }

                // Style baris header (r=0, baris ke-1)
                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']],
                ]);

                // Wrap text seluruh sheet
                $lastRow = count($this->rows);
                $sheet->getStyle("A1:F{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A1:F{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Static filename helper
    // ─────────────────────────────────────────────────────────

    public static function filename(string $kecamatanNama, string $kabupatenNama, string $assessmentTitle, string $periode): string
    {
        $safePart = fn (string $s) => strtoupper(
            preg_replace('/\s+/', '_', preg_replace('/[^a-z0-9 _-]/i', '', trim($s)))
        );

        return "KECAMATAN_{$safePart($kecamatanNama)}_{$safePart($kabupatenNama)}-{$assessmentTitle}-{$periode}.xlsx";
    }
}
