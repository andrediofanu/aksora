<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'course_id',
        'title',
        'instructions',
        'submission_type',
        'due_at',
        'allow_late',
        'max_score',
        'weight',
        'rubric',
        'allowed_extensions',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'published_at' => 'datetime',
            'allow_late' => 'boolean',
            'max_score' => 'float',
            'weight' => 'float',
            'rubric' => 'array',
            'allowed_extensions' => 'array',
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

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
            ->where('attachable_type', Assignment::class);
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

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }

    public function acceptsSubmission(): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        if ($this->due_at === null) {
            return true;
        }

        return $this->due_at->isFuture() || $this->allow_late;
    }

    public function allowsFile(): bool
    {
        return in_array($this->submission_type, ['file', 'both'], true);
    }

    public function allowsText(): bool
    {
        return in_array($this->submission_type, ['text', 'both'], true);
    }

    /** Ekstensi file yang diizinkan untuk tugas ini. */
    public function allowedExtensions(): array
    {
        return $this->allowed_extensions ?: ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'zip'];
    }
}
