<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentRubric;
use App\Models\RubricItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin Panduan Rubrik
 * Mengelola satu panduan rubrik per assessment.
 * Alur: admin buat/edit → muncul di /kecamatan/panduan
 */
class PanduanController extends Controller
{
    /**
     * GET /admin/panduan
     * Jika sudah ada rubrik → redirect langsung ke detail.
     * Jika belum → tampil halaman "belum ada" + tombol buat.
     */
    public function index(): View|RedirectResponse
    {
        $rubric = AssessmentRubric::with('assessment:id,title,periode')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->first();

        // Sudah ada rubrik → langsung ke detail
        if ($rubric) {
            return redirect()->route('admin.panduan.show', $rubric->id);
        }

        // Belum ada → pass assessments untuk form buat
        $assessments = Assessment::orderByDesc('created_at')->get(['id', 'title', 'periode', 'status']);
        return view('admin.panduan.index', compact('assessments'));
    }

    /**
     * GET /admin/panduan/new
     * Form buat rubrik baru (tanpa items dulu, items diisi setelah rubrik dibuat).
     */
    public function create(): View
    {
        $assessments = Assessment::orderByDesc('created_at')->get(['id', 'title', 'periode', 'status']);
        return view('admin.panduan.create', compact('assessments'));
    }

    /**
     * POST /admin/panduan
     * Simpan rubrik baru beserta semua items sekaligus.
     * Items dikirim sebagai JSON array: [[s1,s2,s3,s4], ...]
     * Mapping ke indikator berdasarkan urutan aktif assessment.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'assessment_id' => ['required', 'exists:assessments,id'],
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:2000'],
        ]);

        $rubric = AssessmentRubric::create([
            'assessment_id' => $request->assessment_id,
            'title'         => $request->title,
            'description'   => $request->description ?: null,
        ]);

        return redirect()->route('admin.panduan.show', $rubric->id)
            ->with('success', 'Panduan rubrik berhasil dibuat. Silakan isi deskripsi tiap skor per indikator.');
    }

    /**
     * GET /admin/panduan/{id}
     * Detail panduan rubrik — tampil tabel per kategori dengan skor 1–4.
     */
    public function show(string $id): View
    {
        $rubric = AssessmentRubric::with([
            'assessment:id,title,periode',
            'items',
            'items.indicator:id,category_id,number,indicator',
            'items.indicator.category:id,assessment_id,code,name,order',
        ])->findOrFail($id);

        $categories = $this->groupByCategory($rubric);

        // Semua indikator aktif dari assessment yang belum punya item (untuk info kelengkapan)
        $totalIndicators = $rubric->assessment
            ? \App\Models\AssessmentIndicator::where('assessment_id', $rubric->assessment_id)
                ->where('is_active', true)->count()
            : 0;

        return view('admin.panduan.show', compact('id', 'rubric', 'categories', 'totalIndicators'));
    }

    /**
     * GET /admin/panduan/{id}/edit
     * Form edit — tampil tabel dengan textarea per sel skor, bisa diedit inline.
     */
    public function edit(string $id): View
    {
        $rubric = AssessmentRubric::with([
            'assessment:id,title,periode',
            'items',
            'items.indicator:id,category_id,number,indicator',
            'items.indicator.category:id,assessment_id,code,name,order',
        ])->findOrFail($id);

        $categories = $this->groupByCategory($rubric);
        $assessments = Assessment::orderByDesc('created_at')->get(['id', 'title', 'periode', 'status']);

        return view('admin.panduan.edit', compact('id', 'rubric', 'categories', 'assessments'));
    }

    /**
     * PUT /admin/panduan/{id}
     * Update metadata rubrik + semua konten skor per item.
     * Input items: items[{rubric_item_id}][score1], [score2], [score3], [score4]
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $rubric = AssessmentRubric::findOrFail($id);

        $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $rubric) {
            $rubric->update([
                'title'       => $request->title,
                'description' => $request->description ?: null,
            ]);

            // Update konten tiap item
            $items = $request->input('items', []);
            foreach ($items as $itemId => $scores) {
                RubricItem::where('id', $itemId)
                    ->where('rubric_id', $rubric->id) // pastikan ownership
                    ->update([
                        'score1' => $scores['score1'] ?? '',
                        'score2' => $scores['score2'] ?? '',
                        'score3' => $scores['score3'] ?? '',
                        'score4' => $scores['score4'] ?? '',
                    ]);
            }
        });

        return redirect()->route('admin.panduan.show', $id)
            ->with('success', 'Panduan rubrik berhasil diperbarui.');
    }

    /**
     * DELETE /admin/panduan/{id}
     * Hapus rubrik beserta semua items-nya.
     */
    public function destroy(string $id): RedirectResponse
    {
        $rubric = AssessmentRubric::findOrFail($id);

        DB::transaction(function () use ($rubric) {
            RubricItem::where('rubric_id', $rubric->id)->delete();
            $rubric->delete();
        });

        return redirect()->route('admin.panduan.index')
            ->with('success', 'Panduan rubrik berhasil dihapus.');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function groupByCategory(AssessmentRubric $rubric): array
    {
        $categories = [];
        foreach ($rubric->items as $item) {
            $cat = $item->indicator->category;
            if (!$cat) continue;
            $key = $cat->id;
            if (!isset($categories[$key])) {
                $categories[$key] = [
                    'code'  => $cat->code,
                    'name'  => $cat->name,
                    'order' => $cat->order,
                    'items' => [],
                ];
            }
            $categories[$key]['items'][] = $item;
        }
        usort($categories, fn ($a, $b) => $a['order'] <=> $b['order']);
        // Sort items per kategori by indicator number
        foreach ($categories as &$cat) {
            usort($cat['items'], fn ($a, $b) => $a->indicator->number <=> $b->indicator->number);
        }
        return $categories;
    }
}
