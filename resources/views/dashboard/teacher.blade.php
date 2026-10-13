<x-layouts.app :title="'Dasbor'">
    <x-page-header title="Dasbor Guru" subtitle="Selamat datang kembali, {{ auth()->user()->name }}">
        <x-slot name="actions">
            <a href="{{ route('courses.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Buat Kelas</a>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Kelas Aktif" :value="$stats['courses']" icon="book" />
        <x-stat-card label="Total Murid" :value="$stats['students']" icon="users" />
        <x-stat-card label="Menunggu Penilaian" :value="$stats['pending']" icon="document" :tone="$stats['pending'] > 0 ? 'amber' : 'slate'" />
        <x-stat-card label="Materi Dibuat" :value="$stats['materials']" icon="folder" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card>
                <x-slot name="header">
                    <h2 class="card-title">Kelas saya</h2>
                    <a href="{{ route('courses.index') }}" class="link text-sm">Semua kelas</a>
                </x-slot>
                <div class="divide-y divide-slate-100">
                    @forelse ($courses as $course)
                        <a href="{{ route('courses.show', $course) }}" class="-mx-5 flex items-center justify-between gap-3 rounded-lg px-5 py-3 transition hover:bg-slate-50">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $course->name }}</p>
                                <p class="text-xs text-slate-500">{{ $course->subject?->name }} · {{ $course->academicYear?->name }}</p>
                            </div>
                            <span class="badge-slate badge shrink-0">{{ $course->memberships()->where('role', 'student')->where('status', 'active')->count() }} murid</span>
                        </a>
                    @empty
                        <x-empty title="Belum ada kelas" message="Mulai dengan membuat kelas baru." action-label="Buat Kelas" :action-url="route('courses.create')" />
                    @endforelse
                </div>
            </x-card>

            <x-card class="mt-6">
                <x-slot name="header">
                    <h2 class="card-title">Pengumpulan menunggu penilaian</h2>
                </x-slot>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Murid</th><th>Tugas</th><th>Dikumpulkan</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($pendingGrading as $submission)
                                <tr>
                                    <td class="font-medium text-slate-800">{{ $submission->user?->name }}</td>
                                    <td>
                                        <p class="text-slate-700">{{ $submission->assignment?->title }}</p>
                                        <p class="text-xs text-slate-400">{{ $submission->assignment?->course?->name }}</p>
                                    </td>
                                    <td class="whitespace-nowrap text-slate-500">{{ $submission->submitted_at?->diffForHumans() }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('assignments.submissions', [$submission->assignment?->course_id, $submission->assignment_id]) }}" class="btn btn-secondary btn-sm">Nilai</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty compact title="Tidak ada yang menunggu" message="Semua pengumpulan sudah dinilai." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
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
                            <p class="text-xs text-slate-500">{{ $assignment->course?->name }} · {{ $assignment->due_at->translatedFormat('d M Y, H:i') }}</p>
                        </a>
                    @empty
                        <x-empty compact title="Tidak ada tenggat" />
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <x-slot name="header"><h2 class="card-title">Pengumuman terbaru</h2></x-slot>
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
