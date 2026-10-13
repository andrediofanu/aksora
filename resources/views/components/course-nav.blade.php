@props(['course', 'active' => 'overview'])

@php
    $items = [
        'overview' => ['Ringkasan', route('courses.show', $course)],
        'materials' => ['Materi', route('materials.index', $course)],
        'assignments' => ['Tugas', route('assignments.index', $course)],
        'quizzes' => ['Kuis', route('quizzes.index', $course)],
        'grades' => ['Nilai', route('grades.index', $course)],
        'announcements' => ['Pengumuman', route('announcements.index', $course)],
        'discussions' => ['Diskusi', route('discussions.index', $course)],
        'members' => ['Anggota', route('course.members', $course)],
    ];
@endphp

<div class="border-b border-slate-200">
    <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Navigasi kelas">
        @foreach ($items as $key => [$label, $url])
            @continue(! in_array($key, $tabs ?? array_keys($items), true))
            <a href="{{ $url }}"
               @class(['whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition',
                   'border-indigo-600 text-indigo-600' => $active === $key,
                   'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $active !== $key])
               @if ($active === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
</div>
