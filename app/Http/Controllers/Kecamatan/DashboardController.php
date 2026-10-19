<?php

namespace App\Http\Controllers\Kecamatan;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\SelfAssessment;
use App\Models\UserAssessmentStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $userId = $user->id;

        // ─── Statistik self-assessment user ini ───────────────────────────
        $totalMenunggu   = SelfAssessment::where('submitted_by_id', $userId)
            ->where('status', 'SUBMITTED')
            ->count();

        $totalDivalidasi = SelfAssessment::where('submitted_by_id', $userId)
            ->where('status', 'VALIDATED')
            ->count();

        // ─── Assessment yang perlu tindakan kecamatan ─────────────────────
        // Hanya tampilkan assessment yang:
        //   - BELUM_DIISI (belum ada jawaban sama sekali)
        //   - NEEDS_REVISION (validator menolak/minta revisi, atau admin update template)
        //   - HAS_UPDATE (admin publish versi baru, ada indikator baru)
        //   - PARTIAL/DRAFT (diisi sebagian, belum submit)
        // Tidak menampilkan: SUBMITTED (menunggu validasi) dan VALIDATED (selesai)
        $actionableAssessments = $this->getActionableAssessments($userId);

        // ─── Nama lokasi kecamatan ─────────────────────────────────────────
        $kecamatanName = $user->kecamatanWilayah?->name ?? $user->kecamatan ?? null;
        $kabupatenName = $user->kabupatenWilayah?->name ?? $user->kabupaten ?? null;
        $location      = collect([$kecamatanName, $kabupatenName])->filter()->implode(', ');

        return view('kecamatan.dashboard', compact(
            'user',
            'location',
            'totalMenunggu',
            'totalDivalidasi',
            'actionableAssessments',
        ));
    }

    /**
     * Ambil assessment yang perlu tindakan kecamatan.
     * Logika sama dengan AssessmentController::index() tapi di-filter hanya yang actionable.
     */
    private function getActionableAssessments(int $userId): array
    {
        $assessments = Assessment::with([
            'categories' => fn ($q) => $q->where('is_active', true),
            'categories.indicators' => fn ($q) => $q->where('is_active', true),
        ])
            ->whereIn('status', ['PUBLISHED', 'REVISION'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'description', 'periode', 'status', 'current_version']);

        if ($assessments->isEmpty()) {
            return [];
        }

        $assessmentIds = $assessments->pluck('id');

        // User assessment status
        $statuses = UserAssessmentStatus::where('user_id', $userId)
            ->whereIn('assessment_id', $assessmentIds)
            ->get()
            ->keyBy('assessment_id');

        // Versi current per assessment
        $currentVersionByAssessment = $assessments->pluck('current_version', 'id')->toArray();
        foreach ($statuses as $aid => $st) {
            $currentVersionByAssessment[$aid] = $st->current_version;
        }

        // Entri jawaban user dengan validasi terbaru
        $entries = SelfAssessment::where('submitted_by_id', $userId)
            ->whereHas('indicator', fn ($q) => $q->whereIn('assessment_id', $assessmentIds))
            ->with([
                'indicator.category:id,assessment_id',
                'indicator.version:id,version_number',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ])
            ->get(['id', 'indicator_id', 'status', 'submitted_by_id']);

        $entriesByAssessment = [];
        foreach ($entries as $e) {
            $aid = $e->indicator->category->assessment_id ?? null;
            if ($aid === null) continue;
            $entriesByAssessment[$aid][] = ['status' => $e->status];
        }

        // validatorFeedbackSet (sama dengan AssessmentController::index)
        $hasUpdateTmp = [];
        foreach ($statuses as $aid => $st) {
            if ($st->status === 'HAS_UPDATE' || $st->latest_version > $st->current_version) {
                $hasUpdateTmp[$aid] = true;
            }
        }

        $validatorFeedbackSet = [];
        foreach ($entries as $e) {
            $aid = $e->indicator->category->assessment_id ?? null;
            if ($aid === null) continue;
            if (isset($hasUpdateTmp[$aid])) continue;

            $latestValidation = $e->validations->first();
            if (!$latestValidation) continue;

            $entryVersionNumber = $e->indicator->version->version_number ?? 0;
            $currentVersion     = $currentVersionByAssessment[$aid] ?? 0;
            if ($entryVersionNumber !== $currentVersion) continue;

            if (in_array($latestValidation->status, ['REJECTED', 'REVISION_NEEDED'], true)) {
                $validatorFeedbackSet[$aid] = true;
            }
        }

        // Bangun daftar assessment actionable
        $result = [];
        foreach ($assessments as $a) {
            $st = $statuses->get($a->id);
            $totalInd = $a->categories->sum(fn ($c) => $c->indicators->count());
            $entries  = $entriesByAssessment[$a->id] ?? [];
            $entryCount = count($entries);

            // Skip assessment yang sedang REVISION dan user belum pernah mulai (blocked)
            if ($a->status === 'REVISION') {
                $userHasStarted = $st && $st->status !== 'NOT_STARTED';
                $userHasAnswers = $entryCount > 0;
                if (!$userHasStarted && !$userHasAnswers) {
                    // Tampilkan sebagai "Sedang Diperbarui" — perlu info tapi tidak bisa diklik
                    $result[] = [
                        'id'             => $a->id,
                        'title'          => $a->title,
                        'description'    => $a->description,
                        'periode'        => $a->periode,
                        'status'         => 'UPDATING',
                        'totalInd'       => $totalInd,
                        'categoryCount'  => $a->categories->count(),
                        'entryCount'     => 0,
                        'message'        => 'Assessment sedang diperbarui admin. Coba beberapa saat lagi.',
                    ];
                    continue;
                }
            }

            // Derive status (sama dengan view index.blade.php)
            $needsRevision = ($st && $st->status === 'NEEDS_REVISION') || isset($validatorFeedbackSet[$a->id]);
            $hasUpdate     = $st && ($st->status === 'HAS_UPDATE' || $st->latest_version > $st->current_version);

            if ($needsRevision) {
                $derivedStatus = 'NEEDS_REVISION';
            } elseif ($hasUpdate) {
                $derivedStatus = 'HAS_UPDATE';
            } elseif ($entryCount === 0) {
                $derivedStatus = 'BELUM_DIISI';
            } elseif (collect($entries)->every(fn ($e) => $e['status'] === 'VALIDATED')) {
                $derivedStatus = 'VALIDATED';
            } elseif (collect($entries)->every(fn ($e) => in_array($e['status'], ['SUBMITTED', 'VALIDATED']))) {
                $derivedStatus = 'SUBMITTED';
            } elseif ($entryCount < $totalInd) {
                $derivedStatus = 'PARTIAL';
            } else {
                $derivedStatus = 'DRAFT';
            }

            // Filter: hanya tampilkan yang perlu tindakan
            $actionable = in_array($derivedStatus, [
                'BELUM_DIISI',
                'NEEDS_REVISION',
                'HAS_UPDATE',
                'PARTIAL',
                'DRAFT',
            ], true);

            if (!$actionable) continue;

            // Pesan spesifik
            $message = match ($derivedStatus) {
                'BELUM_DIISI'    => 'Assessment belum diisi. Mulai isi sekarang.',
                'NEEDS_REVISION' => isset($validatorFeedbackSet[$a->id])
                    ? 'Tim teknis meminta perbaikan. Buka assessment untuk mengisi ulang indikator yang ditandai.'
                    : 'Ada perubahan dari admin. Silakan lengkapi indikator yang baru/diperbarui.',
                'HAS_UPDATE'     => 'Assessment diperbarui admin. Silakan lengkapi indikator baru yang tersedia.',
                'PARTIAL'        => "Diisi sebagian ({$entryCount}/{$totalInd} indikator). Lanjutkan pengisian.",
                'DRAFT'          => 'Draft tersimpan. Lanjutkan pengisian dan submit.',
                default          => null,
            };

            $result[] = [
                'id'            => $a->id,
                'title'         => $a->title,
                'description'   => $a->description,
                'periode'       => $a->periode,
                'status'        => $derivedStatus,
                'totalInd'      => $totalInd,
                'categoryCount' => $a->categories->count(),
                'entryCount'    => $entryCount,
                'message'       => $message,
            ];
        }

        return $result;
    }
}
