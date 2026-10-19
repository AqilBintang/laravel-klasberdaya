<?php

namespace App\Http\Controllers\Kecamatan;

use App\Helpers\Scoring;
use App\Http\Controllers\Controller;
use App\Models\SelfAssessment;
use Illuminate\View\View;

/**
 * Hasil penilaian kecamatan — port dari NextJS KecamatanHasilPage.
 * Menghitung klasifikasi per kategori dan statusAkhir menggunakan Scoring helper.
 */
class HasilController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        // Ambil semua entri yang sudah SUBMITTED / VALIDATED / REJECTED
        // Sama dengan NextJS: status: { in: ['SUBMITTED','VALIDATED','REJECTED'] }
        $entries = SelfAssessment::where('submitted_by_id', $userId)
            ->whereIn('status', ['SUBMITTED', 'VALIDATED', 'REJECTED'])
            ->with([
                'indicator:id,category_id,number,indicator,max_score',
                'indicator.category:id,assessment_id,code,name,order',
                'indicator.category.assessment:id,title,periode',
                'validations' => fn ($q) => $q->orderByDesc('validated_at')->take(1),
            ])
            ->orderBy('indicator_id')
            ->get();

        // ── Group by (assessmentId_periode) ────────────────────────────────
        $assessmentMap = [];

        foreach ($entries as $entry) {
            $category   = $entry->indicator->category;
            $assessment = $category->assessment;

            if (!$assessment) {
                continue;
            }

            $key = "{$assessment->id}_{$entry->periode}";

            if (!isset($assessmentMap[$key])) {
                $assessmentMap[$key] = [
                    'assessment'      => $assessment,
                    'categories'      => [],
                    'totalScore'      => 0,
                    'maxScore'        => 0,
                    'maxPossibleTotal'=> 0,
                    'statusAkhir'     => null,
                ];
            }

            // Skor efektif: gunakan validated_score jika sudah divalidasi
            $validation    = $entry->validations->first();
            $effectiveScore = $validation?->validated_score ?? $entry->score;

            // Akumulasi per kategori
            $catId = $category->id;
            if (!isset($assessmentMap[$key]['categories'][$catId])) {
                $assessmentMap[$key]['categories'][$catId] = [
                    'id'               => $catId,
                    'code'             => $category->code,
                    'name'             => $category->name,
                    'order'            => $category->order,
                    'totalScore'       => 0,
                    'maxScore'         => 0,
                    'klasifikasi'      => null,
                    'items'            => [],
                ];
            }

            $assessmentMap[$key]['categories'][$catId]['totalScore']  += $effectiveScore;
            $assessmentMap[$key]['categories'][$catId]['maxScore']     += $entry->indicator->max_score;
            $assessmentMap[$key]['categories'][$catId]['items'][]      = $entry;

            $assessmentMap[$key]['totalScore']        += $effectiveScore;
            $assessmentMap[$key]['maxScore']          += $entry->indicator->max_score;
            $assessmentMap[$key]['maxPossibleTotal']  += $entry->indicator->max_score;
        }

        // ── Hitung klasifikasi per kategori + statusAkhir ─────────────────
        foreach ($assessmentMap as &$ag) {
            // Sort kategori by order
            uasort($ag['categories'], fn ($a, $b) => $a['order'] <=> $b['order']);

            $categoryScores = [];

            foreach ($ag['categories'] as &$cat) {
                // Klasifikasi per kategori (Rintisan/Berkembang/Maju/Unggul — hanya A,B,C,D)
                $cat['klasifikasi'] = Scoring::getKlasifikasiPerKategori($cat['code'], $cat['totalScore']);
                $categoryScores[] = [
                    'code'     => $cat['code'],
                    'score'    => $cat['totalScore'],
                    'maxScore' => $cat['maxScore'],
                ];
            }
            unset($cat);

            // StatusAkhir: weighted scoring dari semua kategori
            // Identik dengan NextJS getStatusAkhir(totalScore, maxPossible, categoryScores)
            $ag['statusAkhir'] = Scoring::getStatusAkhir(
                $ag['totalScore'],
                $ag['maxPossibleTotal'],
                $categoryScores
            );

            // Konversi kategori ke array values
            $ag['categories'] = array_values($ag['categories']);
        }
        unset($ag);

        $groups = array_values($assessmentMap);

        return view('kecamatan.hasil', compact('groups'));
    }
}
