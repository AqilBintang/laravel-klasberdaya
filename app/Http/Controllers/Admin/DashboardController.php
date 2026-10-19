<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\SelfAssessment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // ─── Statistik ringkasan ───────────────────────────────────────────
        $totalKecamatan = User::where('role', 'USER')
            ->where('is_active', true)
            ->count();

        $totalAssessmentPublished = Assessment::where('status', 'PUBLISHED')->count();

        $totalMenunggu = SelfAssessment::where('status', 'SUBMITTED')->count();

        // Kecamatan yang sudah punya minimal 1 self-assessment VALIDATED
        // dan tidak punya self-assessment yang statusnya bukan VALIDATED
        $totalDivalidasi = User::where('role', 'USER')
            ->where('is_active', true)
            ->whereHas('selfAssessments')
            ->whereDoesntHave('selfAssessments', function ($q) {
                $q->where('status', '!=', 'VALIDATED');
            })
            ->count();

        // ─── Assessment terbaru (5 terakhir dibuat) ────────────────────────
        $assessmentTerbaru = Assessment::with('categories')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'title', 'periode', 'status', 'created_at']);

        // ─── Log aktivitas (10 terbaru) ────────────────────────────────────
        $activityLog = AuditLog::with('user:id,name,role,kecamatan,kabupaten')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get(['id', 'user_id', 'action', 'target_type', 'target_id', 'details', 'metadata', 'created_at']);

        return view('admin.dashboard', compact(
            'totalKecamatan',
            'totalAssessmentPublished',
            'totalMenunggu',
            'totalDivalidasi',
            'assessmentTerbaru',
            'activityLog',
        ));
    }
}
