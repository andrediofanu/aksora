<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinCourseRequest;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\CourseMembership;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ConversationService;
use App\Services\Notifier;
use Illuminate\Http\Request;

/**
 * Bergabung ke kelas melalui kode kelas atau tautan undangan.
 */
class CourseJoinController extends Controller
{
    public function __construct(private ConversationService $conversations)
    {
    }

    /** Form bergabung (dibuka dari halaman "Kelas Saya"). */
    public function create()
    {
        return view('courses.join');
    }

    public function store(JoinCourseRequest $request)
    {
        $code = strtoupper(trim($request->validated('code')));

        $course = Course::where('status', Course::STATUS_ACTIVE)
            ->where('code', $code)
            ->first();

        if (! $course) {
            return back()->withErrors(['code' => 'Kode kelas tidak ditemukan atau kelas sudah ditutup.']);
        }

        return $this->enroll($request, $course);
    }

    /** Halaman tautan undangan. */
    public function showByToken(string $token)
    {
        $course = Course::where('status', Course::STATUS_ACTIVE)
            ->where('invite_token', $token)
            ->firstOrFail();

        return view('courses.join', ['course' => $course, 'token' => $token]);
    }

    public function storeByToken(Request $request, string $token)
    {
        $course = Course::where('status', Course::STATUS_ACTIVE)
            ->where('invite_token', $token)
            ->firstOrFail();

        return $this->enroll($request, $course);
    }

    private function enroll(Request $request, Course $course)
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return back()->withErrors(['code' => 'Hanya akun murid yang dapat bergabung ke kelas.']);
        }

        $existing = $course->memberships()->where('user_id', $user->id)->first();

        if ($existing && $existing->status === 'active') {
            return redirect()
                ->route('courses.show', $course)
                ->with('success', 'Anda sudah menjadi anggota kelas ini.');
        }

        if ($course->studentCount() >= $course->max_members) {
            return back()->withErrors(['code' => 'Kapasitas kelas sudah penuh. Hubungi guru Anda.']);
        }

        CourseMembership::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'role' => 'student',
                'status' => 'active',
                'joined_source' => $existing ? 'rejoin' : 'code',
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

        app(Notifier::class)->send([$course->teacher], [
            'title' => 'Murid bergabung',
            'body' => "{$user->name} bergabung ke kelas \"{$course->name}\".",
            'url' => route('course.members', $course),
            'kind' => 'course',
            'actor' => $user->name,
        ], $user);

        AuditLogger::log('course.join', "Bergabung ke kelas: {$course->name}", $course);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', "Berhasil bergabung ke kelas \"{$course->name}\".");
    }
}
