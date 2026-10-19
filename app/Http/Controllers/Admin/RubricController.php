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

class RubricController extends Controller
{
    public function index(): View
    {
        $rubrics = AssessmentRubric::with('assessment:id,title,periode')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.rubric.index', compact('rubrics'));
    }

    public function create(): View
    {
        $assessments = Assessment::orderByDesc('created_at')
            ->get(['id', 'title', 'periode', 'status']);

        return view('admin.rubric.create', compact('assessments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'assessment_id' => ['required', 'exists:assessments,id'],
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:2000'],
        ]);

        AssessmentRubric::create([
            'assessment_id' => $request->assessment_id,
            'title'         => $request->title,
            'description'   => $request->description ?: null,
        ]);

        return redirect()->route('admin.rubric.index')
            ->with('success', 'Rubric berhasil ditambahkan.');
    }

    public function show(string $id): View
    {
        $rubric = AssessmentRubric::with([
            'assessment:id,title,periode',
            'items',
            'items.indicator:id,category_id,number,indicator',
            'items.indicator.category:id,assessment_id,code,name,order',
        ])->findOrFail($id);

        // Kelompokkan item per kategori, diurutkan
        $categories = [];
        foreach ($rubric->items as $item) {
            $cat = $item->indicator->category;
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

        return view('admin.rubric.show', compact('id', 'rubric', 'categories'));
    }

    public function edit(string $id): View
    {
        $rubric      = AssessmentRubric::with('assessment:id,title,periode')->findOrFail($id);
        $assessments = Assessment::orderByDesc('created_at')->get(['id', 'title', 'periode', 'status']);

        return view('admin.rubric.edit', compact('id', 'rubric', 'assessments'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $rubric = AssessmentRubric::findOrFail($id);

        $request->validate([
            'assessment_id' => ['required', 'exists:assessments,id'],
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:2000'],
        ]);

        $rubric->update([
            'assessment_id' => $request->assessment_id,
            'title'         => $request->title,
            'description'   => $request->description ?: null,
        ]);

        return redirect()->route('admin.rubric.show', $id)
            ->with('success', 'Rubric berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $rubric = AssessmentRubric::findOrFail($id);

        DB::transaction(function () use ($rubric) {
            RubricItem::where('rubric_id', $rubric->id)->delete();
            $rubric->delete();
        });

        return redirect()->route('admin.rubric.index')
            ->with('success', 'Rubric berhasil dihapus.');
    }
}
