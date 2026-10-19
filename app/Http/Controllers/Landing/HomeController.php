<?php

namespace App\Http\Controllers\Landing;

use App\Helpers\Scoring;
use App\Http\Controllers\Controller;
use App\Models\AssessmentVersion;
use App\Models\KlasifikasiSnapshot;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\SelfAssessment;
use App\Models\Wilayah;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Migrasi dari: src/app/(landing)/home/page.tsx
     * - getLandingContent()                      → SiteSetting
     * - getKlasifikasiKecamatanAggPerYear()       → query langsung
     * - getKabKotaJateng() / getKecamatanMapJateng() → Wilayah model
     */
    public function index(): View
    {
        // ── Konten landing dari SiteSetting ──────────────────────────────────
        $bannerRow  = SiteSetting::where('key', 'landing_banner')->first();
        $tentangRow = SiteSetting::where('key', 'landing_tentang_platform')->first();

        $banner = $bannerRow?->value ?? [
            'slides' => [
                ['id' => 'banner-1', 'imageUrl' => '', 'alt' => ''],
                ['id' => 'banner-2', 'imageUrl' => '', 'alt' => ''],
                ['id' => 'banner-3', 'imageUrl' => '', 'alt' => ''],
            ],
        ];

        $tentang = $tentangRow?->value ?? [
            'heading'     => 'Apa itu Klasifikasi Berdaya?',
            'description' => 'Klasifikasi Kecamatan Berdaya adalah sistem penilaian mandiri berbasis indikator yang mengukur kapasitas dan kinerja kecamatan dalam menjalankan program pemberdayaan masyarakat di Jawa Tengah.',
            'points'      => [
                'Penilaian dilakukan oleh kecamatan secara mandiri melalui self-assessment',
                'Setiap indikator divalidasi oleh tim admin untuk menjamin akurasi data',
                'Hasil klasifikasi dipublikasikan secara transparan kepada masyarakat',
                'Data digunakan sebagai dasar pengambilan kebijakan pemberdayaan wilayah',
            ],
            'imageUrl'    => '',
        ];

        // ── Statistik agregat ─────────────────────────────────────────────────
        [$years, $chartData] = $this->buildYearsAndChart();

        // totalRegistered: gabungan user aktif + kecamatan dari snapshot
        // (deduplikasi by kabupaten|kecamatan agar tidak double-count)
        $registeredFromUsers = User::where('role', 'USER')
            ->where('is_active', true)
            ->whereNotNull('kecamatan_id')
            ->count();

        $snapshotCount = KlasifikasiSnapshot::count();

        // Hitung berapa snapshot yang bukan milik user yang sudah terdaftar
        // (sama dengan logika merge di controller lain)
        $userKecKeys = User::where('role', 'USER')
            ->where('is_active', true)
            ->whereNotNull('kecamatan_id')
            ->get(['kabupaten', 'kecamatan'])
            ->map(fn ($u) => strtolower(trim($u->kabupaten ?? '')) . '|' . strtolower(trim($u->kecamatan ?? '')))
            ->flip()
            ->toArray();

        $snapshotOnly = KlasifikasiSnapshot::get(['kabupaten', 'kecamatan'])
            ->filter(fn ($s) => !isset($userKecKeys[
                strtolower(trim($s->kabupaten)) . '|' . strtolower(trim($s->kecamatan))
            ]))
            ->count();

        $totalRegistered = $registeredFromUsers + $snapshotOnly;

        // totalWithData: kecamatan dari assessment VALIDATED + semua snapshot
        $usersWithData = SelfAssessment::where('status', 'VALIDATED')
            ->whereIn('submitted_by_id', User::where('role', 'USER')
                ->where('is_active', true)
                ->whereNotNull('kecamatan_id')
                ->pluck('id'))
            ->distinct('submitted_by_id')
            ->count('submitted_by_id');

        $totalWithData = $usersWithData + $snapshotOnly;

        $stats = [
            'totalRegistered' => $totalRegistered,
            'totalWithData'   => $totalWithData,
            'years'           => $years,
            'chartData'       => $chartData,
        ];

        // ── Data wilayah untuk filter (level 2 = kabupaten, level 3 = kecamatan)
        // Termasuk data path polygon untuk render peta SVG
        $kabKotaModels = Wilayah::where('level', 2)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'path', 'lat', 'lng'])
            ->map(fn ($k) => [
                'kode' => $k->kode,
                'nama' => $k->nama,
                'path' => $k->path,
                'lat'  => $k->lat,
                'lng'  => $k->lng,
            ])
            ->toArray();

        // kecamatanMap: ['kode_kabupaten' => [['kode','nama'],...]]
        $kecamatanMap = [];
        Wilayah::where('level', 3)
            ->whereNotNull('parent_id')
            ->orderBy('nama')
            ->with('parent:id,kode')
            ->get(['id', 'kode', 'nama', 'parent_id'])
            ->each(function ($kec) use (&$kecamatanMap) {
                $parentKode = $kec->parent?->kode;
                if ($parentKode) {
                    $kecamatanMap[$parentKode][] = ['kode' => $kec->kode, 'nama' => $kec->nama];
                }
            });

        // ── Bangun SVG shapes dari polygon path ───────────────────────────────
        [$kabKotaShapes, $mapViewBox] = $this->buildMapShapes($kabKotaModels);

        return view('landing.home', [
            'banner'        => $banner,
            'tentang'       => $tentang,
            'stats'         => $stats,
            'kabKota'       => $kabKotaModels,
            'kecamatanMap'  => $kecamatanMap,
            'kabKotaShapes' => $kabKotaShapes,
            'mapViewBox'    => $mapViewBox,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Konversi data polygon [lat,lng] ke SVG path string.
     * Migrasi dari: src/lib/wilayah/geometry.ts (extractRings, buildPath)
     * dan JatengKabKotaMap.tsx (viewBox calculation + project function)
     *
     * @param  array  $kabKotaModels  ['kode'=>, 'nama'=>, 'path'=>JSON, 'lat'=>, 'lng'=>]
     * @return array  [shapes[], viewBox string]
     */
    private function buildMapShapes(array $kabKotaModels): array
    {
        $VB_WIDTH  = 1000;

        // Pass 1: parse semua rings, hitung bounding box global
        $parsed = [];
        $minLng =  INF;
        $maxLng = -INF;
        $minLat =  INF;
        $maxLat = -INF;

        foreach ($kabKotaModels as $kab) {
            if (empty($kab['path'])) continue;
            $rings = $this->extractRings(json_decode($kab['path'], true) ?? []);
            if (empty($rings)) continue;

            foreach ($rings as $ring) {
                foreach ($ring as [$lat, $lng]) {
                    if ($lng < $minLng) $minLng = $lng;
                    if ($lng > $maxLng) $maxLng = $lng;
                    if ($lat < $minLat) $minLat = $lat;
                    if ($lat > $maxLat) $maxLat = $lat;
                }
            }
            $parsed[] = ['kode' => $kab['kode'], 'nama' => $kab['nama'], 'rings' => $rings];
        }

        if (empty($parsed) || !is_finite($minLng) || !is_finite($maxLng)) {
            return [[], '0 0 1000 600'];
        }

        $lngRange = $maxLng - $minLng;
        $latRange = $maxLat - $minLat;
        $ratio     = $latRange / max($lngRange, 1e-9);
        $vbHeight  = (int) max(520, min(1200, $VB_WIDTH * $ratio));
        $viewBox   = "0 0 {$VB_WIDTH} {$vbHeight}";

        // Pass 2: project + build SVG path
        $shapes = [];
        foreach ($parsed as $item) {
            $dParts = [];
            foreach ($item['rings'] as $ring) {
                if (count($ring) < 2) continue;
                $pts = [];
                foreach ($ring as [$lat, $lng]) {
                    $x = (($lng - $minLng) / $lngRange) * $VB_WIDTH;
                    $y = (($maxLat - $lat) / $latRange) * $vbHeight;
                    $pts[] = round($x, 2) . ' ' . round($y, 2);
                }
                if (empty($pts)) continue;
                $dParts[] = 'M ' . $pts[0] . ' ' . implode(' L ', array_slice($pts, 1)) . ' Z';
            }
            if (empty($dParts)) continue;
            $shapes[] = [
                'kode' => $item['kode'],
                'nama' => $item['nama'],
                'd'    => implode(' ', $dParts),
            ];
        }

        return [$shapes, $viewBox];
    }

    /**
     * Ekstrak semua ring (array of [lat,lng]) dari struktur GeoJSON nested.
     * Migrasi dari: src/lib/wilayah/geometry.ts (extractRings)
     */
    private function extractRings(mixed $value): array
    {
        $rings = [];
        $this->visitRings($value, $rings);
        return $rings;
    }

    private function visitRings(mixed $v, array &$rings): void
    {
        if (!is_array($v)) return;

        // Cek apakah ini adalah array of [lat, lng] pairs
        if (!empty($v) && $this->isCoordPair($v[0])) {
            $rings[] = $v;
            return;
        }

        $pending = [];
        foreach ($v as $child) {
            if ($this->isCoordPair($child)) {
                $pending[] = $child;
                continue;
            }
            if (count($pending) >= 3) $rings[] = $pending;
            $pending = [];
            $this->visitRings($child, $rings);
        }
        if (count($pending) >= 3) $rings[] = $pending;
    }

    private function isCoordPair(mixed $v): bool
    {
        return is_array($v)
            && count($v) === 2
            && is_numeric($v[0])
            && is_numeric($v[1])
            && is_finite((float) $v[0])
            && is_finite((float) $v[1]);
    }

    /**
     * Bangun daftar tahun dan distribusi level per tahun.
     * Sumber data HYBRID: assessment VALIDATED (prioritas) + klasifikasi_snapshots (fallback).
     *
     * @return array{0: string[], 1: array<int, array{year:string,rintisan:int,berkembang:int,maju:int,unggul:int}>}
     */
    private function buildYearsAndChart(): array
    {
        // ── SUMBER 1: Assessment VALIDATED ───────────────────────────────────
        $userIds = User::where('role', 'USER')
            ->where('is_active', true)
            ->whereNotNull('kecamatan_id')
            ->pluck('id')
            ->all();

        // key: "kabupaten_lower|kecamatan_lower|tahun" → statusAkhir
        $assessmentResults = [];

        if (!empty($userIds)) {
            $entries = SelfAssessment::where('status', 'VALIDATED')
                ->whereIn('submitted_by_id', $userIds)
                ->with([
                    'indicator:id,category_id,max_score,version_id',
                    'indicator.category:id,assessment_id,code',
                    'indicator.version:id,version_number',
                    'submittedBy:id,kabupaten,kecamatan',
                    'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
                ])
                ->get(['id', 'submitted_by_id', 'periode', 'indicator_id', 'score']);

            if ($entries->isNotEmpty()) {
                $versionIds = $entries->pluck('indicator.version_id')->filter()->unique()->all();
                $indicatorCountsByVersion = [];
                if (!empty($versionIds)) {
                    $rows = AssessmentVersion::whereIn('id', $versionIds)->withCount('indicators')->get(['id']);
                    foreach ($rows as $r) $indicatorCountsByVersion[$r->id] = $r->indicators_count;
                }

                $groupsMap = [];
                foreach ($entries as $e) {
                    $ind = $e->indicator; $cat = $ind->category;
                    $assessmentId = $cat->assessment_id ?? null;
                    $versionId    = $ind->version_id;
                    if (!$assessmentId || !$versionId) continue;
                    $key = "{$e->submitted_by_id}_{$assessmentId}_{$e->periode}_{$versionId}";
                    $effScore = $e->validations->first()?->validated_score ?? $e->score;
                    if (!isset($groupsMap[$key])) {
                        $groupsMap[$key] = [
                            'userId' => $e->submitted_by_id, 'assessmentId' => $assessmentId,
                            'periode' => $e->periode, 'versionId' => $versionId,
                            'versionNumber' => $ind->version->version_number ?? 1,
                            'indicatorCount' => 0, 'totalScore' => 0, 'maxPossibleTotal' => 0,
                            'catMap' => [],
                            'kabupaten' => strtolower(trim($e->submittedBy->kabupaten ?? '')),
                            'kecamatan' => strtolower(trim($e->submittedBy->kecamatan ?? '')),
                        ];
                    }
                    $g = &$groupsMap[$key];
                    $g['indicatorCount']++;
                    $g['totalScore']      += $effScore;
                    $g['maxPossibleTotal'] += $ind->max_score;
                    $catCode = $cat->code;
                    if (!isset($g['catMap'][$catCode])) {
                        $g['catMap'][$catCode] = ['code' => $catCode, 'score' => 0, 'maxScore' => 0];
                    }
                    $g['catMap'][$catCode]['score']    += $effScore;
                    $g['catMap'][$catCode]['maxScore'] += $ind->max_score;
                    unset($g);
                }

                $completeGroups = [];
                foreach ($groupsMap as $g) {
                    $expected = $indicatorCountsByVersion[$g['versionId']] ?? 0;
                    if ($g['indicatorCount'] !== $expected) continue;
                    $categoryScores = array_values($g['catMap']);
                    $g['statusAkhir'] = Scoring::getStatusAkhir($g['totalScore'], $g['maxPossibleTotal'], $categoryScores);
                    $completeGroups[] = $g;
                }

                // Latest per (kabupaten|kecamatan|tahun)
                $latestByKecYear = [];
                foreach ($completeGroups as $g) {
                    $year = $this->extractYear($g['periode']);
                    if (!$year) continue;
                    $k = "{$g['kabupaten']}|{$g['kecamatan']}|{$year}";
                    if (!isset($latestByKecYear[$k]) || $this->isNewerPeriode($g, $latestByKecYear[$k])) {
                        $latestByKecYear[$k] = $g;
                    }
                }

                foreach ($latestByKecYear as $k => $g) {
                    $assessmentResults[$k] = $g['statusAkhir'];
                }
            }
        }

        // ── SUMBER 2: Snapshot ────────────────────────────────────────────────
        $snapshots = KlasifikasiSnapshot::all(['kabupaten', 'kecamatan', 'tahun', 'klasifikasi']);
        $snapshotResults = [];
        foreach ($snapshots as $snap) {
            $k = strtolower(trim($snap->kabupaten)) . '|' . strtolower(trim($snap->kecamatan)) . '|' . $snap->tahun;
            $snapshotResults[$k] = $snap->klasifikasi;
        }

        // ── MERGE: assessment menimpa snapshot ────────────────────────────────
        $merged = $snapshotResults;
        foreach ($assessmentResults as $k => $status) {
            $merged[$k] = $status;
        }

        if (empty($merged)) return [[], []];

        // ── Ekstrak tahun + distribusi ─────────────────────────────────────────
        // Pastikan semua tahun dikonversi ke string agar perbandingan === konsisten.
        // Kolom `tahun` di DB bertipe smallint → integer, tapi setelah konkatenasi
        // string di key $merged sudah menjadi string. sort() pada array string
        // bernilai numerik kadang mengembalikan integer via array_keys — cast
        // eksplisit ke string menghindari mismatch pada strict comparison.
        $yearSet = [];
        foreach ($merged as $k => $status) {
            $parts = explode('|', $k);
            $year  = isset($parts[2]) ? (string) $parts[2] : null;
            if ($year !== null && $year !== '') $yearSet[$year] = true;
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

        return [$years, $chartData];
    }

    /**
     * Bandingkan apakah grup A lebih baru dari B.
     * Migrasi dari: aggregation.ts isNewerPeriode().
     */
    private function isNewerPeriode(array $a, array $b): bool
    {
        $ya = $this->extractYear($a['periode']) ?? -1;
        $yb = $this->extractYear($b['periode']) ?? -1;
        $ya = (int) $ya;
        $yb = (int) $yb;
        if ($ya !== $yb) return $ya > $yb;
        if ($a['periode'] !== $b['periode']) return strcmp($a['periode'], $b['periode']) > 0;
        if ($a['assessmentId'] === $b['assessmentId'] && ($a['versionNumber'] ?? 0) !== ($b['versionNumber'] ?? 0)) {
            return ($a['versionNumber'] ?? 0) > ($b['versionNumber'] ?? 0);
        }
        return $a['assessmentId'] > $b['assessmentId'];
    }

    private function extractYear(string $period): ?string
    {
        if (!preg_match_all('/\d{4}/', $period, $matches)) return null;
        if (empty($matches[0])) return null;
        $year = (int) end($matches[0]);
        return ($year >= 2000 && $year <= 2100) ? (string) $year : null;
    }
}
