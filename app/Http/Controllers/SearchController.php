<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Pencarian global di topbar. Hasil disaring sesuai hak akses pengguna.
 */
class SearchController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['required', 'string', 'max:100']]);

        $q = trim($request->query('q'));
        $user = $request->user();

        $courseIds = $this->accessibleCourseIds($user);

        $courses = Course::whereIn('id', $courseIds)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            })
            ->with(['teacher:id,name', 'subject:id,name'])
            ->limit(8)
            ->get();

        $materialsQuery = LearningMaterial::whereIn('course_id', $courseIds)
            ->where('title', 'like', "%{$q}%");

        if (! $user->isAdmin()) {
            // Guru hanya melihat materi terbit milik kelasnya; murid mengikuti aturan terbit.
            $materialsQuery->visibleToStudents();
        }

        $materials = $materialsQuery->with('course:id,name')->limit(8)->get();

        $assignments = Assignment::whereIn('course_id', $courseIds)
            ->where(function ($query) use ($user) {
                if (! $user->hasAcademicAccess()) {
                    $query->visibleToStudents();
                }
            })
            ->where('title', 'like', "%{$q}%")
            ->with('course:id,name')
            ->limit(8)
            ->get();

        $quizzes = Quiz::whereIn('course_id', $courseIds)
            ->where(function ($query) use ($user) {
                if (! $user->hasAcademicAccess()) {
                    $query->visibleToStudents();
                }
            })
            ->where('title', 'like', "%{$q}%")
            ->with('course:id,name')
            ->limit(8)
            ->get();

        $users = collect();
        if ($user->isAdmin() || $user->isTeacher()) {
            $users = User::whereNull('deleted_at')
                ->where('name', 'like', "%{$q}%")
                ->limit(8)
                ->get(['id', 'name', 'email', 'role']);
        }

        $isEmpty = $courses->isEmpty() && $materials->isEmpty()
            && $assignments->isEmpty() && $quizzes->isEmpty() && $users->isEmpty();

        return view('search.index', [
            'q' => $q,
            'courses' => $courses,
            'materials' => $materials,
            'assignments' => $assignments,
            'quizzes' => $quizzes,
            'users' => $users,
            'isEmpty' => $isEmpty,
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function accessibleCourseIds(User $user): array
    {
        if ($user->isAdmin()) {
            return Course::pluck('id')->all();
        }

        if ($user->isTeacher()) {
            return Course::where('teacher_id', $user->id)
                ->orWhereHas('memberships', fn ($q) => $q
                    ->where('user_id', $user->id)
                    ->where('status', 'active'))
                ->pluck('id')
                ->all();
        }

        return $user->enrolledCourses()->pluck('courses.id')->all();
    }
}
