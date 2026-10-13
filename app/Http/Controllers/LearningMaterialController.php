<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\MaterialCompletion;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;

class LearningMaterialController extends Controller
{
    public function __construct(private AttachmentService $attachments)
    {
    }

    public function index(Request $request, Course $course)
    {
        abort_unless($request->user()->can('view', $course), 403);

        $user = $request->user();
        $canManage = $user->can('create', [LearningMaterial::class, $course]);

        $query = $course->materials()->withCount('attachments')->orderBy('sort_order')->orderBy('id');

        if (! $canManage) {
            $query->visibleToStudents();
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        $materials = $query->get();

        $completedIds = MaterialCompletion::whereIn('learning_material_id', $materials->pluck('id'))
            ->where('user_id', $user->id)
            ->pluck('learning_material_id')
            ->flip();

        $topics = $materials->pluck('topic')->filter()->unique()->sort()->values();

        return view('materials.index', [
            'course' => $course,
            'materials' => $materials,
            'completedIds' => $completedIds,
            'topics' => $topics,
            'canManage' => $canManage,
            'search' => $search,
        ]);
    }

    public function create(Course $course)
    {
        abort_unless(auth()->user()->can('create', [LearningMaterial::class, $course]), 403);

        return view('materials.create', ['course' => $course]);
    }

    public function store(StoreMaterialRequest $request, Course $course)
    {
        $material = LearningMaterial::create([
            'course_id' => $course->id,
            'title' => $request->validated('title'),
            'topic' => $request->validated('topic'),
            'description' => $request->validated('description'),
            'content' => $request->validated('content'),
            'external_url' => $request->validated('external_url'),
            'sort_order' => $request->validated('sort_order', 0),
            'status' => $request->validated('status'),
            'published_at' => $this->publishedAt($request),
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('file')) {
            $this->attachments->store($material, $request->file('file'), $request->user()->id, [
                'allowed_extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'png', 'jpg', 'jpeg', 'zip'],
            ]);
        }

        AuditLogger::log('material.create', "Membuat materi: {$material->title}", $material);

        if ($material->isPublished()) {
            $this->notifyStudents($course, $material);
        }

        return redirect()
            ->route('materials.show', [$course, $material])
            ->with('success', 'Materi berhasil disimpan.');
    }

    public function show(Request $request, Course $course, LearningMaterial $material)
    {
        abort_if($material->course_id !== $course->id, 404);
        abort_unless($request->user()->can('view', $material), 403);

        $user = $request->user();

        return view('materials.show', [
            'course' => $course,
            'material' => $material->load('attachments', 'creator:id,name,title'),
            'canManage' => $user->can('update', $material),
            'isCompleted' => $material->isCompletedBy($user),
        ]);
    }

    public function edit(Course $course, LearningMaterial $material)
    {
        abort_if($material->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('update', $material), 403);

        return view('materials.edit', ['course' => $course, 'material' => $material]);
    }

    public function update(UpdateMaterialRequest $request, Course $course, LearningMaterial $material)
    {
        abort_if($material->course_id !== $course->id, 404);

        $wasPublished = $material->isPublished();

        $material->update([
            'title' => $request->validated('title'),
            'topic' => $request->validated('topic'),
            'description' => $request->validated('description'),
            'content' => $request->validated('content'),
            'external_url' => $request->validated('external_url'),
            'sort_order' => $request->validated('sort_order', $material->sort_order),
            'status' => $request->validated('status'),
            'published_at' => $this->publishedAt($request),
        ]);

        if ($request->hasFile('file')) {
            $this->attachments->store($material, $request->file('file'), $request->user()->id, [
                'allowed_extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'png', 'jpg', 'jpeg', 'zip'],
            ]);
        }

        AuditLogger::log('material.update', "Memperbarui materi: {$material->title}", $material);

        if (! $wasPublished && $material->isPublished()) {
            $this->notifyStudents($course, $material);
        }

        return redirect()
            ->route('materials.show', [$course, $material])
            ->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Course $course, LearningMaterial $material)
    {
        abort_if($material->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('delete', $material), 403);

        AuditLogger::log('material.delete', "Menghapus materi: {$material->title}", $material);
        $material->delete();

        return redirect()
            ->route('materials.index', $course)
            ->with('success', 'Materi berhasil dihapus.');
    }

    /** Murid menandai materi selesai. */
    public function complete(Request $request, Course $course, LearningMaterial $material)
    {
        abort_if($material->course_id !== $course->id, 404);
        abort_unless($request->user()->can('markComplete', $material), 403);

        MaterialCompletion::firstOrCreate(
            ['learning_material_id' => $material->id, 'user_id' => $request->user()->id],
            ['completed_at' => now()],
        );

        return back()->with('success', 'Materi ditandai selesai.');
    }

    public function uncomplete(Request $request, Course $course, LearningMaterial $material)
    {
        abort_if($material->course_id !== $course->id, 404);
        abort_unless($request->user()->can('markComplete', $material), 403);

        MaterialCompletion::where('learning_material_id', $material->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('success', 'Status selesai dibatalkan.');
    }

    private function publishedAt(StoreMaterialRequest|UpdateMaterialRequest $request): ?\Carbon\Carbon
    {
        $raw = $request->validated('published_at');

        if ($raw) {
            return \Carbon\Carbon::parse($raw);
        }

        // Materi langsung terbit = waktu sekarang; draft/scheduled tanpa jadwal = null.
        return $request->validated('status') === 'published' ? now() : null;
    }

    private function notifyStudents(Course $course, LearningMaterial $material): void
    {
        $students = $course->students()->get();

        if ($students->isEmpty()) {
            return;
        }

        app(Notifier::class)->send($students, [
            'title' => 'Materi baru',
            'body' => "Materi \"{$material->title}\" tersedia di kelas {$course->name}.",
            'url' => route('materials.show', [$course, $material]),
            'kind' => 'material',
        ], $material->creator);
    }
}
