<?php

namespace App\Exports;

use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Export template semua assessment (struktur kategori + indikator).
 *
 * Setiap assessment = satu sheet.
 * Format kolom per sheet:
 *   A  = Kode Kategori
 *   B  = Nama Kategori
 *   C  = No Indikator
 *   D  = Indikator
 *   E  = Skor Maks
 *   F  = Status Assessment
 *   G  = Periode
 */
class AssessmentTemplateExport implements Export, WithMultipleSheets
{
    private ?int $assessmentId;

    public function __construct(?int $assessmentId = null)
    {
        $this->assessmentId = $assessmentId;
    }

    public function sheets(): array
    {
        $query = Assessment::with([
            'categories' => fn ($q) => $q->orderBy('order')->with([
                'indicators' => fn ($q2) => $q2->orderBy('number'),
            ]),
        ])->orderBy('id');

        if ($this->assessmentId) {
            $query->where('id', $this->assessmentId);
        }

        $assessments = $query->get();

        $sheets = [];
        foreach ($assessments as $assessment) {
            $sheets[] = new AssessmentTemplateSheetExport($assessment);
        }

        return $sheets;
    }

    public static function filename(?int $assessmentId = null): string
    {
        $stamp = now()->format('Y-m-d');
        if ($assessmentId) {
            return "template-assessment-{$assessmentId}-{$stamp}.xlsx";
        }
        return "template-assessment-semua-{$stamp}.xlsx";
    }
}

/**
 * Sheet untuk satu assessment.
 */
class AssessmentTemplateSheetExport implements FromArray, WithEvents, \Maatwebsite\Excel\Concerns\WithTitle
{
    private Assessment $assessment;

    public function __construct(Assessment $assessment)
    {
        $this->assessment = $assessment;
    }

    public function title(): string
    {
        // Sheet name maks 31 karakter, strip karakter invalid
        $name = preg_replace('/[\\\\\/\*\?\[\]\:]/u', '-', $this->assessment->title ?? 'Sheet');
        return mb_substr($name, 0, 31);
    }

    public function array(): array
    {
        $rows = [];

        // Judul dokumen
        $rows[] = ["TEMPLATE ASSESSMENT: {$this->assessment->title}", '', '', '', '', '', ''];
        $rows[] = ['Periode', $this->assessment->periode ?? '-', '', 'Status', $this->assessment->status ?? '-', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];

        // Header kolom
        $rows[] = ['Kode Kat.', 'Nama Kategori', 'No', 'Indikator', 'Skor Maks', 'Versi', ''];

        foreach ($this->assessment->categories as $cat) {
            foreach ($cat->indicators as $ind) {
                $rows[] = [
                    $cat->code,
                    $cat->name,
                    $ind->number,
                    $ind->indicator,
                    $ind->max_score,
                    "v{$this->assessment->current_version}",
                    '',
                ];
            }

            // Baris total per kategori
            $catMax = $cat->indicators->sum('max_score');
            $rows[] = ['', "Total Skor Maks Kategori {$cat->code}", '', '', $catMax, '', ''];
            $rows[] = ['', '', '', '', '', '', ''];
        }

        // Grand total
        $totalMax = $this->assessment->categories->flatMap->indicators->sum('max_score');
        $rows[] = ['', 'TOTAL SKOR MAKSIMUM', '', '', $totalMax, '', ''];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Lebar kolom
                $sheet->getColumnDimension('A')->setWidth(12);
                $sheet->getColumnDimension('B')->setWidth(40);
                $sheet->getColumnDimension('C')->setWidth(6);
                $sheet->getColumnDimension('D')->setWidth(65);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(8);
                $sheet->getColumnDimension('G')->setWidth(5);

                // Style baris judul (r=1)
                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 12],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9E1F2']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->mergeCells('A1:G1');

                // Style header kolom (r=4)
                $sheet->getStyle('A4:F4')->applyFromArray([
                    'font'      => ['bold' => true],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBDD7EE']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']],
                    ],
                ]);

                // Freeze baris pertama konten (r=5)
                $sheet->freezePane('A5');

                // Wrap text
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:G{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A1:G{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }
}
