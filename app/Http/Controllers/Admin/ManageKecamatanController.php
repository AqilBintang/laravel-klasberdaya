<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SelfAssessment;
use Illuminate\View\View;

class ManageKecamatanController extends Controller
{
    public function index(): View
    {
        // Ambil semua user kecamatan dengan relasi kabupaten wilayah
        $kecamatans = User::where('role', 'USER')
            ->with('kabupatenWilayah:id,nama')
            ->get(['id', 'name', 'kabupaten', 'kabupaten_id', 'is_active']);

        // Kelompokkan per kabupaten
        $grouped = [];
        foreach ($kecamatans as $u) {
            $kabNama = $u->kabupatenWilayah?->nama ?? $u->kabupaten ?? 'Tidak Diketahui';

            if (!isset($grouped[$kabNama])) {
                $grouped[$kabNama] = [
                    'nama'          => $kabNama,
                    'total'         => 0,
                    'aktif'         => 0,
                    'isiAssessment' => 0,
                    'tervalidasi'   => 0,
                ];
            }

            $grouped[$kabNama]['total']++;
            if ($u->is_active) {
                $grouped[$kabNama]['aktif']++;
            }
        }

        // Hitung isi & tervalidasi per kabupaten
        foreach ($kecamatans as $u) {
            $kabNama = $u->kabupatenWilayah?->nama ?? $u->kabupaten ?? 'Tidak Diketahui';

            $hasSubmitted = SelfAssessment::where('submitted_by_id', $u->id)
                ->whereIn('status', ['SUBMITTED', 'VALIDATED'])
                ->exists();

            $allValidated = SelfAssessment::where('submitted_by_id', $u->id)
                ->where('status', 'VALIDATED')
                ->exists()
                && !SelfAssessment::where('submitted_by_id', $u->id)
                    ->where('status', '!=', 'VALIDATED')
                    ->exists();

            if ($hasSubmitted) {
                $grouped[$kabNama]['isiAssessment']++;
            }
            if ($allValidated) {
                $grouped[$kabNama]['tervalidasi']++;
            }
        }

        $stats = array_values($grouped);
        usort($stats, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        return view('admin.manage-kecamatan', compact('stats'));
    }
}
