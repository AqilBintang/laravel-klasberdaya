<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Scoring;
use App\Http\Controllers\Controller;
use App\Models\AssessmentVersion;
use App\Models\KlasifikasiSnapshot;
use App\Models\SelfAssessment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * KlasifikasiBerdayaController
 *
 * Menampilkan halaman statistik klasifikasi kecamatan.
 *
 * SUMBER DATA (prioritas):
 *  1. Data dari alur assessment reguler (self_assessments VALIDATED → user role USER)
 *  2. Data dari tabel klasifikasi_snapshots (import xlsx rekapitulasi tim teknis)
 *
 * Per kecamatan, data assessment selalu menimpa snapshot jika tersedia.
 * Kecamatan yang belum punya data assessment akan ditampilkan dari snapshot.
 */
class KlasifikasiBerdayaController extends Controller
{
    public function index(Request $request): View
    {
        $tahunFilter = $request->query('tahun');

        // ── SUMBER 1: Data dari alur assessment ─────────────────────
        $assessmentEntries = $this->loadFromAssessment();

        // ── SUMBER 2: Data dari snapshot import xlsx ─────────────────
        $snapshotEntries = $this->loadFromSnapshot();

        // ── MERGE: assessment menimpa snapshot per kecamatan+tahun ───
        // Key: "kecamatan_lower|tahun"
        $merged = $snapshotEntries;
        foreach ($assessmentEntries as $key => $entry) {
            $merged[$key] = $entry; // assessment selalu menang
        }

        if (empty($merged)) {
            return view('admin.klasifikasi-berdaya', ['totalRegistered' => 0]);
        }

        $allEntries = array_values($merged);

        // ── Daftar tahun tersedia ─────────────────────────────────────
        $years = array_values(array_unique(array_column($allEntries, 'year')));
        sort($years);

        $activeYear = ($tahunFilter && in_array($tahunFilter, $years))
            ? (string) $tahunFilter
            : ($years ? (string) end($years) : null);

        // ── Hitung summary & distribusi kabupaten ─────────────────────
        $filteredEntries = array_filter($allEntries, fn ($e) => (string) $e['year'] === (string) $activeYear);

        $summary = ['Rintisan' => 0, 'Berkembang' => 0, 'Maju' => 0, 'Unggul' => 0];
        $kabMap  = [];

        foreach ($filteredEntries as $e) {
            $s = $e['status'] ?? null;
            if ($s && isset($summary[$s])) {
                $summary[$s]++;
            }

            $kabKey = strtolower(trim($e['kabupatenNama']));
            if (!isset($kabMap[$kabKey])) {
                $kabMap[$kabKey] = [
                    'nama'   => $e['kabupatenNama'],
                    'counts' => ['Rintisan' => 0, 'Berkembang' => 0, 'Maju' => 0, 'Unggul' => 0],
                    'total'  => 0,
                ];
            }
            $kabMap[$kabKey]['total']++;
            if ($s && isset($kabMap[$kabKey]['counts'][$s])) {
                $kabMap[$kabKey]['counts'][$s]++;
            }
        }

        $kabDistribusi = array_values($kabMap);
        usort($kabDistribusi, fn ($a, $b) => strcmp($a['nama'], $b['nama']));

        // ── Chart data per tahun ──────────────────────────────────────
        $chartData = array_map(function ($year) use ($allEntries) {
            $row = ['year' => $year, 'rintisan' => 0, 'berkembang' => 0, 'maju' => 0, 'unggul' => 0];
            foreach ($allEntries as $e) {
                if ((string) $e['year'] !== (string) $year) continue;
                match ($e['status'] ?? '') {
                    'Rintisan'   => $row['rintisan']++,
                    'Berkembang' => $row['berkembang']++,
                    'Maju'       => $row['maju']++,
                    'Unggul'     => $row['unggul']++,
                    default      => null,
                };
            }
            return $row;
        }, $years);

        // Total terdaftar: jumlah unik kecamatan di tahun aktif
        $totalRegistered = count($filteredEntries);

        // Total tervalidasi: hanya yang berasal dari alur assessment
        $totalValidated = count(array_filter(
            $filteredEntries,
            fn ($e) => ($e['sumber'] ?? 'snapshot') === 'assessment'
        ));

        return view('admin.klasifikasi-berdaya', [
            'totalRegistered' => $totalRegistered,
            'totalValidated'  => $totalValidated,
            'activeYear'      => $activeYear,
            'years'           => $years,
            'chartData'       => $chartData,
            'kabDistribusi'   => $kabDistribusi,
            'summary'         => $summary,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE: Load dari alur assessment (logika lama, tidak diubah)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @return array<string, array>  key = "kecamatan_lower|tahun"
     */
    private function loadFromAssessment(): array
    {
        $users = User::where('role', 'USER')
            ->where('is_active', true)
            ->whereNotNull('kecamatan_id')
            ->with([
                'kabupatenWilayah:id,nama',
                'kecamatanWilayah:id,nama',
            ])
            ->get(['id', 'kabupaten_id', 'kecamatan_id', 'kabupaten', 'kecamatan']);

        if ($users->isEmpty()) return [];

        $userIds = $users->pluck('id')->all();

        $entries = SelfAssessment::where('status', 'VALIDATED')
            ->whereIn('submitted_by_id', $userIds)
            ->with([
                'indicator:id,max_score,version_id,category_id',
                'indicator.version:id,version_number',
                'indicator.category:id,assessment_id,code',
                'validations' => fn ($q) => $q->latest('validated_at')->limit(1)
                    ->select('id', 'self_assessment_id', 'validated_score'),
            ])
            ->get(['id', 'submitted_by_id', 'periode', 'score', 'indicator_id']);

        if ($entries->isEmpty()) return [];

        $versionIds = $entries->pluck('indicator.version_id')->unique()->all();
        $indicatorCountsByVersion = AssessmentVersion::whereIn('id', $versionIds)
            ->withCount('indicators')
            ->pluck('indicators_count', 'id');

        $groupMap = [];
        foreach ($entries as $e) {
            $ind = $e->indicator;
            $cat = $ind->category;
            $eff = $e->validations->first()?->validated_score ?? $e->score;
            $key = "{$e->submitted_by_id}_{$cat->assessment_id}_{$e->periode}_{$ind->version_id}";

            if (!isset($groupMap[$key])) {
                $groupMap[$key] = [
                    'userId'         => $e->submitted_by_id,
                    'assessmentId'   => $cat->assessment_id,
                    'periode'        => $e->periode,
                    'versionId'      => $ind->version_id,
                    'versionNumber'  => $ind->version->version_number,
                    'indicatorCount' => 0,
                    'totalScore'     => 0,
                    'maxPossible'    => 0,
                    'catMap'         => [],
                ];
            }

            $groupMap[$key]['indicatorCount']++;
            $groupMap[$key]['totalScore']  += $eff;
            $groupMap[$key]['maxPossible'] += $ind->max_score;

            $code = $cat->code;
            if (!isset($groupMap[$key]['catMap'][$code])) {
                $groupMap[$key]['catMap'][$code] = ['code' => $code, 'score' => 0, 'maxScore' => 0];
            }
            $groupMap[$key]['catMap'][$code]['score']    += $eff;
            $groupMap[$key]['catMap'][$code]['maxScore'] += $ind->max_score;
        }

        // Ambil snapshot terbaru per user+assessment+periode
        $latestBySubmission = [];
        foreach ($groupMap as $g) {
            if (($indicatorCountsByVersion[$g['versionId']] ?? 0) !== $g['indicatorCount']) {
                continue;
            }
            $sKey = "{$g['userId']}_{$g['assessmentId']}_{$g['periode']}";
            $cur  = $latestBySubmission[$sKey] ?? null;
            if (!$cur || $g['versionNumber'] > $cur['versionNumber']) {
                $latestBySubmission[$sKey] = $g;
            }
        }

        // Ambil status terbaru per user per tahun
        $userMap   = $users->keyBy('id');
        $result    = [];

        foreach ($latestBySubmission as $g) {
            preg_match('/\b(20\d{2})\b/', $g['periode'], $m);
            $year = $m[1] ?? null;
            if (!$year) continue;

            $u    = $userMap[$g['userId']];
            $kab  = strtolower(trim($u->kabupatenWilayah?->nama ?? $u->kabupaten ?? ''));
            $kec  = strtolower(trim($u->kecamatanWilayah?->nama ?? $u->kecamatan ?? ''));
            $mKey = "{$kab}|{$kec}|{$year}";

            $cur    = $result[$mKey] ?? null;
            $status = Scoring::getStatusAkhir(
                $g['totalScore'], $g['maxPossible'], array_values($g['catMap'])
            );

            if (!$cur
                || $g['periode'] > $cur['periode']
                || ($g['periode'] === $cur['periode'] && $g['versionNumber'] > $cur['versionNumber'])
            ) {
                $result[$mKey] = [
                    'year'          => $year,
                    'periode'       => $g['periode'],
                    'versionNumber' => $g['versionNumber'],
                    'status'        => $status,
                    'kabupatenNama' => $u->kabupatenWilayah?->nama ?? $u->kabupaten ?? 'Tidak Diketahui',
                    'kecamatanNama' => $u->kecamatanWilayah?->nama ?? $u->kecamatan ?? '—',
                    'sumber'        => 'assessment',
                ];
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE: Load dari tabel klasifikasi_snapshots
    // ─────────────────────────────────────────────────────────────────

    /**
     * @return array<string, array>  key = "kabupaten_lower|kecamatan_lower|tahun"
     */
    private function loadFromSnapshot(): array
    {
        $snapshots = KlasifikasiSnapshot::all();
        $result    = [];

        foreach ($snapshots as $snap) {
            $kab  = strtolower(trim($snap->kabupaten));
            $kec  = strtolower(trim($snap->kecamatan));
            $key  = "{$kab}|{$kec}|{$snap->tahun}";

            $result[$key] = [
                'year'          => (string) $snap->tahun,
                'periode'       => (string) $snap->tahun,
                'status'        => $snap->klasifikasi,
                'kabupatenNama' => $snap->kabupaten,
                'kecamatanNama' => $snap->kecamatan,
                'sumber'        => 'snapshot',
            ];
        }

        return $result;
    }
}
