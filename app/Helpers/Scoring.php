<?php

namespace App\Helpers;

/**
 * Scoring helper — port dari src/lib/scoring.ts
 *
 * Klasifikasi tingkat kecamatan berdasarkan weighted score dari kategori.
 * Formula weighted: A*0.31 + B*0.255 + C*0.16 + D*0.22 + E*0.01 + F*0.045
 * Threshold: ≤14.41 (Rintisan), ≤29.13 (Berkembang), ≤43.23 (Maju), ≥43.24 (Unggul)
 */
class Scoring
{
    // ─────────────────────────────────────────────────────────
    // Konstanta
    // ─────────────────────────────────────────────────────────

    /** Bobot per kode kategori */
    public const CATEGORY_WEIGHTS = [
        'A' => 0.31,
        'B' => 0.255,
        'C' => 0.16,
        'D' => 0.22,
        'E' => 0.01,
        'F' => 0.045,
    ];

    /** Threshold kategori: [rintisan_max, berkembang_max, maju_max] */
    private const CATEGORY_THRESHOLDS = [
        'A' => [21, 42, 63],
        'B' => [16, 32, 48],
        'C' => [5,  10, 15],
        'D' => [11, 22, 33],
    ];

    // ─────────────────────────────────────────────────────────
    // Weighted scoring
    // ─────────────────────────────────────────────────────────

    /**
     * Hitung weighted score dari array category scores.
     *
     * @param  array  $categoryScores  [['code'=>'A','score'=>10,'maxScore'=>20], ...]
     * @return array  ['weightedScore' => float, 'maxWeightedScore' => float]
     */
    public static function calculateWeightedScore(array $categoryScores): array
    {
        $weightedScore    = 0.0;
        $maxWeightedScore = 0.0;

        foreach ($categoryScores as $cat) {
            $weight = self::CATEGORY_WEIGHTS[strtoupper($cat['code'])] ?? 0;
            if ($weight > 0) {
                $weightedScore    += $cat['score'] * $weight;
                $maxWeightedScore += $cat['maxScore'] * $weight;
            }
        }

        return compact('weightedScore', 'maxWeightedScore');
    }

    /**
     * Klasifikasi berdasarkan weighted score.
     * ≤14.41 Rintisan | ≤29.13 Berkembang | ≤43.23 Maju | ≥43.24 Unggul
     */
    public static function getKlasifikasiFromWeighted(float $weightedScore): ?string
    {
        if ($weightedScore < 0) return null;
        if ($weightedScore <= 14.41) return 'Rintisan';
        if ($weightedScore <= 29.13) return 'Berkembang';
        if ($weightedScore <= 43.23) return 'Maju';
        return 'Unggul';
    }

    // ─────────────────────────────────────────────────────────
    // Per-kategori
    // ─────────────────────────────────────────────────────────

    /**
     * Klasifikasi per kategori menggunakan threshold tim teknis.
     * Hanya berlaku untuk kategori A, B, C, D.
     * Kategori lain (E, F, dst.) mengembalikan null.
     */
    public static function getKlasifikasiPerKategori(string $code, int $score): ?string
    {
        $thresholds = self::CATEGORY_THRESHOLDS[strtoupper($code)] ?? null;
        if (!$thresholds) return null;

        [$rintisan, $berkembang, $maju] = $thresholds;
        if ($score <= $rintisan)  return 'Rintisan';
        if ($score <= $berkembang) return 'Berkembang';
        if ($score <= $maju)      return 'Maju';
        return 'Unggul';
    }

    // ─────────────────────────────────────────────────────────
    // Legacy / fallback
    // ─────────────────────────────────────────────────────────

    /**
     * Legacy klasifikasi berdasarkan total score (backward compatibility).
     * Skor minimum 49 agar semua threshold terpenuhi.
     */
    public static function getKlasifikasi(int $totalScore, int $maxScore): ?string
    {
        if ($maxScore < 49) return null;
        if ($totalScore <= 16) return 'Rintisan';
        if ($totalScore <= 32) return 'Berkembang';
        if ($totalScore <= 48) return 'Maju';
        return 'Unggul';
    }

    // ─────────────────────────────────────────────────────────
    // Custom scoring rule (dari field scoring_rule di category)
    // ─────────────────────────────────────────────────────────

    /**
     * Evaluasi skor kategori berdasarkan scoringRule kustom.
     * Rule: [['max' => 5, 'label' => 'Belum Berdaya'], ...]
     * Entry tanpa 'max' = fallback.
     *
     * @param  int    $score
     * @param  array  $rule   Array of ['max' => int|null, 'label' => string]
     */
    public static function evaluateScoringRule(int $score, array $rule): ?string
    {
        if (empty($rule)) return null;
        foreach ($rule as $entry) {
            if (!isset($entry['max']) || $score <= $entry['max']) {
                return $entry['label'] ?? null;
            }
        }
        return null;
    }

    // ─────────────────────────────────────────────────────────
    // Status akhir (main entry point)
    // ─────────────────────────────────────────────────────────

    /**
     * Status akhir kecamatan.
     * Prioritas 1: weighted scoring jika ada data kategori.
     * Prioritas 2: fallback ke total score.
     *
     * @param  int        $totalScore
     * @param  int        $maxPossibleTotal
     * @param  array|null $categoryScores  [['code', 'score', 'maxScore'], ...]
     */
    public static function getStatusAkhir(
        int $totalScore,
        int $maxPossibleTotal,
        ?array $categoryScores = null
    ): ?string {
        if (!empty($categoryScores)) {
            $result = self::calculateWeightedScore($categoryScores);
            return self::getKlasifikasiFromWeighted($result['weightedScore']);
        }
        return self::getKlasifikasi($totalScore, $maxPossibleTotal);
    }

    // ─────────────────────────────────────────────────────────
    // CSS config (untuk Blade views)
    // ─────────────────────────────────────────────────────────

    /**
     * Kembalikan Tailwind class untuk badge klasifikasi.
     * @return array ['color' => string, 'bg' => string, 'border' => string]
     */
    public static function getKlasifikasiConfig(string $level): array
    {
        return match ($level) {
            'Rintisan'   => ['color' => 'text-red-700',   'bg' => 'bg-red-100',   'border' => 'border-red-200'],
            'Berkembang' => ['color' => 'text-amber-700', 'bg' => 'bg-amber-100', 'border' => 'border-amber-200'],
            'Maju'       => ['color' => 'text-blue-700',  'bg' => 'bg-blue-100',  'border' => 'border-blue-200'],
            'Unggul'     => ['color' => 'text-green-700', 'bg' => 'bg-green-100', 'border' => 'border-green-200'],
            default      => ['color' => 'text-gray-700',  'bg' => 'bg-gray-100',  'border' => 'border-gray-200'],
        };
    }

    /**
     * Hitung total skor dari array entries (prioritaskan validated_score jika ada).
     * @param  array  $entries  [['score' => int, 'validated_score' => int|null], ...]
     */
    public static function calcCategoryScore(array $entries): int
    {
        return array_sum(array_map(
            fn ($e) => $e['validated_score'] ?? $e['score'],
            $entries
        ));
    }
}
