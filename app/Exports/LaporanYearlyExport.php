<?php

namespace App\Exports;

use App\Helpers\Scoring;
use App\Models\SelfAssessment;
use App\Models\User;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Export laporan tahunan kecamatan.
 *
 * Port dari buildYearlyReportWorkbook (Next.js).
 * Satu workbook — satu sheet per periode assessment dalam tahun yang diminta.
 *
 * Format kolom (A–G):
 *   A  = No
 *   B  = Indikator
 *   C  = Deskripsi          ┐
 *   D  = Skor               │ SELF ASSESSMENT
 *   E  = Dokumen Pendukung  │
 *   F  = Skor Validasi      │
 *   G  = Komentar Tim Teknis┘
 */
class LaporanYearlyExport implements Export, WithMultipleSheets
{
    private int $userId;
    private int $tahun;

    public function __construct(int $userId, int $tahun)
    {
        $this->userId = $userId;
        $this->tahun  = $tahun;
    }

    // ─────────────────────────────────────────────────────────
    // Build sheets
    // ─────────────────────────────────────────────────────────

    public function sheets(): array
    {
        // Info user
        $user = User::with(['kabupatenWilayah', 'kecamatanWilayah'])->findOrFail($this->userId);
        $kecamatanNama = strtoupper($user->kecamatanWilayah?->nama ?? $user->kecamatan ?? '-');
        $kabupatenNama = strtoupper($user->kabupatenWilayah?->nama ?? $user->kabupaten ?? '-');

        // Ambil semua self-assessment user pada tahun tersebut
        $entries = SelfAssessment::with([
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->limit(1),
                'indicator.category.assessment',
            ])
            ->where('submitted_by_id', $this->userId)
            ->where('periode', 'like', '%' . $this->tahun . '%')
            ->orderBy('periode')
            ->get();

        if ($entries->isEmpty()) {
            throw new \RuntimeException("Tidak ada data assessment untuk tahun {$this->tahun}");
        }

        // Kelompokkan per assessmentId+periode
        $groups = [];
        foreach ($entries as $e) {
            $cat   = $e->indicator->category;
            $assmt = $cat->assessment;
            $key   = $assmt->id . '_' . $e->periode;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'assessmentId' => $assmt->id,
                    'title'        => $assmt->title,
                    'periode'      => $e->periode,
                    'entries'      => [],
                ];
            }
            $groups[$key]['entries'][] = $e;
        }

        $sheets = [];
        foreach ($groups as $group) {
            $sheets[] = new LaporanYearlySheetExport(
                $kecamatanNama,
                $kabupatenNama,
                $group['periode'],
                $group['entries'],
            );
        }

        return $sheets;
    }

    // ─────────────────────────────────────────────────────────
    // Static filename helper
    // ─────────────────────────────────────────────────────────

    public static function filename(string $kecamatanNama, int $tahun): string
    {
        $safe = strtoupper(
            preg_replace('/\s+/', '_', preg_replace('/[^a-z0-9 _-]/i', '', trim($kecamatanNama)))
        );
        return "LAPORAN_{$safe}_{$tahun}.xlsx";
    }
}


/**
 * Satu sheet per periode dalam workbook laporan tahunan.
 */
class LaporanYearlySheetExport implements FromArray, WithEvents, WithTitle
{
    private const COL_COUNT = 7;

    private string $kecamatanNama;
    private string $kabupatenNama;
    private string $periode;
    /** @var \App\Models\SelfAssessment[] */
    private array  $entries;

    // Diisi oleh build()
    private array  $rows   = [];
    private array  $merges = [];

    public function __construct(
        string $kecamatanNama,
        string $kabupatenNama,
        string $periode,
        array  $entries,
    ) {
        $this->kecamatanNama = $kecamatanNama;
        $this->kabupatenNama = $kabupatenNama;
        $this->periode       = $periode;
        $this->entries       = $entries;
    }

    // ─────────────────────────────────────────────────────────
    // Build data rows (port dari buildYearlyReportWorkbook)
    // ─────────────────────────────────────────────────────────

