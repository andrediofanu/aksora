<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionThread extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'user_id',
        'title',
        'is_pinned',
        'is_locked',
        'last_post_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'last_post_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class, 'thread_id')->orderBy('created_at');
    }

    public function rootPosts(): HasMany
    {
        return $this->posts()->whereNull('parent_id');
    }

    public function postCount(): int
    {
        return $this->posts()->count();
    }
}
