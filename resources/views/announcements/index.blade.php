<x-layouts.app :title="'Pengumuman — '.$course->name">
    <x-page-header :title="'Pengumuman: '.$course->name" subtitle="Informasi resmi dari guru dan sekolah untuk kelas ini.">
        <x-slot name="actions">
            @if ($canManage)
                <a href="{{ route('announcements.create', $course) }}" class="btn btn-primary">
                    <x-icon name="plus" class="h-4 w-4" /> Buat Pengumuman
                </a>
            @endif
        </x-slot>
    </x-page-header>

    <div class="mb-4"><x-course-nav :course="$course" active="announcements" /></div>

    <x-card>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <form method="GET" action="{{ route('announcements.index', $course) }}" class="flex flex-1 gap-2" role="search">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari judul atau isi pengumuman…"
                       class="input" aria-label="Cari pengumuman" />
                <button type="submit" class="btn btn-secondary shrink-0">
                    <x-icon name="search" class="h-4 w-4" /> Cari
                </button>
            </form>
            @if ($search)
                <a href="{{ route('announcements.index', $course) }}" class="link text-sm">Reset pencarian</a>
            @endif
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($announcements as $announcement)
                <a href="{{ route('announcements.show', [$course, $announcement]) }}"
                   class="-mx-5 flex items-start justify-between gap-3 px-5 py-4 transition hover:bg-slate-50">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($announcement->is_pinned)
                                <x-badge color="indigo" label="Disematkan" icon="pin" />
                            @endif
                            @if ($announcement->published_at === null)
                                <x-badge color="slate" label="Terbit langsung" />
                            @elseif ($announcement->published_at->isFuture())
                                <x-badge color="amber" label="Terjadwal" icon="clock" />
                            @endif
                        </div>
                        <p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ $announcement->title }}</p>
                        <p class="mt-0.5 truncate text-xs text-slate-500">
                            {{ $announcement->creator?->name ?? '—' }}
                            @if ($announcement->published_at)
                                · {{ $announcement->published_at->translatedFormat('d F Y, H:i') }}
                            @endif
                        </p>
                    </div>
                    <x-icon name="chevron-right" class="mt-1 h-5 w-5 shrink-0 text-slate-300" />
                </a>
            @empty
                <x-empty icon="megaphone"
                         :title="$search ? 'Pengumuman tidak ditemukan' : 'Belum ada pengumuman'"
                         :message="$search ? 'Coba kata kunci lain.' : 'Pengumuman yang diterbitkan guru akan muncul di sini.'" />
            @endforelse
        </div>

        @if ($announcements->hasPages())
            <div class="mt-4">{{ $announcements->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