    private function build(): void
    {
        if (!empty($this->rows)) return;

        $rows   = [];
        $merges = [];
        $merge  = function (int $r1, int $c1, int $r2, int $c2) use (&$merges) {
            $merges[] = [$r1, $c1, $r2, $c2];
        };
        $empty = fn () => array_fill(0, self::COL_COUNT, '');

        // ── Header dokumen (r=0..4) ─────────────────────────────────
        // r=0: judul → A:G merge
        $rows[] = ['SELF ASESSMENT KECAMATAN BERDAYA PROVINSI JAWA TENGAH', '', '', '', '', '', ''];
        $merge(0, 0, 0, 6);

        // r=1: Kecamatan → label di B, nilai di C:G
        $rows[] = ['', 'Kecamatan', $this->kecamatanNama, '', '', '', ''];
        $merge(1, 2, 1, 6);

        // r=2: Kabupaten/Kota → label di B, nilai di C:G
        $rows[] = ['', 'Kabupaten/Kota', $this->kabupatenNama, '', '', '', ''];
        $merge(2, 2, 2, 6);

        // r=3: Periode → label di B, nilai di C:G
        $rows[] = ['', 'Periode', $this->periode, '', '', '', ''];
        $merge(3, 2, 3, 6);

        // r=4: kosong
        $rows[] = $empty();

        // ── Kelompokkan entries per kategori ────────────────────────
        // Struktur: catId => ['code','name','order','items'[]]
        $catMap = [];
        foreach ($this->entries as $e) {
            $cat = $e->indicator->category;
            if (!isset($catMap[$cat->id])) {
                $catMap[$cat->id] = [
                    'code'  => $cat->code,
                    'name'  => $cat->name,
                    'order' => $cat->order,
                    'items' => [],
                ];
            }
            $catMap[$cat->id]['items'][] = $e;
        }
        // Urutkan berdasarkan order kategori
        usort($catMap, fn ($a, $b) => $a['order'] <=> $b['order']);

        $grandTotal     = 0;
        $grandMax       = 0;
        $categoryScores = [];
        $rekapRows      = [];

        foreach ($catMap as $cat) {
            $catScore = 0;
            $catMax   = 0;

            // r: header kategori → A:G merge
            $rCatHeader = count($rows);
            $rows[] = ["{$cat['code']}. {$cat['name']}", '', '', '', '', '', ''];
            $merge($rCatHeader, 0, $rCatHeader, 6);

            // r+1: sub-header baris atas
            //   A=NO (merge 2 baris), B=INDIKATOR (merge 2 baris), C:G = "SELF ASSESSMENT"
            $rSubH1 = count($rows);
            $rows[] = ['NO', 'INDIKATOR', 'SELF ASSESSMENT', '', '', '', ''];
            $merge($rSubH1, 0, $rSubH1 + 1, 0);  // NO: merge 2 baris
            $merge($rSubH1, 1, $rSubH1 + 1, 1);  // INDIKATOR: merge 2 baris
            $merge($rSubH1, 2, $rSubH1, 6);       // SELF ASSESSMENT: merge C:G

            // r+2: sub-header baris bawah (detail kolom)
            $rows[] = ['', '', 'DESKRIPSI', 'SKOR', 'DOKUMEN PENDUKUNG', 'SKOR VALIDASI', 'KOMENTAR TIM TEKNIS'];

            // Urutkan items berdasarkan nomor indikator
            usort($cat['items'], fn ($a, $b) => $a->indicator->number <=> $b->indicator->number);

            // Baris data indikator
            foreach ($cat['items'] as $e) {
                /** @var \App\Models\SelfAssessment $e */
                $validatedScore = $e->validations->first()?->validated_score ?? null;
                $effectiveScore = $validatedScore ?? $e->score;
                $catScore += $effectiveScore;
                $catMax   += $e->indicator->max_score;

                $rows[] = [
                    $e->indicator->number,
                    $e->indicator->indicator,
                    $e->description ?? '',
                    $e->score,
                    $e->supporting_doc ?? '-',
                    $validatedScore ?? '-',
                    $e->validations->first()?->notes ?? '-',
                ];
            }

            $grandTotal += $catScore;
            $grandMax   += $catMax;
            $categoryScores[] = ['code' => $cat['code'], 'score' => $catScore, 'maxScore' => $catMax];
            $klasifikasi       = Scoring::getKlasifikasiPerKategori($cat['code'], $catScore);

            // TOTAL SKOR kategori → A:C merge, nilai di D
            $rTotal = count($rows);
            $rows[] = ["TOTAL SKOR {$cat['code']}", '', '', $catScore, '', '', ''];
            $merge($rTotal, 0, $rTotal, 2);

            // KATEGORI PROGRAM PRIORITAS (hanya A, B, C, D)
            if ($klasifikasi !== null) {
                $rKlas = count($rows);
                $rows[] = ['KATEGORI PROGRAM PRIORITAS', '', $klasifikasi, '', '', '', ''];
                $merge($rKlas, 0, $rKlas, 1);
                $merge($rKlas, 2, $rKlas, 3);
            }

            $rows[] = $empty();

            $rekapRows[] = [$cat['code'], $cat['name'], $catScore, $catMax, $klasifikasi ?? '-'];
        }

        // ── Grand total ─────────────────────────────────────────────
        $statusAkhir = Scoring::getStatusAkhir($grandTotal, $grandMax, $categoryScores);

        $rGrand = count($rows);
        $rows[] = ['TOTAL SKOR KESELURUHAN', '', '', $grandTotal, '', '', ''];
        $merge($rGrand, 0, $rGrand, 2);

        $rStatus = count($rows);
        $rows[] = ['KATEGORI KECAMATAN BERDAYA', '', $statusAkhir ?? '-', '', '', '', ''];
        $merge($rStatus, 0, $rStatus, 1);
        $merge($rStatus, 2, $rStatus, 3);

        $rows[] = $empty();

        // ── Tabel rekapitulasi ──────────────────────────────────────
        $rRekap = count($rows);
        $rows[] = ['REKAPITULASI', '', '', '', '', '', ''];
        $merge($rRekap, 0, $rRekap, 6);

        $rows[] = ['Kode', 'Kategori', 'Skor', 'Skor Maks', 'Klasifikasi', '', ''];
        foreach ($rekapRows as $rr) {
            $rows[] = array_merge($rr, ['', '']);
        }
        $rows[] = ['', 'TOTAL', $grandTotal, $grandMax, $statusAkhir ?? '-', '', ''];

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
        // Sheet name maks 31 karakter, strip karakter invalid Excel
        return substr(preg_replace('/[\\\\\/\*\?\[\]:]+/', '-', $this->periode), 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->build();
                $sheet = $event->sheet->getDelegate();

                // ── Lebar kolom ──────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(50);
                $sheet->getColumnDimension('C')->setWidth(55);
                $sheet->getColumnDimension('D')->setWidth(8);
                $sheet->getColumnDimension('E')->setWidth(40);
                $sheet->getColumnDimension('F')->setWidth(14);
                $sheet->getColumnDimension('G')->setWidth(45);

                // ── Merge cells ──────────────────────────────────────
                $colMap = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
                foreach ($this->merges as [$r1, $c1, $r2, $c2]) {
                    $range = "{$colMap[$c1]}" . ($r1 + 1) . ":{$colMap[$c2]}" . ($r2 + 1);
                    $sheet->mergeCells($range);
                }

                $lastRow  = count($this->rows);
                $fullRange = "A1:G{$lastRow}";

                // ── Wrap text + vertical align ────────────────────────
                $sheet->getStyle($fullRange)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                // ── Baris judul (r=0, baris 1) ────────────────────────
                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFD9E1F2'],
                    ],
                ]);

                // ── Baris header info (Kecamatan, Kabupaten, Periode) ──
                $sheet->getStyle('A2:G4')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                // ── Freeze panes setelah baris ke-5 ─────────────────────
                $sheet->freezePane('A6');
            },
        ];
    }
}
