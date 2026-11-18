<?php

namespace App\Http\Controllers\Kabupaten;

use App\Http\Controllers\Controller;
use App\Models\KabupatenApproval;
use App\Models\SelfAssessment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $wilayahScope = function ($q) use ($user) {
            if ($user->kabupaten_id) {
                $q->where('kabupaten_id', $user->kabupaten_id);
            } elseif ($user->kabupaten) {
                $q->where('kabupaten', $user->kabupaten);
            }
        };

        // Total kecamatan (role USER) di wilayah ini
        $totalKecamatan = User::where('role', 'USER')
            ->where('is_active', true)
            ->when($user->kabupaten_id, fn ($q) => $q->where('kabupaten_id', $user->kabupaten_id))
            ->when(!$user->kabupaten_id && $user->kabupaten, fn ($q) => $q->where('kabupaten', $user->kabupaten))
            ->count();

        // ── Backfill otomatis ─────────────────────────────────────────────
        // Pastikan setiap kecamatan yang sudah SUBMITTED punya KabupatenApproval.
        $kecamatanIdsInWilayah = User::where('role', 'USER')
            ->where('is_active', true)
            ->when($user->kabupaten_id, fn ($q) => $q->where('kabupaten_id', $user->kabupaten_id))
            ->when(!$user->kabupaten_id && $user->kabupaten, fn ($q) => $q->where('kabupaten', $user->kabupaten))
            ->pluck('id');

        if ($kecamatanIdsInWilayah->isNotEmpty()) {
            $orphanGroups = SelfAssessment::whereIn('submitted_by_id', $kecamatanIdsInWilayah)
                ->where('status', 'SUBMITTED')
                ->with('indicator.category:id,assessment_id')
                ->get()
                ->map(fn ($e) => [
                    'kecamatan_user_id' => $e->submitted_by_id,
                    'assessment_id'     => $e->indicator->category->assessment_id,
                    'periode'           => $e->periode,
                ])
                ->unique(fn ($r) => "{$r['kecamatan_user_id']}_{$r['assessment_id']}_{$r['periode']}")
                ->values();

            foreach ($orphanGroups as $g) {
                KabupatenApproval::firstOrCreate(
                    [
                        'kecamatan_user_id' => $g['kecamatan_user_id'],
                        'assessment_id'     => $g['assessment_id'],
                        'periode'           => $g['periode'],
                    ],
                    [
                        'status'            => 'PENDING',
                        'kabupaten_user_id' => null,
                        'notes'             => null,
                        'reviewed_at'       => null,
                    ]
                );
            }
        }

        // Kecamatan yang sudah submit (punya record KabupatenApproval)
        $totalSudahSubmit = KabupatenApproval::whereHas('kecamatanUser', $wilayahScope)
            ->distinct('kecamatan_user_id')
            ->count('kecamatan_user_id');

        // Menunggu review: PENDING di wilayah kabupaten ini
        $totalMenunggu = KabupatenApproval::where('status', 'PENDING')
            ->whereHas('kecamatanUser', $wilayahScope)
            ->count();

        // Sudah disetujui: APPROVED di wilayah ini
        $totalDisetujui = KabupatenApproval::where('status', 'APPROVED')
            ->whereHas('kecamatanUser', $wilayahScope)
            ->count();

        // Perlu perbaikan: NEEDS_REVISION di wilayah ini
        $totalRevisi = KabupatenApproval::where('status', 'NEEDS_REVISION')
            ->whereHas('kecamatanUser', $wilayahScope)
            ->count();

        // Belum submit sama sekali
        $totalBelumSubmit = max(0, $totalKecamatan - $totalSudahSubmit);

        return view('kabupaten.dashboard', compact(
            'user',
            'totalKecamatan',
            'totalSudahSubmit',
            'totalBelumSubmit',
            'totalMenunggu',
            'totalDisetujui',
            'totalRevisi',
        ));
    }
}
