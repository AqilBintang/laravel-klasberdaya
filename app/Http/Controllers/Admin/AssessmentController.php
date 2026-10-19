<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Scoring;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentCategory;
use App\Models\AssessmentIndicator;
use App\Models\AssessmentVersion;
use App\Models\IndicatorChange;
use App\Models\SelfAssessment;
use App\Models\User;
use App\Models\UserAssessmentStatus;
use App\Models\KlasifikasiSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function create(): View
    {
        $assessments = Assessment::with([
            'categories' => fn ($q) => $q->orderBy('order'),
            'categories.indicators' => fn ($q) => $q->orderBy('number'),
        ])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'periode', 'status']);

        return view('admin.assessment.create', compact('assessments'));
    }

    /** GET /admin/assessment/create/new — form buat assessment blank */
    public function createNew(): View
    {
        return view('admin.assessment.create-new');
    }

    /**
     * POST /admin/assessment
     * Dua mode:
     *   - mode "new"       : categories_json berisi array kategori + indikator
     *   - mode "duplicate" : source_id + periode (salin dari assessment yang ada)
     */
    public function store(Request $request): RedirectResponse
    {
        // ── Mode: Duplicate ─────────────────────────────────────────────
        if ($request->filled('source_id')) {
            return $this->storeDuplicate($request);
        }

        // ── Mode: Baru dari scratch ──────────────────────────────────────
        $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'periode'         => ['required', 'string', 'regex:/^\d{4}$/'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'status'          => ['required', 'in:DRAFT,PUBLISHED'],
            'categories_json' => ['required', 'json'],
        ], [
            'title.required'           => 'Judul assessment wajib diisi.',
            'periode.required'         => 'Periode wajib diisi.',
            'periode.regex'            => 'Periode harus 4 digit tahun (contoh: 2026).',
            'status.required'          => 'Status wajib dipilih.',
            'categories_json.required' => 'Minimal 1 kategori harus ditambahkan.',
            'categories_json.json'     => 'Data kategori tidak valid.',
        ]);

        $categories = json_decode($request->categories_json, true);

        if (empty($categories)) {
            return back()->withInput()->withErrors(['categories_json' => 'Minimal 1 kategori harus ditambahkan.']);
        }

        // Cek duplikat periode
        if (Assessment::where('periode', $request->periode)->exists()) {
            return back()->withInput()->withErrors([
                'periode' => "Assessment untuk periode \"{$request->periode}\" sudah ada. Hanya boleh 1 assessment per periode.",
            ]);
        }

        DB::transaction(function () use ($request, $categories) {
            // 1. Assessment
            $assessment = Assessment::create([
                'title'           => $request->title,
                'description'     => $request->description ?: null,
                'periode'         => $request->periode,
                'status'          => $request->status,
                'current_version' => 1,
            ]);

            // 2. Version baseline (v1)
            $version = AssessmentVersion::create([
                'assessment_id'   => $assessment->id,
                'version_number'  => 1,
                'title'           => $request->title,
                'changes_summary' => 'Versi awal assessment',
                'created_by'      => auth()->id(),
                'created_at'      => now(),
            ]);

            // 3. Categories + Indicators
            foreach ($categories as $catData) {
                $category = AssessmentCategory::create([
                    'assessment_id' => $assessment->id,
                    'version_id'    => $version->id,
                    'code'          => strtoupper(trim($catData['code'])),
                    'name'          => trim($catData['name']),
                    'description'   => isset($catData['description']) ? trim($catData['description']) : null,
                    'order'         => (int) ($catData['order'] ?? 0),
                    'scoring_rule'  => !empty($catData['scoringRule']) ? $catData['scoringRule'] : null,
                    'is_active'     => true,
                ]);

                $indicators = array_map(fn ($ind) => [
                    'assessment_id' => $assessment->id,
                    'category_id'   => $category->id,
                    'version_id'    => $version->id,
                    'number'        => (int) $ind['number'],
                    'indicator'     => trim($ind['indicator']),
                    'max_score'     => (int) ($ind['maxScore'] ?? 4),
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ], $catData['indicators'] ?? []);

                if (!empty($indicators)) {
                    AssessmentIndicator::insert($indicators);
                }
            }
        });

        return redirect()->route('admin.assessment.create')
            ->with('success', "Assessment \"{$request->title}\" berhasil dibuat.");
    }

    /**
     * Duplikat assessment dari source ke periode baru.
     */
    private function storeDuplicate(Request $request): RedirectResponse
    {
        $request->validate([
            'source_id' => ['required', 'exists:assessments,id'],
            'periode'   => ['required', 'string', 'regex:/^\d{4}$/'],
        ], [
            'source_id.required' => 'Source assessment tidak valid.',
            'source_id.exists'   => 'Source assessment tidak ditemukan.',
            'periode.required'   => 'Periode baru wajib diisi.',
            'periode.regex'      => 'Periode harus 4 digit tahun (contoh: 2026).',
        ]);

        if (Assessment::where('periode', $request->periode)->exists()) {
            return back()->withInput()->withErrors([
                'periode' => "Assessment untuk periode \"{$request->periode}\" sudah ada.",
            ]);
        }

        $source = Assessment::with([
            'categories' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
            'categories.indicators' => fn ($q) => $q->where('is_active', true)->orderBy('number'),
        ])->findOrFail($request->source_id);

        DB::transaction(function () use ($request, $source) {
            // 1. Assessment baru
            $newAssessment = Assessment::create([
                'title'           => $source->title,
                'description'     => $source->description,
                'periode'         => $request->periode,
                'status'          => 'DRAFT',
                'current_version' => 1,
            ]);

            // 2. Version baseline
            $version = AssessmentVersion::create([
                'assessment_id'   => $newAssessment->id,
                'version_number'  => 1,
                'title'           => $source->title,
                'changes_summary' => "Duplikat dari assessment \"{$source->title}\" (periode {$source->periode})",
                'created_by'      => auth()->id(),
                'created_at'      => now(),
            ]);

            // 3. Salin categories + indicators
            foreach ($source->categories as $cat) {
                $newCat = AssessmentCategory::create([
                    'assessment_id' => $newAssessment->id,
                    'version_id'    => $version->id,
                    'code'          => $cat->code,
                    'name'          => $cat->name,
                    'description'   => $cat->description,
                    'order'         => $cat->order,
                    'scoring_rule'  => $cat->scoring_rule,
                    'is_active'     => true,
                ]);

                $indicators = $cat->indicators->map(fn ($ind) => [
                    'assessment_id' => $newAssessment->id,
                    'category_id'   => $newCat->id,
                    'version_id'    => $version->id,
                    'number'        => $ind->number,
                    'indicator'     => $ind->indicator,
                    'max_score'     => $ind->max_score,
                    'is_active'     => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ])->toArray();

                if (!empty($indicators)) {
                    AssessmentIndicator::insert($indicators);
                }
            }
        });

        return redirect()->route('admin.assessment.create')
            ->with('success', "Assessment \"{$source->title}\" berhasil diduplikat ke periode {$request->periode}.");
    }

    public function show(string $id): View
    {
        $assessment = Assessment::with([
            'categories' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
            'categories.indicators' => fn ($q) => $q->where('is_active', true)->orderBy('number'),
        ])->findOrFail($id);

        return view('admin.assessment.show', compact('assessment', 'id'));
    }

    public function edit(string $id): View
    {
        $assessment = Assessment::with([
            'categories' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
            'categories.indicators' => fn ($q) => $q->where('is_active', true)->orderBy('number'),
        ])->findOrFail($id);

        // Build struktur JSON untuk pre-populate editor JS
        $structure = $assessment->categories->map(function ($cat) {
            return [
                'code'         => $cat->code,
                'name'         => $cat->name,
                'description'  => $cat->description ?? '',
                'order'        => $cat->order,
                'scoringRule'  => $cat->scoring_rule ?? [],
                'indicators'   => $cat->indicators->map(fn ($ind) => [
                    'number'    => $ind->number,
                    'indicator' => $ind->indicator,
                    'maxScore'  => $ind->max_score,
                ])->toArray(),
            ];
        })->toArray();

        return view('admin.assessment.edit', compact('assessment', 'id', 'structure'));
    }

    /**
     * PUT /admin/assessment/{id}
     * Update metadata (title, description, periode, status).
     * Untuk perubahan struktur (categories/indicators) gunakan publishVersion().
     * Khusus DRAFT: jika ada categories_json, update struktur v1 di tempat (aman karena belum ada jawaban).
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $assessment = Assessment::findOrFail($id);

        $rules = [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'periode'     => ['required', 'string', 'regex:/^\d{4}$/'],
            'status'      => ['required', 'in:DRAFT,PUBLISHED,REVISION,ARCHIVED'],
        ];
        // DRAFT boleh edit struktur v1
        if ($assessment->status === 'DRAFT') {
            $rules['categories_json'] = ['nullable', 'json'];
        }

        $request->validate($rules, [
            'title.required'   => 'Judul assessment wajib diisi.',
            'periode.required'  => 'Periode wajib diisi.',
            'periode.regex'     => 'Periode harus 4 digit tahun (contoh: 2026).',
            'status.required'   => 'Status wajib dipilih.',
        ]);

        $oldStatus = $assessment->status;
        $newStatus = $request->status;

        // Validasi transisi status
        $validTransitions = [
            'DRAFT'     => ['DRAFT', 'PUBLISHED', 'ARCHIVED'],
            'PUBLISHED' => ['PUBLISHED', 'REVISION', 'ARCHIVED'],
            'REVISION'  => ['REVISION', 'PUBLISHED'],  // PUBLISHED = batal revisi
            'ARCHIVED'  => ['ARCHIVED', 'DRAFT'],
        ];
        if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [], true) && $oldStatus !== $newStatus) {
            return back()->withInput()->withErrors([
                'status' => "Tidak dapat mengubah status dari {$oldStatus} ke {$newStatus}.",
            ]);
        }

        DB::transaction(function () use ($request, $assessment, $newStatus) {
            $assessment->update([
                'title'       => $request->title,
                'description' => $request->description ?: null,
                'periode'     => $request->periode,
                'status'      => $newStatus,
            ]);

            // DRAFT: update struktur v1 di tempat jika ada categories_json
            if ($assessment->status === 'DRAFT' && $request->filled('categories_json')) {
                $this->replaceDraftStructure($assessment, json_decode($request->categories_json, true));
            }
        });

        $msg = $newStatus === 'REVISION' && $oldStatus !== 'REVISION'
            ? 'Assessment dikunci untuk revisi. Edit struktur lalu publish versi baru.'
            : 'Assessment berhasil diperbarui.';

        return redirect()->route('admin.assessment.edit', $id)->with('success', $msg);
    }

    /**
     * Ganti struktur v1 untuk assessment DRAFT (belum ada jawaban).
     * Aman karena DRAFT belum pernah diisi kecamatan.
     */
    private function replaceDraftStructure(Assessment $assessment, array $categories): void
    {
        $version = AssessmentVersion::where('assessment_id', $assessment->id)
            ->where('version_number', 1)
            ->first();

        if (!$version) {
            return; // tidak ada struktur untuk diganti
        }

        // Hapus struktur lama (DRAFT = belum ada jawaban, aman)
        AssessmentIndicator::where('assessment_id', $assessment->id)->delete();
        AssessmentCategory::where('assessment_id', $assessment->id)->delete();

        foreach ($categories as $catData) {
            $category = AssessmentCategory::create([
                'assessment_id' => $assessment->id,
                'version_id'    => $version->id,
                'code'          => strtoupper(trim($catData['code'])),
                'name'          => trim($catData['name']),
                'description'   => isset($catData['description']) ? trim($catData['description']) : null,
                'order'         => (int) ($catData['order'] ?? 0),
                'scoring_rule'  => !empty($catData['scoringRule']) ? $catData['scoringRule'] : null,
                'is_active'     => true,
            ]);

            foreach ($catData['indicators'] ?? [] as $ind) {
                AssessmentIndicator::create([
                    'assessment_id' => $assessment->id,
                    'category_id'   => $category->id,
                    'version_id'    => $version->id,
                    'number'        => (int) $ind['number'],
                    'indicator'     => trim($ind['indicator']),
                    'max_score'     => (int) ($ind['maxScore'] ?? 4),
                    'is_active'     => true,
                ]);
            }
        }
    }

    /**
     * POST /admin/assessment/{id}/version
     * Publish versi baru assessment. Hanya bisa jika status REVISION.
     * Alur (migrasi dari NextJS POST /api/assessment/[id]/version):
     *   1. Snapshot struktur aktif lama (key: categoryCode:indicatorNumber)
     *   2. Buat AssessmentVersion baru (current_version + 1)
     *   3. Retire struktur lama (is_active = false, BUKAN hapus)
     *   4. Buat struktur baru dengan version_id baru
     *   5. Auto-compute diff (ADDED/MODIFIED/REMOVED) → indicator_changes
     *   6. Update assessment (current_version, status PUBLISHED)
     *   7. Update user_assessment_status per kelompok user
     */
    public function publishVersion(Request $request, string $id): RedirectResponse
    {
        $assessment = Assessment::findOrFail($id);

        if ($assessment->status !== 'REVISION') {
            return back()->withErrors([
                'status' => 'Assessment harus dalam status REVISION untuk publish versi baru. Set status ke Revisi dulu.',
            ]);
        }

        $request->validate([
            'changes_summary' => ['nullable', 'string', 'max:2000'],
            'categories_json'  => ['required', 'json'],
        ], [
            'categories_json.required' => 'Struktur kategori wajib diisi.',
            'categories_json.json'     => 'Data struktur tidak valid.',
        ]);

        $categories = json_decode($request->categories_json, true);

        if (empty($categories)) {
            return back()->withErrors(['categories_json' => 'Minimal 1 kategori harus ditambahkan.']);
        }

        // Validasi minimal setiap kategori punya indikator
        foreach ($categories as $i => $cat) {
            if (empty($cat['indicators'])) {
                return back()->withErrors([
                    'categories_json' => "Kategori \"{$cat['code']}\" harus punya minimal 1 indikator.",
                ]);
            }
        }

        DB::transaction(function () use ($assessment, $categories, $request) {
            $newVersionNumber = $assessment->current_version + 1;

            // 1. Snapshot struktur lama (key stabil: categoryCode:indicatorNumber)
            $oldCategories = AssessmentCategory::where('assessment_id', $assessment->id)
                ->where('is_active', true)
                ->with(['indicators' => fn ($q) => $q->where('is_active', true)])
                ->get();

            $oldByKey = [];
            foreach ($oldCategories as $cat) {
                foreach ($cat->indicators as $ind) {
                    $oldByKey["{$cat->code}:{$ind->number}"] = [
                        'indicator' => $ind->indicator,
                        'maxScore'  => $ind->max_score,
                    ];
                }
            }

            // 2. Buat AssessmentVersion baru
            $version = AssessmentVersion::create([
                'assessment_id'   => $assessment->id,
                'version_number'  => $newVersionNumber,
                'title'           => $assessment->title,
                'changes_summary' => $request->changes_summary ?: "Versi {$newVersionNumber}",
                'created_by'      => auth()->id(),
                'created_at'      => now(),
            ]);

            // 3. Retire struktur lama (soft-delete via is_active, BUKAN hapus)
            AssessmentCategory::where('assessment_id', $assessment->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            AssessmentIndicator::where('assessment_id', $assessment->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // 4. Buat struktur baru dengan version_id baru
            $newByKey = [];
            foreach ($categories as $catData) {
                $category = AssessmentCategory::create([
                    'assessment_id' => $assessment->id,
                    'version_id'    => $version->id,
                    'code'          => strtoupper(trim($catData['code'])),
                    'name'          => trim($catData['name']),
                    'description'   => isset($catData['description']) ? trim($catData['description']) : null,
                    'order'         => (int) ($catData['order'] ?? 0),
                    'scoring_rule'  => !empty($catData['scoringRule']) ? $catData['scoringRule'] : null,
                    'is_active'     => true,
                ]);

                foreach ($catData['indicators'] ?? [] as $indData) {
                    $indicator = AssessmentIndicator::create([
                        'assessment_id' => $assessment->id,
                        'category_id'   => $category->id,
                        'version_id'    => $version->id,
                        'number'        => (int) $indData['number'],
                        'indicator'     => trim($indData['indicator']),
                        'max_score'     => (int) ($indData['maxScore'] ?? 4),
                        'is_active'     => true,
                    ]);
                    $newByKey["{$category->code}:{$indicator->number}"] = [
                        'id'        => $indicator->id,
                        'indicator' => $indicator->indicator,
                        'maxScore'  => $indicator->max_score,
                    ];
                }
            }

            // 5. Auto-compute diff (ADDED / MODIFIED / REMOVED)
            $changes = [];
            foreach ($newByKey as $key => $next) {
                $prev = $oldByKey[$key] ?? null;
                if ($prev === null) {
                    $changes[] = [
                        'version_id'        => $version->id,
                        'indicator_id'      => $next['id'],
                        'change_type'       => 'ADDED',
                        'old_value'         => null,
                        'new_value'         => $next,
                        'requires_resubmit' => true,
                        'created_at'        => now(),
                    ];
                } elseif ($prev['indicator'] !== $next['indicator'] || (int) $prev['maxScore'] !== (int) $next['maxScore']) {
                    $changes[] = [
                        'version_id'        => $version->id,
                        'indicator_id'      => $next['id'],
                        'change_type'       => 'MODIFIED',
                        'old_value'         => $prev,
                        'new_value'         => $next,
                        'requires_resubmit' => true,
                        'created_at'        => now(),
                    ];
                }
            }
            foreach ($oldByKey as $key => $prev) {
                if (!isset($newByKey[$key])) {
                    $changes[] = [
                        'version_id'        => $version->id,
                        'indicator_id'      => null,
                        'change_type'       => 'REMOVED',
                        'old_value'         => $prev,
                        'new_value'         => null,
                        'requires_resubmit' => false,
                        'created_at'        => now(),
                    ];
                }
            }
            if (!empty($changes)) {
                IndicatorChange::insert($changes);
            }

            $hasRequiredResubmit = collect($changes)->contains(fn ($c) => $c['requires_resubmit']);

            // 6. Update assessment: versi naik, status kembali PUBLISHED
            $assessment->update([
                'current_version'      => $newVersionNumber,
                'status'               => 'PUBLISHED',
                'last_major_update_at' => now(),
            ]);

            // 7. Update user_assessment_status per kelompok user
            $this->updateUserStatusesForNewVersion($assessment->id, $newVersionNumber, $hasRequiredResubmit);
        });

        return redirect()->route('admin.assessment.show', $id)
            ->with('success', "Versi " . ($assessment->fresh()->current_version) . " berhasil dipublish.");
    }

    /**
     * Update user_assessment_status saat versi baru dipublish.
     * Migrasi dari NextJS POST /api/assessment/[id]/version (bagian 6).
     *
     * Skenario yang dicakup:
     *  - User dengan jawaban + requiresResubmit         → NEEDS_REVISION
     *  - User dengan jawaban tanpa requiresResubmit     → HAS_UPDATE
     *  - User IN_PROGRESS (sedang mengisi)              → HAS_UPDATE (current_version tetap, latest naik)
     *  - User SUBMITTED/HAS_UPDATE/RESUBMITTED + resubmit → NEEDS_REVISION
     *  - User SUBMITTED/HAS_UPDATE/RESUBMITTED tanpa resubmit → HAS_UPDATE
     *  - User NOT_STARTED (sudah punya row tapi belum mulai) → latest_version diperbarui
     *  - User kecamatan yang belum punya row sama sekali → buat row NOT_STARTED dengan versi terbaru
     */
    private function updateUserStatusesForNewVersion(int $assessmentId, int $newVersionNumber, bool $hasRequiredResubmit): void
    {
        // ── Langkah 0: Pastikan semua user kecamatan aktif punya row status ─────────────────
        // Kecamatan yang belum pernah membuka assessment tidak punya row.
        // Mereka perlu row agar saat REVISION mereka masuk blockedSet dengan benar.
        $allKecamatanIds = User::where('role', 'USER')
            ->where('is_active', true)
            ->pluck('id');

        if ($allKecamatanIds->isNotEmpty()) {
            $existingUserIds = UserAssessmentStatus::where('assessment_id', $assessmentId)
                ->whereIn('user_id', $allKecamatanIds)
                ->pluck('user_id');

            $newUserIds = $allKecamatanIds->diff($existingUserIds);

            if ($newUserIds->isNotEmpty()) {
                $now = now();
                $rows = $newUserIds->map(fn ($uid) => [
                    'user_id'          => $uid,
                    'assessment_id'    => $assessmentId,
                    'current_version'  => $newVersionNumber,
                    'latest_version'   => $newVersionNumber,
                    'status'           => 'NOT_STARTED',
                    'last_viewed_at'   => null,
                    'last_activity_at' => null,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ])->toArray();

                // Gunakan insertOrIgnore agar aman jika ada race condition
                DB::table('user_assessment_status')->insertOrIgnore($rows);
            }
        }

        // ── Langkah 1: Cari versi tertinggi yang pernah diisi per user ────────────────────
        $existingAnswers = SelfAssessment::whereHas('indicator', fn ($q) =>
            $q->where('assessment_id', $assessmentId)
        )->with('indicator.version')->get();

        $answeredVersionByUser = [];
        foreach ($existingAnswers as $answer) {
            $uid  = $answer->submitted_by_id;
            $vNum = $answer->indicator->version->version_number ?? 1;
            $answeredVersionByUser[$uid] = max($answeredVersionByUser[$uid] ?? 0, $vNum);
        }

        // ── Langkah 2: Upsert untuk user yang sudah punya jawaban ────────────────────────
        foreach ($answeredVersionByUser as $userId => $currentVersion) {
            UserAssessmentStatus::updateOrCreate(
                ['user_id' => $userId, 'assessment_id' => $assessmentId],
                [
                    'current_version'  => $currentVersion,
                    'latest_version'   => $newVersionNumber,
                    'status'           => $hasRequiredResubmit ? 'NEEDS_REVISION' : 'HAS_UPDATE',
                    'last_activity_at' => now(),
                ]
            );
        }

        $answeredUserIds = array_keys($answeredVersionByUser);

        // ── Langkah 3: User NOT_STARTED (belum mulai, sudah punya row) ────────────────────
        // → Perbarui latest_version ke versi terbaru agar saat PUBLISHED nanti bisa diisi
        $notStartedQuery = UserAssessmentStatus::where('assessment_id', $assessmentId)
            ->where('status', 'NOT_STARTED');
        if (!empty($answeredUserIds)) {
            $notStartedQuery->whereNotIn('user_id', $answeredUserIds);
        }
        $notStartedQuery->update([
            'current_version' => $newVersionNumber,
            'latest_version'  => $newVersionNumber,
            'updated_at'      => now(),
        ]);

        // ── Langkah 4: User IN_PROGRESS (sedang mengisi) ─────────────────────────────────
        // → HAS_UPDATE: bisa lanjut isi, tapi tahu ada indikator baru
        UserAssessmentStatus::where('assessment_id', $assessmentId)
            ->where('status', 'IN_PROGRESS')
            ->whereNotIn('user_id', $answeredUserIds)  // hindari double-update
            ->update([
                'status'           => 'HAS_UPDATE',
                'latest_version'   => $newVersionNumber,
                'last_activity_at' => now(),
                'updated_at'       => now(),
            ]);

        // ── Langkah 5: User SUBMITTED/HAS_UPDATE/RESUBMITTED/NEEDS_REVISION ─────────────
        // (yang sudah punya jawaban di-handle di langkah 2, ini untuk edge case status tidak sinkron)
        if ($hasRequiredResubmit) {
            UserAssessmentStatus::where('assessment_id', $assessmentId)
                ->whereIn('status', ['SUBMITTED', 'HAS_UPDATE', 'RESUBMITTED', 'NEEDS_REVISION'])
                ->whereNotIn('user_id', $answeredUserIds)
                ->update([
                    'status'           => 'NEEDS_REVISION',
                    'latest_version'   => $newVersionNumber,
                    'last_activity_at' => now(),
                    'updated_at'       => now(),
                ]);
        } else {
            UserAssessmentStatus::where('assessment_id', $assessmentId)
                ->whereIn('status', ['SUBMITTED', 'HAS_UPDATE', 'RESUBMITTED'])
                ->whereNotIn('user_id', $answeredUserIds)
                ->update([
                    'status'           => 'HAS_UPDATE',
                    'latest_version'   => $newVersionNumber,
                    'last_activity_at' => now(),
                    'updated_at'       => now(),
                ]);
        }
    }

    /**
     * DELETE /admin/assessment/{id}
     * Hapus assessment. Ditolak jika sudah ada kecamatan yang mengisi (lock).
     * Migrasi dari NextJS DELETE /api/assessment/[id].
     */
    public function destroy(string $id): RedirectResponse
    {
        $assessment = Assessment::findOrFail($id);

        // Lock check: tolak jika ada jawaban
        $answerCount = SelfAssessment::whereHas('indicator', fn ($q) =>
            $q->where('assessment_id', $id)
        )->count();

        if ($answerCount > 0) {
            return back()->withErrors([
                'delete' => "Assessment tidak dapat dihapus karena sudah ada {$answerCount} jawaban kecamatan. " .
                            "Pertimbangkan untuk mengarsipkan sebagai gantinya.",
            ]);
        }

        // Hapus berurutan agar tidak terkena FK restrict:
        // indicator_changes (via version) → indicators → categories → versions → assessment
        DB::transaction(function () use ($assessment) {
            $versionIds = AssessmentVersion::where('assessment_id', $assessment->id)->pluck('id');

            if ($versionIds->isNotEmpty()) {
                IndicatorChange::whereIn('version_id', $versionIds)->delete();
            }
            AssessmentIndicator::where('assessment_id', $assessment->id)->delete();
            AssessmentCategory::where('assessment_id', $assessment->id)->delete();
            if ($versionIds->isNotEmpty()) {
                AssessmentVersion::where('assessment_id', $assessment->id)->delete();
            }
            UserAssessmentStatus::where('assessment_id', $assessment->id)->delete();
            $assessment->delete();
        });

        return redirect()->route('admin.assessment.create')
            ->with('success', "Assessment \"{$assessment->title}\" berhasil dihapus.");
    }

    // ── Results ──────────────────────────────────────────────────────────

    /**
     * Rekapitulasi semua kecamatan yang sudah punya self-assessment VALIDATED.
     * Migrasi dari: /admin/assessment/results/page.tsx
     */
    public function results(Request $request): View
    {
        $periodeFilter = $request->query('tahun');

        // ── Kumpulkan semua tahun yang tersedia (untuk dropdown filter) ──
        $allPeriodes = SelfAssessment::where('status', 'VALIDATED')
            ->distinct()
            ->pluck('periode');

        $yearsFromAssessment = $allPeriodes
            ->map(fn ($p) => preg_match('/\d{4}/', $p, $m) ? $m[0] : null)
            ->filter()
            ->unique()
            ->toArray();

        $yearsFromSnapshot = KlasifikasiSnapshot::distinct()->pluck('tahun')
            ->map(fn ($t) => (string) $t)
            ->toArray();

        $years = collect(array_unique(array_merge($yearsFromAssessment, $yearsFromSnapshot)))
            ->sort()
            ->values()
            ->toArray();

        // ── SUMBER 1: Assessment VALIDATED ──────────────────────────────
        $query = SelfAssessment::where('status', 'VALIDATED')
            ->with([
                'submittedBy:id,name,kabupaten,kecamatan,kabupaten_id,kecamatan_id',
                'indicator.category.assessment:id,title,periode',
                'indicator.category:id,assessment_id,code,name',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ]);

        if ($periodeFilter) {
            $query->where('periode', $periodeFilter);
        }

        $entries = $query->get();

        // Group per userId_assessmentId_periode
        $map = [];
        foreach ($entries as $e) {
            $assessment = $e->indicator->category->assessment;
            $key = "{$e->submitted_by_id}_{$assessment->id}_{$e->periode}";

            if (!isset($map[$key])) {
                $map[$key] = [
                    'userId'          => $e->submitted_by_id,
                    'assessmentId'    => $assessment->id,
                    'kecamatan'       => $e->submittedBy->kecamatan ?? $e->submittedBy->name,
                    'kabupaten'       => $e->submittedBy->kabupaten ?? null,
                    'assessmentTitle' => $assessment->title,
                    'periode'         => $e->periode,
                    'totalScore'      => 0,
                    'maxScore'        => 0,
                    'catScores'       => [],
                    'sumber'          => 'assessment',
                ];
            }

            $effScore = $e->validations->first()?->validated_score ?? $e->score;
            $map[$key]['totalScore'] += $effScore;
            $map[$key]['maxScore']   += $e->indicator->max_score;

            $catCode = strtoupper($e->indicator->category->code);
            if (!isset($map[$key]['catScores'][$catCode])) {
                $map[$key]['catScores'][$catCode] = [
                    'code'     => $catCode,
                    'name'     => $e->indicator->category->name,
                    'score'    => 0,
                    'maxScore' => 0,
                ];
            }
            $map[$key]['catScores'][$catCode]['score']    += $effScore;
            $map[$key]['catScores'][$catCode]['maxScore'] += $e->indicator->max_score;
        }

        // Kumpulkan key unik kecamatan+tahun dari assessment (untuk deduplikasi dengan snapshot)
        $assessmentKeys = [];
        foreach ($map as $g) {
            preg_match('/\d{4}/', $g['periode'], $m);
            $year = $m[0] ?? null;
            if ($year) {
                $kab = strtolower(trim($g['kabupaten'] ?? ''));
                $kec = strtolower(trim($g['kecamatan']));
                $assessmentKeys["{$kab}|{$kec}|{$year}"] = true;
            }
        }

        $assessmentResults = array_values(array_map(function ($g) {
            $catScores = array_values($g['catScores']);

            $weightedResult    = Scoring::calculateWeightedScore($catScores);
            $g['finalScore']   = round($weightedResult['weightedScore'], 2);
            $g['statusAkhir']  = Scoring::getStatusAkhir($g['totalScore'], $g['maxScore'], $catScores);

            $g['categories'] = array_map(function ($cat) {
                return array_merge($cat, [
                    'klasifikasi' => Scoring::getKlasifikasiPerKategori($cat['code'], $cat['score']),
                ]);
            }, $catScores);

            unset($g['catScores']);
            return $g;
        }, $map));

        // ── SUMBER 2: Snapshot — hanya untuk kecamatan yang TIDAK ada di assessment ──
        $snapshotQuery = KlasifikasiSnapshot::query();
        if ($periodeFilter) {
            $snapshotQuery->where('tahun', $periodeFilter);
        }
        $snapshots = $snapshotQuery->get();

        $snapshotResults = [];
        foreach ($snapshots as $snap) {
            $kecKey = strtolower(trim($snap->kabupaten)) . '|' . strtolower(trim($snap->kecamatan)) . '|' . $snap->tahun;

            // Skip jika kecamatan ini sudah ada dari assessment
            if (isset($assessmentKeys[$kecKey])) {
                continue;
            }

            $snapshotResults[] = [
                'userId'          => null,        // tidak ada user — tidak bisa di-link detail
                'assessmentId'    => null,
                'kecamatan'       => $snap->kecamatan,
                'kabupaten'       => $snap->kabupaten,
                'assessmentTitle' => 'Rekapitulasi Tim Teknis',
                'periode'         => (string) $snap->tahun,
                'totalScore'      => $snap->totalSkor(),
                'maxScore'        => 244,         // total max semua kategori (84+64+20+44+8+24)
                'finalScore'      => round($snap->skor_final, 2),
                'statusAkhir'     => $snap->klasifikasi,
                'sumber'          => 'snapshot',
                'categories'      => [
                    ['code' => 'A', 'name' => 'RPPA',                          'score' => $snap->skor_a, 'maxScore' => 84,  'klasifikasi' => $snap->status_a],
                    ['code' => 'B', 'name' => 'Perlindungan Lansia & Disabilitas', 'score' => $snap->skor_b, 'maxScore' => 64,  'klasifikasi' => $snap->status_b],
                    ['code' => 'C', 'name' => 'Taruna Karya Mandiri',          'score' => $snap->skor_c, 'maxScore' => 20,  'klasifikasi' => $snap->status_c],
                    ['code' => 'D', 'name' => 'Sport Center',                  'score' => $snap->skor_d, 'maxScore' => 44,  'klasifikasi' => $snap->status_d],
                    ['code' => 'E', 'name' => 'Administrasi',                  'score' => $snap->skor_e, 'maxScore' => 8,   'klasifikasi' => null],
                    ['code' => 'F', 'name' => 'Publikasi',                     'score' => $snap->skor_f, 'maxScore' => 24,  'klasifikasi' => null],
                ],
            ];
        }

        // ── MERGE: assessment duluan, snapshot mengisi sisanya ───────────
        $results = array_merge($assessmentResults, $snapshotResults);

        // Urutkan: kabupaten → kecamatan alphabetically
        usort($results, function ($a, $b) {
            $kabCmp = strcmp($a['kabupaten'] ?? '', $b['kabupaten'] ?? '');
            return $kabCmp !== 0 ? $kabCmp : strcmp($a['kecamatan'] ?? '', $b['kecamatan'] ?? '');
        });

        // ── Pagination manual ────────────────────────────────────────────
        $perPage     = 20;
        $currentPage = max(1, (int) $request->query('page', 1));
        $total       = count($results);
        $totalPages  = (int) ceil($total / $perPage);
        $currentPage = min($currentPage, max(1, $totalPages));
        $paged       = array_slice($results, ($currentPage - 1) * $perPage, $perPage);

        return view('admin.assessment.results.index', [
            'results'      => $paged,
            'allResults'   => $results,
            'years'        => $years,
            'selectedYear' => $periodeFilter,
            'currentPage'  => $currentPage,
            'totalPages'   => $totalPages,
            'total'        => $total,
            'perPage'      => $perPage,
        ]);
    }

    /**
     * Semua assessment yang diisi oleh satu kecamatan.
     * Migrasi dari: /admin/assessment/results/[userId]/page.tsx
     */
    public function resultsByUser(string $userId): View
    {
        $user = User::findOrFail($userId);

        $entries = SelfAssessment::where('submitted_by_id', $userId)
            ->where('status', 'VALIDATED')
            ->with([
                'indicator.category.assessment:id,title,periode',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ])
            ->get();

        // Group per assessmentId_periode
        $map = [];
        foreach ($entries as $e) {
            $assessment = $e->indicator->category->assessment;
            $key = "{$assessment->id}_{$e->periode}";

            if (!isset($map[$key])) {
                $map[$key] = [
                    'assessmentId'    => $assessment->id,
                    'assessmentTitle' => $assessment->title,
                    'periode'         => $e->periode,
                    'totalScore'      => 0,
                    'maxScore'        => 0,
                    'catScores'       => [],
                ];
            }

            $effScore = $e->validations->first()?->validated_score ?? $e->score;
            $map[$key]['totalScore'] += $effScore;
            $map[$key]['maxScore']   += $e->indicator->max_score;

            $catCode = $e->indicator->category->code;
            if (!isset($map[$key]['catScores'][$catCode])) {
                $map[$key]['catScores'][$catCode] = ['code' => $catCode, 'score' => 0, 'maxScore' => 0];
            }
            $map[$key]['catScores'][$catCode]['score']    += $effScore;
            $map[$key]['catScores'][$catCode]['maxScore'] += $e->indicator->max_score;
        }

        $entriesResult = array_values(array_map(function ($g) {
            $catScores = array_values($g['catScores']);
            $g['statusAkhir'] = Scoring::getStatusAkhir($g['totalScore'], $g['maxScore'], $catScores);
            unset($g['catScores']);
            return $g;
        }, $map));

        return view('admin.assessment.results.user', compact('userId', 'user', 'entriesResult'));
    }

    /**
     * Detail pengisian per assessment oleh satu kecamatan.
     * Migrasi dari: /admin/assessment/results/[userId]/[assessmentId]/page.tsx
     */
    public function resultDetail(Request $request, string $userId, string $assessmentId): View
    {
        $periode = $request->query('periode');

        $user = User::findOrFail($userId);

        $assessment = Assessment::with([
            'categories' => fn ($q) => $q->orderBy('order'),
            'categories.indicators' => fn ($q) => $q->orderBy('number'),
        ])->findOrFail($assessmentId);

        // Self-assessment user ini untuk assessment ini, filter per periode jika ada
        $query = SelfAssessment::where('submitted_by_id', $userId)
            ->whereHas('indicator', fn ($q) => $q->where('assessment_id', $assessmentId))
            ->with([
                'indicator',
                'validations' => fn ($q) => $q->orderByDesc('validated_at'),
            ]);

        if ($periode) {
            $query->where('periode', $periode);
        }

        $entriesRaw = $query->get()->keyBy('indicator_id');

        // ── Hitung ringkasan per kategori ─────────────────────────────
        $catSummary = [];  // indexed by category code
        $totalScore = 0;
        $maxScore   = 0;

        foreach ($assessment->categories->sortBy('order') as $category) {
            $catTotal = 0;
            $catMax   = 0;

            foreach ($category->indicators->sortBy('number') as $ind) {
                $entry    = $entriesRaw->get($ind->id);
                $effScore = $entry?->validations->sortByDesc('validated_at')->first()?->validated_score
                            ?? $entry?->score
                            ?? 0;
                $catTotal += $effScore;
                $catMax   += $ind->max_score;
            }

            $totalScore += $catTotal;
            $maxScore   += $catMax;

            $scoringRule = $category->scoring_rule
                ? (is_string($category->scoring_rule) ? json_decode($category->scoring_rule, true) : $category->scoring_rule)
                : null;

            $catSummary[] = [
                'code'        => $category->code,
                'name'        => $category->name,
                'totalScore'  => $catTotal,
                'maxScore'    => $catMax,
                'klasifikasi' => Scoring::getKlasifikasiPerKategori($category->code, $catTotal),
                'customLabel' => $scoringRule ? Scoring::evaluateScoringRule($catTotal, $scoringRule) : null,
            ];
        }

        $categoryScores = array_map(fn ($c) => [
            'code'     => $c['code'],
            'score'    => $c['totalScore'],
            'maxScore' => $c['maxScore'],
        ], $catSummary);

        $statusAkhir = Scoring::getStatusAkhir($totalScore, $maxScore, $categoryScores);
        $pct         = $maxScore > 0 ? round(($totalScore / $maxScore) * 100) : 0;

        return view('admin.assessment.results.detail', [
            'userId'       => $userId,
            'assessmentId' => $assessmentId,
            'periode'      => $periode,
            'user'         => $user,
            'assessment'   => $assessment,
            'entries'      => $entriesRaw,
            'catSummary'   => $catSummary,
            'totalScore'   => $totalScore,
            'maxScore'     => $maxScore,
            'statusAkhir'  => $statusAkhir,
            'pct'          => $pct,
        ]);
    }
}
