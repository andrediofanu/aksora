<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;

/**
 * Kebijakan privasi percakapan.
 *
 * Aturan inti:
 *  - Hanya peserta aktif percakapan yang boleh membaca/mengirim pesan.
 *    Admin pun TIDAK otomatis dapat membaca percakapan pribadi orang lain.
 *  - Pengguna yang saling memblokir tidak dapat memulai/melanjutkan pesan pribadi.
 *  - Percakapan kelas mengikuti keanggotaan kelas dan pengaturan sekolah.
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participantRows()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();
    }

    public function send(User $user, Conversation $conversation): bool
    {
        if (! $this->view($user, $conversation)) {
            return false;
        }

        if ($conversation->isDirect()) {
            $other = $conversation->activeParticipants()
                ->where('users.id', '!=', $user->id)
                ->first();

            if ($other) {
                // Pemblokiran bersifat dua arah untuk pesan pribadi.
                if ($user->hasBlocked($other) || $user->isBlockedBy($other)) {
                    return false;
                }

                // Pengaturan sekolah: murid tidak boleh saling DM bila dimatikan.
                if ($user->isStudent() && $other->isStudent()
                    && ! $this->allowStudentPrivateMessage($conversation)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** Buat/mulai percakapan baru. */
    public function start(User $user): bool
    {
        return $user->isActive();
    }

    /** Ubah judul/tambah anggota (hanya grup yang dibuat pengguna). */
    public function update(User $user, Conversation $conversation): bool
    {
        if ($conversation->isClass()) {
            // Grup kelas dikelola guru pengampu kelas tersebut.
            $course = $conversation->course;

            return $course && ($course->teacher_id === $user->id || $user->isAdmin());
        }

        $pivot = $conversation->participantRows()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (! $pivot) {
            return false;
        }

        return $pivot->role === ConversationParticipant::ROLE_OWNER
            || $pivot->role === ConversationParticipant::ROLE_MODERATOR
            || $user->isAdmin();
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $this->update($user, $conversation);
    }

    /** Peserta keluar dari percakapan grup (bukan kelas). */
    public function leave(User $user, Conversation $conversation): bool
    {
        if ($conversation->isClass()) {
            return false; // anggota kelas mengikuti keanggotaan kelas
        }

        return $conversation->participantRows()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();
    }

    private function allowStudentPrivateMessage(Conversation $conversation): bool
    {
        if ($conversation->course) {
            return (bool) $conversation->course->allow_student_private_message;
        }

        $setting = \App\Models\Setting::get('allow_student_private_message', '1');

        return $setting === '1' || $setting === 1;
    }
}
