<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    public const TYPE_DIRECT = 'direct';
    public const TYPE_GROUP = 'group';
    public const TYPE_CLASS = 'class';

    protected $fillable = [
        'type',
        'course_id',
        'title',
        'created_by',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
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

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'last_read_at', 'is_muted', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function participantRows(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderByDesc('id');
    }

    /** Pesan terakhir (satu query lewat subquery, menghindari N+1). */
    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'conversation_id')->latestOfMany();
    }

    public function activeParticipants()
    {
        return $this->participants()->wherePivotNull('left_at');
    }

    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    public function isClass(): bool
    {
        return $this->type === self::TYPE_CLASS;
    }

    /**
     * Judul tampilan untuk satu pengguna (untuk percakapan pribadi
     * menampilkan nama lawan bicara).
     */
    public function displayTitleFor(User $user): string
    {
        if ($this->isDirect()) {
            $other = $this->activeParticipants()
                ->where('users.id', '!=', $user->id)
                ->first();

            return $other?->name ?: ($this->title ?: 'Percakapan');
        }

        if ($this->isClass() && $this->course) {
            return $this->course->name;
        }

        return $this->title ?: 'Percakapan grup';
    }

    public function unreadCountFor(User $user): int
    {
        $pivot = $this->participantRows()->where('user_id', $user->id)->first();

        if (! $pivot) {
            return 0;
        }

        $query = $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('deleted_at');

        if ($pivot->last_read_at) {
            $query->where('created_at', '>', $pivot->last_read_at);
        }

        return $query->count();
    }
}
