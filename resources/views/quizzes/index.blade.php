<x-layouts.app :title="'Kuis — '.$course->name">
    <x-page-header :title="'Kuis — '.$course->name" :subtitle="$course->subject?->name ?? 'Daftar kuis di kelas ini'">
        <x-slot name="actions">
            @if ($canManage)
                <a href="{{ route('quizzes.create', $course) }}" class="btn btn-primary">
                    <x-icon name="plus" class="h-4 w-4" /> Buat Kuis
                </a>
            @endif
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="quizzes" />

    <div class="mt-6 space-y-4">
        @forelse ($quizzes as $quiz)
            @php
                $attempts = $canManage ? collect() : $myAttempts->get($quiz->id, collect());
                $latest = $attempts->first();
                $active = $attempts->first(
                    fn ($a) => $a->status === \App\Models\QuizAttempt::STATUS_IN_PROGRESS
                );
                $used = $attempts->count();
                $remaining = max(0, $quiz->max_attempts - $used);
                $questionCount = $quiz->questions()->count();
                $points = (float) $quiz->questions()->sum('points');
            @endphp

            <div class="card">
                <div class="card-body flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('quizzes.show', [$course, $quiz]) }}"
                               class="text-sm font-semibold text-slate-800 hover:text-indigo-600">
                                {{ $quiz->title }}
                            </a>
                            @if ($quiz->status === \App\Models\Quiz::STATUS_PUBLISHED)
                                <x-badge color="green" label="Terbit" />
                            @else
                                <x-badge color="amber" label="Draf" />
                            @endif
                            @if ($quiz->isPublished() && $quiz->isUpcoming())
                                <x-badge color="slate" label="Akan datang" />
                            @elseif ($quiz->hasClosed())
                                <x-badge color="red" label="Ditutup" />
                            @endif
                        </div>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ $questionCount }} soal · {{ rtrim(rtrim(number_format($points, 2), '0'), '.') }} poin
                            · {{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' menit' : 'Tanpa batas waktu' }}
                            · Maks. {{ $quiz->max_attempts }} percobaan
                        </p>

                        @if ($quiz->opens_at || $quiz->closes_at)
                            <p class="mt-0.5 text-xs text-slate-400">
                                Dibuka:
                                {{ $quiz->opens_at?->translatedFormat('d M Y, H:i') ?? 'kapan saja' }}
                                —
                                {{ $quiz->closes_at?->translatedFormat('d M Y, H:i') ?? 'tanpa batas' }}
                            </p>
                        @endif

                        @if (! $canManage && $quiz->max_attempts > 1)
                            <p class="mt-0.5 text-xs text-slate-400">Sisa percobaan: {{ $remaining }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if ($canManage)
                            <a href="{{ route('quizzes.show', [$course, $quiz]) }}" class="btn btn-secondary btn-sm">
                                <x-icon name="clipboard" class="h-4 w-4" /> Kelola Soal
                            </a>
                        @else
                            @if ($active)
                                <a href="{{ route('quizzes.take', [$course, $quiz, $active]) }}" class="btn btn-primary btn-sm">
                                    <x-icon name="chevron-right" class="h-4 w-4" /> Lanjutkan
                                </a>
                            @elseif ($quiz->canAttempt(auth()->user()))
                                <form action="{{ route('quizzes.start', [$course, $quiz]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <x-icon name="chevron-right" class="h-4 w-4" /> Mulai
                                    </button>
                                </form>
                            @elseif ($remaining <= 0 && $quiz->isOpenNow())
                                <span class="badge-slate badge">Percobaan habis</span>
                            @elseif (! $quiz->isOpenNow() && $quiz->isUpcoming())
                                <span class="badge-slate badge">Belum dibuka</span>
                            @elseif (! $quiz->isOpenNow())
                                <span class="badge-red badge">Sudah ditutup</span>
                            @endif

                            @if ($latest && $latest->status !== \App\Models\QuizAttempt::STATUS_IN_PROGRESS && $quiz->canShowResults($latest))
                                <a href="{{ route('quizzes.results', [$course, $quiz, $latest]) }}" class="btn btn-secondary btn-sm">
                                    <x-icon name="eye" class="h-4 w-4" /> Lihat Hasil
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <x-card>
                <x-empty
                    icon="quiz"
                    title="Belum ada kuis"
                    :message="$canManage ? 'Buat kuis pertama untuk kelas ini.' : 'Belum ada kuis yang tersedia untuk Anda.'"
                    :action-label="$canManage ? 'Buat Kuis' : null"
                    :action-url="$canManage ? route('quizzes.create', $course) : null"
                />
            </x-card>
        @endforelse
    </div>
</x-layouts.app>
