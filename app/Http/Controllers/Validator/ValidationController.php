<?php

namespace App\Http\Controllers\Validator;

use App\Helpers\Scoring;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentBackup;
use App\Models\AssessmentRubric;
use App\Models\AssessmentValidation;
use App\Models\KabupatenApproval;
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
        // HANYA yang sudah di-approve oleh kabupaten (KabupatenApproval.status = APPROVED)
        // Filter dicocokkan per (kecamatan_user_id, assessment_id, periode) agar
        // approval dari assessment/periode lain tidak ikut menjadi gerbang akses.
        $entries = SelfAssessment::where('self_assessments.status', 'SUBMITTED')
            ->with([
                'submittedBy:id,name,kecamatan,kabupaten,kecamatan_id,kabupaten_id',
                'submittedBy.kecamatanWilayah:id,nama',
                'submittedBy.kabupatenWilayah:id,nama',
                'indicator.category.assessment:id,title,periode',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ])
            ->join('assessment_indicators as ai', 'ai.id', '=', 'self_assessments.indicator_id')
            ->join('assessment_categories as ac', 'ac.id', '=', 'ai.category_id')
            ->whereExists(function ($query) {
                // Pastikan ada KabupatenApproval APPROVED yang cocok persis:
                // kecamatan yang sama, assessment yang sama, periode yang sama
                $query->select(DB::raw(1))
                    ->from('kabupaten_approvals')
                    ->whereColumn('kabupaten_approvals.kecamatan_user_id', 'self_assessments.submitted_by_id')
                    ->whereColumn('kabupaten_approvals.assessment_id', 'ac.assessment_id')
                    ->whereColumn('kabupaten_approvals.periode', 'self_assessments.periode')
                    ->where('kabupaten_approvals.status', 'APPROVED');
            })
            ->select('self_assessments.*')
            ->orderBy('self_assessments.submitted_at', 'asc')
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

            // Overall status: semua entri di $group sudah dijamin SUBMITTED oleh query di atas.
            // Cukup cek apakah ada yang pernah di-reject (validasi terakhir REJECTED).
            $anyRejected  = $group->contains(fn ($s) =>
                $s->validations->first()?->status === 'REJECTED'
            );
            $overallStatus = $anyRejected ? 'REJECTED' : 'SUBMITTED';

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

        // ── History: kecamatan yang sudah pernah divalidasi ──────────────
        // Ambil semua self-assessment VALIDATED atau REJECTED (tidak ada lagi yang SUBMITTED)
        // yang sudah pernah melalui proses validasi tim teknis.
        $historyEntries = SelfAssessment::whereIn('self_assessments.status', ['VALIDATED', 'REJECTED', 'DRAFT'])
            ->with([
                'submittedBy:id,name,kecamatan,kabupaten,kecamatan_id,kabupaten_id',
                'submittedBy.kecamatanWilayah:id,nama',
                'submittedBy.kabupatenWilayah:id,nama',
                'indicator.category.assessment:id,title,periode',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ])
            ->join('assessment_indicators as ai', 'ai.id', '=', 'self_assessments.indicator_id')
            ->join('assessment_categories as ac', 'ac.id', '=', 'ai.category_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('assessment_validations')
                    ->whereColumn('assessment_validations.self_assessment_id', 'self_assessments.id');
            })
            ->select('self_assessments.*')
            ->get();

        // Group history per (userId, assessmentId, periode) — sama dengan $groups
        $historyGrouped = $historyEntries->groupBy(function ($s) {
            $assessmentId = $s->indicator?->category?->assessment?->id;
            return "{$s->submitted_by_id}:{$assessmentId}:{$s->periode}";
        });

        // Buang group yang sudah ada di $groups (masih ada SUBMITTED = sudah masuk antrian aktif)
        $activeKeys = collect($groups)->pluck('key')->flip()->toArray();

        $historyGroups = [];
        foreach ($historyGrouped as $key => $group) {
            if (isset($activeKeys[$key])) continue; // sudah ada di antrian aktif

            $first      = $group->first();
            $assessment = $first->indicator?->category?->assessment;
            $user       = $first->submittedBy;

            $kecamatan = $user?->kecamatanWilayah?->nama ?? $user?->kecamatan ?? $user?->name;
            $kabupaten = $user?->kabupatenWilayah?->nama ?? $user?->kabupaten;

            $totalAll       = $group->count();
            $totalValidated = $group->where('status', 'VALIDATED')->count();
            $totalRejected  = $group->where('status', 'REJECTED')->count();
            $totalDraft     = $group->where('status', 'DRAFT')->count();

            // Status keseluruhan group history
            if ($totalValidated === $totalAll) {
                $histOverallStatus = 'FULLY_VALIDATED';
            } elseif ($totalRejected > 0 || $totalDraft > 0) {
                $histOverallStatus = 'PARTIAL_REJECTED';
            } else {
                $histOverallStatus = 'PARTIAL_VALIDATED';
            }

            $historyGroups[] = [
                'key'               => $key,
                'userId'            => $first->submitted_by_id,
                'representativeId'  => $first->id,
                'kecamatan'         => $kecamatan,
                'kabupaten'         => $kabupaten,
                'assessmentId'      => $assessment?->id,
                'assessmentTitle'   => $assessment?->title ?? '—',
                'periode'           => $first->periode,
                'totalAll'          => $totalAll,
                'totalValidated'    => $totalValidated,
                'totalRejected'     => $totalRejected,
                'totalDraft'        => $totalDraft,
                'overallStatus'     => $histOverallStatus,
            ];
        }

        usort($historyGroups, fn ($a, $b) => strcmp($a['kecamatan'] ?? '', $b['kecamatan'] ?? ''));

        return view('validator.validasi.index', compact('groups', 'totalPending', 'historyGroups'));
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

        // ── Load rubrik panduan untuk referensi validator ───────────────
        // Remap ke indicator_id entries yang aktif menggunakan kunci logis
        // "categoryCode:indicatorNumber" — sama persis dengan kecamatan.
        $rubric            = null;
        $rubricByIndicator = [];

        if ($assessmentId) {
            $rubric = AssessmentRubric::with([
                'items',
                'items.indicator:id,category_id,number,indicator',
                'items.indicator.category:id,assessment_id,code,name,order',
            ])->where('assessment_id', $assessmentId)->first();

            if ($rubric) {
                // Bangun lookup: "categoryCode:indicatorNumber" → rubric item
                $rubricLookup = [];
                foreach ($rubric->items as $item) {
                    $code   = $item->indicator->category->code ?? '';
                    $number = $item->indicator->number;
                    $rubricLookup["{$code}:{$number}"] = $item;
                }

                // Map ke indicator_id entries yang sedang ditampilkan
                foreach ($entries as $entry) {
                    $indId  = $entry->indicator?->id;
                    $code   = $entry->indicator?->category?->code ?? '';
                    $number = $entry->indicator?->number;
                    $key    = "{$code}:{$number}";
                    if ($indId && isset($rubricLookup[$key])) {
                        $rubricByIndicator[$indId] = $rubricLookup[$key];
                    }
                }
            }
        }

        // ── Pesan dari kabupaten/kota saat menyetujui submission ──────────
        $kabupatenApproval = KabupatenApproval::where('kecamatan_user_id', $selfAssessment->submitted_by_id)
            ->where('assessment_id', $assessmentId)
            ->where('periode', $selfAssessment->periode)
            ->where('status', 'APPROVED')
            ->latest('reviewed_at')
            ->first(['notes', 'reviewed_at']);

        // ── History: indikator yang sudah VALIDATED / REJECTED / DRAFT ────
        // Ditampilkan sebagai panel read-only di bawah form validasi
        // agar validator bisa melihat progress keseluruhan kecamatan ini.
        $historyEntries = SelfAssessment::where('submitted_by_id', $selfAssessment->submitted_by_id)
            ->where('periode', $selfAssessment->periode)
            ->whereNotIn('status', ['SUBMITTED'])
            ->whereHas('indicator', fn ($q) =>
                $q->where('assessment_id', $assessmentId)
            )
            ->with([
                'indicator:id,category_id,number,indicator,max_score',
                'indicator.category:id,code,name,order',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->limit(1),
            ])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->indicator->category->order ?? 0) <=> ($b->indicator->category->order ?? 0),
                fn ($a, $b) => ($a->indicator->number ?? 0) <=> ($b->indicator->number ?? 0),
            ]);

        return view('validator.validasi.show', compact('id', 'selfAssessment', 'entries', 'summary', 'rubric', 'rubricByIndicator', 'kabupatenApproval', 'historyEntries'));
    }

    /**
     * POST /validator/validasi/{id}/bulk
     * Simpan validasi untuk seluruh indikator SUBMITTED sekaligus.
     * $id = id SelfAssessment anchor (representatif) untuk mendapatkan konteks
     *       (submitted_by_id, periode, assessment_id).
     *
     * Request body:
     *   entries[{selfAssessmentId}][validated_score] int
     *   entries[{selfAssessmentId}][status]          APPROVED|REJECTED|REVISION_NEEDED
     *   entries[{selfAssessmentId}][notes]           string|null
     */
    public function storeBulk(Request $request, string $id): RedirectResponse
    {
        $anchor = SelfAssessment::with('indicator.category.assessment')->findOrFail($id);

        $assessmentId = $anchor->indicator?->category?->assessment_id;
        $userId       = $anchor->submitted_by_id;
        $periode      = $anchor->periode;

        // Ambil semua indikator SUBMITTED milik user+assessment+periode ini
        $pendingEntries = SelfAssessment::where('submitted_by_id', $userId)
            ->where('periode', $periode)
            ->where('status', 'SUBMITTED')
            ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
            ->get()
            ->keyBy('id');

        if ($pendingEntries->isEmpty()) {
            return redirect()
                ->route('validator.validasi.show', $id)
                ->with('error', 'Tidak ada indikator yang menunggu validasi.');
        }

        // Validasi input bulk
        $entries = $request->input('entries', []);

        $validated        = [];
        $missingDecision  = [];
        $invalidScore     = [];

        foreach ($pendingEntries as $entryId => $entry) {
            $data   = $entries[$entryId] ?? [];
            $score  = $data['validated_score'] ?? null;
            $status = $data['status'] ?? null;

            if (!in_array($status, ['APPROVED', 'REJECTED', 'REVISION_NEEDED'], true)) {
                $missingDecision[] = $entry->indicator?->number;
                continue;
            }

            $maxScore = $entry->indicator?->max_score ?? 4;
            $scoreInt = (int) $score;

            if ($score === null || $score === '' || $scoreInt < 1 || $scoreInt > $maxScore) {
                $invalidScore[] = $entry->indicator?->number;
                continue;
            }

            $validated[$entryId] = [
                'entry'  => $entry,
                'score'  => $scoreInt,
                'status' => $status,
                'notes'  => $data['notes'] ?: null,
            ];
        }

        if (!empty($missingDecision) || !empty($invalidScore)) {
            $messages = [];
            if (!empty($missingDecision)) {
                $count = count($missingDecision);
                $messages[] = "Keputusan belum dipilih untuk {$count} indikator (#" . implode(', #', $missingDecision) . ').';
            }
            if (!empty($invalidScore)) {
                $count = count($invalidScore);
                $messages[] = "Skor tidak valid untuk {$count} indikator (#" . implode(', #', $invalidScore) . ').';
            }
            return redirect()
                ->route('validator.validasi.show', $id)
                ->with('error', implode(' ', $messages));
        }

        // Simpan semua sekaligus dalam satu transaksi
        DB::transaction(function () use ($validated, $assessmentId, $userId, $periode) {
            $validatorId = auth()->id();
            $now         = now();

            foreach ($validated as $data) {
                $entry     = $data['entry'];
                $newStatus = match ($data['status']) {
                    'APPROVED'        => 'VALIDATED',
                    'REJECTED'        => 'REJECTED',
                    'REVISION_NEEDED' => 'DRAFT',
                    default           => 'SUBMITTED',
                };

                // Simpan record validasi
                AssessmentValidation::create([
                    'self_assessment_id' => $entry->id,
                    'validator_id'       => $validatorId,
                    'status'             => $data['status'],
                    'validated_score'    => $data['score'],
                    'notes'              => $data['notes'],
                    'validated_at'       => $now,
                ]);

                // Update status self-assessment
                $entry->update(['status' => $newStatus]);
            }

            // Sinkronkan UserAssessmentStatus setelah semua tersimpan
            // Cek apakah masih ada yang SUBMITTED/DRAFT setelah operasi ini
            $anyRevision = collect($validated)->contains(
                fn ($d) => in_array($d['status'], ['REVISION_NEEDED', 'REJECTED'])
            );

            if ($anyRevision) {
                UserAssessmentStatus::where('user_id', $userId)
                    ->where('assessment_id', $assessmentId)
                    ->update(['status' => 'NEEDS_REVISION', 'last_activity_at' => now()]);
            } else {
                // Semua APPROVED — cek apakah masih ada indikator lain yang belum divalidasi
                $stillPending = SelfAssessment::where('submitted_by_id', $userId)
                    ->where('periode', $periode)
                    ->whereIn('status', ['SUBMITTED', 'DRAFT'])
                    ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
                    ->exists();

                if (!$stillPending) {
                    // Benar-benar semua selesai — cek semua VALIDATED
                    $allValidated = !SelfAssessment::where('submitted_by_id', $userId)
                        ->where('periode', $periode)
                        ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
                        ->whereNotIn('status', ['VALIDATED'])
                        ->exists();

                    if ($allValidated) {
                        UserAssessmentStatus::where('user_id', $userId)
                            ->where('assessment_id', $assessmentId)
                            ->update(['status' => 'VALIDATED', 'last_activity_at' => now()]);
                    }
                }
            }
        });

        // Generate/update backup snapshot jika semua sudah VALIDATED
        $this->maybeGenerateBackupSnapshot($anchor);

        $count = count($validated);

        return redirect()
            ->route('validator.validasi.show', $id)
            ->with('success', "{$count} indikator berhasil divalidasi sekaligus.");
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

            // Sinkronkan UserAssessmentStatus kecamatan agar dashboard & form
            // menampilkan status yang benar setelah keputusan validator.
            $userId       = $selfAssessment->submitted_by_id;
            $assessmentId = $selfAssessment->load('indicator.category')
                ->indicator?->category?->assessment_id;

            if ($assessmentId) {
                if ($request->status === 'REVISION_NEEDED' || $request->status === 'REJECTED') {
                    // Tandai ke kecamatan bahwa ada indikator yang perlu diperbaiki
                    UserAssessmentStatus::where('user_id', $userId)
                        ->where('assessment_id', $assessmentId)
                        ->update(['status' => 'NEEDS_REVISION', 'last_activity_at' => now()]);
                } elseif ($request->status === 'APPROVED') {
                    // Cek apakah masih ada indikator SUBMITTED lain pada assessment ini
                    $stillPending = SelfAssessment::where('submitted_by_id', $userId)
                        ->where('periode', $selfAssessment->periode)
                        ->where('status', 'SUBMITTED')
                        ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
                        ->where('id', '!=', $selfAssessment->id)
                        ->exists();

                    if (!$stillPending) {
                        // Semua sudah divalidasi — cek apakah benar-benar semua VALIDATED
                        $allValidated = !SelfAssessment::where('submitted_by_id', $userId)
                            ->where('periode', $selfAssessment->periode)
                            ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
                            ->whereNotIn('status', ['VALIDATED'])
                            ->exists();

                        if ($allValidated) {
                            UserAssessmentStatus::where('user_id', $userId)
                                ->where('assessment_id', $assessmentId)
                                ->update(['status' => 'VALIDATED', 'last_activity_at' => now()]);
                        }
                    }
                }
            }
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

            // Sinkronkan UserAssessmentStatus kecamatan — sama seperti store()
            $userId       = $selfAssessment->submitted_by_id;
            $assessmentId = $selfAssessment->load('indicator.category')
                ->indicator?->category?->assessment_id;

            if ($assessmentId) {
                if ($request->status === 'REVISION_NEEDED' || $request->status === 'REJECTED') {
                    // Tandai ke kecamatan bahwa ada indikator yang perlu diperbaiki
                    UserAssessmentStatus::where('user_id', $userId)
                        ->where('assessment_id', $assessmentId)
                        ->update(['status' => 'NEEDS_REVISION', 'last_activity_at' => now()]);
                } elseif ($request->status === 'APPROVED') {
                    // Cek apakah masih ada indikator SUBMITTED atau DRAFT (belum selesai divalidasi)
                    $stillPending = SelfAssessment::where('submitted_by_id', $userId)
                        ->where('periode', $selfAssessment->periode)
                        ->whereIn('status', ['SUBMITTED', 'DRAFT'])
                        ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
                        ->where('id', '!=', $selfAssessment->id)
                        ->exists();

                    if (!$stillPending) {
                        // Semua sudah divalidasi — cek apakah benar-benar semua VALIDATED
                        $allValidated = !SelfAssessment::where('submitted_by_id', $userId)
                            ->where('periode', $selfAssessment->periode)
                            ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
                            ->whereNotIn('status', ['VALIDATED'])
                            ->exists();

                        if ($allValidated) {
                            UserAssessmentStatus::where('user_id', $userId)
                                ->where('assessment_id', $assessmentId)
                                ->update(['status' => 'VALIDATED', 'last_activity_at' => now()]);
                        }
                    }
                }
            }
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
