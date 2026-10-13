<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Course;
use App\Models\CourseMembership;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ConversationService;
use App\Services\Notifier;
use Illuminate\Http\Request;

class CourseMembershipController extends Controller
{
    public function __construct(private ConversationService $conversations)
    {
    }

    /** Halaman daftar anggota kelas. */
    public function index(Request $request, Course $course)
    {
        abort_unless($request->user()->can('view', $course), 403);

        $search = trim((string) $request->query('q'));

        $query = CourseMembership::with('user:id,name,email,role,avatar_path')
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->orderBy('role')
            ->orderBy('joined_at');

        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return view('courses.members', [
            'course' => $course,
            'memberships' => $query->paginate(20)->withQueryString(),
            'search' => $search,
            'canManage' => $request->user()->can('manageMembers', $course),
        ]);
    }

    /** Guru menambahkan murid ke kelas. */
    public function store(Request $request, Course $course)
    {
        abort_unless($request->user()->can('manageMembers', $course), 403);

        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ], [
            'identifier.required' => 'Masukkan email atau NIS murid.',
        ]);

        $identifier = trim($request->input('identifier'));

        $student = User::where('role', 'student')
            ->where('status', 'active')
            ->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)
                    ->orWhere('nis', $identifier)
                    ->orWhere('name', $identifier);
            })
            ->first();

        if (! $student) {
            return back()->withErrors([
                'identifier' => 'Murid dengan email/NIS tersebut tidak ditemukan atau tidak aktif.',
            ]);
        }

        if ($course->memberships()->where('user_id', $student->id)->where('status', 'active')->exists()) {
            return back()->withErrors(['identifier' => "{$student->name} sudah menjadi anggota kelas ini."]);
        }

        if ($course->studentCount() >= $course->max_members) {
            return back()->withErrors(['identifier' => 'Kapasitas kelas sudah penuh.']);
        }

        $this->addMember($course, $student, 'teacher');

        AuditLogger::log('course.member.add', "Menambahkan {$student->name} ke kelas {$course->name}", $course);

        return back()->with('success', "{$student->name} berhasil ditambahkan ke kelas.");
    }

    /** Guru mengeluarkan murid. */
    public function destroy(Request $request, Course $course, User $user)
    {
        abort_unless($request->user()->can('manageMembers', $course), 403);

        abort_if($course->teacher_id === $user->id, 422, 'Guru pengampu tidak dapat dikeluarkan dari kelasnya sendiri.');

        $membership = $course->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $membership->update(['status' => 'removed', 'left_at' => now()]);

        $classConversation = Conversation::query()
            ->where('type', Conversation::TYPE_CLASS)
            ->where('course_id', $course->id)
            ->first();

        if ($classConversation) {
            $this->conversations->syncClassParticipants($classConversation, $course);
        }

        AuditLogger::log('course.member.remove', "Mengeluarkan {$user->name} dari kelas {$course->name}", $course);

        return back()->with('success', "{$user->name} dikeluarkan dari kelas.");
    }

    /** Murid keluar sendiri dari kelas. */
    public function leave(Request $request, Course $course)
    {
        abort_unless($request->user()->can('leave', $course), 403);

        $user = $request->user();

        $membership = $course->memberships()
            ->where('user_id', $user->id)
            ->where('role', 'student')
            ->where('status', 'active')
            ->firstOrFail();

        $membership->update(['status' => 'removed', 'left_at' => now()]);

        $classConversation = Conversation::query()
            ->where('type', Conversation::TYPE_CLASS)
            ->where('course_id', $course->id)
            ->first();

        if ($classConversation) {
            $this->conversations->syncClassParticipants($classConversation, $course);
        }

        AuditLogger::log('course.leave', "Keluar dari kelas: {$course->name}", $course);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Anda telah keluar dari kelas.');
    }

    /** Tambah anggota + notifikasi + sinkron percakapan kelas. */
    private function addMember(Course $course, User $student, string $source): void
    {
        CourseMembership::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $student->id],
            [
                'role' => 'student',
                'status' => 'active',
                'joined_source' => $source,
                'joined_at' => now(),
                'left_at' => null,
            ],
        );

        $classConversation = Conversation::query()
            ->where('type', Conversation::TYPE_CLASS)
            ->where('course_id', $course->id)
            ->first();

        if ($classConversation) {
            $this->conversations->syncClassParticipants($classConversation, $course);
        }

        app(Notifier::class)->send([$student], [
            'title' => 'Kelas baru',
            'body' => "Anda terdaftar pada kelas \"{$course->name}\".",
            'url' => route('courses.show', $course),
            'kind' => 'course',
        ]);
    }
}
