<x-layouts.app :title="'Kelas Saya'">
    <x-page-header title="Kelas Saya" subtitle="Semua kelas yang Anda ikuti beserta materi, tugas, dan anggotanya.">
        <x-slot name="actions">
            @can('create', \App\Models\Course::class)
                <a href="{{ route('courses.create') }}" class="btn btn-secondary">
                    <x-icon name="plus" class="h-4 w-4" /> Buat Kelas
                </a>
            @endcan
            <a href="{{ route('courses.join') }}" class="btn btn-primary">
                <x-icon name="users" class="h-4 w-4" /> Gabung Kelas
            </a>
        </x-slot>
    </x-page-header>

    {{-- Filter pencarian & status --}}
    <form method="GET" action="{{ route('courses.index') }}" class="card mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label class="label" for="filter-q">Cari kelas</label>
            <input type="search" name="q" id="filter-q" value="{{ $filters['q'] }}" class="input"
                   placeholder="Nama kelas atau kode kelas" />
        </div>

        <div class="sm:w-56">
            <label class="label" for="filter-status">Status</label>
            <select name="status" id="filter-status" class="select">
                <option value="">Semua status</option>
                <option value="{{ \App\Models\Course::STATUS_ACTIVE }}" @selected($filters['status'] === \App\Models\Course::STATUS_ACTIVE)>
                    {{ \App\Support\CourseStatus::label(\App\Models\Course::STATUS_ACTIVE) }}
                </option>
                <option value="{{ \App\Models\Course::STATUS_ARCHIVED }}" @selected($filters['status'] === \App\Models\Course::STATUS_ARCHIVED)>
                    {{ \App\Support\CourseStatus::label(\App\Models\Course::STATUS_ARCHIVED) }}
                </option>
            </select>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
                <x-icon name="search" class="h-4 w-4" /> Terapkan
            </button>
            @if ($filters['q'] !== '' || $filters['status'])
                <a href="{{ route('courses.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </div>
    </form>

    {{-- Daftar kelas --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($courses as $course)
            @php($archived = $course->status === \App\Models\Course::STATUS_ARCHIVED)
            <a href="{{ route('courses.show', $course) }}"
               @class(['group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md',
                   'opacity-60 hover:opacity-100' => $archived])>
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <x-icon name="book" class="h-5 w-5" />
                    </span>
                    <x-badge :color="\App\Support\CourseStatus::badgeColor($course->status)"
                             :label="\App\Support\CourseStatus::label($course->status)" />
                </div>

                <h3 class="mt-3 text-sm font-semibold text-slate-800 transition group-hover:text-indigo-600">
                    {{ $course->name }}
                </h3>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $course->subject?->name ?? 'Tanpa mata pelajaran' }}{{ $course->grade_level ? ' · '.$course->grade_level : '' }}
                </p>

                <div class="mt-3 flex flex-col gap-1 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <x-icon name="user" class="h-3.5 w-3.5" /> {{ $course->teacher?->name ?? 'Guru belum ditentukan' }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <x-icon name="calendar" class="h-3.5 w-3.5" /> {{ $course->academicYear?->name ?? 'Tanpa tahun ajaran' }}
                    </span>
                </div>

                <div class="mt-4 flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                    <span class="badge-slate badge">
                        <x-icon name="users" class="h-3.5 w-3.5" /> {{ $course->students_count }} murid
                    </span>
                    <span class="flex items-center gap-1 text-xs font-medium text-indigo-600 opacity-0 transition group-hover:opacity-100">
                        Buka <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                    </span>
                </div>
            </a>
        @empty
            <div class="sm:col-span-2 lg:col-span-3">
                <x-card>
                    @if ($filters['q'] !== '' || $filters['status'])
                        <x-empty icon="search" title="Tidak ada kelas yang cocok"
                                 message="Ubah kata kunci atau filter status, lalu coba lagi."
                                 action-label="Reset filter" :action-url="route('courses.index')" />
                    @elseif (auth()->user()->can('create', \App\Models\Course::class))
                        <x-empty icon="book" title="Belum ada kelas"
                                 message="Anda belum memiliki kelas. Buat kelas baru untuk mulai mengajar."
                                 action-label="Buat Kelas" :action-url="route('courses.create')" />
                    @else
                        <x-empty icon="book" title="Belum ada kelas"
                                 message="Anda belum bergabung ke kelas mana pun. Masukkan kode kelas dari guru Anda untuk mulai belajar."
                                 action-label="Gabung Kelas" :action-url="route('courses.join')" />
                    @endif
                </x-card>
            </div>
        @endforelse
    </div>

    @if ($courses->hasPages())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            {{ $courses->links() }}
        </div>
    @endif
</x-layouts.app>
