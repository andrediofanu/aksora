@php($user = auth()->user())
<x-layouts.app :title="$announcement->title">
    <x-page-header :title="$announcement->title" :subtitle="'Kelas: '.$course->name">
        <x-slot name="actions">
            @if ($user->can('update', $announcement))
                <a href="{{ route('announcements.edit', [$course, $announcement]) }}" class="btn btn-secondary">
                    <x-icon name="edit" class="h-4 w-4" /> Ubah
                </a>
            @endif
            @if ($user->can('delete', $announcement))
                <x-confirm-delete label="Hapus" icon="trash"
                                  title="Hapus pengumuman ini?"
                                  message="Pengumuman “{{ $announcement->title }}” akan dihapus permanen dan tidak lagi terlihat oleh anggota kelas."
                                  :url="route('announcements.destroy', [$course, $announcement])" />
            @endif
        </x-slot>
    </x-page-header>

    <div class="mb-4"><x-course-nav :course="$course" active="announcements" /></div>

    <div class="max-w-3xl space-y-4">
        <x-card>
            <div class="flex flex-wrap items-center gap-2">
                @if ($announcement->is_pinned)
                    <x-badge color="indigo" label="Disematkan" icon="pin" />
                @endif
                @if ($announcement->published_at === null)
                    <x-badge color="green" label="Sudah terbit" icon="check-circle" />
                @elseif ($announcement->published_at->isFuture())
                    <x-badge color="amber" label="Terjadwal" icon="clock" />
                @else
                    <x-badge color="green" label="Sudah terbit" icon="check-circle" />
                @endif
            </div>

            <div class="mt-4 flex items-center gap-3">
                <x-avatar :user="$announcement->creator" size="sm" />
                <div>
                    <p class="text-sm font-medium text-slate-700">{{ $announcement->creator?->name ?? '—' }}</p>
                    <p class="text-xs text-slate-500">
                        @if ($announcement->published_at)
                            Dipublikasikan {{ $announcement->published_at->translatedFormat('d F Y, H:i') }}
                        @else
                            Diterbitkan langsung
                        @endif
                    </p>
                </div>
            </div>

            <div class="divider my-4"></div>

            <div class="prose-content">
                {!! nl2br(e($announcement->content)) !!}
            </div>
        </x-card>

        <a href="{{ route('announcements.index', $course) }}" class="link text-sm">
            ← Kembali ke daftar pengumuman
        </a>
    </div>
</x-layouts.app>
