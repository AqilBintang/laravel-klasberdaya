<?php

namespace App\Http\Controllers\Kabupaten;

use App\Http\Controllers\Controller;
use App\Models\KabupatenApproval;
use App\Models\SelfAssessment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        // Hitung jumlah kecamatan yang menunggu review kabupaten
        // (approval PENDING yang kecamatannya berada di wilayah kabupaten ini)
        $totalMenunggu = KabupatenApproval::where('status', 'PENDING')
            ->whereHas('kecamatanUser', function ($q) use ($user) {
                if ($user->kabupaten_id) {
                    $q->where('kabupaten_id', $user->kabupaten_id);
                } elseif ($user->kabupaten) {
                    $q->where('kabupaten', $user->kabupaten);
                }
            })
            ->count();

        $totalDisetujui = KabupatenApproval::where('status', 'APPROVED')
            ->where('kabupaten_user_id', $user->id)
            ->count();

        $totalRevisi = KabupatenApproval::where('status', 'NEEDS_REVISION')
            ->where('kabupaten_user_id', $user->id)
            ->count();

        return view('kabupaten.dashboard', compact(
            'user',
            'totalMenunggu',
            'totalDisetujui',
            'totalRevisi',
        ));
    }
}
