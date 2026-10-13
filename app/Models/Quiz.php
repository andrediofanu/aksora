<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'time_limit_minutes',
        'opens_at',
        'closes_at',
        'max_attempts',
        'show_results',
        'shuffle_questions',
        'pass_score',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'published_at' => 'datetime',
            'shuffle_questions' => 'boolean',
            'time_limit_minutes' => 'integer',
            'max_attempts' => 'integer',
            'pass_score' => 'float',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function scopeVisibleToStudents(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ($this->published_at === null || $this->published_at->lte(now()));
    }

    public function isOpenNow(): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        if ($this->opens_at !== null && $this->opens_at->isFuture()) {
            return false;
        }

        if ($this->closes_at !== null && $this->closes_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isUpcoming(): bool
    {
        return $this->isPublished()
            && $this->opens_at !== null
            && $this->opens_at->isFuture();
    }

    public function hasClosed(): bool
    {
        return $this->closes_at !== null && $this->closes_at->isPast();
    }

    /** Apakah hasil boleh ditampilkan kepada murid sekarang? */
    public function canShowResults(?QuizAttempt $attempt): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        return match ($this->show_results) {
            'immediately' => true,
            'after_deadline' => $this->closes_at !== null && $this->closes_at->isPast(),
            default => false,
        };
    }

    public function totalPoints(): float
    {
        return (float) $this->questions()->sum('points');
    }

    public function attemptsUsedBy(User $user): int
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->whereIn('status', ['in_progress', 'submitted', 'graded'])
            ->count();
    }

    public function canAttempt(User $user): bool
    {
        return $this->isOpenNow() && $this->attemptsUsedBy($user) < $this->max_attempts;
    }
}
