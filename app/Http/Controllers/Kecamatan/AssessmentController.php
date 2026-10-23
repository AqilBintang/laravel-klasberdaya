<?php

namespace App\Http\Controllers\Kecamatan;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentCategory;
use App\Models\AssessmentIndicator;
use App\Models\AssessmentRubric;
use App\Models\AssessmentVersion;
use App\Models\IndicatorChange;
use App\Models\SelfAssessment;
use App\Models\UserAssessmentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    /**
     * GET /kecamatan/assessment
     * Daftar assessment yang tersedia untuk kecamatan.
     * Migrasi dari: (kecamatan)/kecamatan/assessment/page.tsx
     *
     * Skenario update:
     *  - Assessment REVISION + user NOT_STARTED (belum punya jawaban) → blocked (tidak bisa diklik)
     *  - Assessment REVISION + user IN_PROGRESS/SUBMITTED/dst → tetap bisa diklik + notifikasi update
     *  - HAS_UPDATE / NEEDS_REVISION dari user_assessment_status → notifikasi indikator baru
     */
    public function index(): View
    {
        $userId = auth()->id();

        // Hanya PUBLISHED dan REVISION yang tampil untuk kecamatan
        $assessments = Assessment::with([
            'categories' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
            'categories.indicators' => fn ($q) => $q->where('is_active', true)->orderBy('number'),
        ])
            ->whereIn('status', ['PUBLISHED', 'REVISION'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'description', 'periode', 'status']);

        if ($assessments->isEmpty()) {
            return view('kecamatan.assessment.index', [
                'assessments'        => collect(),
                'entriesByAssessment' => [],
                'needsRevisionSet'   => [],
                'hasUpdateSet'       => [],
                'blockedSet'         => [],
            ]);
        }

        $assessmentIds = $assessments->pluck('id');

        // Status pengisian user per assessment (sumber kebenaran utama)
        $statuses = UserAssessmentStatus::where('user_id', $userId)
            ->whereIn('assessment_id', $assessmentIds)
            ->get()
            ->keyBy('assessment_id');

        // Versi current per assessment (pakai current_version dari user_assessment_status
        // jika ada, jika tidak fallback ke assessment.current_version dari model)
        $currentVersionByAssessment = $assessments->pluck('current_version', 'id')->toArray();
        foreach ($statuses as $assessmentId => $st) {
            // current_version di UserAssessmentStatus = versi yang user sedang isi
            $currentVersionByAssessment[$assessmentId] = $st->current_version;
        }

        // Entri jawaban user per assessment dengan info versi dan validasi terbaru
        // Butuh versi indikator dan status validasi terakhir untuk deteksi validator feedback
        $entries = SelfAssessment::where('submitted_by_id', $userId)
            ->whereHas('indicator', fn ($q) => $q->whereIn('assessment_id', $assessmentIds))
            ->with([
                'indicator.category:id,assessment_id',
                'indicator.version:id,version_number',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ])
            ->get(['id', 'indicator_id', 'status', 'submitted_by_id']);

        $entriesByAssessment = [];
        $userProgressAssessmentIds = [];

        foreach ($entries as $e) {
            $assessmentId = $e->indicator->category->assessment_id ?? null;
            if ($assessmentId === null) continue;
            $entriesByAssessment[$assessmentId][] = ['status' => $e->status];
            $userProgressAssessmentIds[$assessmentId] = true;
        }

        // ── validatorFeedbackSet ──────────────────────────────────────────────
        // Migrasi dari NextJS: entri dengan validasi terbaru REJECTED/REVISION_NEEDED
        // pada versi yang sedang aktif (bukan versi lama), dan assessment belum HAS_UPDATE
        // (HAS_UPDATE sudah punya penanganan sendiri).
        $hasUpdateTmp = [];
        foreach ($statuses as $aid => $st) {
            if ($st->status === 'HAS_UPDATE' || $st->latest_version > $st->current_version) {
                $hasUpdateTmp[$aid] = true;
            }
        }

        $validatorFeedbackSet = [];
        foreach ($entries as $e) {
            $assessmentId = $e->indicator->category->assessment_id ?? null;
            if ($assessmentId === null) continue;

            // Skip jika assessment sudah masuk HAS_UPDATE (berbeda konteks)
            if (isset($hasUpdateTmp[$assessmentId])) continue;

            $latestValidation = $e->validations->first();
            if (!$latestValidation) continue;

            // Hanya tandai jika validasi berasal dari versi yang sedang aktif
            $entryVersionNumber = $e->indicator->version->version_number ?? 0;
            $currentVersion     = $currentVersionByAssessment[$assessmentId] ?? 0;
            if ($entryVersionNumber !== $currentVersion) continue;

            if (in_array($latestValidation->status, ['REJECTED', 'REVISION_NEEDED'], true)) {
                $validatorFeedbackSet[$assessmentId] = true;
            }
        }

        $needsRevisionSet = [];
        $hasUpdateSet     = [];
        $blockedSet       = [];

        foreach ($assessments as $a) {
            /** @var \App\Models\UserAssessmentStatus|null $st */
            $st = $statuses->get($a->id);

            // REVISION + belum pernah mulai sama sekali (tidak ada status atau status NOT_STARTED
            // dan tidak punya jawaban) → blocked
            if ($a->status === 'REVISION') {
                $userHasStarted = $st && $st->status !== 'NOT_STARTED';
                $userHasAnswers = isset($entriesByAssessment[$a->id]);

                if (!$userHasStarted && !$userHasAnswers) {
                    $blockedSet[] = $a->id;
                    continue;
                }
            }

            // Prioritaskan status dari DB (user_assessment_status) untuk notifikasi
            $userStatusNeedsRevision = false;
            $userStatusHasUpdate      = false;

            if ($st) {
                if ($st->status === 'NEEDS_REVISION') {
                    $userStatusNeedsRevision = true;
                } elseif (
                    $st->status === 'HAS_UPDATE'
                    || ($st->latest_version > $st->current_version)
                ) {
                    $userStatusHasUpdate = true;
                }
            }

            // Gabungkan dengan validatorFeedback (REJECTED/REVISION_NEEDED dari tim teknis).
            // Sama dengan NextJS: needsRevision = userStatus OR validatorFeedback.
            $hasValidatorFeedback = isset($validatorFeedbackSet[$a->id]);

            if ($userStatusNeedsRevision || $hasValidatorFeedback) {
                $needsRevisionSet[] = $a->id;
            } elseif ($userStatusHasUpdate) {
                $hasUpdateSet[] = $a->id;
            }
        }

        return view('kecamatan.assessment.index', compact(
            'assessments',
            'entriesByAssessment',
            'needsRevisionSet',
            'hasUpdateSet',
            'blockedSet',
            'validatorFeedbackSet'
        ));
    }

    /**
     * GET /kecamatan/assessment/{id}
     * Form pengisian self-assessment.
     * Migrasi dari: (kecamatan)/kecamatan/assessment/[id]/page.tsx
     *
     * Skenario update:
     *  - REVISION + user NOT_STARTED dan belum punya jawaban → layar blok "Sedang Diperbarui"
     *  - REVISION + user sudah mulai (IN_PROGRESS/SUBMITTED/dst) → banner peringatan, tetap bisa lanjut
     *  - HAS_UPDATE → load versi terbaru, tandai indikator baru
     *  - NEEDS_REVISION → load versi terbaru, tandai indikator yang perlu diisi ulang
     */
    public function show(string $id): View
    {
        $userId = auth()->id();

        $assessment = Assessment::findOrFail($id);

        // Hanya PUBLISHED atau REVISION yang bisa diakses
        if (!in_array($assessment->status, ['PUBLISHED', 'REVISION'])) {
            abort(404);
        }

        // Ambil / buat user_assessment_status (sumber kebenaran untuk blocked)
        $userStatus = UserAssessmentStatus::where('user_id', $userId)
            ->where('assessment_id', $id)
            ->first();

        // REVISION gate: konsisten dengan index() — cek status DB dan keberadaan jawaban
        if ($assessment->status === 'REVISION') {
            $userHasStarted = $userStatus && $userStatus->status !== 'NOT_STARTED';
            $userHasAnswers = SelfAssessment::where('submitted_by_id', $userId)
                ->where('periode', $assessment->periode)
                ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $id))
                ->exists();

            if (!$userHasStarted && !$userHasAnswers) {
                return view('kecamatan.assessment.show', [
                    'id'         => $id,
                    'blocked'    => true,
                    'assessment' => $assessment,
                ]);
            }
        }

        // Buat row status jika belum ada (user pertama kali buka assessment PUBLISHED)
        if (!$userStatus) {
            $userStatus = UserAssessmentStatus::create([
                'user_id'          => $userId,
                'assessment_id'    => $id,
                'current_version'  => $assessment->current_version,
                'latest_version'   => $assessment->current_version,
                'status'           => 'NOT_STARTED',
                'last_viewed_at'   => now(),
            ]);
        } else {
            // Update last_viewed_at
            $userStatus->update(['last_viewed_at' => now()]);
        }

        $needsRevision = $userStatus->status === 'NEEDS_REVISION';
        $hasUpdate     = $userStatus->status === 'HAS_UPDATE'
                         || $userStatus->latest_version > $userStatus->current_version;

        // Tentukan versi yang harus di-load
        $versionToLoad = $userStatus->current_version;
        if ($hasUpdate || $needsRevision) {
            $versionToLoad = $userStatus->latest_version;
        }

        // Fetch version record
        $versionRecord = AssessmentVersion::where('assessment_id', $id)
            ->where('version_number', $versionToLoad)
            ->first();

        if (!$versionRecord) {
            // Fallback ke v1
            $versionRecord = AssessmentVersion::where('assessment_id', $id)
                ->where('version_number', 1)
                ->firstOrFail();
        }
        $versionId = $versionRecord->id;

        // Load categories + indicators untuk versi target
        $categories = AssessmentCategory::where('assessment_id', $id)
            ->where('version_id', $versionId)
            ->orderBy('order')
            ->with(['indicators' => fn ($q) => $q->orderBy('number')])
            ->get();

        $assessment->setRelation('categories', $categories);

        // Load SEMUA jawaban user (semua versi) dengan info indikator untuk logical mapping
        $existingEntries = SelfAssessment::where('submitted_by_id', $userId)
            ->where('periode', $assessment->periode)
            ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $id))
            ->with([
                'indicator.category:id,assessment_id,code',
                'indicator.version:id,version_number',
                'validations' => fn ($q) => $q->orderByDesc('validated_at'),
            ])
            ->orderByDesc('updated_at')
            ->get();

        // Hitung indikator baru (indikator tanpa jawaban by logical key)
        // Berlaku untuk HAS_UPDATE dan NEEDS_REVISION (keduanya ada indikator baru)
        $newIndicatorIds = [];
        if ($hasUpdate || $needsRevision) {
            // Build answer map by logical key: assessmentId:categoryCode:indicatorNumber
            $answerMap = [];
            foreach ($existingEntries as $entry) {
                $catCode   = $entry->indicator->category->code ?? '';
                $indNumber = $entry->indicator->number;
                $key       = "{$id}:{$catCode}:{$indNumber}";
                if (!isset($answerMap[$key])) {
                    $answerMap[$key] = $entry;
                }
            }

            foreach ($categories as $cat) {
                foreach ($cat->indicators as $ind) {
                    $key = "{$id}:{$cat->code}:{$ind->number}";
                    if (!isset($answerMap[$key])) {
                        $newIndicatorIds[] = $ind->id;
                    }
                }
            }
        }

        // Untuk NEEDS_REVISION: tambahkan indikator yang berubah dari indicator_changes
        if ($needsRevision) {
            $latestVersion = AssessmentVersion::where('assessment_id', $id)
                ->where('version_number', $userStatus->latest_version)
                ->first();

            if ($latestVersion) {
                $changedIds = IndicatorChange::where('version_id', $latestVersion->id)
                    ->where('requires_resubmit', true)
                    ->whereNotNull('indicator_id')
                    ->pluck('indicator_id')
                    ->toArray();
                $newIndicatorIds = array_values(array_unique(array_merge($newIndicatorIds, $changedIds)));
            }
        }

        // Indikator dengan catatan revisi/ditolak dari validator (hanya versi yang sedang dibuka)
        $validatorRevisionIndicatorIds = [];
        $validatorRejectedIndicatorIds = [];
        foreach ($existingEntries as $entry) {
            if ((int) $entry->indicator->version_id !== $versionId) {
                continue;
            }
            $latestValidation = $entry->validations->first();
            if (!$latestValidation) {
                continue;
            }
            if ($latestValidation->status === 'REVISION_NEEDED') {
                $validatorRevisionIndicatorIds[] = $entry->indicator_id;
            } elseif ($latestValidation->status === 'REJECTED') {
                $validatorRejectedIndicatorIds[] = $entry->indicator_id;
            }
        }

        // Load rubrik panduan untuk assessment ini (jika ada)
        $rubric = AssessmentRubric::with([
            'items',
            'items.indicator:id,category_id,number,indicator',
            'items.indicator.category:id,assessment_id,code,name,order',
        ])
            ->where('assessment_id', $id)
            ->first();

        return view('kecamatan.assessment.show', compact(
            'id',
            'assessment',
            'existingEntries',
            'needsRevision',
            'hasUpdate',
            'newIndicatorIds',
            'validatorRevisionIndicatorIds',
            'validatorRejectedIndicatorIds',
            'rubric'
        ));
    }

    /**
     * POST /kecamatan/assessment/{id}/draft
     * Auto-save draft jawaban.
     */
    public function saveDraft(Request $request, string $id): JsonResponse
    {
        $userId = auth()->id();
        $assessment = Assessment::findOrFail($id);

        if (!in_array($assessment->status, ['PUBLISHED', 'REVISION'])) {
            return response()->json(['message' => 'Assessment tidak dapat diisi saat ini.'], 423);
        }

        $entries = $request->input('entries', []);
        if (empty($entries)) {
            return response()->json(['message' => 'Tidak ada data untuk disimpan.']);
        }

        DB::transaction(function () use ($entries, $userId, $assessment, $id) {
            foreach ($entries as $indicatorId => $data) {
                $description = $data['description'] ?? '';
                $score = (int) ($data['score'] ?? 0);

                SelfAssessment::updateOrCreate(
                    [
                        'indicator_id' => $indicatorId,
                        'submitted_by_id' => $userId,
                        'periode' => $assessment->periode,
                    ],
                    [
                        'description' => $description,
                        'score' => $score,
                        'status' => 'DRAFT',
                    ]
                );
            }

            // Update user status ke IN_PROGRESS
            UserAssessmentStatus::updateOrCreate(
                ['user_id' => $userId, 'assessment_id' => $id],
                [
                    'status' => 'IN_PROGRESS',
                    'last_activity_at' => now(),
                ]
            );
        });

        return response()->json(['message' => 'Draft tersimpan.']);
    }

    /**
     * POST /kecamatan/assessment/{id}
     * Submit self-assessment untuk divalidasi.
     */
    public function submit(Request $request, string $id): RedirectResponse
    {
        $userId = auth()->id();
        $assessment = Assessment::findOrFail($id);

        if (!in_array($assessment->status, ['PUBLISHED', 'REVISION'])) {
            return redirect()->route('kecamatan.assessment.index')
                ->with('error', 'Assessment tidak dapat dikirim saat ini.');
        }

        $entries = $request->input('entries', []);
        if (empty($entries)) {
            return back()->with('error', 'Tidak ada jawaban untuk dikirim.');
        }

        // Validasi: semua indikator aktif wajib diisi (description tidak boleh kosong)
        $validationError = $this->validateAllIndicatorsFilled($assessment, $entries, $userId);
        if ($validationError) {
            return back()->with('error', $validationError)->withInput();
        }

        DB::transaction(function () use ($entries, $userId, $assessment, $id) {
            foreach ($entries as $indicatorId => $data) {
                $description = $data['description'] ?? '';
                $score = (int) ($data['score'] ?? 0);
                $supportingDoc = $data['supporting_doc'] ?? null;

                SelfAssessment::updateOrCreate(
                    [
                        'indicator_id' => $indicatorId,
                        'submitted_by_id' => $userId,
                        'periode' => $assessment->periode,
                    ],
                    [
                        'description' => $description,
                        'score' => $score,
                        'supporting_doc' => $supportingDoc,
                        'status' => 'SUBMITTED',
                        'submitted_at' => now(),
                    ]
                );
            }

            // Update user status ke SUBMITTED, sinkronkan versi
            $userStatus = UserAssessmentStatus::firstOrCreate(
                ['user_id' => $userId, 'assessment_id' => $id],
                [
                    'current_version' => $assessment->current_version,
                    'latest_version' => $assessment->current_version,
                ]
            );
            $userStatus->update([
                'status' => 'SUBMITTED',
                'current_version' => $assessment->current_version,
                'latest_version' => $assessment->current_version,
                'last_activity_at' => now(),
            ]);
        });

        return redirect()->route('kecamatan.assessment.index')
            ->with('success', 'Assessment berhasil dikirim untuk divalidasi.');
    }

    /**
     * PUT /kecamatan/assessment/{id}
     * Resubmit setelah revisi dari validator.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $userId = auth()->id();
        $assessment = Assessment::findOrFail($id);

        if (!in_array($assessment->status, ['PUBLISHED', 'REVISION'])) {
            return redirect()->route('kecamatan.assessment.index')
                ->with('error', 'Assessment tidak dapat diperbarui saat ini.');
        }

        $entries = $request->input('entries', []);
        if (empty($entries)) {
            return back()->with('error', 'Tidak ada jawaban untuk dikirim.');
        }

        // Validasi: semua indikator aktif wajib diisi (description tidak boleh kosong)
        $validationError = $this->validateAllIndicatorsFilled($assessment, $entries, $userId);
        if ($validationError) {
            return back()->with('error', $validationError)->withInput();
        }

        DB::transaction(function () use ($entries, $userId, $assessment, $id) {
            foreach ($entries as $indicatorId => $data) {
                $description = $data['description'] ?? '';
                $score = (int) ($data['score'] ?? 0);
                $supportingDoc = $data['supporting_doc'] ?? null;

                SelfAssessment::updateOrCreate(
                    [
                        'indicator_id' => $indicatorId,
                        'submitted_by_id' => $userId,
                        'periode' => $assessment->periode,
                    ],
                    [
                        'description' => $description,
                        'score' => $score,
                        'supporting_doc' => $supportingDoc,
                        'status' => 'SUBMITTED',
                        'submitted_at' => now(),
                    ]
                );
            }

            // Update user status ke RESUBMITTED, naikkan versi ke latest
            $userStatus = UserAssessmentStatus::firstOrCreate(
                ['user_id' => $userId, 'assessment_id' => $id],
                [
                    'current_version' => $assessment->current_version,
                    'latest_version' => $assessment->current_version,
                ]
            );
            $userStatus->update([
                'status' => 'RESUBMITTED',
                'current_version' => $userStatus->latest_version,
                'last_activity_at' => now(),
            ]);
        });

        return redirect()->route('kecamatan.assessment.index')
            ->with('success', 'Revisi assessment berhasil dikirim ulang.');
    }

    /**
     * Validasi bahwa semua indikator aktif sudah diisi (description tidak kosong).
     * Indikator yang sudah VALIDATED dan tidak dalam status revisi dikecualikan.
     *
     * @return string|null Pesan error, atau null jika valid
     */
    private function validateAllIndicatorsFilled(Assessment $assessment, array $entries, int $userId): ?string
    {
        // Kumpulkan semua indicator_id aktif untuk assessment ini
        $activeIndicatorIds = AssessmentIndicator::whereHas('category', fn ($q) =>
            $q->where('assessment_id', $assessment->id)->where('is_active', true)
        )
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        // Indikator yang sudah VALIDATED (dikecualikan dari kewajiban isi ulang)
        $validatedIndicatorIds = SelfAssessment::where('submitted_by_id', $userId)
            ->where('periode', $assessment->periode)
            ->where('status', 'VALIDATED')
            ->whereIn('indicator_id', $activeIndicatorIds)
            ->pluck('indicator_id')
            ->toArray();

        $missingIndicators = [];

        foreach ($activeIndicatorIds as $indicatorId) {
            // Lewati indikator yang sudah tervalidasi
            if (in_array($indicatorId, $validatedIndicatorIds, true)) {
                continue;
            }

            $description   = trim($entries[$indicatorId]['description'] ?? '');
            $score         = $entries[$indicatorId]['score'] ?? '';
            $supportingDoc = $entries[$indicatorId]['supporting_doc'] ?? '';

            // supporting_doc wajib: harus diisi dengan URL atau nilai 'NO_DOC'
            $docMissing = ($supportingDoc === '');

            if ($description === '' || $score === '' || $docMissing) {
                $missingIndicators[] = $indicatorId;
            }
        }

        if (empty($missingIndicators)) {
            return null;
        }

        $count = count($missingIndicators);
        return "Masih ada {$count} indikator yang belum lengkap. Deskripsi, skor mandiri, dan dokumen pendukung wajib diisi sebelum submit.";
    }
}
