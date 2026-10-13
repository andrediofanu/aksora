<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationBlock;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCourses;
use Tests\TestCase;

/**
 * Percakapan: privasi ketat (hanya peserta aktif), blokir dua arah,
 * dan pembatasan DM murid sesuai pengaturan sekolah.
 */
class MessagingTest extends TestCase
{
    use BuildsCourses, RefreshDatabase;

    /** Percakapan pribadi antara dua murid. */
    private function direct(User $a, User $b): Conversation
    {
        return app(ConversationService::class)->findOrCreateDirect($a, $b);
    }

    public function test_participant_can_open_conversation(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        $this->actingAs($a)
            ->get("/percakapan/{$conversation->id}")
            ->assertOk();
    }

    public function test_non_participant_cannot_open_conversation(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);
        $stranger = User::factory()->student()->create();

        $this->actingAs($stranger)
            ->get("/percakapan/{$conversation->id}")
            ->assertForbidden();
    }

    public function test_admin_cannot_read_other_peoples_private_conversation(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get("/percakapan/{$conversation->id}")
            ->assertForbidden();

        $this->actingAs($admin)
            ->get("/percakapan/{$conversation->id}/pesan")
            ->assertForbidden();
    }

    public function test_non_participant_cannot_send_message(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);
        $stranger = User::factory()->student()->create();

        $this->actingAs($stranger)
            ->post("/percakapan/{$conversation->id}/kirim", ['body' => 'Nyasar'])
            ->assertForbidden();

        $this->assertDatabaseMissing('messages', ['body' => 'Nyasar']);
    }

    public function test_participant_can_send_message_and_poll_returns_it(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        $this->actingAs($a)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => 'Halo, apa kabar?'])
            ->assertOk();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $a->id,
            'body' => 'Halo, apa kabar?',
        ]);

        // Polling mengembalikan pesan baru sejak latest_id.
        $poll = $this->actingAs($b)
            ->getJson("/percakapan/{$conversation->id}/pantau?after=0")
            ->assertOk()
            ->json();

        $this->assertNotEmpty($poll['messages']);
        $this->assertArrayHasKey('latest_id', $poll);
        $this->assertArrayHasKey('server_time', $poll);
    }

    public function test_message_body_is_required(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        $this->actingAs($a)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_message_body_cannot_exceed_limit(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        $this->actingAs($a)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => str_repeat('x', 5001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_oversized_attachment_is_rejected(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        // Berkas 11 MB (di atas batas 10 MB).
        $file = \Illuminate\Http\UploadedFile::fake()->create('besar.pdf', 11000, 'application/pdf');

        $this->actingAs($a)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_blocked_user_cannot_send_message_in_direct_conversation(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        ConversationBlock::create(['blocker_id' => $a->id, 'blocked_id' => $b->id]);

        // Dua arah: pemblokir dan yang diblokir sama-sama tertutup.
        $this->actingAs($a)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => 'dari pemblokir'])
            ->assertForbidden();

        $this->actingAs($b)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => 'dari yang diblokir'])
            ->assertForbidden();
    }

    public function test_student_dm_is_blocked_when_school_setting_is_off(): void
    {
        Setting::put('allow_student_private_message', '0');

        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        $this->actingAs($a)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => 'DM murid'])
            ->assertForbidden();
    }

    public function test_teacher_can_still_message_student_when_student_dm_is_off(): void
    {
        Setting::put('allow_student_private_message', '0');

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $conversation = $this->direct($teacher, $student);

        $this->actingAs($teacher)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => 'Pesan guru'])
            ->assertOk();
    }

    public function test_left_group_participant_cannot_send_message(): void
    {
        $owner = User::factory()->teacher()->create();
        $member = User::factory()->student()->create();

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'title' => 'Grup Uji',
            'created_by' => $owner->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $owner->id,
            'role' => ConversationParticipant::ROLE_OWNER,
            'joined_at' => now(),
        ]);
        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'role' => ConversationParticipant::ROLE_MEMBER,
            'joined_at' => now(),
            'left_at' => now(),
        ]);

        $this->actingAs($member)
            ->get("/percakapan/{$conversation->id}")
            ->assertForbidden();

        $this->actingAs($member)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => 'setelah keluar'])
            ->assertForbidden();
    }

    public function test_conversation_list_only_shows_own_conversations(): void
    {
        $mine = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        $strangerB = User::factory()->student()->create();

        $mineConv = $this->direct($mine, $other);
        $otherConv = $this->direct($other, $strangerB);

        $response = $this->actingAs($mine)->get('/percakapan');
        $response->assertOk();
        $response->assertDontSee("/percakapan/{$otherConv->id}", false);
    }

    public function test_message_can_only_be_deleted_by_sender_or_course_manager(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $a->id,
            'body' => 'Pesan milik A',
        ]);

        // B (penerima) tidak boleh menghapus pesan orang lain.
        $this->actingAs($b)
            ->deleteJson("/percakapan/{$conversation->id}/pesan/{$message->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $message->id]);

        // Pengirim bebas menghapus pesannya.
        $this->actingAs($a)
            ->deleteJson("/percakapan/{$conversation->id}/pesan/{$message->id}")
            ->assertOk();

        // Message memakai soft delete → periksa via soft delete assertion.
        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    public function test_new_message_shows_terkirim_until_a_recipient_reads_it(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        $this->actingAs($a)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => 'Halo juga'])
            ->assertOk()
            ->assertJsonPath('message.read_label', 'terkirim')
            ->assertJsonPath('message.read', false);

        $payload = $this->actingAs($a)
            ->getJson("/percakapan/{$conversation->id}/pantau?after=0")
            ->assertOk()
            ->json();

        $sent = collect($payload['messages'])->firstWhere('body', 'Halo juga');
        $this->assertFalse($sent['read'], 'Pesan baru belum boleh ditandai dibaca.');
        $this->assertSame('terkirim', $sent['read_label']);
        $this->assertContains('terkirim', array_values($payload['read_labels']));

        // Tampilan halaman juga menampilkan "terkirim", bukan "dibaca".
        $this->actingAs($a)
            ->get("/percakapan/{$conversation->id}")
            ->assertOk()
            ->assertSee('· terkirim');
    }

    public function test_message_becomes_dibaca_after_recipient_opens_conversation(): void
    {
        $a = User::factory()->student()->create();
        $b = User::factory()->student()->create();
        $conversation = $this->direct($a, $b);

        $this->actingAs($a)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => 'Tunggu balasan'])
            ->assertOk();

        // Penerima membuka percakapan → menandai baca.
        $this->actingAs($b)->get("/percakapan/{$conversation->id}")->assertOk();

        $payload = $this->actingAs($a)
            ->getJson("/percakapan/{$conversation->id}/pantau?after=0")
            ->assertOk()
            ->json();

        $sent = collect($payload['messages'])->firstWhere('body', 'Tunggu balasan');
        $this->assertTrue($sent['read']);
        $this->assertSame('dibaca', $sent['read_label']);
    }

    public function test_group_message_reports_partial_and_full_read_states(): void
    {
        $owner = User::factory()->teacher()->create();
        $m1 = User::factory()->student()->create();
        $m2 = User::factory()->student()->create();

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'title' => 'Grup Status Baca',
            'created_by' => $owner->id,
        ]);

        foreach ([$owner, $m1, $m2] as $index => $user) {
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'role' => $index === 0 ? ConversationParticipant::ROLE_OWNER : ConversationParticipant::ROLE_MEMBER,
                'joined_at' => now(),
            ]);
        }

        $this->actingAs($owner)
            ->postJson("/percakapan/{$conversation->id}/kirim", ['body' => 'Pengumuman kelas'])
            ->assertOk()
            ->assertJsonPath('message.read_label', 'terkirim');

        $label = function () use ($owner, $conversation) {
            $payload = $this->actingAs($owner)
                ->getJson("/percakapan/{$conversation->id}/pantau?after=0")
                ->assertOk()
                ->json();

            return collect($payload['messages'])->firstWhere('body', 'Pengumuman kelas')['read_label'];
        };

        $this->actingAs($m1)->get("/percakapan/{$conversation->id}")->assertOk();
        $this->assertSame('dibaca sebagian', $label());

        $this->actingAs($m2)->get("/percakapan/{$conversation->id}")->assertOk();
        $this->assertSame('dibaca semua', $label());
    }
}
