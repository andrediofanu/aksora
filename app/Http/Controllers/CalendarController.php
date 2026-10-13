<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Course;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Kalender akademik: tenggat tugas, jadwal kuis, dan agenda kelas.
 * Data digabung dari tabel transaksi sehingga tidak ada jadwal ganda.
 */
class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $from = \Carbon\Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()));
        $to = \Carbon\Carbon::parse($request->query('to', $from->copy()->addMonths(1)));

        $courseIds = $user->isAdmin()
            ? Course::pluck('id')
            : ($user->isTeacher()
                ? Course::where('teacher_id', $user->id)
                    ->orWhereHas('memberships', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))
                    ->pluck('id')
                : $user->enrolledCourses()->pluck('courses.id'));

        $courses = Course::whereIn('id', $courseIds)->orderBy('name')->get();

        $events = $this->collectEvents($user, $courseIds, $from, $to);

        return view('calendar.index', [
            'events' => $events->sortBy('starts_at')->values(),
            'courses' => $courses,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /** API JSON untuk kalender (dipakai halaman kalender). */
    public function data(Request $request)
    {
        $user = $request->user();

        $from = \Carbon\Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()));
        $to = \Carbon\Carbon::parse($request->query('to', $from->copy()->addMonths(2)));

        $courseIds = $user->isAdmin()
            ? Course::pluck('id')
            : ($user->isTeacher()
                ? Course::where('teacher_id', $user->id)
                    ->orWhereHas('memberships', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))
                    ->pluck('id')
                : $user->enrolledCourses()->pluck('courses.id'));

        $events = $this->collectEvents($user, $courseIds, $from, $to);

        return response()->json([
            'data' => $events->sortBy('starts_at')->values()->map(fn ($e) => [
                'id' => $e['id'],
                'title' => $e['title'],
                'starts_at' => $e['starts_at']->toIso8601String(),
                'ends_at' => $e['ends_at']?->toIso8601String(),
                'type' => $e['type'],
                'course' => $e['course'],
                'url' => $e['url'],
            ]),
        ]);
    }

    public function create(Request $request, Course $course)
    {
        abort_unless($request->user()->can('manageContent', $course), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ], [
            'title.required' => 'Judul agenda wajib diisi.',
            'starts_at.required' => 'Waktu mulai wajib diisi.',
            'ends_at.after' => 'Waktu selesai harus setelah waktu mulai.',
        ]);

        CalendarEvent::create([
            'course_id' => $course->id,
            'academic_year_id' => $course->academic_year_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => CalendarEvent::TYPE_AGENDA,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('calendar.create', "Menambah agenda: {$data['title']}", $course);

        return back()->with('success', 'Agenda kalender berhasil ditambahkan.');
    }

    public function destroy(Request $request, CalendarEvent $event)
    {
        abort_unless($request->user()->can('manageContent', $event->course), 403);

        $event->delete();

        return back()->with('success', 'Agenda dihapus.');
    }

    /**
     * @param  array<int, mixed>  $courseIds
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function collectEvents($user, $courseIds, \Carbon\Carbon $from, \Carbon\Carbon $to)
    {
        $events = collect();

        // 1. Agenda eksplisit.
        foreach (CalendarEvent::whereIn('course_id', $courseIds)
                     ->where('starts_at', '>=', $from)
                     ->where('starts_at', '<=', $to)
                     ->with('course:id,name,code')
                     ->get() as $event) {
            $events->push([
                'id' => 'evt-'.$event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'type' => $event->type,
                'course' => $event->course?->name,
                'course_id' => $event->course_id,
                'url' => null,
            ]);
        }

        // 2. Tenggat tugas.
        $assignmentsQuery = \App\Models\Assignment::whereIn('course_id', $courseIds)
            ->where('status', 'published')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$from, $to])
            ->with('course:id,name,code');

        if (! $user->hasAcademicAccess()) {
            $assignmentsQuery->whereHas('course', fn ($q) => $q
                ->whereHas('memberships', fn ($m) => $m
                    ->where('user_id', $user->id)
                    ->where('status', 'active')));
        }

        foreach ($assignmentsQuery->get() as $assignment) {
            $events->push([
                'id' => 'asg-'.$assignment->id,
                'title' => 'Tenggat: '.$assignment->title,
                'starts_at' => $assignment->due_at,
                'ends_at' => null,
                'type' => CalendarEvent::TYPE_ASSIGNMENT,
                'course' => $assignment->course?->name,
                'course_id' => $assignment->course_id,
                'url' => route('assignments.show', [$assignment->course_id, $assignment]),
            ]);
        }

        // 3. Jadwal kuis (buka & tutup).
        foreach (\App\Models\Quiz::whereIn('course_id', $courseIds)
                     ->where('status', 'published')
                     ->with('course:id,name,code')
                     ->get() as $quiz) {
            foreach (['opens_at' => 'Dibuka: ', 'closes_at' => 'Ditutup: '] as $field => $prefix) {
                $at = $quiz->{$field};
                if ($at && $at->between($from, $to)) {
                    $events->push([
                        'id' => 'qz-'.$quiz->id.'-'.$field,
                        'title' => $prefix.$quiz->title,
                        'starts_at' => $at,
                        'ends_at' => null,
                        'type' => CalendarEvent::TYPE_QUIZ,
                        'course' => $quiz->course?->name,
                        'course_id' => $quiz->course_id,
                        'url' => route('quizzes.show', [$quiz->course_id, $quiz]),
                    ]);
                }
            }
        }

        return $events;
    }
}
