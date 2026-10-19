<?php

namespace App\Http\Controllers\Landing;

use App\Helpers\Scoring;
use App\Http\Controllers\Controller;
use App\Models\AssessmentVersion;
use App\Models\KlasifikasiSnapshot;
use App\Models\SelfAssessment;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PublicKlasifikasiController
 *
 * Endpoint publik untuk landing page — dipanggil AJAX filter peta Jawa Tengah.
 * Migrasi dari: src/app/api/public/klasifikasi/route.ts
 *
 * GET /public/klasifikasi
 *   ?kabKotaKode=33xx      → distribusi chart satu kabupaten/kota
 *   ?kecamatanKode=33xxxx  → skor per tahun satu kecamatan
 *   (tanpa param)          → distribusi chart seluruh Jawa Tengah
 *
 * Response JSON:
 * {
 *   "data": {
 *     "label": "...",
 *     "chartData": [...],      // distribusi mode
 *     "skorPerTahun": [...]    // kecamatan mode
 *   }
 * }
 */
class PublicKlasifikasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $kabKotaKode   = $request->query('kabKotaKode');
        $kecamatanKode = $request->query('kecamatanKode');

        $kabupatenId   = null;
        $kabupatenNama = null;
        $label         = 'Jawa Tengah';

        if ($kabKotaKode) {
            $kab = Wilayah::where('kode', $kabKotaKode)->where('level', 2)->first(['id', 'nama']);
            if (!$kab) {
                return response()->json(['error' => 'Kabupaten/kota tidak ditemukan.'], 400);
            }
            $kabupatenId   = $kab->id;
            $kabupatenNama = $kab->nama;
            $label         = $kab->nama;
        }

        if ($kecamatanKode) {
            $kec = Wilayah::where('kode', $kecamatanKode)->where('level', 3)->first(['id', 'nama']);
            if (!$kec) {
                return response()->json(['error' => 'Kecamatan tidak ditemukan.'], 400);
            }
            $label = $kabupatenNama ? "{$kabupatenNama} › {$kec->nama}" : $kec->nama;
            $data  = $this->getKecamatanData($kec->id, $kec->nama, $kabupatenNama);
            return response()->json(['data' => array_merge($data, ['label' => $label])]);
        }

        $data = $this->getDistribusiData($kabupatenId, $kabupatenNama, $label);
        return response()->json(['data' => $data]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Distribusi: chart rintisan/berkembang/maju/unggul per tahun
    // ─────────────────────────────────────────────────────────────────────────

    private function getDistribusiData(?int $kabupatenId, ?string $kabupatenNama, string $label): array
    {
        // SUMBER 1: assessment VALIDATED
        $userQuery = User::where('role', 'USER')
            ->where('is_active', true)
            ->whereNotNull('kecamatan_id');
        if ($kabupatenId) {
            $userQuery->where('kabupaten_id', $kabupatenId);
        }
        $userIds = $userQuery->pluck('id')->all();

        $assessmentResults = []; // key: "kab|kec|tahun" → status

        if (!empty($userIds)) {
            $users = User::whereIn('id', $userIds)->get(['id', 'kabupaten', 'kecamatan']);

            $entries = SelfAssessment::where('status', 'VALIDATED')
                ->whereIn('submitted_by_id', $userIds)
                ->with([
                    'indicator:id,category_id,max_score,version_id',
                    'indicator.category:id,assessment_id,code',
                    'indicator.version:id,version_number',
                    'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
                ])
                ->get(['id', 'submitted_by_id', 'periode', 'indicator_id', 'score']);

            if ($entries->isNotEmpty()) {
                $userMap = $users->keyBy('id');

                $versionIds = $entries->pluck('indicator.version_id')->filter()->unique()->all();
                $indicatorCountsByVersion = [];
                if (!empty($versionIds)) {
                    AssessmentVersion::whereIn('id', $versionIds)->withCount('indicators')->get(['id'])
                        ->each(fn ($r) => $indicatorCountsByVersion[$r->id] = $r->indicators_count);
                }

                $groupsMap = [];
                foreach ($entries as $e) {
                    $ind = $e->indicator;
                    $cat = $ind->category;
                    if (!$cat->assessment_id || !$ind->version_id) continue;
                    $key = "{$e->submitted_by_id}_{$cat->assessment_id}_{$e->periode}_{$ind->version_id}";
                    $eff = $e->validations->first()?->validated_score ?? $e->score;
                    if (!isset($groupsMap[$key])) {
                        $u = $userMap[$e->submitted_by_id];
                        $groupsMap[$key] = [
                            'assessmentId'   => $cat->assessment_id,
                            'periode'        => $e->periode,
                            'versionId'      => $ind->version_id,
                            'versionNumber'  => $ind->version->version_number ?? 1,
                            'indicatorCount' => 0, 'totalScore' => 0, 'maxPossible' => 0,
                            'catMap'         => [],
                            'kab'            => strtolower(trim($u->kabupaten ?? '')),
                            'kec'            => strtolower(trim($u->kecamatan ?? '')),
                        ];
                    }
                    $g = &$groupsMap[$key];
                    $g['indicatorCount']++;
                    $g['totalScore']  += $eff;
                    $g['maxPossible'] += $ind->max_score;
                    $catCode = $cat->code;
                    if (!isset($g['catMap'][$catCode])) {
                        $g['catMap'][$catCode] = ['code' => $catCode, 'score' => 0, 'maxScore' => 0];
                    }
                    $g['catMap'][$catCode]['score']    += $eff;
                    $g['catMap'][$catCode]['maxScore'] += $ind->max_score;
                    unset($g);
                }

                $latestByKecYear = [];
                foreach ($groupsMap as $g) {
                    $expected = $indicatorCountsByVersion[$g['versionId']] ?? 0;
                    if ($g['indicatorCount'] !== $expected) continue;
                    preg_match('/\d{4}/', $g['periode'], $m);
                    $year = $m[0] ?? null;
                    if (!$year) continue;
                    $k   = "{$g['kab']}|{$g['kec']}|{$year}";
                    $cur = $latestByKecYear[$k] ?? null;
                    if (!$cur
                        || $g['periode'] > $cur['periode']
                        || ($g['periode'] === $cur['periode'] && $g['versionNumber'] > $cur['versionNumber'])
                    ) {
                        $latestByKecYear[$k] = $g;
                    }
                }

                foreach ($latestByKecYear as $k => $g) {
                    $assessmentResults[$k] = Scoring::getStatusAkhir(
                        $g['totalScore'], $g['maxPossible'], array_values($g['catMap'])
                    );
                }
            }
        }

        // SUMBER 2: snapshot
        // Nama kabupaten di tabel wilayah pakai prefix "Kabupaten " (mis. "Kabupaten Cilacap")
        // sedangkan snapshot menyimpan tanpa prefix (mis. "CILACAP").
        // Strip prefix "Kabupaten " sebelum membandingkan; "Kota ..." sudah cocok di kedua sisi.
        $snapQuery = KlasifikasiSnapshot::query();
        if ($kabupatenNama) {
            $snapNama = preg_replace('/^kabupaten\s+/i', '', trim($kabupatenNama));
            $snapQuery->whereRaw('LOWER(TRIM(kabupaten)) = ?', [strtolower(trim($snapNama))]);
        }
        $snapshotMerge = [];
        foreach ($snapQuery->get(['kabupaten', 'kecamatan', 'tahun', 'klasifikasi']) as $snap) {
            // Cast tahun ke string agar perbandingan === konsisten (kolom smallint → integer di PHP)
            $k = strtolower(trim($snap->kabupaten)) . '|' . strtolower(trim($snap->kecamatan)) . '|' . (string) $snap->tahun;
            $snapshotMerge[$k] = $snap->klasifikasi;
        }

        // Merge — assessment menang
        $merged = $snapshotMerge;
        foreach ($assessmentResults as $k => $status) {
            $merged[$k] = $status;
        }

        if (empty($merged)) {
            return ['label' => $label, 'chartData' => []];
        }

        // Build chart — cast semua tahun ke string agar strict comparison konsisten
        $yearSet = [];
        foreach ($merged as $k => $s) {
            $parts = explode('|', $k);
            if (isset($parts[2])) $yearSet[(string) $parts[2]] = true;
        }
        $years = array_map('strval', array_keys($yearSet));
        sort($years);

        $chartData = [];
        foreach ($years as $year) {
            $dist = ['year' => $year, 'rintisan' => 0, 'berkembang' => 0, 'maju' => 0, 'unggul' => 0];
            foreach ($merged as $k => $status) {
                $parts = explode('|', $k);
                if (!isset($parts[2]) || (string) $parts[2] !== $year) continue;
                match ($status) {
                    'Rintisan'   => $dist['rintisan']++,
                    'Berkembang' => $dist['berkembang']++,
                    'Maju'       => $dist['maju']++,
                    'Unggul'     => $dist['unggul']++,
                    default      => null,
                };
            }
            $chartData[] = $dist;
        }

        return ['label' => $label, 'chartData' => $chartData];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Kecamatan: skor + klasifikasi per tahun untuk satu kecamatan
    // ─────────────────────────────────────────────────────────────────────────

    private function getKecamatanData(int $kecamatanId, string $kecNama, ?string $kabNama): array
    {
        $skorPerTahun = [];

        // Cek assessment dulu
        $user = User::where('role', 'USER')
            ->where('is_active', true)
            ->where('kecamatan_id', $kecamatanId)
            ->first(['id']);

        if ($user) {
            $entries = SelfAssessment::where('status', 'VALIDATED')
                ->where('submitted_by_id', $user->id)
                ->with([
                    'indicator:id,category_id,max_score,version_id',
                    'indicator.category:id,assessment_id,code',
                    'indicator.version:id,version_number',
                    'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
                ])
                ->get(['id', 'submitted_by_id', 'periode', 'indicator_id', 'score']);

            if ($entries->isNotEmpty()) {
                $versionIds = $entries->pluck('indicator.version_id')->filter()->unique()->all();
                $indicatorCountsByVersion = [];
                if (!empty($versionIds)) {
                    AssessmentVersion::whereIn('id', $versionIds)->withCount('indicators')->get(['id'])
                        ->each(fn ($r) => $indicatorCountsByVersion[$r->id] = $r->indicators_count);
                }

                $groupsMap = [];
                foreach ($entries as $e) {
                    $ind = $e->indicator;
                    $cat = $ind->category;
                    if (!$cat->assessment_id || !$ind->version_id) continue;
                    $key = "{$cat->assessment_id}_{$e->periode}_{$ind->version_id}";
                    $eff = $e->validations->first()?->validated_score ?? $e->score;
                    if (!isset($groupsMap[$key])) {
                        $groupsMap[$key] = [
                            'assessmentId'   => $cat->assessment_id,
                            'periode'        => $e->periode,
                            'versionId'      => $ind->version_id,
                            'versionNumber'  => $ind->version->version_number ?? 1,
                            'indicatorCount' => 0, 'totalScore' => 0, 'maxPossible' => 0,
                            'catMap'         => [],
                        ];
                    }
                    $g = &$groupsMap[$key];
                    $g['indicatorCount']++;
                    $g['totalScore']  += $eff;
                    $g['maxPossible'] += $ind->max_score;
                    $catCode = $cat->code;
                    if (!isset($g['catMap'][$catCode])) {
                        $g['catMap'][$catCode] = ['code' => $catCode, 'score' => 0, 'maxScore' => 0];
                    }
                    $g['catMap'][$catCode]['score']    += $eff;
                    $g['catMap'][$catCode]['maxScore'] += $ind->max_score;
                    unset($g);
                }

                foreach ($groupsMap as $g) {
                    $expected = $indicatorCountsByVersion[$g['versionId']] ?? 0;
                    if ($g['indicatorCount'] !== $expected) continue;
                    preg_match('/\d{4}/', $g['periode'], $m);
                    $year = $m[0] ?? null;
                    if (!$year) continue;
                    $weighted = Scoring::calculateWeightedScore(array_values($g['catMap']));
                    $status   = Scoring::getStatusAkhir($g['totalScore'], $g['maxPossible'], array_values($g['catMap']));
                    $cur = $skorPerTahun[$year] ?? null;
                    if (!$cur
                        || $g['periode'] > ($cur['_periode'] ?? '')
                        || ($g['periode'] === ($cur['_periode'] ?? '') && $g['versionNumber'] > ($cur['_version'] ?? 0))
                    ) {
                        $skorPerTahun[$year] = [
                            'year'             => (string) $year,
                            'weightedScore'    => round($weighted['weightedScore'], 2),
                            'maxWeightedScore' => round($weighted['maxWeightedScore'], 2),
                            'statusAkhir'      => $status,
                            '_periode'         => $g['periode'],
                            '_version'         => $g['versionNumber'],
                        ];
                    }
                }
            }
        }

        // Fallback ke snapshot jika belum ada data assessment
        if (empty($skorPerTahun)) {
            // Nama kabupaten di wilayah pakai prefix "Kabupaten " — strip sebelum bandingkan
            $snapKabNama = $kabNama ? preg_replace('/^kabupaten\s+/i', '', trim($kabNama)) : null;

            $snaps = KlasifikasiSnapshot::whereRaw('LOWER(TRIM(kecamatan)) = ?', [strtolower(trim($kecNama))])
                ->when($snapKabNama, fn ($q) => $q->whereRaw('LOWER(TRIM(kabupaten)) = ?', [strtolower(trim($snapKabNama))]))
                ->get(['tahun', 'skor_final', 'klasifikasi']);

            foreach ($snaps as $s) {
                // JS mengharapkan: year, weightedScore, maxWeightedScore, statusAkhir
                // Gunakan skor_final sebagai weightedScore dengan max 100 (skala %)
                $skorPerTahun[(string) $s->tahun] = [
                    'year'             => (string) $s->tahun,
                    'weightedScore'    => (float) $s->skor_final,
                    'maxWeightedScore' => 100,
                    'statusAkhir'      => $s->klasifikasi,
                ];
            }
        }

        // Bersihkan internal keys, sort by year
        $result = array_values(array_map(function ($row) {
            unset($row['_periode'], $row['_version']);
            return $row;
        }, $skorPerTahun));
        usort($result, fn ($a, $b) => strcmp($a['year'], $b['year']));

        return ['skorPerTahun' => $result];
    }
}
