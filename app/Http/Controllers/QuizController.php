<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRequest;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request, Course $course)
    {
        abort_unless($request->user()->can('view', $course), 403);

        $user = $request->user();
        $canManage = $user->can('create', [Quiz::class, $course]);

        $query = $course->quizzes()->orderByDesc('created_at');

        if (! $canManage) {
            $query->visibleToStudents();
        }

        $quizzes = $query->get();

        $myAttempts = collect();
        if (! $canManage) {
            $myAttempts = QuizAttempt::whereIn('quiz_id', $quizzes->pluck('id'))
                ->where('user_id', $user->id)
                ->orderByDesc('attempt_no')
                ->get()
                ->groupBy('quiz_id');
        }

        return view('quizzes.index', [
            'course' => $course,
            'quizzes' => $quizzes,
            'myAttempts' => $myAttempts,
            'canManage' => $canManage,
        ]);
    }

    public function create(Course $course)
    {
        abort_unless(auth()->user()->can('create', [Quiz::class, $course]), 403);

        return view('quizzes.create', ['course' => $course]);
    }

    public function store(StoreQuizRequest $request, Course $course)
    {
        $data = $request->validated();

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
            'opens_at' => $data['opens_at'] ?? null,
            'closes_at' => $data['closes_at'] ?? null,
            'max_attempts' => $data['max_attempts'],
            'show_results' => $data['show_results'],
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'pass_score' => $data['pass_score'],
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published'
                ? ($data['published_at'] ?? now())
                : null,
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('quiz.create', "Membuat kuis: {$quiz->title}", $quiz);

        if ($quiz->isPublished()) {
            app(Notifier::class)->send(
                $course->students()->get(),
                [
                    'title' => 'Kuis baru',
                    'body' => "Kuis \"{$quiz->title}\" tersedia di kelas {$course->name}.",
                    'url' => route('quizzes.show', [$course, $quiz]),
                    'kind' => 'quiz',
                ],
                $request->user(),
            );
        }

        return redirect()
            ->route('quizzes.show', [$course, $quiz])
            ->with('success', 'Kuis dibuat. Silakan tambahkan soal.');
    }

    public function show(Request $request, Course $course, Quiz $quiz)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_unless($request->user()->can('view', $quiz), 403);

        $user = $request->user();
        $canManage = $user->can('update', $quiz);

        $data = [
            'course' => $course,
            'quiz' => $quiz,
        ];

        if ($canManage) {
            $data['questions'] = $quiz->questions()->with('options')->get();
            $data['attempts'] = $quiz->attempts()
                ->with('user:id,name,email,nis')
                ->orderByDesc('submitted_at')
                ->paginate(20);
            $data['totalPoints'] = $quiz->questions()->sum('points');
        } else {
            $data['myAttempts'] = $quiz->attempts()
                ->where('user_id', $user->id)
                ->orderByDesc('attempt_no')
                ->get();
            $data['canAttempt'] = $user->can('start', $quiz);
            $data['questionsCount'] = $quiz->questions()->count();
            $data['totalPoints'] = $quiz->questions()->sum('points');
            $data['activeAttempt'] = $quiz->attempts()
                ->where('user_id', $user->id)
                ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
                ->first();
        }

        $data['canManage'] = $canManage;

        return view('quizzes.show', $data);
    }

    public function edit(Course $course, Quiz $quiz)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('update', $quiz), 403);

        return view('quizzes.edit', ['course' => $course, 'quiz' => $quiz]);
    }

    public function update(StoreQuizRequest $request, Course $course, Quiz $quiz)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_unless($request->user()->can('update', $quiz), 403);

        $data = $request->validated();
        $wasPublished = $quiz->isPublished();

        $quiz->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
            'opens_at' => $data['opens_at'] ?? null,
            'closes_at' => $data['closes_at'] ?? null,
            'max_attempts' => $data['max_attempts'],
            'show_results' => $data['show_results'],
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'pass_score' => $data['pass_score'],
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published'
                ? ($data['published_at'] ?? $quiz->published_at ?? now())
                : null,
        ]);

        AuditLogger::log('quiz.update', "Memperbarui kuis: {$quiz->title}", $quiz);

        if (! $wasPublished && $quiz->isPublished()) {
            app(Notifier::class)->send(
                $course->students()->get(),
                [
                    'title' => 'Kuis baru',
                    'body' => "Kuis \"{$quiz->title}\" tersedia di kelas {$course->name}.",
                    'url' => route('quizzes.show', [$course, $quiz]),
                    'kind' => 'quiz',
                ],
                $request->user(),
            );
        }

        return redirect()
            ->route('quizzes.show', [$course, $quiz])
            ->with('success', 'Kuis berhasil diperbarui.');
    }

    public function destroy(Course $course, Quiz $quiz)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('delete', $quiz), 403);

        AuditLogger::log('quiz.delete', "Menghapus kuis: {$quiz->title}", $quiz);
        $quiz->delete();

        return redirect()
            ->route('quizzes.index', $course)
            ->with('success', 'Kuis berhasil dihapus.');
    }
}
