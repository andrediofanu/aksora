<x-layouts.app :title="'Diskusi — '.$course->name">
    <x-page-header title="Diskusi" :subtitle="'Tempat bertanya dan berdiskusi untuk kelas: '.$course->name">
        <x-slot name="actions">
            <a href="{{ route('discussions.create', $course) }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Buat Diskusi
            </a>
        </x-slot>
    </x-page-header>

    <div class="mb-4"><x-course-nav :course="$course" active="discussions" /></div>

    <x-card>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <form method="GET" action="{{ route('discussions.index', $course) }}" class="flex flex-1 gap-2" role="search">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari judul diskusi…"
                       class="input" aria-label="Cari diskusi" />
                <button type="submit" class="btn btn-secondary shrink-0">
                    <x-icon name="search" class="h-4 w-4" /> Cari
                </button>
            </form>
            @if ($search)
                <a href="{{ route('discussions.index', $course) }}" class="link text-sm">Reset pencarian</a>
            @endif
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($threads as $thread)
                @php($replies = max(($postCounts[$thread->id] ?? 0) - 1, 0))
                <div class="-mx-5 flex items-start justify-between gap-3 px-5 py-4 transition hover:bg-slate-50">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <x-avatar :user="$thread->author" size="sm" class="mt-0.5" />
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($thread->is_pinned)
                                    <x-badge color="indigo" label="Disematkan" icon="pin" />
                                @endif
                                @if ($thread->is_locked)
                                    <x-badge color="red" label="Terkunci" icon="lock" />
                                @endif
                            </div>
                            <a href="{{ route('discussions.show', [$course, $thread]) }}"
                               class="mt-1 block truncate text-sm font-semibold text-slate-800 hover:text-indigo-600">
                                {{ $thread->title }}
                            </a>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ $thread->author?->name ?? '—' }}
                                @if ($thread->last_post_at)
                                    · aktivitas terakhir {{ $thread->last_post_at->diffForHumans() }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="badge-slate badge shrink-0">
                        <x-icon name="chat" class="h-3.5 w-3.5" />
                        {{ $replies }} balasan
                    </span>
                </div>
            @empty
                <x-empty icon="chat"
                         :title="$search ? 'Diskusi tidak ditemukan' : 'Belum ada diskusi'"
                         :message="$search ? 'Coba kata kunci lain.' : 'Jadilah yang pertama memulai percakapan di kelas ini.'"
                         action-label="Buat Diskusi" :action-url="route('discussions.create', $course)" />
            @endforelse
        </div>

        @if ($threads->hasPages())
            <div class="mt-4">{{ $threads->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
