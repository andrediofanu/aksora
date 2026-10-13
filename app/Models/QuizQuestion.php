<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_SHORT_ANSWER = 'short_answer';

    protected $fillable = [
        'quiz_id',
        'type',
        'question',
        'points',
        'sort_order',
        'correct_answer',
        'case_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'float',
            'sort_order' => 'integer',
            'case_sensitive' => 'boolean',
        ];
    }

    /** Kolom yang tidak boleh pernah ikut di-serialize ke murid. */
    protected $hidden = [
        'correct_answer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class, 'question_id')->orderBy('sort_order')->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function isMultipleChoice(): bool
    {
        return $this->type === self::TYPE_MULTIPLE_CHOICE;
    }

    /**
     * Benar/salahnya jawaban murid. Hanya dipanggil saat proses penilaian
     * di backend, tidak pernah dikirim ke klien sebelum hasil diizinkan.
     */
    public function isAnswerCorrect(?int $optionId, ?string $text): bool
    {
        if ($this->isMultipleChoice()) {
            if ($optionId === null) {
                return false;
            }

            return $this->options()
                ->where('id', $optionId)
                ->where('is_correct', true)
                ->exists();
        }

        if ($text === null || $this->correct_answer === null) {
            return false;
        }

        $expected = trim($this->correct_answer);
        $given = trim($text);

        return $this->case_sensitive
            ? $expected === $given
            : mb_strtolower($expected) === mb_strtolower($given);
    }
}
