<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AssessmentBackupSnapshotExport;
use App\Exports\AssessmentTemplateExport;
use App\Exports\RekapStatusAkhirExport;
use App\Helpers\Scoring;
use App\Models\Assessment;
use App\Models\AssessmentBackup;
use App\Models\SelfAssessment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * Halaman UI export (GET /admin/export/assessment dan /admin/export/).
     * Menampilkan blade dengan filter periode + kecamatan + dua card download.
     *
     * Identik dengan Next.js BackupExportClient yang memanggil /api/export/options.
     */
    public function index(Request $request): View
    {
        // Periodes dari AssessmentBackup (sama dengan /api/export/options di Next.js)
        $periodes = AssessmentBackup::query()
            ->distinct()
            ->orderBy('periode')
            ->pluck('periode')
            ->filter()
            ->values()
            ->all();

        // Jika tidak ada backup, fallback ke SelfAssessment
        if (empty($periodes)) {
            $periodes = SelfAssessment::query()
                ->distinct()
                ->orderBy('periode')
                ->pluck('periode')
                ->filter()
                ->values()
                ->all();
        }

        // Kecamatans dari AssessmentBackup (sama dengan sumber data periodes)
        $kecamatans = AssessmentBackup::query()
            ->distinct()
            ->orderBy('kecamatan')
            ->pluck('kecamatan')
            ->filter()
            ->values()
            ->all();

        // Tahun yang tersedia (diparsing dari periode, untuk backward compat)
        $years = collect($periodes)
            ->map(fn ($p) => $this->extractYear($p))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Default periode (terbaru)
        $defaultPeriode = end($periodes) ?: null;

        return view('admin.export', compact('periodes', 'kecamatans', 'years', 'defaultPeriode'));
    }

    /**
     * Download template assessment (GET /admin/export/template).
     * Query param: assessment_id (opsional)
     */
    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $assessmentId = $request->integer('assessment_id') ?: null;

        $export   = new AssessmentTemplateExport($assessmentId);
        $filename = AssessmentTemplateExport::filename($assessmentId);

        return Excel::download($export, $filename);
    }

    /**
     * Download backup data lengkap / snapshot satu kecamatan
     * (GET /admin/export/snapshot).
     *
     * Identik dengan Next.js /api/export/backup/snapshot.
     *
     * Query params:
     *   - periode   : wajib
     *   - kecamatan : wajib
     */
    public function snapshot(Request $request): BinaryFileResponse
    {
        $periode   = $request->query('periode')   ?: null;
        $kecamatan = $request->query('kecamatan') ?: null;

        $export   = new AssessmentBackupSnapshotExport($periode, $kecamatan);
        $filename = AssessmentBackupSnapshotExport::filename($periode, $kecamatan);

        return Excel::download($export, $filename);
    }

    /**
     * Download rekap status akhir semua kecamatan (GET /admin/export/self-assessment).
     * Identik dengan Next.js /api/export/backup/rekap-status-akhir.
     *
     * Query params:
     *   - periode  : filter periode (opsional)
     *   - tahun    : filter tahun (opsional, diparsing ke periode yang cocok)
     */
    public function selfAssessment(Request $request): BinaryFileResponse
    {
        $periode = $request->query('periode') ?: null;

        // Jika tidak ada periode tapi ada tahun, cari periode yang mengandung tahun itu
        if (!$periode && $request->filled('tahun')) {
            $tahun   = $request->query('tahun');
            $periode = SelfAssessment::where('periode', 'like', "%{$tahun}%")
                ->orderBy('periode')
                ->value('periode');
        }

        $export   = new RekapStatusAkhirExport($periode);
        $filename = RekapStatusAkhirExport::filename($periode);

        return Excel::download($export, $filename);
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    /**
     * POST /admin/export/backfill-snapshots
     * Generate AssessmentBackup untuk semua grup (userId+assessmentId+periode)
     * yang semua indikatornya sudah VALIDATED tapi belum punya backup.
     *
     * Digunakan sekali untuk data lama yang divalidasi sebelum fitur snapshot otomatis.
     */
    public function backfillSnapshots(Request $request): RedirectResponse
    {
        // Ambil semua self-assessment VALIDATED
        $allValidated = SelfAssessment::where('status', 'VALIDATED')
            ->with([
                'submittedBy:id,name,kecamatan,kabupaten,kecamatan_id,kabupaten_id',
                'submittedBy.kecamatanWilayah:id,nama',
                'submittedBy.kabupatenWilayah:id,nama',
                'indicator:id,category_id,number,indicator,max_score',
                'indicator.category:id,assessment_id,code,name,order',
                'indicator.category.assessment:id,title,periode',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ])
            ->get();

        // Kelompokkan per (userId, assessmentId, periode)
        $groups = $allValidated->groupBy(function ($e) {
            $assessmentId = $e->indicator?->category?->assessment_id;
            return "{$e->submitted_by_id}:{$assessmentId}:{$e->periode}";
        });

        $created = 0;
        $skipped = 0;

        foreach ($groups as $key => $entries) {
            $first        = $entries->first();
            $assessmentId = $first->indicator?->category?->assessment_id;
            $periode      = $first->periode;
            $user         = $first->submittedBy;

            if (!$assessmentId || !$user) { $skipped++; continue; }

            $kecamatanNama = $user->kecamatanWilayah?->nama ?? $user->kecamatan ?? $user->name ?? '';
            $kabupatenNama = $user->kabupatenWilayah?->nama ?? $user->kabupaten ?? '';

            if (!$kecamatanNama) { $skipped++; continue; }

            // Ambil assessment dengan semua kategori+indikator
            $assessment = Assessment::with([
                'categories' => fn ($q) => $q->orderBy('order'),
                'categories.indicators' => fn ($q) => $q->orderBy('number'),
            ])->find($assessmentId);

            if (!$assessment) { $skipped++; continue; }

            // Cek apakah SEMUA indikator assessment ini sudah ada di group
            $totalIndicatorsInAssessment = $assessment->categories
                ->flatMap(fn ($c) => $c->indicators)
                ->count();

            if ($entries->count() < $totalIndicatorsInAssessment) {
                // Belum semua indikator tervalidasi — skip
                $skipped++;
                continue;
            }

            $entriesByIndicator = $entries->keyBy('indicator_id');

            $categoriesSnapshot        = [];
            $totalScore                = 0;
            $maxPossibleTotal          = 0;
            $categoryScoresForWeighted = [];

            foreach ($assessment->categories as $cat) {
                $catScore = 0;
                $catMax   = 0;
                $indicatorsSnapshot = [];

                foreach ($cat->indicators as $ind) {
                    $entry          = $entriesByIndicator->get($ind->id);
                    $rawScore       = $entry?->score ?? 0;
                    $validatedScore = $entry?->validations->first()?->validated_score ?? null;
                    $effectiveScore = $validatedScore ?? $rawScore;

                    $catScore += $effectiveScore;
                    $catMax   += $ind->max_score;

                    $indicatorsSnapshot[] = [
                        'number'         => $ind->number,
                        'indicator'      => $ind->indicator,
                        'maxScore'       => $ind->max_score,
                        'description'    => $entry?->description ?? '',
                        'supportingDoc'  => $entry?->supporting_doc ?? null,
                        'score'          => $rawScore,
                        'validatedScore' => $validatedScore,
                        'effectiveScore' => $effectiveScore,
                    ];
                }

                $catKlasifikasi = Scoring::getKlasifikasiPerKategori($cat->code, $catScore);

                $categoriesSnapshot[] = [
                    'code'        => $cat->code,
                    'name'        => $cat->name,
                    'order'       => $cat->order,
                    'totalScore'  => $catScore,
                    'maxScore'    => $catMax,
                    'klasifikasi' => $catKlasifikasi,
                    'indicators'  => $indicatorsSnapshot,
                ];

                $categoryScoresForWeighted[] = ['code' => $cat->code, 'score' => $catScore, 'maxScore' => $catMax];

                $totalScore       += $catScore;
                $maxPossibleTotal += $catMax;
            }

            $statusAkhir = Scoring::getStatusAkhir($totalScore, $maxPossibleTotal, $categoryScoresForWeighted);

            preg_match('/(\d{4})/', $periode, $m);
            $tahun = isset($m[1]) ? (int) $m[1] : null;

            $snapshot = [
                'assessmentTitle'  => $assessment->title,
                'periode'          => $periode,
                'tahun'            => $tahun,
                'kabupaten'        => $kabupatenNama,
                'kecamatan'        => $kecamatanNama,
                'categories'       => $categoriesSnapshot,
                'totalScore'       => $totalScore,
                'maxPossibleTotal' => $maxPossibleTotal,
                'statusAkhir'      => $statusAkhir,
            ];

            $existingVersion = AssessmentBackup::where('assessment_title', $assessment->title)
                ->where('periode', $periode)
                ->where('kecamatan', $kecamatanNama)
                ->max('version_number') ?? 0;

            AssessmentBackup::updateOrCreate(
                [
                    'assessment_title' => $assessment->title,
                    'periode'          => $periode,
                    'kecamatan'        => $kecamatanNama,
                    'version_number'   => max(1, $existingVersion),
                ],
                [
                    'tahun'              => $tahun,
                    'kabupaten'          => $kabupatenNama,
                    'total_score'        => $totalScore,
                    'max_possible_total' => $maxPossibleTotal,
                    'status_akhir'       => $statusAkhir,
                    'snapshot'           => $snapshot,
                ]
            );

            $created++;
        }

        return redirect()
            ->route('admin.export.index')
            ->with('success', "Backfill selesai: {$created} snapshot dibuat/diperbarui, {$skipped} dilewati (belum semua indikator tervalidasi).");
    }

    private function extractYear(string $periode): ?int
    {
        preg_match_all('/\d{4}/', $periode, $matches);
        if (empty($matches[0])) return null;
        $year = (int) end($matches[0]);
        return ($year >= 2000 && $year <= 2100) ? $year : null;
    }
}
