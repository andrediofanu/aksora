<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGradeWeightsRequest;
use App\Models\Course;
use App\Services\AuditLogger;
use App\Services\GradeCalculator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Buku nilai kelas + rumus nilai akhir (lihat GradeCalculator).
 */
class GradebookController extends Controller
{
    public function __construct(private GradeCalculator $calculator)
    {
    }

    public function index(Request $request, Course $course)
    {
        abort_unless($request->user()->can('viewGradebook', $course), 403);

        $search = trim((string) $request->query('q'));

        $rows = $this->calculator->gradebook($course);

        if ($search !== '') {
            $rows = $rows->filter(
                fn (array $row) => str_contains(mb_strtolower($row['student']->name), mb_strtolower($search))
                    || str_contains(mb_strtolower((string) $row['student']->nis), mb_strtolower($search))
            )->values();
        }

        $assignments = $course->assignments()->where('status', 'published')->orderBy('due_at')->get();
        $quizzes = $course->quizzes()->where('status', 'published')->orderBy('created_at')->get();

        $submissions = \App\Models\AssignmentSubmission::whereIn('assignment_id', $assignments->pluck('id'))
            ->get(['assignment_id', 'user_id', 'score', 'status'])
            ->groupBy('assignment_id');

        $attempts = \App\Models\QuizAttempt::whereIn('quiz_id', $quizzes->pluck('id'))
            ->whereIn('status', ['submitted', 'graded'])
            ->whereNotNull('score')
            ->orderByDesc('score')
            ->get(['quiz_id', 'user_id', 'score'])
            ->groupBy('quiz_id');

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows,
            $rows->count(),
            50,
            $request->query('page', 1),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('grades.index', [
            'course' => $course,
            'rows' => $paginator,
            'weights' => $this->calculator->weights($course),
            'assignments' => $assignments,
            'quizzes' => $quizzes,
            'submissions' => $submissions,
            'attempts' => $attempts,
            'search' => $search,
        ]);
    }

    public function updateWeights(UpdateGradeWeightsRequest $request, Course $course)
    {
        $data = $request->validated();

        \App\Models\GradeWeight::updateOrCreate(
            ['course_id' => $course->id, 'category' => 'assignment'],
            ['weight' => (float) $data['assignment']],
        );
        \App\Models\GradeWeight::updateOrCreate(
            ['course_id' => $course->id, 'category' => 'quiz'],
            ['weight' => (float) $data['quiz']],
        );

        AuditLogger::log('grade.weight.update', "Memperbarui bobot nilai kelas {$course->name}", $course);

        return back()->with('success', 'Bobot nilai berhasil disimpan. Total 100%.');
    }

    public function export(Request $request, Course $course): StreamedResponse
    {
        abort_unless($request->user()->can('viewGradebook', $course), 403);

        $filename = 'nilai-'.$course->code.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($course) {
            $handle = fopen('php://output', 'w');

            // BOM agar dibuka dengan benar di Excel (UTF-8).
            fwrite($handle, "\xEF\xBB\xBF");

            $header = ['NIS', 'Nama', 'Rata-rata Tugas (%)', 'Rata-rata Kuis (%)', 'Nilai Akhir'];
            fputcsv($handle, $header, ';');

            foreach ($this->calculator->gradebook($course) as $row) {
                fputcsv($handle, [
                    $row['student']->nis,
                    $row['student']->name,
                    $row['assignment']['average'] !== null
                        ? round($row['assignment']['average'] * 100, 2) : '',
                    $row['quiz']['average'] !== null
                        ? round($row['quiz']['average'] * 100, 2) : '',
                    $row['final'] ?? 'Belum dinilai',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** Nilai milik murid sendiri (lintas kelas). */
    public function mine(Request $request)
    {
        $user = $request->user();
        $courses = $user->enrolledCourses()->with(['subject:id,name', 'teacher:id,name,title'])->get();

        $rows = $courses->map(fn (Course $course) => [
            'course' => $course,
            'grade' => $this->calculator->forStudent($course, $user),
        ]);

        $overall = $rows
            ->pluck('grade.final')
            ->filter()
            ->count() > 0
            ? round($rows->pluck('grade.final')->filter()->avg(), 2)
            : null;

        return view('grades.mine', [
            'rows' => $rows,
            'overall' => $overall,
        ]);
    }
}
