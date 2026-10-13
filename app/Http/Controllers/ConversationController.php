<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationBlock;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pusat percakapan: daftar, buat, dan tampilan obrolan.
 *
 * Otorisasi: ConversationPolicy memastikan hanya peserta aktif yang boleh
 * membaca/mengirim. Pemeriksaan dilakukan di backend, bukan di Blade.
 */
class ConversationController extends Controller
{
    public function __construct(private ConversationService $service)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $search = trim((string) $request->query('q'));
        $type = $request->query('type');

        $conversationIds = ConversationParticipant::where('user_id', $user->id)
            ->whereNull('left_at')
            ->pluck('conversation_id');

        $base = Conversation::whereIn('id', $conversationIds);

        if (in_array($type, ['direct', 'group', 'class'], true)) {
            $base->where('type', $type);
        }

        if ($search !== '') {
            // Cari berdasarkan judul percakapan ATAU nama peserta.
            $base->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('participants', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        $conversations = $base->with(['participants', 'lastMessage.sender:id,name,avatar_path'])
            ->orderByDesc('last_message_at')
            ->paginate(20)
            ->withQueryString();

        foreach ($conversations as $conversation) {
            $conversation->setAttribute('_unread', $conversation->unreadCountFor($user));
            $conversation->setAttribute('_display_title', $conversation->displayTitleFor($user));
        }

        $candidateQuery = User::query()
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->limit(15);

        if (! $user->isAdmin() && ! $user->isTeacher()) {
            // Murid hanya dapat mencari pengguna yang berbagi kelas dengannya.
            $candidateQuery->whereHas('memberships', function ($m) use ($user) {
                $m->where('status', 'active')
                    ->whereIn('course_id', function ($q2) use ($user) {
                        $q2->select('course_id')->from('course_memberships')
                            ->where('user_id', $user->id)
                            ->where('status', 'active');
                    });
            });
        }

        if ($search !== '') {
            $candidateQuery->where('name', 'like', "%{$search}%");
        }

        $blockedIds = ConversationBlock::where('blocker_id', $user->id)->pluck('blocked_id');

        $directCandidates = $candidateQuery
            ->get(['id', 'name', 'avatar_path', 'role'])
            ->reject(fn (User $u) => $blockedIds->contains($u->id))
            ->values();

        return view('conversations.index', [
            'conversations' => $conversations,
            'search' => $search,
            'typeFilter' => $type,
            'candidates' => $directCandidates,
            'unreadTotal' => $user->totalUnreadMessages(),
        ]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        abort_unless($request->user()->can('view', $conversation), 403);

        $user = $request->user();

        $this->service->markRead($conversation, $user);

        $before = $request->query('before');
        $perPage = 30;

        $query = $conversation->messages()
            ->with(['sender:id,name,avatar_path,title', 'replyTo.sender:id,name', 'attachments'])
            ->orderByDesc('id');

        if (is_numeric($before)) {
            $query->where('id', '<', (int) $before);
        }

        $messages = $query->limit($perPage)->get();
        $hasMore = $messages->count() === $perPage;

        $participantRows = $conversation->participantRows()->get();

        return view('conversations.show', [
            'conversation' => $conversation,
            'messages' => $messages->reverse()->values(),
            'hasMore' => $hasMore,
            'oldestId' => $messages->min('id'),
            'readLabels' => $messages
                ->filter(fn (Message $m) => $m->sender_id === $user->id)
                ->mapWithKeys(fn (Message $m) => [$m->id => $m->readLabelFromRows($participantRows)])
                ->all(),
            'participants' => $conversation->participants()
                ->whereNull('conversation_participants.left_at')
                ->orderBy('users.name')
                ->get(['users.id', 'users.name', 'users.avatar_path', 'users.role',
                    'conversation_participants.role as participant_role',
                    'conversation_participants.last_read_at']),
            'canSend' => $user->can('send', $conversation),
            'canManage' => $user->can('update', $conversation),
            'canLeave' => $user->can('leave', $conversation),
            'blockedUsers' => ConversationBlock::where('blocker_id', $user->id)->pluck('blocked_id'),
        ]);
    }

    /** Buat percakapan pribadi / grup. */
    public function store(Request $request)
    {
        abort_unless($request->user()->can('start', Conversation::class), 403);

        $data = $request->validate([
            'type' => ['required', 'in:direct,group'],
            'user_ids' => ['required_unless:type,direct', 'array', 'min:1', 'max:50'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'user_id' => ['required_if:type,direct', 'nullable', 'integer', 'exists:users,id'],
            'title' => ['required_if:type,group', 'nullable', 'string', 'max:150'],
        ], [
            'user_id.required_if' => 'Pilih pengguna yang ingin diajak bicara.',
            'title.required_if' => 'Nama grup wajib diisi.',
            'user_ids.required_unless' => 'Pilih minimal satu anggota grup.',
        ]);

        $user = $request->user();

        if ($data['type'] === 'direct') {
            $other = User::where('id', $data['user_id'])
                ->where('status', 'active')
                ->firstOrFail();

            if ($other->id === $user->id) {
                return back()->withErrors(['user_id' => 'Tidak dapat membuat percakapan dengan diri sendiri.']);
            }

            if ($user->hasBlocked($other) || $user->isBlockedBy($other)) {
                return back()->withErrors(['user_id' => 'Pengguna ini diblokir.']);
            }

            if ($user->isStudent() && $other->isStudent()) {
                $sharedClass = DB::table('course_memberships as a')
                    ->join('course_memberships as b', 'a.course_id', '=', 'b.course_id')
                    ->where('a.user_id', $user->id)
                    ->where('a.status', 'active')
                    ->where('b.user_id', $other->id)
                    ->where('b.status', 'active')
                    ->exists();

                $allowed = \App\Models\Setting::get('allow_student_private_message', '1');

                if (! $sharedClass || $allowed !== '1') {
                    return back()->withErrors([
                        'user_id' => 'Pesan pribadi antar murid tidak diizinkan oleh pengaturan sekolah.',
                    ]);
                }
            }

            $conversation = $this->service->findOrCreateDirect($user, $other);
        } else {
            $ids = array_map('intval', (array) $data['user_ids']);
            $conversation = $this->service->createGroup($user, $data['title'], $ids);
        }

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'Percakapan siap digunakan.');
    }

    public function update(Request $request, Conversation $conversation)
    {
        abort_unless($request->user()->can('update', $conversation), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
        ], ['title.required' => 'Nama grup wajib diisi.']);

        $conversation->update(['title' => $data['title']]);

        return back()->with('success', 'Nama percakapan diperbarui.');
    }

    public function leave(Request $request, Conversation $conversation)
    {
        abort_unless($request->user()->can('leave', $conversation), 403);

        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $request->user()->id)
            ->update(['left_at' => now()]);

        return redirect()
            ->route('conversations.index')
            ->with('success', 'Anda keluar dari percakapan.');
    }

