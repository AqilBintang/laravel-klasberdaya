<?php

namespace App\Http\Controllers\Kabupaten;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentIndicator;
use App\Models\KabupatenApproval;
use App\Models\SelfAssessment;
use App\Models\UserAssessmentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    /**
     * GET /kabupaten/review
     * Daftar kecamatan dalam wilayah kabupaten ini yang sudah submit assessment.
     *
     * Hanya tampilkan kecamatan yang:
     * 1. Sudah submit (ada SelfAssessment SUBMITTED / VALIDATED)
     * 2. Belum diapprove kabupaten (PENDING) ATAU sudah diapprove (untuk ditampilkan history)
     */
    public function index(): View
    {
        $user = auth()->user();

        // Ambil semua approval untuk kecamatan di wilayah kabupaten ini
        // (termasuk PENDING, APPROVED, NEEDS_REVISION)
        $approvals = KabupatenApproval::with([
            'kecamatanUser:id,name,kecamatan,kabupaten,kecamatan_id,kabupaten_id',
            'kecamatanUser.kecamatanWilayah:id,nama',
            'kecamatanUser.kabupatenWilayah:id,nama',
            'assessment:id,title,periode',
        ])
            ->whereHas('kecamatanUser', function ($q) use ($user) {
                if ($user->kabupaten_id) {
                    $q->where('kabupaten_id', $user->kabupaten_id);
                } elseif ($user->kabupaten) {
                    $q->where('kabupaten', $user->kabupaten);
                }
            })
            ->orderByRaw("FIELD(status, 'PENDING', 'NEEDS_REVISION', 'APPROVED')")
            ->orderBy('created_at', 'asc')
            ->get();

        return view('kabupaten.review.index', compact('approvals'));
    }

    /**
     * GET /kabupaten/review/{approvalId}
     * Detail jawaban satu kecamatan — tampilkan semua jawaban per indikator.
     * Tidak perlu per-indikator validasi — hanya keputusan satu kali di bawah.
     */
    public function show(string $approvalId): View
    {
        $user     = auth()->user();
        $approval = KabupatenApproval::with([
            'kecamatanUser:id,name,kecamatan,kabupaten,kecamatan_id,kabupaten_id',
            'kecamatanUser.kecamatanWilayah:id,nama',
            'kecamatanUser.kabupatenWilayah:id,nama',
            'assessment:id,title,periode',
        ])->findOrFail($approvalId);

        // Pastikan kecamatan ini berada di wilayah kabupaten yang login
        $kecamatanUser = $approval->kecamatanUser;
        $belongsToKabupaten = ($user->kabupaten_id && $kecamatanUser->kabupaten_id === $user->kabupaten_id)
            || ($user->kabupaten && $kecamatanUser->kabupaten === $user->kabupaten);

        abort_unless($belongsToKabupaten, 403, 'Kecamatan ini bukan bagian dari wilayah Anda.');

        $assessmentId = $approval->assessment_id;
        $periode      = $approval->periode;
        $kecamatanId  = $approval->kecamatan_user_id;

        // Ambil semua jawaban kecamatan untuk assessment+periode ini
        $entries = SelfAssessment::where('submitted_by_id', $kecamatanId)
            ->where('periode', $periode)
            ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
            ->with([
                'indicator:id,category_id,number,indicator,max_score',
                'indicator.category:id,code,name,order',
            ])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->indicator->category->order ?? 0) <=> ($b->indicator->category->order ?? 0),
                fn ($a, $b) => ($a->indicator->number ?? 0) <=> ($b->indicator->number ?? 0),
            ]);

        // Hitung ringkasan
        $summary = [
            'total'     => $entries->count(),
            'submitted' => $entries->where('status', 'SUBMITTED')->count(),
            'validated' => $entries->where('status', 'VALIDATED')->count(),
            'draft'     => $entries->where('status', 'DRAFT')->count(),
        ];

        return view('kabupaten.review.show', compact('approval', 'entries', 'summary'));
    }

    /**
     * POST /kabupaten/review/{approvalId}
     * Simpan keputusan kabupaten: APPROVED atau NEEDS_REVISION.
     *
     * Jika APPROVED → SelfAssessment sudah bisa dilihat validator tim teknis.
     * Jika NEEDS_REVISION → UserAssessmentStatus kecamatan → NEEDS_REVISION
     *   dan semua SelfAssessment SUBMITTED → DRAFT (kecamatan harus isi ulang).
     */
    public function store(Request $request, string $approvalId): RedirectResponse
    {
        $user     = auth()->user();
        $approval = KabupatenApproval::with('kecamatanUser')->findOrFail($approvalId);

        // Pastikan wilayah cocok
        $kecamatanUser = $approval->kecamatanUser;
        $belongsToKabupaten = ($user->kabupaten_id && $kecamatanUser->kabupaten_id === $user->kabupaten_id)
            || ($user->kabupaten && $kecamatanUser->kabupaten === $user->kabupaten);
        abort_unless($belongsToKabupaten, 403);

        // Hanya boleh review yang masih PENDING
        if ($approval->status !== 'PENDING') {
            return back()->with('error', 'Keputusan untuk submission ini sudah diberikan.');
        }

        $request->validate([
            'status' => ['required', 'in:APPROVED,NEEDS_REVISION'],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ], [
            'status.required' => 'Keputusan wajib dipilih.',
            'status.in'       => 'Keputusan tidak valid.',
        ]);

        DB::transaction(function () use ($request, $approval, $user) {
            // Simpan keputusan
            $approval->update([
                'status'           => $request->status,
                'notes'            => $request->notes ?: null,
                'kabupaten_user_id' => $user->id,
                'reviewed_at'      => now(),
            ]);

            if ($request->status === 'NEEDS_REVISION') {
                // Kembalikan semua SelfAssessment SUBMITTED ke DRAFT
                SelfAssessment::where('submitted_by_id', $approval->kecamatan_user_id)
                    ->where('periode', $approval->periode)
                    ->where('status', 'SUBMITTED')
                    ->whereHas('indicator', fn ($q) =>
                        $q->where('assessment_id', $approval->assessment_id)
                    )
                    ->update(['status' => 'DRAFT']);

                // Update UserAssessmentStatus kecamatan → NEEDS_REVISION
                UserAssessmentStatus::where('user_id', $approval->kecamatan_user_id)
                    ->where('assessment_id', $approval->assessment_id)
                    ->update(['status' => 'NEEDS_REVISION', 'last_activity_at' => now()]);
            }
            // Jika APPROVED: tidak ada perubahan status SelfAssessment,
            // kecamatan tetap SUBMITTED, dan sekarang bisa dilihat validator.
        });

        $message = $request->status === 'APPROVED'
            ? 'Assessment disetujui. Validator tim teknis dapat melanjutkan review.'
            : 'Perbaikan diminta. Kecamatan akan memperbaiki jawaban mereka.';

        return redirect()->route('kabupaten.review.index')
            ->with('success', $message);
    }
}
