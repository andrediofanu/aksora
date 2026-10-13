<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Layanan percakapan. Semua pemeriksaan akses dilakukan lewat
 * ConversationPolicy oleh pemanggil; service ini hanya menangani
 * konsistensi data (transaksi, keanggotaan, penandaan baca).
 */
class ConversationService
{
    /**
     * Ambil atau buat percakapan pribadi antara dua pengguna.
     */
    public function findOrCreateDirect(User $author, User $other): Conversation
    {
        $existing = Conversation::query()
            ->where('type', Conversation::TYPE_DIRECT)
            ->whereHas('participantRows', fn ($q) => $q->where('user_id', $author->id))
            ->whereHas('participantRows', fn ($q) => $q->where('user_id', $other->id))
            ->withCount(['participantRows as pcnt' => fn ($q) => $q->whereNull('left_at')])
            ->get()
            ->first(fn ($c) => $c->pcnt === 2);

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($author, $other) {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_DIRECT,
                'created_by' => $author->id,
            ]);

            foreach ([$author, $other] as $user) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                    'role' => ConversationParticipant::ROLE_MEMBER,
                    'joined_at' => now(),
                ]);
            }

            return $conversation;
        });
    }

    /**
     * Buat percakapan grup.
     *
     * @param  array<int, int>  $userIds
     */
    public function createGroup(User $creator, string $title, array $userIds, ?int $courseId = null): Conversation
    {
        $ids = collect($userIds)
            ->push($creator->id)
            ->unique()
            ->values();

        return DB::transaction(function () use ($creator, $title, $ids, $courseId) {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_GROUP,
                'title' => $title,
                'course_id' => $courseId,
                'created_by' => $creator->id,
            ]);

            foreach ($ids as $index => $userId) {
                ConversationParticipant::firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                ], [
                    'role' => $userId === $creator->id
                        ? ConversationParticipant::ROLE_OWNER
                        : ConversationParticipant::ROLE_MEMBER,
                    'joined_at' => now(),
                ]);
            }

            return $conversation;
        });
    }

    /**
     * Buat (atau ambil) percakapan grup otomatis untuk sebuah kelas.
     * Guru pengampu menjadi owner.
     */
    public function ensureClassConversation(\App\Models\Course $course): Conversation
    {
        $existing = Conversation::query()
            ->where('type', Conversation::TYPE_CLASS)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($course) {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_CLASS,
                'title' => $course->name,
                'course_id' => $course->id,
                'created_by' => $course->teacher_id,
            ]);

            $this->syncClassParticipants($conversation, $course);

            return $conversation;
        });
    }

    /**
     * Sinkronkan peserta grup kelas dengan keanggotaan kelas aktif.
     * Dipanggil saat guru menambah/mengeluarkan murid.
     */
    public function syncClassParticipants(Conversation $conversation, \App\Models\Course $course): void
    {
        $activeIds = $course->memberships()
            ->where('status', 'active')
            ->pluck('user_id')
            ->push($course->teacher_id)
            ->filter()
            ->unique();

        DB::transaction(function () use ($conversation, $course, $activeIds) {
            $existing = ConversationParticipant::where('conversation_id', $conversation->id)
                ->pluck('user_id', 'user_id');

            // Tambahkan yang belum ada.
            foreach ($activeIds as $userId) {
                ConversationParticipant::firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                ], [
                    'role' => $userId === $course->teacher_id
                        ? ConversationParticipant::ROLE_OWNER
                        : ConversationParticipant::ROLE_MEMBER,
                    'joined_at' => now(),
                ]);
            }

            // Tandai keluar untuk yang tidak lagi anggota.
            $orphaned = ConversationParticipant::where('conversation_id', $conversation->id)
                ->whereNull('left_at')
                ->whereNotIn('user_id', $activeIds->all())
                ->get();

            foreach ($orphaned as $row) {
                $row->update(['left_at' => now()]);
            }
        });
    }

    /**
     * Kirim pesan + lampiran opsional. Mengembalikan pesan tersimpan.
     *
     * @param  array<string, mixed>  $payload
     */
    public function sendMessage(User $sender, Conversation $conversation, array $payload): Message
    {
        return DB::transaction(function () use ($sender, $conversation, $payload) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'body' => $payload['body'] ?? null,
                'reply_to_id' => $payload['reply_to_id'] ?? null,
                'type' => ! empty($payload['attachment']) ? Message::TYPE_FILE : Message::TYPE_TEXT,
            ]);

            $conversation->update(['last_message_at' => now()]);

            // Pengirim otomatis dianggap telah membaca percakapan ini.
            ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $sender->id)
                ->update(['last_read_at' => now()]);

            return $message;
        });
    }

    /** Tandai seluruh pesan dalam percakapan sebagai sudah dibaca. */
    public function markRead(Conversation $conversation, User $user): void
    {
        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    /**
     * Lampiran pesan (dipakai controller untuk menyimpan file).
     *
     * @param  array<string, mixed>  $options
     */
    public function attach(Message $message, \Illuminate\Http\UploadedFile $file, User $uploader, array $options = []): Attachment
    {
        return app(AttachmentService::class)->store($message, $file, $uploader->id, $options);
    }
}
