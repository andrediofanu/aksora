<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Message extends Model
{
    use SoftDeletes;

    public const TYPE_TEXT = 'text';
    public const TYPE_FILE = 'file';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'reply_to_id',
        'type',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
            ->where('attachable_type', Message::class);
    }

    /** Baris participant aktif percakapan ini (kecuali pengirim pesan). */
    public function activeRecipientRows(): Collection
    {
        return $this->conversation
            ->participantRows()
            ->whereNull('left_at')
            ->where('user_id', '!=', $this->sender_id)
            ->get();
    }

    /**
     * Status baca dari baris participant yang sudah dimuat (hemat query saat render banyak pesan).
     *
     * @param  iterable<ConversationParticipant>  $rows
     * @return 'sent'|'some'|'all'
     */
    public function stateFromRows(iterable $rows): string
    {
        $recipients = collect($rows)->filter(
            fn ($row) => $row->left_at === null && $row->user_id !== $this->sender_id
        );

        $total = $recipients->count();

        if ($total === 0) {
            return 'all';
        }

        $read = $recipients->filter(
            fn ($row) => $row->last_read_at !== null && $row->last_read_at->gte($this->created_at)
        )->count();

        if ($read === 0) {
            return 'sent';
        }

        return $read < $total ? 'some' : 'all';
    }

    /** @return 'sent'|'some'|'all' */
    public function readState(): string
    {
        return $this->stateFromRows($this->activeRecipientRows());
    }

    /**
     * Label status untuk pengirim: terkirim / dibaca / dibaca sebagian / dibaca semua.
     *
     * @param  iterable<ConversationParticipant>  $rows
     */
    public function readLabelFromRows(iterable $rows): string
    {
        $state = $this->stateFromRows($rows);

        if ($state === 'sent') {
            return 'terkirim';
        }

        $recipients = collect($rows)->filter(
            fn ($row) => $row->left_at === null && $row->user_id !== $this->sender_id
        )->count();

        if ($recipients > 1) {
            return $state === 'all' ? 'dibaca semua' : 'dibaca sebagian';
        }

        return 'dibaca';
    }

    public function readLabel(): string
    {
        return $this->readLabelFromRows($this->activeRecipientRows());
    }
}
