<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Pembuat notifikasi internal (disimpan di tabel `notifications`
 * menggunakan format database notification bawaan Laravel).
 *
 * Tidak mengklaim pengiriman real-time: klien memperbarui notifikasi
 * melalui halaman notifikasi atau polling ringan.
 */
class Notifier
{
    /**
     * @param  iterable<User>  $recipients
     * @param  array<string, mixed>  $data  Harus memuat: title, body, url (opsional), kind
     */
    public function send(iterable $recipients, array $data, ?User $actor = null): void
    {
        $payload = array_merge([
            'title' => 'Notifikasi',
            'body' => '',
            'url' => null,
            'kind' => 'general',
            'actor' => $actor?->name,
        ], $data);

        $now = now();
        $type = \Illuminate\Notifications\DatabaseNotification::class;

        $rows = [];
        foreach ($recipients as $recipient) {
            if (! $recipient instanceof User) {
                continue;
            }
            if ($actor && $recipient->id === $actor->id) {
                continue; // jangan notifikasi diri sendiri
            }

            $rows[] = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => $type,
                'notifiable_type' => $recipient->getMorphClass(),
                'notifiable_id' => $recipient->id,
                'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
                'read_at' => null,
            ];
        }

        if ($rows === []) {
            return;
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('notifications')->insert($chunk);
        }
    }
}
