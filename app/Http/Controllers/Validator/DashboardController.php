<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use App\Models\SelfAssessment;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Jumlah self-assessment yang menunggu divalidasi (global)
        $totalMenunggu = SelfAssessment::where('status', 'SUBMITTED')->count();

        // Jumlah yang pernah divalidasi oleh user ini
        $totalDivalidasiByMe = SelfAssessment::whereHas('validations', function ($q) use ($user) {
            $q->where('validator_id', $user->id);
        })->count();

        return view('validator.dashboard', compact(
            'user',
            'totalMenunggu',
            'totalDivalidasiByMe',
        ));
    }
}
