<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\GradeCalculator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan global untuk admin.
 */
class ReportController extends Controller
{
    public function __construct()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function index(Request $request, GradeCalculator $calculator)
    {
        $courses = Course::with(['teacher:id,name', 'subject:id,name'])
            ->withCount([
                'memberships as student_count' => fn ($q) => $q->where('role', 'student')->where('status', 'active'),
            ])
            ->orderBy('name')
            ->paginate(15);

        $summary = [
            'total_students' => User::whereNull('deleted_at')->where('role', 'student')->count(),
            'total_teachers' => User::whereNull('deleted_at')->where('role', 'teacher')->count(),
            'total_courses' => Course::count(),
            'total_assignments' => Assignment::count(),
            'graded_submissions' => \App\Models\AssignmentSubmission::whereNotNull('score')->count(),
            'pending_submissions' => \App\Models\AssignmentSubmission::whereNull('score')->count(),
            'quiz_attempts' => QuizAttempt::whereIn('status', ['submitted', 'graded'])->count(),
            'avg_score' => round((float) \App\Models\AssignmentSubmission::whereNotNull('score')->avg('score'), 2),
        ];

        return view('admin.reports', [
            'courses' => $courses,
            'summary' => $summary,
        ]);
    }

    /** Unduh rekap nilai seluruh kelas (CSV). */
    public function exportAll(GradeCalculator $calculator): StreamedResponse
    {
        $filename = 'rekap-nilai-semua-kelas-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($calculator) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Kode Kelas', 'Nama Kelas', 'Guru', 'NIS', 'Murid', 'Tugas (%)', 'Kuis (%)', 'Nilai Akhir'], ';');

            foreach (Course::with('teacher:id,name')->orderBy('name')->get() as $course) {
                foreach ($calculator->gradebook($course) as $row) {
                    fputcsv($handle, [
                        $course->code,
                        $course->name,
                        $course->teacher?->name,
                        $row['student']->nis,
                        $row['student']->name,
                        $row['assignment']['average'] !== null ? round($row['assignment']['average'] * 100, 2) : '',
                        $row['quiz']['average'] !== null ? round($row['quiz']['average'] * 100, 2) : '',
                        $row['final'] ?? 'Belum dinilai',
                    ], ';');
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
