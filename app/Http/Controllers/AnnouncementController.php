<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Course;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request, Course $course)
    {
        abort_unless($request->user()->can('view', $course), 403);

        $user = $request->user();
        $canManage = $user->can('create', [Announcement::class, $course]);

        $query = $course->announcements()->with('creator:id,name,title')->orderByDesc('is_pinned')->latest('published_at');

        if (! $canManage) {
            $query->whereNotNull('published_at')->where('published_at', '<=', now());
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        return view('announcements.index', [
            'course' => $course,
            'announcements' => $query->paginate(10)->withQueryString(),
            'canManage' => $canManage,
            'search' => $search,
        ]);
    }

    public function create(Course $course)
    {
        abort_unless(auth()->user()->can('create', [Announcement::class, $course]), 403);

        return view('announcements.create', ['course' => $course]);
    }

    public function store(StoreAnnouncementRequest $request, Course $course)
    {
        $data = $request->validated();

        $announcement = Announcement::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => $data['published_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('announcement.create', "Membuat pengumuman: {$announcement->title}", $announcement);

        if ($announcement->isPublished()) {
            app(Notifier::class)->send(
                $course->students()->get(),
                [
                    'title' => 'Pengumuman baru',
                    'body' => "{$announcement->title} — kelas {$course->name}",
                    'url' => route('announcements.show', [$course, $announcement]),
                    'kind' => 'announcement',
                ],
                $request->user(),
            );
        }

        return redirect()
            ->route('announcements.index', $course)
            ->with('success', 'Pengumuman berhasil diterbitkan.');
    }

    public function show(Course $course, Announcement $announcement)
    {
        abort_if($announcement->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('view', $announcement), 403);

        return view('announcements.show', [
            'course' => $course,
            'announcement' => $announcement->load('creator:id,name,title'),
        ]);
    }

    public function edit(Course $course, Announcement $announcement)
    {
        abort_if($announcement->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('update', $announcement), 403);

        return view('announcements.edit', ['course' => $course, 'announcement' => $announcement]);
    }

    public function update(StoreAnnouncementRequest $request, Course $course, Announcement $announcement)
    {
        abort_if($announcement->course_id !== $course->id, 404);

        $data = $request->validated();

        $announcement->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => $data['published_at'] ?? $announcement->published_at ?? now(),
        ]);

        AuditLogger::log('announcement.update', "Mengubah pengumuman: {$announcement->title}", $announcement);

        return redirect()
            ->route('announcements.show', [$course, $announcement])
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Course $course, Announcement $announcement)
    {
        abort_if($announcement->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('delete', $announcement), 403);

        AuditLogger::log('announcement.delete', "Menghapus pengumuman: {$announcement->title}", $announcement);
        $announcement->delete();

        return redirect()
            ->route('announcements.index', $course)
            ->with('success', 'Pengumuman berhasil dihapus.');
    }
}
