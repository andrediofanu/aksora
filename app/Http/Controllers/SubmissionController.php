<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradeSubmissionRequest;
use App\Http\Requests\SubmitAssignmentRequest;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengumpulan & penilaian tugas.
 */
class SubmissionController extends Controller
{
    public function __construct(private AttachmentService $attachments)
    {
    }

    /** Daftar pengumpulan (guru/admin). */
    public function index(Request $request, Course $course, Assignment $assignment)
    {
        abort_if($assignment->course_id !== $course->id, 404);
        abort_unless($request->user()->can('viewSubmissions', $assignment), 403);

        $filter = $request->query('status', 'all');

        $query = $assignment->submissions()->with(['user:id,name,email,nis', 'attachments']);

        if (in_array($filter, ['pending', 'graded', 'late'], true)) {
            if ($filter === 'pending') {
                $query->whereNull('score');
            }
            if ($filter === 'graded') {
                $query->whereNotNull('score');
            }
            if ($filter === 'late') {
                $query->where('is_late', true);
            }
        }

        $students = $course->students()->get();

        $submissions = $query->orderBy('submitted_at')->paginate(25)->withQueryString();
        $submissionKeys = $submissions->getCollection()->pluck('user_id');

        // Murid yang belum mengumpulkan → ditampilkan sebagai "Belum dikumpulkan".
        $missing = $students->whereNotIn('id', $submissionKeys->all())->values();

        return view('assignments.submissions', [
            'course' => $course,
            'assignment' => $assignment,
            'submissions' => $submissions,
            'missing' => $missing,
            'filter' => $filter,
            'gradedCount' => $assignment->submissions()->whereNotNull('score')->count(),
            'submittedCount' => $assignment->submissions()->count(),
            'totalStudents' => $students->count(),
        ]);
    }

    /** Murid mengumpulkan / memperbarui tugas. */
    public function store(SubmitAssignmentRequest $request, Course $course, Assignment $assignment)
    {
        abort_if($assignment->course_id !== $course->id, 404);

        $user = $request->user();

        $existing = $assignment->submissions()->where('user_id', $user->id)->first();

        $isLate = $assignment->due_at !== null && now()->greaterThan($assignment->due_at);

        if ($isLate && ! $assignment->allow_late && $existing) {
            return back()->withErrors(['content' => 'Tenggat telah lewat, pengumpulan tidak dapat diubah.']);
        }

        if ($isLate && ! $assignment->allow_late && ! $existing) {
            return back()->withErrors(['content' => 'Tenggat telah lewat, pengumpulan ditutup.']);
        }

        $submission = DB::transaction(function () use ($request, $assignment, $user, $existing, $isLate) {
            $submission = AssignmentSubmission::updateOrCreate(
                ['assignment_id' => $assignment->id, 'user_id' => $user->id],
                [
                    'content' => $request->validated('content') ?? ($existing?->content),
                    'submitted_at' => now(),
                    'is_late' => $isLate,
                    'status' => AssignmentSubmission::STATUS_SUBMITTED,
                    // Nilai lama dihapus ketika murid mengumpulkan ulang.
                    'score' => null,
                    'feedback' => $existing?->feedback,
                    'graded_at' => null,
                    'graded_by' => null,
                ],
            );

            if ($request->hasFile('file')) {
                // Hapus lampiran lama sebelumnya agar tidak menumpuk.
                foreach ($submission->attachments()->get() as $old) {
                    $this->attachments->delete($old);
                }

                $this->attachments->store($submission, $request->file('file'), $user->id, [
                    'allowed_extensions' => $assignment->allowedExtensions(),
                ]);
            }

            return $submission;
        });

        AuditLogger::log('assignment.submit', "Mengumpulkan tugas: {$assignment->title}", $assignment);

        app(Notifier::class)->send(
            [$assignment->course->teacher],
            [
                'title' => 'Pengumpulan tugas baru',
                'body' => "{$user->name} mengumpulkan \"{$assignment->title}\".",
                'url' => route('assignments.submissions', [$assignment->course_id, $assignment]),
                'kind' => 'submission',
            ],
            $user,
        );

        return back()->with('success', $isLate
            ? 'Tugas berhasil dikumpulkan (terlambat).'
            : 'Tugas berhasil dikumpulkan.');
    }

    /** Hapus pengumpulan (murid sebelum dinilai / guru). */
    public function destroy(Request $request, Course $course, Assignment $assignment, AssignmentSubmission $submission)
    {
        abort_if($assignment->course_id !== $course->id, 404);
        abort_if($submission->assignment_id !== $assignment->id, 404);

        $user = $request->user();

        $allowed = $submission->user_id === $user->id
            ? ($user->can('submit', $assignment) && $submission->score === null)
            : $user->can('grade', $assignment);

        abort_unless($allowed, 403);

        foreach ($submission->attachments()->get() as $attachment) {
            $this->attachments->delete($attachment);
        }

        $submission->delete();

        AuditLogger::log('assignment.submission.delete', "Menghapus pengumpulan tugas {$assignment->title}", $assignment);

        return back()->with('success', 'Pengumpulan berhasil dihapus.');
    }

    /** Guru memberi nilai + umpan balik. */
    public function grade(GradeSubmissionRequest $request, Course $course, Assignment $assignment, AssignmentSubmission $submission)
    {
        abort_if($assignment->course_id !== $course->id, 404);
        abort_if($submission->assignment_id !== $assignment->id, 404);

        $data = $request->validated();

        $submission->update([
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'status' => $data['status'] ?? AssignmentSubmission::STATUS_GRADED,
            'graded_at' => now(),
            'graded_by' => $request->user()->id,
        ]);

        AuditLogger::log('assignment.grade', "Menilai tugas {$assignment->title}: {$data['score']}", $submission);

        app(Notifier::class)->send(
            [$submission->user],
            [
                'title' => 'Nilai diterbitkan',
                'body' => "Nilai tugas \"{$assignment->title}\": {$data['score']} dari {$assignment->max_score}.",
                'url' => route('assignments.show', [$course, $assignment]),
                'kind' => 'grade',
            ],
            $request->user(),
        );

        return back()->with('success', 'Nilai berhasil disimpan.');
    }
}
