<?php

namespace App\Http\Controllers\Kecamatan;

use App\Helpers\Scoring;
use App\Http\Controllers\Controller;
use App\Models\SelfAssessment;
use Illuminate\View\View;

/**
 * Hasil penilaian kecamatan — port dari NextJS KecamatanHasilPage.
 * Menghitung klasifikasi per kategori dan statusAkhir menggunakan Scoring helper.
 *
 * Hasil hanya ditampilkan jika SEMUA indikator dalam satu assessment+periode
 * sudah berstatus VALIDATED. Selama masih ada yang SUBMITTED/DRAFT/REJECTED,
 * assessment tersebut tidak muncul di halaman hasil.
 */
class HasilController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        // Langkah 1: cari assessment+periode yang SEMUA indikatornya sudah VALIDATED.
        // Caranya: ambil semua entry milik user, lalu per (assessmentId, periode)
        // pastikan tidak ada satu pun entry yang statusnya bukan VALIDATED.

        // Ambil seluruh entry aktif (semua status) untuk menghitung apakah sudah penuh tervalidasi
        $allEntries = SelfAssessment::where('submitted_by_id', $userId)
            ->whereIn('status', ['DRAFT', 'SUBMITTED', 'VALIDATED', 'REJECTED'])
            ->with([
                'indicator:id,category_id',
                'indicator.category:id,assessment_id',
            ])
            ->get(['id', 'indicator_id', 'status', 'periode', 'submitted_by_id']);

        // Group per (assessmentId, periode) dan cek apakah semua VALIDATED
        $fullyValidatedKeys = [];
        $grouped = $allEntries->groupBy(function ($e) {
            $assessmentId = $e->indicator?->category?->assessment_id;
            return "{$assessmentId}_{$e->periode}";
        });

        foreach ($grouped as $key => $group) {
            $allValidated = $group->every(fn ($e) => $e->status === 'VALIDATED');
            if ($allValidated && $group->isNotEmpty()) {
                $fullyValidatedKeys[] = $key;
            }
        }

        // Jika tidak ada assessment yang sudah fully validated, tampilkan halaman kosong
        if (empty($fullyValidatedKeys)) {
            return view('kecamatan.hasil', ['groups' => []]);
        }

        // Langkah 2: ambil hanya entry VALIDATED untuk assessment yang sudah selesai
        $entries = SelfAssessment::where('submitted_by_id', $userId)
            ->where('status', 'VALIDATED')
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

            // Lewati assessment yang belum fully validated
            if (!in_array($key, $fullyValidatedKeys, true)) {
                continue;
            }

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

            // Skor efektif: gunakan validated_score dari Tim Teknis
            $validation     = $entry->validations->first();
            $effectiveScore = $validation?->validated_score ?? $entry->score;

            // Akumulasi per kategori
            $catId = $category->id;
            if (!isset($assessmentMap[$key]['categories'][$catId])) {
                $assessmentMap[$key]['categories'][$catId] = [
                    'id'          => $catId,
                    'code'        => $category->code,
                    'name'        => $category->name,
                    'order'       => $category->order,
                    'totalScore'  => 0,
                    'maxScore'    => 0,
                    'klasifikasi' => null,
                    'items'       => [],
                ];
            }

            $assessmentMap[$key]['categories'][$catId]['totalScore'] += $effectiveScore;
            $assessmentMap[$key]['categories'][$catId]['maxScore']   += $entry->indicator->max_score;
            $assessmentMap[$key]['categories'][$catId]['items'][]     = $entry;

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

                $weight = Scoring::CATEGORY_WEIGHTS[strtoupper($cat['code'])] ?? 0;
                $cat['weight']        = $weight;
                $cat['weightedScore'] = round($cat['totalScore'] * $weight, 2);

                $categoryScores[] = [
                    'code'     => $cat['code'],
                    'score'    => $cat['totalScore'],
                    'maxScore' => $cat['maxScore'],
                ];
            }
            unset($cat);

            // StatusAkhir: weighted scoring dari semua kategori
            $weightedResult         = Scoring::calculateWeightedScore($categoryScores);
            $ag['weightedScore']    = round($weightedResult['weightedScore'], 2);
            $ag['maxWeightedScore'] = round($weightedResult['maxWeightedScore'], 2);
            $ag['statusAkhir']      = Scoring::getKlasifikasiFromWeighted($ag['weightedScore']);

            // Konversi kategori ke array values
            $ag['categories'] = array_values($ag['categories']);
        }
        unset($ag);

        $groups = array_values($assessmentMap);

        return view('kecamatan.hasil', compact('groups'));
    }
}
