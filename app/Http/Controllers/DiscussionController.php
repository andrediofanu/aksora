<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\StoreThreadRequest;
use App\Models\Course;
use App\Models\DiscussionPost;
use App\Models\DiscussionThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscussionController extends Controller
{
    public function index(Request $request, Course $course)
    {
        abort_unless($request->user()->can('view', $course), 403);

        $query = $course->discussionThreads()
            ->with(['author:id,name,title,avatar_path', 'posts'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_post_at');

        if ($search = trim((string) $request->query('q'))) {
            $query->where('title', 'like', "%{$search}%");
        }

        $threads = $query->paginate(15)->withQueryString();

        // Hitung jumlah postingan dalam satu query agar tidak N+1.
        $postCounts = DiscussionPost::whereIn('thread_id', $threads->pluck('id'))
            ->groupBy('thread_id')
            ->selectRaw('thread_id, COUNT(*) as total')
            ->pluck('total', 'thread_id');

        return view('discussions.index', [
            'course' => $course,
            'threads' => $threads,
            'postCounts' => $postCounts,
            'search' => $search,
            'canModerate' => $request->user()->can('moderate', $course),
        ]);
    }

    public function create(Course $course)
    {
        abort_unless(auth()->user()->can('createThread', [DiscussionThread::class, $course]), 403);

        return view('discussions.create', ['course' => $course]);
    }

    public function store(StoreThreadRequest $request, Course $course)
    {
        $data = $request->validated();

        $thread = DB::transaction(function () use ($data, $course, $request) {
            $thread = DiscussionThread::create([
                'course_id' => $course->id,
                'user_id' => $request->user()->id,
                'title' => $data['title'],
                'last_post_at' => now(),
            ]);

            DiscussionPost::create([
                'thread_id' => $thread->id,
                'user_id' => $request->user()->id,
                'body' => $data['body'],
            ]);

            return $thread;
        });

        return redirect()
            ->route('discussions.show', [$course, $thread])
            ->with('success', 'Diskusi berhasil dibuat.');
    }

    public function show(Request $request, Course $course, DiscussionThread $thread)
    {
        abort_if($thread->course_id !== $course->id, 404);
        abort_unless($request->user()->can('view', $thread), 403);

        $posts = $thread->posts()
            ->with(['author:id,name,title,avatar_path', 'parent:id,user_id,body'])
            ->orderBy('created_at')
            ->paginate(20);

        return view('discussions.show', [
            'course' => $course,
            'thread' => $thread,
            'posts' => $posts,
            'canReply' => $request->user()->can('reply', $thread),
            'canModerate' => $request->user()->can('moderate', $course),
        ]);
    }

    public function storePost(StorePostRequest $request, Course $course, DiscussionThread $thread)
    {
        abort_if($thread->course_id !== $course->id, 404);

        $data = $request->validated();

        $parentId = $data['parent_id'] ?? null;
        if ($parentId) {
            $parent = DiscussionPost::where('thread_id', $thread->id)->find($parentId);
            abort_unless($parent, 404);
            $parentId = $parent->id;
        }

        DiscussionPost::create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'body' => $data['body'],
        ]);

        $thread->update(['last_post_at' => now()]);

        return back()->with('success', 'Balasan terkirim.');
    }

    public function destroyThread(Course $course, DiscussionThread $thread)
    {
        abort_if($thread->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('deleteThread', $thread), 403);

        $thread->delete();

        return redirect()
            ->route('discussions.index', $course)
            ->with('success', 'Diskusi berhasil dihapus.');
    }

    public function destroyPost(Course $course, DiscussionThread $thread, DiscussionPost $post)
    {
        abort_if($thread->course_id !== $course->id, 404);
        abort_if($post->thread_id !== $thread->id, 404);
        abort_unless(auth()->user()->can('deletePost', $post), 403);

        if ($thread->posts()->count() <= 1) {
            $thread->delete();

            return redirect()
                ->route('discussions.index', $course)
                ->with('success', 'Diskusi dihapus bersama satu-satunya balasannya.');
        }

        $post->update(['is_removed' => true, 'body' => '—']);

        return back()->with('success', 'Balasan disembunyikan.');
    }

    /** Kunci/buka diskusi (guru/admin). */
    public function toggleLock(Course $course, DiscussionThread $thread)
    {
        abort_if($thread->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('moderate', $course), 403);

        $thread->update(['is_locked' => ! $thread->is_locked]);

        return back()->with('success', $thread->is_locked
            ? 'Diskusi dikunci.'
            : 'Diskusi dibuka kembali.');
    }

    public function togglePin(Course $course, DiscussionThread $thread)
    {
        abort_if($thread->course_id !== $course->id, 404);
        abort_unless(auth()->user()->can('moderate', $course), 403);

        $thread->update(['is_pinned' => ! $thread->is_pinned]);

        return back()->with('success', $thread->is_pinned
            ? 'Diskusi disematkan.'
            : 'Sematan dilepas.');
    }
}
