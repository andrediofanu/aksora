<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CourseMembership;
use App\Models\GradeWeight;
use App\Services\AuditLogger;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Course::with(['teacher:id,name', 'subject:id,name', 'academicYear:id,name'])
            ->withCount([
                'memberships as students_count' => fn ($q) => $q->where('role', 'student')->where('status', 'active'),
            ])
            ->where('status', Course::STATUS_ACTIVE)
            ->orderBy('name');

        if ($user->isAdmin()) {
            // admin melihat semua kelas
        } elseif ($user->isTeacher()) {
            $query->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhereHas('memberships', fn ($m) => $m
                        ->where('user_id', $user->id)
                        ->where('status', 'active'));
            });
        } else {
            $query->whereHas('memberships', fn ($m) => $m
                ->where('user_id', $user->id)
                ->where('status', 'active'));
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $status = $request->query('status');
        if (in_array($status, [Course::STATUS_ACTIVE, Course::STATUS_ARCHIVED], true)) {
            $query->where('status', $status);
        }

        return view('courses.index', [
            'courses' => $query->paginate(12)->withQueryString(),
            'filters' => ['q' => $search, 'status' => $status],
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->can('create', Course::class), 403);

        return view('courses.create', [
            'subjects' => \App\Models\Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'academicYears' => \App\Models\AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(StoreCourseRequest $request, ConversationService $conversations)
    {
        $course = DB::transaction(function () use ($request, $conversations) {
            $user = $request->user();

            $course = Course::create([
                'name' => $request->validated('name'),
                'code' => $this->generateUniqueCode(),
                'description' => $request->validated('description'),
                'subject_id' => $request->validated('subject_id'),
                'academic_year_id' => $request->validated('academic_year_id')
                    ?: \App\Models\AcademicYear::where('is_active', true)->value('id'),
                'teacher_id' => $user->id,
                'grade_level' => $request->validated('grade_level'),
                'room' => $request->validated('room'),
                'max_members' => $request->validated('max_members', 40),
                'allow_student_private_message' => $request->boolean('allow_student_private_message', true),
                'status' => Course::STATUS_ACTIVE,
            ]);

            CourseMembership::create([
                'course_id' => $course->id,
                'user_id' => $user->id,
                'role' => 'teacher',
                'status' => 'active',
                'joined_source' => 'teacher',
                'joined_at' => now(),
            ]);

            foreach ([GradeWeight::CATEGORY_ASSIGNMENT => 60, GradeWeight::CATEGORY_QUIZ => 40] as $cat => $w) {
                GradeWeight::create(['course_id' => $course->id, 'category' => $cat, 'weight' => $w]);
            }

            $conversations->ensureClassConversation($course);

            return $course;
        });

        AuditLogger::log('course.create', "Membuat kelas: {$course->name}", $course);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', "Kelas \"{$course->name}\" berhasil dibuat. Bagikan kode kelas: {$course->code}");
    }

    public function show(Request $request, Course $course)
    {
        abort_unless($request->user()->can('view', $course), 403);

        $user = $request->user();
        $isManager = $request->user()->can('manageContent', $course);

        $tab = $request->query('tab', 'overview');

        $course->load(['subject', 'academicYear', 'teacher:id,name,title']);

        $data = [
            'course' => $course,
            'tab' => $tab,
            'isManager' => $isManager,
            'studentCount' => $course->studentCount(),
        ];

        if ($isManager) {
            $data['pendingSubmissions'] = \App\Models\AssignmentSubmission::whereIn('assignment_id', function ($q) use ($course) {
                $q->select('id')->from('assignments')->where('course_id', $course->id);
            })->whereNull('score')->count();
        } else {
            $data['mySubmissionCounts'] = [
                'pending' => \App\Models\Assignment::where('course_id', $course->id)
                    ->visibleToStudents()
                    ->whereDoesntHave('submissions', fn ($q) => $q->where('user_id', $user->id))
                    ->count(),
            ];
        }

        return view('courses.show', $data);
    }

    public function edit(Course $course)
    {
        abort_unless(auth()->user()->can('update', $course), 403);

        return view('courses.edit', [
            'course' => $course,
            'subjects' => \App\Models\Subject::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'academicYears' => \App\Models\AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $course->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'subject_id' => $request->validated('subject_id'),
            'academic_year_id' => $request->validated('academic_year_id'),
            'grade_level' => $request->validated('grade_level'),
            'room' => $request->validated('room'),
            'max_members' => $request->validated('max_members'),
            'allow_student_private_message' => $request->boolean('allow_student_private_message', true),
        ]);

        AuditLogger::log('course.update', "Mengubah kelas: {$course->name}", $course);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Perubahan kelas berhasil disimpan.');
    }

    public function destroy(Request $request, Course $course)
    {
        abort_unless($request->user()->can('delete', $course), 403);

        AuditLogger::log('course.delete', "Menghapus kelas: {$course->name}", $course);
        $name = $course->name;
        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', "Kelas \"{$name}\" berhasil dihapus.");
    }

    public function archive(Request $request, Course $course)
    {
        abort_unless($request->user()->can('archive', $course), 403);

        $archiving = $course->status !== Course::STATUS_ARCHIVED;

        $course->update([
            'status' => $archiving ? Course::STATUS_ARCHIVED : Course::STATUS_ACTIVE,
            'archived_at' => $archiving ? now() : null,
        ]);

        AuditLogger::log('course.archive', ($archiving ? 'Mengarsipkan' : 'Mengaktifkan')." kelas: {$course->name}", $course);

        return back()->with('success', $archiving
            ? 'Kelas berhasil diarsipkan.'
            : 'Kelas berhasil diaktifkan kembali.');
    }

    /** Perbarui kode kelas (mis. bila kode bocor). */
    public function regenerateCode(Request $request, Course $course)
    {
        abort_unless($request->user()->can('update', $course), 403);

        $course->update(['code' => $this->generateUniqueCode()]);

        AuditLogger::log('course.recode', "Memperbarui kode kelas: {$course->name}", $course);

        return back()->with('success', 'Kode kelas baru: '.$course->code);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(7));
        } while (Course::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
