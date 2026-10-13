<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bobot kategori nilai per kelas.
 *
 * Rumus nilai akhir (dilakukan di App\Services\GradeCalculator):
 *   Nilai Akhir = WA * Bobot Tugas + WK * Bobot Kuis
 *   dengan WA = Σ(skor/bobot_poin) / Σ(bobot_poin) pada komponen tugas,
 *       WK = Σ(skor_persen kuis × bobot kuis) / Σ(bobot kuis).
 * Komponen yang belum dinilai TIDAK dihitung (dikecualikan dari rata-rata),
 * bukan dianggap nol.
 */
class GradeWeight extends Model
{
    public const CATEGORY_ASSIGNMENT = 'assignment';
    public const CATEGORY_QUIZ = 'quiz';

    protected $fillable = [
        'course_id',
        'category',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
