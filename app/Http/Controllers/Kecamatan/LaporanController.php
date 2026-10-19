<?php

namespace App\Http\Controllers\Kecamatan;

use App\Exports\LaporanYearlyExport;
use App\Http\Controllers\Controller;
use App\Models\SelfAssessment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    /**
     * Tampilkan halaman Laporan Tahunan.
     * Jika ada query param ?tahun=XXXX, langsung trigger download Excel.
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user            = $request->user();
        $availableYears  = $this->getAvailableYears($user->id);

        // ── Download Excel ──────────────────────────────────────────
        if ($request->filled('tahun')) {
            $tahun = (int) $request->query('tahun');

            if ($tahun < 2000 || $tahun > 2100) {
                return back()->with('error', 'Parameter tahun tidak valid.');
            }

            if (!in_array($tahun, $availableYears)) {
                return back()->with('error', "Tidak ada data assessment untuk tahun {$tahun}.");
            }

            try {
                $kecamatanNama = $user->kecamatanWilayah?->nama ?? $user->kecamatan ?? 'kecamatan';
                $filename      = LaporanYearlyExport::filename($kecamatanNama, $tahun);

                return Excel::download(
                    new LaporanYearlyExport($user->id, $tahun),
                    $filename,
                );
            } catch (\Throwable $e) {
                return back()->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
            }
        }

        // ── Tampilkan halaman ───────────────────────────────────────
        return view('kecamatan.laporan', [
            'availableYears' => $availableYears,
            'user'           => $user,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    /**
     * Ambil daftar tahun yang memiliki data self-assessment untuk user ini.
     * Periode bisa berformat "2025", "2025-1", "Semester 1 2025", dsb.
     *
     * @return int[]  Tahun diurutkan descending
     */
    private function getAvailableYears(int $userId): array
    {
        $periodes = SelfAssessment::where('submitted_by_id', $userId)
            ->select('periode')
            ->distinct()
            ->pluck('periode');

        $years = [];
        foreach ($periodes as $periode) {
            if (preg_match('/\b(20\d{2})\b/', $periode, $match)) {
                $years[] = (int) $match[1];
            }
        }

        $years = array_values(array_unique($years));
        rsort($years);

        return $years;
    }
}
