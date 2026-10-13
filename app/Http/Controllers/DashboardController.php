<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AuditLog;
use App\Models\CalendarEvent;
use App\Models\Course;
use App\Models\User;
use App\Services\GradeCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Dashboard utama. Konten berbeda per peran (admin/guru/murid).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, GradeCalculator $grades)
    {
        $user = $request->user();

        $shared = [
            'unreadMessages' => $user->totalUnreadMessages(),
            'unreadNotifications' => $this->unreadNotifications($user),
            'searchUrl' => route('search.index'),
        ];

        return match ($user->role) {
            'admin' => view('dashboard.admin', array_merge($shared, $this->adminData($user))),
            'teacher' => view('dashboard.teacher', array_merge($shared, $this->teacherData($user))),
            default => view('dashboard.student', array_merge($shared, $this->studentData($user, $grades))),
        };
    }

    private function unreadNotifications(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /* ------------------------------------------------------------------ */

    private function adminData(User $user): array
    {
        return [
            'stats' => [
                'users' => User::whereNull('deleted_at')->count(),
                'teachers' => User::whereNull('deleted_at')->where('role', 'teacher')->count(),
                'students' => User::whereNull('deleted_at')->where('role', 'student')->count(),
                'courses' => Course::where('status', Course::STATUS_ACTIVE)->count(),
            ],
            'recentUsers' => User::withTrashed()
                ->latest('created_at')
                ->limit(6)
                ->get(['id', 'name', 'email', 'role', 'status', 'created_at']),
            'recentLogs' => AuditLog::with('user')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(),
            'courses' => Course::with(['teacher', 'subject'])
                ->where('status', Course::STATUS_ACTIVE)
                ->orderByDesc('updated_at')
                ->limit(6)
                ->get(),
        ];
    }

    private function teacherData(User $user): array
    {
        $courses = Course::with(['subject', 'academicYear'])
            ->where('teacher_id', $user->id)
            ->where('status', Course::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $courseIds = $courses->pluck('id');

        $pendingGrading = AssignmentSubmission::whereIn('assignment_id', function ($query) use ($courseIds) {
            $query->select('id')->from('assignments')->whereIn('course_id', $courseIds);
        })
            ->whereNull('score')
            ->with(['assignment:id,title,course_id,due_at', 'user:id,name'])
            ->orderByDesc('submitted_at')
            ->limit(8)
            ->get();

        $upcoming = Assignment::whereIn('course_id', $courseIds)
            ->where('status', Assignment::STATUS_PUBLISHED)
            ->where('due_at', '>=', now())
            ->orderBy('due_at')
            ->with('course:id,name')
            ->limit(6)
            ->get();

        $announcements = Announcement::whereIn('course_id', $courseIds)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['course:id,name', 'creator:id,name'])
            ->latest('published_at')
            ->limit(5)
            ->get();

        return [
            'courses' => $courses,
            'pendingGrading' => $pendingGrading,
            'upcoming' => $upcoming,
            'announcements' => $announcements,
            'stats' => [
                'courses' => $courses->count(),
                'students' => \App\Models\CourseMembership::whereIn('course_id', $courseIds)
                    ->where('role', 'student')
                    ->where('status', 'active')
                    ->count(),
                'pending' => AssignmentSubmission::whereIn('assignment_id', function ($query) use ($courseIds) {
                    $query->select('id')->from('assignments')->whereIn('course_id', $courseIds);
                })->whereNull('score')->count(),
                'materials' => \App\Models\LearningMaterial::whereIn('course_id', $courseIds)->count(),
            ],
        ];
    }    private function studentData(User $user, GradeCalculator $grades): array
    {
        $courseIds = $user->enrolledCourses()->pluck('courses.id');

        $courses = Course::with(['teacher:id,name', 'subject:id,name', 'academicYear:id,name'])
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();

        $enrolledAssignments = Assignment::whereIn('course_id', $courseIds)
            ->where('status', Assignment::STATUS_PUBLISHED)
            ->where(function ($query) {
                $query->whereNull('due_at')->orWhere('due_at', '>=', now()->subDays(30));
            })
            ->with(['course:id,name', 'submissions' => fn ($q) => $q->where('user_id', $user->id)])
            ->get();

        $upcoming = $enrolledAssignments
            ->filter(fn (Assignment $a) => $a->due_at !== null
                && $a->due_at->isFuture()
                && ! $a->submissions->first())
            ->sortBy('due_at')
            ->values()
            ->take(6);

        $todo = $enrolledAssignments
            ->filter(function (Assignment $a) use ($user) {
                $submission = $a->submissions->first();

                if ($submission && $submission->status !== AssignmentSubmission::STATUS_REVISION) {
                    return false;
                }

                return $a->due_at === null
                    || $a->due_at->isFuture()
                    || $submission !== null;
            })
            ->sortBy(fn (Assignment $a) => $a->due_at?->timestamp ?? PHP_INT_MAX)
            ->values()
            ->take(8);

        $announcements = Announcement::whereIn('course_id', $courseIds)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['course:id,name', 'creator:id,name,title'])
            ->latest('published_at')
            ->limit(5)
            ->get();

        $events = CalendarEvent::whereIn('course_id', $courseIds)
            ->where('starts_at', '>=', now()->startOfDay())
            ->with('course:id,name')
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

        // Progres materi dihitung dengan 2 query terpisah agar tidak N+1.
        $visibleMaterials = \App\Models\LearningMaterial::whereIn('course_id', $courseIds)
            ->visibleToStudents()
            ->get(['id', 'course_id']);

        $completedIds = \App\Models\MaterialCompletion::whereIn('learning_material_id', $visibleMaterials->pluck('id'))
            ->where('user_id', $user->id)
            ->pluck('learning_material_id')
            ->all();

        $materialsByCourse = $visibleMaterials->groupBy('course_id');
        $completedByMaterial = collect($completedIds);

        $courseProgress = $courses->map(function (Course $course) use ($materialsByCourse, $completedByMaterial, $user, $grades) {
            $ids = $materialsByCourse->get($course->id, collect())->pluck('id');
            $total = $ids->count();
            $done = $ids->filter(fn ($id) => $completedByMaterial->contains($id))->count();

            return [
                'course' => $course,
                'total' => $total,
                'done' => $done,
                'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
                'grade' => $grades->forStudent($course, $user)['final'],
            ];
        });

        return [
            'courses' => $courses,
            'courseProgress' => $courseProgress,
            'upcoming' => $upcoming,
            'todo' => $todo,
            'announcements' => $announcements,
            'events' => $events,
            'stats' => [
                'courses' => $courses->count(),
                'todo' => $todo->count(),
                'upcoming' => $upcoming->count(),
                'announcements' => $announcements->count(),
            ],
        ];
    }
}