    public function destroy(Request $request, Conversation $conversation)
    {
        abort_unless($request->user()->can('delete', $conversation), 403);

        if ($conversation->isClass()) {
            return back()->withErrors(['conversation' => 'Percakapan kelas tidak dapat dihapus.']);
        }

        $conversation->delete();

        return redirect()
            ->route('conversations.index')
            ->with('success', 'Percakapan dihapus.');
    }

    /** Blokir pengguna (melarang pesan pribadi dua arah). */
    public function block(Request $request, User $user)
    {
        $me = $request->user();

        abort_if($me->id === $user->id, 422, 'Tidak dapat memblokir diri sendiri.');

        ConversationBlock::firstOrCreate([
            'blocker_id' => $me->id,
            'blocked_id' => $user->id,
        ]);

        AuditLogger::log('user.block', "Memblokir pengguna: {$user->name}");

        return back()->with('success', "{$user->name} berhasil diblokir.");
    }

    public function unblock(Request $request, User $user)
    {
        ConversationBlock::where('blocker_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->delete();

        return back()->with('success', 'Pemblokiran dibatalkan.');
    }

    /** Laporkan pesan. */
    public function report(Request $request, Message $message)
    {
        $conversation = $message->conversation;
        abort_unless($request->user()->can('view', $conversation), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], ['reason.required' => 'Alasan laporan wajib diisi.']);

        MessageReport::updateOrCreate(
            ['message_id' => $message->id, 'reporter_id' => $request->user()->id],
            ['reason' => $data['reason'], 'status' => MessageReport::STATUS_OPEN],
        );

        AuditLogger::log('message.report', 'Melaporkan pesan #'.$message->id, $message);

        return back()->with('success', 'Laporan terkirim. Admin akan meninjaunya.');
    }
}
