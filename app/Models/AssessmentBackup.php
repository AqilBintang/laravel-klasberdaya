<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentBackup extends Model
{
    protected $fillable = [
        'assessment_title',
        'periode',
        'version_number',
        'tahun',
        'kabupaten',
        'kecamatan',
        'total_score',
        'max_possible_total',
        'status_akhir',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'snapshot'          => 'array',
            'version_number'    => 'integer',
            'tahun'             => 'integer',
            'total_score'       => 'integer',
            'max_possible_total' => 'integer',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }
}
