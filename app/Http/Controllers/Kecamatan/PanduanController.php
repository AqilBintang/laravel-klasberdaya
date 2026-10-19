<?php

namespace App\Http\Controllers\Kecamatan;

use App\Http\Controllers\Controller;
use App\Models\AssessmentRubric;
use Illuminate\View\View;

class PanduanController extends Controller
{
    public function index(): View
    {
        $rubrics = AssessmentRubric::with([
            'assessment:id,title,periode',
            'items',
            'items.indicator:id,category_id,number,indicator',
            'items.indicator.category:id,assessment_id,code,name,order',
        ])
            ->whereHas('assessment', fn ($q) => $q->where('status', 'PUBLISHED'))
            ->orderByDesc('created_at')
            ->get();

        return view('kecamatan.panduan', compact('rubrics'));
    }
}
