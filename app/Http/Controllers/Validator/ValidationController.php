<?php

namespace App\Http\Controllers\Validator;

use App\Helpers\Scoring;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentBackup;
use App\Models\AssessmentValidation;
use App\Models\SelfAssessment;
use App\Models\UserAssessmentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ValidationController extends Controller
{
    /**
     * GET /validator/validasi
     * Daftar kecamatan yang punya self-assessment SUBMITTED.
     * Dikelompokkan per kecamatan → per assessment → ringkasan indikator.
     *
     * Data yang dikirim ke view:
     *   $groups  — array of:
     *     userId, kecamatan, kabupaten, assessmentId, assessmentTitle,
     *     periode, totalIndicators, pendingCount, overallStatus,
     *     representativeId (id SelfAssessment pertama, untuk route show),
     *     isOutdated (submitterAssessmentStatus === NEEDS_REVISION)
     *   $totalPending — total indikator SUBMITTED di semua kecamatan
     */
    public function index(): View
    {
        // Ambil semua self-assessment SUBMITTED dengan relasi yang dibutuhkan
        $entries = SelfAssessment::where('status', 'SUBMITTED')
            ->with([
                'submittedBy:id,name,kecamatan,kabupaten,kecamatan_id,kabupaten_id',
                'submittedBy.kecamatanWilayah:id,nama',
                'submittedBy.kabupatenWilayah:id,nama',
                'indicator.category.assessment:id,title,periode',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ])
            ->orderBy('submitted_at', 'asc')
            ->get();

        // Group per (userId, assessmentId, periode)
        $grouped = $entries->groupBy(function ($s) {
            $assessmentId = $s->indicator?->category?->assessment?->id;
            return "{$s->submitted_by_id}:{$assessmentId}:{$s->periode}";
        });

        // Ambil UserAssessmentStatus untuk deteksi NEEDS_REVISION
        $pairs = $grouped->map(function ($group) {
            $first = $group->first();
            return [
                'userId' => $first->submitted_by_id,
                'assessmentId' => $first->indicator?->category?->assessment?->id,
            ];
        })->values()->unique(fn ($p) => "{$p['userId']}:{$p['assessmentId']}")->toArray();

        $statusMap = [];
        if (!empty($pairs)) {
            $conditions = array_map(fn ($p) =>
                "(user_id = {$p['userId']} AND assessment_id = {$p['assessmentId']})",
                array_filter($pairs, fn ($p) => $p['assessmentId'] !== null)
            );
            if (!empty($conditions)) {
                $statuses = UserAssessmentStatus::whereRaw('(' . implode(' OR ', $conditions) . ')')->get();
                foreach ($statuses as $st) {
                    $statusMap["{$st->user_id}:{$st->assessment_id}"] = $st->status;
                }
            }
        }

        $groups = [];
        foreach ($grouped as $key => $group) {
            $first = $group->first();
            $assessment = $first->indicator?->category?->assessment;
            $user       = $first->submittedBy;

            // Nama kecamatan: pakai wilayah jika ada, fallback ke kolom string lama
            $kecamatan = $user?->kecamatanWilayah?->nama ?? $user?->kecamatan ?? $user?->name;
            $kabupaten = $user?->kabupatenWilayah?->nama ?? $user?->kabupaten;

            $assessmentId = $assessment?->id;
            $isOutdated   = ($statusMap["{$first->submitted_by_id}:{$assessmentId}"] ?? null) === 'NEEDS_REVISION';

            // Overall status: VALIDATED jika semua sudah punya validasi APPROVED
            $allValidated = $group->every(fn ($s) =>
                $s->validations->first()?->status === 'APPROVED'
            );
            $anyRejected  = $group->contains(fn ($s) =>
                $s->validations->first()?->status === 'REJECTED'
            );
            $overallStatus = $allValidated ? 'VALIDATED' : ($anyRejected ? 'REJECTED' : 'SUBMITTED');

            $groups[] = [
                'key'               => $key,
                'userId'            => $first->submitted_by_id,
                'representativeId'  => $first->id,
                'kecamatan'         => $kecamatan,
                'kabupaten'         => $kabupaten,
                'assessmentId'      => $assessmentId,
                'assessmentTitle'   => $assessment?->title ?? '—',
                'periode'           => $first->periode,
                'totalIndicators'   => $group->count(),
                'pendingCount'      => $group->where('status', 'SUBMITTED')->count(),
                'overallStatus'     => $overallStatus,
                'isOutdated'        => $isOutdated,
                'submittedAt'       => $first->submitted_at,
            ];
        }

        // Urutkan: kecamatan asc
        usort($groups, fn ($a, $b) => strcmp($a['kecamatan'] ?? '', $b['kecamatan'] ?? ''));

        $totalPending = collect($groups)->sum('pendingCount');

        return view('validator.validasi.index', compact('groups', 'totalPending'));
    }

    /**
     * GET /validator/validasi/{id}
     * Detail satu submission — hanya indikator yang PERLU divalidasi (status SUBMITTED).
     *
     * Skenario 1: indikator yang sudah divalidasi (VALIDATED/REJECTED/DRAFT)
     *   tidak ditampilkan kembali.
     * Skenario 2: jika kecamatan melakukan revisi dan resubmit, hanya indikator
     *   yang direvisi (kembali ke SUBMITTED) yang muncul. Indikator yang sudah
     *   divalidasi sebelumnya tidak perlu ditampilkan lagi.
     *
     * $id = id salah satu SelfAssessment (representative untuk group)
     */
    public function show(string $id): View
    {
        // Anchor ke satu self-assessment untuk mendapatkan konteks (user + assessment + periode)
        $selfAssessment = SelfAssessment::with([
            'submittedBy:id,name,kecamatan,kabupaten,kecamatan_id,kabupaten_id',
            'submittedBy.kecamatanWilayah:id,nama',
            'submittedBy.kabupatenWilayah:id,nama',
            'indicator.category.assessment:id,title,periode',
            'validations' => fn ($q) => $q->orderByDesc('validated_at'),
        ])->findOrFail($id);

        $assessmentId = $selfAssessment->indicator?->category?->assessment?->id;

        // ── Hitung ringkasan status semua indikator kecamatan ini ───────────
        $allEntries = SelfAssessment::where('submitted_by_id', $selfAssessment->submitted_by_id)
            ->where('periode', $selfAssessment->periode)
            ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
            ->get(['id', 'status']);

        $totalAll       = $allEntries->count();
        $totalValidated = $allEntries->where('status', 'VALIDATED')->count();
        $totalRejected  = $allEntries->where('status', 'REJECTED')->count();
        $totalDraft     = $allEntries->where('status', 'DRAFT')->count();
        $totalPending   = $allEntries->where('status', 'SUBMITTED')->count();

        // ── Entries yang PERLU divalidasi (status SUBMITTED saja) ──────────
        // Indikator yang sudah divalidasi/ditolak/draft tidak ditampilkan.
        // Saat kecamatan resubmit setelah revisi, hanya indikator yang direvisi
        // yang kembali ke SUBMITTED → hanya itu yang muncul.
        $entries = SelfAssessment::where('submitted_by_id', $selfAssessment->submitted_by_id)
            ->where('periode', $selfAssessment->periode)
            ->where('status', 'SUBMITTED')
            ->whereHas('indicator', fn ($q) =>
                $q->where('assessment_id', $assessmentId)
            )
            ->with([
                'indicator:id,category_id,number,indicator,max_score',
                'indicator.category:id,code,name,order',
                'validations' => fn ($q) => $q->orderByDesc('validated_at'),
            ])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->indicator->category->order ?? 0) <=> ($b->indicator->category->order ?? 0),
                fn ($a, $b) => ($a->indicator->number ?? 0) <=> ($b->indicator->number ?? 0),
            ]);

        $summary = [
            'totalAll'       => $totalAll,
            'totalValidated' => $totalValidated,
            'totalRejected'  => $totalRejected,
            'totalDraft'     => $totalDraft,
            'totalPending'   => $totalPending,
        ];

        return view('validator.validasi.show', compact('id', 'selfAssessment', 'entries', 'summary'));
    }

    /**
     * POST /validator/validasi/{id}
     * Simpan validasi untuk satu self-assessment.
     * $id = id SelfAssessment yang divalidasi.
     */
    public function store(Request $request, string $id): RedirectResponse
    {
        $selfAssessment = SelfAssessment::findOrFail($id);

        $request->validate([
            'validated_score' => ['required', 'integer', 'min:1', 'max:4'],
            'status'          => ['required', Rule::in(['APPROVED', 'REJECTED', 'REVISION_NEEDED'])],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ], [
            'validated_score.required' => 'Skor wajib diisi.',
            'validated_score.min'      => 'Skor minimal 1.',
            'validated_score.max'      => 'Skor maksimal 4.',
            'status.required'          => 'Keputusan wajib dipilih.',
            'status.in'                => 'Keputusan tidak valid.',
        ]);

        DB::transaction(function () use ($request, $selfAssessment) {
            // Simpan validasi
            AssessmentValidation::create([
                'self_assessment_id' => $selfAssessment->id,
                'validator_id'       => auth()->id(),
                'status'             => $request->status,
                'validated_score'    => $request->validated_score,
                'notes'              => $request->notes ?: null,
                'validated_at'       => now(),
            ]);

            // Update status self_assessment
            $newStatus = match ($request->status) {
                'APPROVED'        => 'VALIDATED',
                'REJECTED'        => 'REJECTED',
                'REVISION_NEEDED' => 'DRAFT',  // kecamatan perlu edit ulang
                default           => 'SUBMITTED',
            };
            $selfAssessment->update(['status' => $newStatus]);
        });

        // Generate/update snapshot jika semua indikator sudah VALIDATED
        $this->maybeGenerateBackupSnapshot($selfAssessment);

        return redirect()
            ->route('validator.validasi.show', $id)
            ->with('success', 'Validasi berhasil disimpan.');
    }

    /**
     * PUT /validator/validasi/{id}
     * Update validasi yang sudah ada (ubah keputusan / skor / catatan).
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $selfAssessment = SelfAssessment::findOrFail($id);

        $request->validate([
            'validated_score' => ['required', 'integer', 'min:1', 'max:4'],
            'status'          => ['required', Rule::in(['APPROVED', 'REJECTED', 'REVISION_NEEDED'])],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $selfAssessment) {
            // Tambah validasi baru (append, bukan overwrite — history terjaga)
            AssessmentValidation::create([
                'self_assessment_id' => $selfAssessment->id,
                'validator_id'       => auth()->id(),
                'status'             => $request->status,
                'validated_score'    => $request->validated_score,
                'notes'              => $request->notes ?: null,
                'validated_at'       => now(),
            ]);

            $newStatus = match ($request->status) {
                'APPROVED'        => 'VALIDATED',
                'REJECTED'        => 'REJECTED',
                'REVISION_NEEDED' => 'DRAFT',
                default           => 'SUBMITTED',
            };
            $selfAssessment->update(['status' => $newStatus]);
        });

        // Generate/update snapshot jika semua indikator sudah VALIDATED
        $this->maybeGenerateBackupSnapshot($selfAssessment);

        return redirect()
            ->route('validator.validasi.show', $id)
            ->with('success', 'Validasi berhasil diperbarui.');
    }

    // ─────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────

    /**
     * Cek apakah semua indikator (user+assessment+periode) sudah VALIDATED.
     * Jika ya, buat atau update satu baris di assessment_backups.
     *
     * Dipanggil setelah setiap store()/update() validasi.
     */
    private function maybeGenerateBackupSnapshot(SelfAssessment $pivot): void
    {
        $userId    = $pivot->submitted_by_id;
        $periode   = $pivot->periode;

        // Cari assessmentId dari indikator pivot
        $assessmentId = $pivot->load('indicator.category.assessment')
            ->indicator?->category?->assessment_id
            ?? $pivot->indicator?->category?->assessment?->id;

        if (!$assessmentId) return;

        // Ambil SEMUA self-assessment user ini untuk assessment+periode ini
        $allEntries = SelfAssessment::where('submitted_by_id', $userId)
            ->where('periode', $periode)
            ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
            ->with([
                'indicator:id,category_id,number,indicator,max_score',
                'indicator.category:id,assessment_id,code,name,order',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ])
            ->get();

        // Hanya generate jika SEMUA indikator sudah VALIDATED (tidak ada SUBMITTED/DRAFT/REJECTED)
        $allValidated = $allEntries->isNotEmpty()
            && $allEntries->every(fn ($e) => $e->status === 'VALIDATED');

        if (!$allValidated) return;

        // ── Ambil data assessment & user ─────────────────────────────
        $assessment = Assessment::with([
            'categories' => fn ($q) => $q->orderBy('order'),
            'categories.indicators' => fn ($q) => $q->orderBy('number'),
        ])->find($assessmentId);

        if (!$assessment) return;

        $user = $pivot->submittedBy()->with(['kecamatanWilayah:id,nama', 'kabupatenWilayah:id,nama'])->first();

        $kecamatanNama = $user?->kecamatanWilayah?->nama ?? $user?->kecamatan ?? $user?->name ?? '';
        $kabupatenNama = $user?->kabupatenWilayah?->nama ?? $user?->kabupaten ?? '';

        // ── Bangun snapshot per kategori ─────────────────────────────
        $entriesByIndicator = $allEntries->keyBy('indicator_id');

        $categoriesSnapshot = [];
        $totalScore         = 0;
        $maxPossibleTotal   = 0;
        $categoryScoresForWeighted = [];

        foreach ($assessment->categories as $cat) {
            $catScore = 0;
            $catMax   = 0;
            $indicatorsSnapshot = [];

            foreach ($cat->indicators as $ind) {
                $entry         = $entriesByIndicator->get($ind->id);
                $rawScore      = $entry?->score ?? 0;
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

            $categoryScoresForWeighted[] = [
                'code'     => $cat->code,
                'score'    => $catScore,
                'maxScore' => $catMax,
            ];

            $totalScore       += $catScore;
            $maxPossibleTotal += $catMax;
        }

        $statusAkhir = Scoring::getStatusAkhir($totalScore, $maxPossibleTotal, $categoryScoresForWeighted);

        // Ekstrak tahun dari periode (ambil 4 digit pertama)
        preg_match('/(\d{4})/', $periode, $m);
        $tahun = $m[1] ? (int) $m[1] : null;

        // ── Versi snapshot (increment jika sudah ada) ─────────────────
        $existingVersion = AssessmentBackup::where('assessment_title', $assessment->title)
            ->where('periode', $periode)
            ->where('kecamatan', $kecamatanNama)
            ->max('version_number') ?? 0;

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

        AssessmentBackup::updateOrCreate(
            [
                'assessment_title' => $assessment->title,
                'periode'          => $periode,
                'kecamatan'        => $kecamatanNama,
                'version_number'   => max(1, $existingVersion),
            ],
            [
                'tahun'             => $tahun,
                'kabupaten'         => $kabupatenNama,
                'total_score'       => $totalScore,
                'max_possible_total' => $maxPossibleTotal,
                'status_akhir'      => $statusAkhir,
                'snapshot'          => $snapshot,
            ]
        );
    }
}
