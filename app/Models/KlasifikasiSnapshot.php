<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Snapshot hasil klasifikasi kecamatan dari data rekapitulasi tim teknis.
 * Digunakan sebagai sumber data fallback di KlasifikasiBerdayaController.
 *
 * @property int    $id
 * @property string $kabupaten
 * @property string $kecamatan
 * @property int    $tahun
 * @property int    $skor_a
 * @property string|null $status_a
 * @property int    $skor_b
 * @property string|null $status_b
 * @property int    $skor_c
 * @property string|null $status_c
 * @property int    $skor_d
 * @property string|null $status_d
 * @property int    $skor_e
 * @property int    $skor_f
 * @property float  $skor_final
 * @property string|null $klasifikasi
 * @property string|null $verifikasi
 * @property string $sumber
 */
class KlasifikasiSnapshot extends Model
{
    protected $table = 'klasifikasi_snapshots';

    protected $fillable = [
        'kabupaten',
        'kecamatan',
        'tahun',
        'skor_a',
        'status_a',
        'skor_b',
        'status_b',
        'skor_c',
        'status_c',
        'skor_d',
        'status_d',
        'skor_e',
        'skor_f',
        'skor_final',
        'klasifikasi',
        'verifikasi',
        'sumber',
    ];

    protected function casts(): array
    {
        return [
            'tahun'      => 'integer',
            'skor_a'     => 'integer',
            'skor_b'     => 'integer',
            'skor_c'     => 'integer',
            'skor_d'     => 'integer',
            'skor_e'     => 'integer',
            'skor_f'     => 'integer',
            'skor_final' => 'float',
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    /**
     * Kembalikan array kategori scores, format yang kompatibel
     * dengan Scoring::getStatusAkhir().
     */
    public function toCategoryScores(): array
    {
        return [
            ['code' => 'A', 'score' => $this->skor_a, 'maxScore' => 84],
            ['code' => 'B', 'score' => $this->skor_b, 'maxScore' => 64],
            ['code' => 'C', 'score' => $this->skor_c, 'maxScore' => 20],
            ['code' => 'D', 'score' => $this->skor_d, 'maxScore' => 44],
            ['code' => 'E', 'score' => $this->skor_e, 'maxScore' => 8],
            ['code' => 'F', 'score' => $this->skor_f, 'maxScore' => 24],
        ];
    }

    /**
     * Total skor mentah (A+B+C+D+E+F).
     */
    public function totalSkor(): int
    {
        return $this->skor_a + $this->skor_b + $this->skor_c
             + $this->skor_d + $this->skor_e + $this->skor_f;
    }
}
