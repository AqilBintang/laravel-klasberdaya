<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use App\Models\AssessmentRubric;
use Illuminate\View\View;

class PanduanController extends Controller
{
    /**
     * GET /validator/panduan
     * Tampilkan semua panduan rubrik (semua status assessment) untuk referensi tim teknis.
     */
    public function index(): View
    {
        $rubrics = AssessmentRubric::with([
            'assessment:id,title,periode,status',
            'items',
            'items.indicator:id,category_id,number,indicator',
            'items.indicator.category:id,assessment_id,code,name,order',
        ])
            ->orderByDesc('created_at')
            ->get();

        return view('validator.panduan', compact('rubrics'));
    }
}
