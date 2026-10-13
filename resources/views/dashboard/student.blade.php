<x-layouts.app :title="'Dasbor'">
    <x-page-header title="Dasbor Murid" subtitle="Selamat belajar, {{ auth()->user()->name }}!">

        <x-slot name="actions">
            <a href="{{ route('grades.mine') }}" class="btn btn-secondary"><x-icon name="chart" class="h-4 w-4" /> Nilai Saya</a>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Kelas Diikuti" :value="$stats['courses']" icon="book" />
        <x-stat-card label="Tugas Aktif" :value="$stats['todo']" icon="document" :tone="$stats['todo'] > 0 ? 'amber' : 'slate'" />
        <x-stat-card label="Tenggat Terdekat" :value="$stats['upcoming']" icon="clock" :tone="$stats['upcoming'] > 0 ? 'rose' : 'slate'" />
        <x-stat-card label="Pengumuman Baru" :value="$stats['announcements']" icon="megaphone" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <x-slot name="header">
                    <h2 class="card-title">Yang perlu dikerjakan</h2>
                    <a href="{{ route('courses.index') }}" class="link text-sm">Semua kelas</a>
                </x-slot>
                <div class="divide-y divide-slate-100">
                    @forelse ($todo as $assignment)
                        @php($submission = $assignment->submissions->first())
                        <a href="{{ route('assignments.show', [$assignment->course_id, $assignment]) }}"
                           class="-mx-5 flex items-center justify-between gap-3 rounded-lg px-5 py-3 transition hover:bg-slate-50">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $assignment->title }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $assignment->course?->name }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if ($submission?->status === \App\Models\AssignmentSubmission::STATUS_REVISION)
                                    <span class="badge-amber badge">Revisi</span>
                                @elseif ($submission)
                                    <span class="badge-green badge">Terkumpul</span>
                                @elseif ($assignment->due_at?->isPast())
                                    <span class="badge-red badge">Lewat</span>
                                @elseif ($assignment->due_at)
                                    <span class="badge-slate badge">{{ $assignment->due_at->diffForHumans() }}</span>
                                @else
                                    <span class="badge-slate badge">Tanpa tenggat</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <x-empty title="Tidak ada tugas aktif" message="Semua tugas sudah selesai. Kerja bagus!" />
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <x-slot name="header"><h2 class="card-title">Progres kelas</h2></x-slot>
                <div class="space-y-4">
                    @forelse ($courseProgress as $row)
                        <div>
                            <div class="flex items-center justify-between">
                                <a href="{{ route('courses.show', $row['course']) }}" class="text-sm font-medium text-slate-700 hover:text-indigo-600">
                                    {{ $row['course']->name }}
                                </a>
                                <span class="text-xs text-slate-500">
                                    @if ($row['grade'] !== null)
                                        Nilai: <strong class="text-slate-700">{{ number_format($row['grade'], 1) }}</strong>
                                    @else
                                        Nilai: —
                                    @endif
                                </span>
                            </div>
                            <div class="mt-1.5 flex items-center gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $row['percent'] }}%"></div>
                                </div>
                                <span class="w-24 shrink-0 text-right text-xs text-slate-500">{{ $row['done'] }}/{{ $row['total'] }} materi</span>
                            </div>
                        </div>
                    @empty
                        <x-empty compact title="Belum bergabung kelas" message="Gabung kelas dengan kode dari guru Anda." action-label="Gabung Kelas" :action-url="route('courses.join')" />
                    @endforelse
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <x-slot name="header"><h2 class="card-title">Tenggat terdekat</h2></x-slot>
                <div class="space-y-3">
                    @forelse ($upcoming as $assignment)
                        <a href="{{ route('assignments.show', [$assignment->course_id, $assignment]) }}" class="block rounded-lg p-2 transition hover:bg-slate-50">
                            <p class="text-sm font-medium text-slate-700">{{ $assignment->title }}</p>
                            <p class="text-xs text-rose-600">{{ $assignment->course?->name }} · {{ $assignment->due_at->translatedFormat('d M, H:i') }}</p>
                        </a>
                    @empty
                        <x-empty compact title="Tidak ada tenggat" />
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <x-slot name="header"><h2 class="card-title">Agenda kalender</h2></x-slot>
                <div class="space-y-3">
                    @forelse ($events as $event)
                        <div class="rounded-lg p-2">
                            <p class="text-sm font-medium text-slate-700">{{ $event->title }}</p>
                            <p class="text-xs text-slate-500">{{ $event->course?->name }} · {{ $event->starts_at->translatedFormat('d M, H:i') }}</p>
                        </div>
                    @empty
                        <x-empty compact title="Tidak ada agenda" />
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <x-slot name="header"><h2 class="card-title">Pengumuman</h2></x-slot>
                <div class="space-y-3">
                    @forelse ($announcements as $announcement)
                        <a href="{{ route('announcements.show', [$announcement->course_id, $announcement]) }}" class="block rounded-lg p-2 transition hover:bg-slate-50">
                            <p class="text-sm font-medium text-slate-700">{{ $announcement->title }}</p>
                            <p class="text-xs text-slate-500">{{ $announcement->course?->name }} · {{ $announcement->published_at?->diffForHumans() }}</p>
                        </a>
                    @empty
                        <x-empty compact title="Belum ada pengumuman" />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.app>
