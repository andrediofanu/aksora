<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Services\AttachmentService;
use App\Services\ConversationService;
use App\Services\Notifier;
use Illuminate\Http\Request;

/**
 * Pengiriman & pembaruan pesan.
 *
 * Pembaruan dilakukan dengan polling efisien (endpoint JSON ringan).
 * Bila Laravel Reverb dikonfigurasi pada masa depan, endpoint ini tetap
 * kompatibel karena format payloadnya sama.
 */
class MessageController extends Controller
{
    public function __construct(
        private ConversationService $service,
        private AttachmentService $attachments,
    ) {
    }

    public function store(SendMessageRequest $request, Conversation $conversation)
    {
        $user = $request->user();

        $data = $request->validated();

        $replyTo = null;
        if (! empty($data['reply_to_id'])) {
            $replyTo = Message::where('conversation_id', $conversation->id)
                ->find($data['reply_to_id']);
        }

        $message = $this->service->sendMessage($user, $conversation, [
            'body' => $data['body'] ?? null,
            'reply_to_id' => $replyTo?->id,
            'attachment' => $request->file('file'),
        ]);

        if ($request->hasFile('file')) {
            $this->attachments->store($message, $request->file('file'), $user->id, [
                'allowed_extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'csv', 'png', 'jpg', 'jpeg', 'gif', 'zip', 'mp3', 'mp4'],
            ]);

            if (! $message->body) {
                $message->update(['body' => null, 'type' => Message::TYPE_FILE]);
            }
        }

        $recipients = $conversation->participants()
            ->where('users.id', '!=', $user->id)
            ->whereNull('conversation_participants.left_at')
            ->get();

        if (! $recipients->isEmpty() && ! $conversation->isClass()) {
            app(Notifier::class)->send($recipients, [
                'title' => 'Pesan baru',
                'body' => $user->name.': '.\Illuminate\Support\Str::limit((string) $message->body, 80, '…'),
                'url' => route('conversations.show', $conversation),
                'kind' => 'message',
            ], $user);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => $this->transform($message, $user, $conversation->participantRows()->get()),
            ]);
        }

        return redirect()->route('conversations.show', $conversation);
    }

    /** Pesan lebih lama (pagination riwayat). */
    public function history(Request $request, Conversation $conversation)
    {
        abort_unless($request->user()->can('view', $conversation), 403);

        $request->validate(['before' => ['nullable', 'integer']]);

        $query = $conversation->messages()
            ->with(['sender:id,name,avatar_path,title', 'replyTo.sender:id,name', 'attachments'])
            ->orderByDesc('id');

        if ($before = $request->query('before')) {
            $query->where('id', '<', (int) $before);
        }

        $messages = $query->limit(30)->get();

        $participants = $conversation->participantRows()->get();

        return response()->json([
            'messages' => $messages->reverse()->values()->map(fn (Message $m) => $this->transform($m, $request->user(), $participants)),
            'has_more' => $messages->count() === 30,
            'oldest_id' => $messages->min('id'),
        ]);
    }

    /** Polling pesan baru sejak `after`. */
    public function poll(Request $request, Conversation $conversation)
    {
        abort_unless($request->user()->can('view', $conversation), 403);

        $after = (int) $request->query('after', 0);

        $messages = $conversation->messages()
            ->where('id', '>', $after)
            ->with(['sender:id,name,avatar_path,title', 'replyTo.sender:id,name', 'attachments'])
            ->orderBy('id')
            ->limit(50)
            ->get();

        // Menandai dibaca bila tab sedang terbuka pada percakapan ini.
        $this->service->markRead($conversation, $request->user());

        $participants = $conversation->participantRows()->get();

        // Status baca pesan milik pengguna (agar indikator bisa diperbarui saat polling).
        $mine = $conversation->messages()
            ->where('sender_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'conversation_id', 'sender_id', 'created_at']);

        return response()->json([
            'messages' => $messages->map(fn (Message $m) => $this->transform($m, $request->user(), $participants)),
            'read_labels' => $mine->mapWithKeys(
                fn (Message $m) => [$m->id => $m->readLabelFromRows($participants)]
            )->all(),
            'latest_id' => max($conversation->messages()->max('id'), $after),
            'unread_total' => $request->user()->totalUnreadMessages(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /** Penandaan baca manual. */
    public function markRead(Request $request, Conversation $conversation)
    {
        abort_unless($request->user()->can('view', $conversation), 403);

        $this->service->markRead($conversation, $request->user());

        return response()->json([
            'status' => 'ok',
            'unread_total' => $request->user()->totalUnreadMessages(),
        ]);
    }

    public function destroy(Request $request, Conversation $conversation, Message $message)
    {
        abort_if($message->conversation_id !== $conversation->id, 404);
        abort_unless($request->user()->can('view', $conversation), 403);

        $user = $request->user();

        $allowed = $message->sender_id === $user->id
            || $user->can('update', $conversation);

        abort_unless($allowed, 403);

        foreach ($message->attachments()->get() as $attachment) {
            $this->attachments->delete($attachment);
        }

        $message->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return back()->with('success', 'Pesan dihapus.');
    }

    /**
     * Bentuk payload pesan untuk klien.
     *
     * @param  iterable<\App\Models\ConversationParticipant>  $participants
     * @return array<string, mixed>
     */
    private function transform(Message $message, $user, iterable $participants): array
    {
        $message->loadMissing('sender:id,name,avatar_path,title', 'attachments');

        $state = $message->stateFromRows($participants);

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender' => [
                'id' => $message->sender?->id,
                'name' => $message->sender?->name,
                'avatar' => $message->sender?->avatar_path,
            ],
            'body' => $message->body,
            'type' => $message->type,
            'created_at' => $message->created_at->toIso8601String(),
            'is_mine' => $message->sender_id === $user->id,
            'read' => $state !== 'sent',
            'read_label' => $message->readLabelFromRows($participants),
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'sender' => $message->replyTo->sender?->name,
                'body' => \Illuminate\Support\Str::limit((string) $message->replyTo->body, 80, '…'),
            ] : null,
            'attachments' => $message->attachments->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->original_name,
                'mime' => $a->mime_type,
                'size' => $a->size,
                'url' => route('attachments.download', $a),
            ])->values(),
            'deleted' => $message->trashed(),
        ];
    }
}
