<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(private AttachmentService $attachments)
    {
    }

    public function index(Request $request, Course $course)
    {
        abort_unless($request->user()->can('view', $course), 403);

        $user = $request->user();
        $canManage = $user->can('create', [Assignment::class, $course]);

        $query = $course->assignments()->orderByDesc('created_at');

        if (! $canManage) {
            $query->visibleToStudents();
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['open', 'overdue', 'graded'], true)) {
                if ($status === 'open') {
                    $query->where(function ($q) {
                        $q->whereNull('due_at')->orWhere('due_at', '>=', now());
                    });
                }
                if ($status === 'overdue') {
                    $query->whereNotNull('due_at')->where('due_at', '<', now());
                }
            }
        }

        $assignments = $query->withCount('submissions')->get();

        if (! $canManage) {
            $mySubmissions = AssignmentSubmission::whereIn('assignment_id', $assignments->pluck('id'))
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('assignment_id');
        } else {
            $mySubmissions = collect();
        }

        return view('assignments.index', [
            'course' => $course,
            'assignments' => $assignments,
            'mySubmissions' => $mySubmissions,
            'canManage' => $canManage,
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function create(Course $course)
    {
        abort_unless(auth()->user()->can('create', [Assignment::class, $course]), 403);

        return view('assignments.create', ['course' => $course]);
    }

    public function store(StoreAssignmentRequest $request, Course $course)
    {
        $data = $request->validated();

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'submission_type' => $data['submission_type'],
            'due_at' => isset($data['due_at']) ? $data['due_at'] : null,
            'allow_late' => $request->boolean('allow_late'),
            'max_score' => $data['max_score'],
            'weight' => $data['weight'],
            'rubric' => $data['rubric'] ?? null,
            'allowed_extensions' => $data['allowed_extensions'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published'
                ? ($data['published_at'] ?? now())
                : null,
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('file')) {
            $this->attachments->store($assignment, $request->file('file'), $request->user()->id, [
                'allowed_extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'png', 'jpg', 'jpeg', 'zip'],
            ]);
        }

        AuditLogger::log('assignment.create', "Membuat tugas: {$assignment->title}", $assignment);

        if ($assignment->isPublished()) {
            app(Notifier::class)->send(
                $course->students()->get(),
                [
                    'title' => 'Tugas baru',
                    'body' => "Tugas \"{$assignment->title}\" tersedia di kelas {$course->name}.",
                    'url' => route('assignments.show', [$course, $assignment]),
                    'kind' => 'assignment',
                ],
                $request->user(),
            );
        }

        return redirect()
            ->route('assignments.show', [$course, $assignment])
            ->with('success', 'Tugas berhasil dibuat.');
    }

    public function show(Request $request, Course $course, Assignment $assignment)
    {
        abort_if($assignment->course_id !== $course->id, 404);
        abort_unless($request->user()->can('view', $assignment), 403);

        $user = $request->user();
        $canManage = $user->can('viewSubmissions', $assignment);

        $data = [
            'course' => $course,
            'assignment' => $assignment->load('attachments', 'creator:id,name,title'),
            'canManage' => $canManage,
        ];

        if ($canManage) {
            $data['submissionCount'] = $assignment->submissions()->count();
            $data['gradedCount'] = $assignment->submissions()->whereNotNull('score')->count();
        } else {
            $data['mySubmission'] = $assignment->submissions()
                ->where('user_id', $user->id)
                ->with('attachments')
                ->first();
            $data['canSubmit'] = $user->can('submit', $assignment);
        }

        return view('assignments.show', $data);
    }

    public function edit(Course $course, Assignment $assignment)
    {
        abort_if($assignment->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('update', $assignment), 403);

        return view('assignments.edit', ['course' => $course, 'assignment' => $assignment]);
    }

    public function update(UpdateAssignmentRequest $request, Course $course, Assignment $assignment)
    {
        abort_if($assignment->course_id !== $course->id, 404);

        $data = $request->validated();
        $wasPublished = $assignment->isPublished();

        $assignment->update([
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'submission_type' => $data['submission_type'],
            'due_at' => $data['due_at'] ?? null,
            'allow_late' => $request->boolean('allow_late'),
            'max_score' => $data['max_score'],
            'weight' => $data['weight'],
            'rubric' => $data['rubric'] ?? null,
            'allowed_extensions' => $data['allowed_extensions'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published'
                ? ($data['published_at'] ?? $assignment->published_at ?? now())
                : null,
        ]);

        if ($request->hasFile('file')) {
            $this->attachments->store($assignment, $request->file('file'), $request->user()->id, [
                'allowed_extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'png', 'jpg', 'jpeg', 'zip'],
            ]);
        }

        AuditLogger::log('assignment.update', "Memperbarui tugas: {$assignment->title}", $assignment);

        if (! $wasPublished && $assignment->isPublished()) {
            app(Notifier::class)->send(
                $course->students()->get(),
                [
                    'title' => 'Tugas baru',
                    'body' => "Tugas \"{$assignment->title}\" tersedia di kelas {$course->name}.",
                    'url' => route('assignments.show', [$course, $assignment]),
                    'kind' => 'assignment',
                ],
                $request->user(),
            );
        } else {
            app(Notifier::class)->send(
                $course->students()->get(),
                [
                    'title' => 'Tugas diperbarui',
                    'body' => "Tugas \"{$assignment->title}\" diubah. Periksa tenggat terbaru.",
                    'url' => route('assignments.show', [$course, $assignment]),
                    'kind' => 'assignment',
                ],
                $request->user(),
            );
        }

        return redirect()
            ->route('assignments.show', [$course, $assignment])
            ->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Course $course, Assignment $assignment)
    {
        abort_if($assignment->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('delete', $assignment), 403);

        AuditLogger::log('assignment.delete', "Menghapus tugas: {$assignment->title}", $assignment);
        $assignment->delete();

        return redirect()
            ->route('assignments.index', $course)
            ->with('success', 'Tugas berhasil dihapus.');
    }
}
