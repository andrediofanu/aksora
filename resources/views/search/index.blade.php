<x-layouts.app :title="'Pencarian'">
    <x-page-header title="Hasil Pencarian" :subtitle="'Kata kunci: "'.$q.'"' />

    <form method="GET" action="{{ route('search.index') }}" class="mb-6 flex max-w-xl gap-2">
        <input type="search" name="q" value="{{ $q }}" required maxlength="100" placeholder="Cari kelas, materi, tugas, kuis…"
               class="input" aria-label="Kata kunci pencarian" autofocus />
        <button type="submit" class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> Cari</button>
    </form>

    @if ($isEmpty)
        <x-card>
            <x-empty icon="search" title="Tidak ada hasil"
                     message="Tidak ditemukan data yang cocok dengan “{{ $q }}”. Coba kata kunci lain atau periksa ejaan." />
        </x-card>
    @else
        <div class="space-y-6">
            @if ($courses->isNotEmpty())
                <x-card>
                    <x-slot name="header"><h2 class="card-title">Kelas ({{ $courses->count() }})</h2></x-slot>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($courses as $course)
                            <li>
                                <a href="{{ route('courses.show', $course) }}" class="flex items-center gap-3 py-3 transition hover:bg-slate-50 -mx-5 px-5 rounded-lg">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><x-icon name="book" class="h-4.5 w-4.5" /></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-slate-800">{{ $course->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $course->subject?->name }} · {{ $course->teacher?->name }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if ($materials->isNotEmpty())
                <x-card>
                    <x-slot name="header"><h2 class="card-title">Materi ({{ $materials->count() }})</h2></x-slot>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($materials as $material)
                            <li>
                                <a href="{{ route('materials.show', [$material->course_id, $material]) }}" class="flex items-center gap-3 py-3 transition hover:bg-slate-50 -mx-5 px-5 rounded-lg">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><x-icon name="folder" class="h-4.5 w-4.5" /></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-slate-800">{{ $material->title }}</span>
                                        <span class="block text-xs text-slate-500">{{ $material->course?->name }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if ($assignments->isNotEmpty())
                <x-card>
                    <x-slot name="header"><h2 class="card-title">Tugas ({{ $assignments->count() }})</h2></x-slot>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($assignments as $assignment)
                            <li>
                                <a href="{{ route('assignments.show', [$assignment->course_id, $assignment]) }}" class="flex items-center gap-3 py-3 transition hover:bg-slate-50 -mx-5 px-5 rounded-lg">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><x-icon name="clipboard" class="h-4.5 w-4.5" /></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-slate-800">{{ $assignment->title }}</span>
                                        <span class="block text-xs text-slate-500">{{ $assignment->course?->name }}{{ $assignment->due_at ? ' · tenggat '.$assignment->due_at->translatedFormat('d M Y') : '' }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if ($quizzes->isNotEmpty())
                <x-card>
                    <x-slot name="header"><h2 class="card-title">Kuis ({{ $quizzes->count() }})</h2></x-slot>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($quizzes as $quiz)
                            <li>
                                <a href="{{ route('quizzes.show', [$quiz->course_id, $quiz]) }}" class="flex items-center gap-3 py-3 transition hover:bg-slate-50 -mx-5 px-5 rounded-lg">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600"><x-icon name="quiz" class="h-4.5 w-4.5" /></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-slate-800">{{ $quiz->title }}</span>
                                        <span class="block text-xs text-slate-500">{{ $quiz->course?->name }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if ($users->isNotEmpty())
                <x-card>
                    <x-slot name="header"><h2 class="card-title">Pengguna ({{ $users->count() }})</h2></x-slot>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($users as $foundUser)
                            <li class="flex items-center gap-3 py-3 -mx-5 px-5">
                                <x-avatar :user="$foundUser" size="sm" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-slate-800">{{ $foundUser->name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $foundUser->email }}</span>
                                </span>
                                <x-badge :label="\App\Support\Role::label($foundUser->role)" :color="\App\Support\Role::badgeColor($foundUser->role)" />
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </div>
    @endif
</x-layouts.app>
