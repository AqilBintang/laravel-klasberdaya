<?php

namespace App\Http\Controllers\Kecamatan;

use App\Http\Controllers\Controller;
use App\Helpers\Scoring;
use App\Models\SelfAssessment;
use App\Models\AssessmentVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StatistikController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        // 1. Ambil semua self-assessment yang sudah VALIDATED milik user ini
        $entries = SelfAssessment::with([
                'indicator.version',
                'indicator.category',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->limit(1),
            ])
            ->where('submitted_by_id', $userId)
            ->where('status', 'VALIDATED')
            ->orderBy('periode')
            ->get();

        // 2. Hitung jumlah indikator per version (untuk validasi kelengkapan)
        $versionIds = $entries->map(fn ($e) => $e->indicator->version_id)->unique()->values();

        $indicatorCountsByVersion = AssessmentVersion::whereIn('id', $versionIds)
            ->withCount('indicators')
            ->get()
            ->keyBy('id')
            ->map(fn ($v) => $v->indicators_count);

        // 3. Group by (assessment_id, periode, version_id) — sama persis dengan Next.js
        $periodeMap = [];

        foreach ($entries as $e) {
            $score     = optional($e->validations->first())->validated_score ?? $e->score;
            $cat       = $e->indicator->category;
            $periode   = $e->periode;
            $key       = "{$cat->assessment_id}_{$periode}_{$e->indicator->version_id}";

            if (!isset($periodeMap[$key])) {
                $periodeMap[$key] = [
                    'assessmentId'   => $cat->assessment_id,
                    'periode'        => $periode,
                    'versionId'      => $e->indicator->version_id,
                    'versionNumber'  => $e->indicator->version->version_number,
                    'indicatorCount' => 0,
                    'total'          => 0,
                    'max'            => 0,
                    'cats'           => [],
                ];
            }

            $periodeMap[$key]['indicatorCount'] += 1;
            $periodeMap[$key]['total']          += $score;
            $periodeMap[$key]['max']            += $e->indicator->max_score;

            $catId = $cat->id;
            if (!isset($periodeMap[$key]['cats'][$catId])) {
                $periodeMap[$key]['cats'][$catId] = [
                    'code'  => $cat->code,
                    'name'  => $cat->name,
                    'order' => $cat->order,
                    'total' => 0,
                    'max'   => 0,
                ];
            }
            $periodeMap[$key]['cats'][$catId]['total'] += $score;
            $periodeMap[$key]['cats'][$catId]['max']   += $e->indicator->max_score;
        }

        // 4. Filter hanya group yang indicatorCount-nya cocok dengan jumlah indikator di version
        //    Kemudian ambil versi terbaru per (assessmentId, periode)
        $latestValidatedBySubmission = [];

        foreach ($periodeMap as $group) {
            $expectedCount = $indicatorCountsByVersion->get($group['versionId']);
            if ($expectedCount === null || $group['indicatorCount'] !== $expectedCount) {
                continue; // skip — belum semua indikator terisi
            }

            $submissionKey = "{$group['assessmentId']}_{$group['periode']}";
            $current       = $latestValidatedBySubmission[$submissionKey] ?? null;

            if (!$current || $group['versionNumber'] > $current['versionNumber']) {
                $latestValidatedBySubmission[$submissionKey] = $group;
            }
        }

        // 5. Build array $riwayat untuk dikirim ke view
        $riwayat = collect($latestValidatedBySubmission)
            ->sortBy('periode')
            ->map(function ($group) {
                $categories = collect($group['cats'])
                    ->map(fn ($c, $catId) => [
                        'id'         => (int) $catId,
                        'code'       => $c['code'],
                        'name'       => $c['name'],
                        'order'      => $c['order'],
                        'totalScore' => $c['total'],
                        'maxScore'   => $c['max'],
                        'klasifikasi'=> Scoring::getKlasifikasiPerKategori($c['code'], $c['total']),
                    ])
                    ->sortBy('order')
                    ->values()
                    ->all();

                // Siapkan category scores untuk weighted scoring
                $categoryScores = array_map(fn ($cat) => [
                    'code'     => $cat['code'],
                    'score'    => $cat['totalScore'],
                    'maxScore' => $cat['maxScore'],
                ], $categories);

                return [
                    'periode'     => $group['periode'],
                    'totalScore'  => $group['total'],
                    'maxScore'    => $group['max'],
                    'statusAkhir' => Scoring::getStatusAkhir($group['total'], $group['max'], $categoryScores),
                    'categories'  => $categories,
                ];
            })
            ->values()
            ->all();

        return view('kecamatan.statistik', compact('riwayat'));
    }
}
