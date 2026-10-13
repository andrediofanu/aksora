<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';

    protected $fillable = [
        'course_id',
        'title',
        'topic',
        'description',
        'content',
        'external_url',
        'sort_order',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sort_order' => 'integer',
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

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
            ->where('attachable_type', LearningMaterial::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(MaterialCompletion::class);
    }

    /** Materi yang boleh dilihat murid: terpublikasi dan waktu terbit sudah lewat. */
    public function scopeVisibleToStudents(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', self::STATUS_PUBLISHED)
                ->orWhere(function (Builder $inner) {
                    $inner->where('status', self::STATUS_SCHEDULED)
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now());
                });
        })
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isPublished(): bool
    {
        if ($this->status === self::STATUS_DRAFT) {
            return false;
        }

        if ($this->status === self::STATUS_SCHEDULED
            && $this->published_at !== null
            && $this->published_at->isFuture()) {
            return false;
        }

        return $this->published_at === null || $this->published_at->lte(now());
    }

    /** Status efektif untuk ditampilkan (jadwal lewat dianggap terbit). */
    public function effectiveStatus(): string
    {
        if ($this->status === self::STATUS_SCHEDULED && $this->isPublished()) {
            return self::STATUS_PUBLISHED;
        }

        return $this->status;
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->completions()->where('user_id', $user->id)->exists();
    }
}
