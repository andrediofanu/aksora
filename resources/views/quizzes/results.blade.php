<x-layouts.app :title="'Hasil Kuis'">
    <x-page-header :title="'Hasil — '.$quiz->title" :subtitle="$course->name.' · Percobaan #'.$attempt->attempt_no">
        <x-slot name="actions">
            <a href="{{ route('quizzes.show', [$course, $quiz]) }}" class="btn btn-secondary">
                <x-icon name="chevron-left" class="h-4 w-4" /> Kembali ke Kuis
            </a>
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="quizzes" class="mb-6" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">

            {{-- Skor --}}
            <x-card>
                @if (! $allowed)
                    <x-alert type="warning">
                        Guru belum menampilkan hasil untuk kuis ini. Nilai Anda sudah tercatat dan akan ditampilkan sesuai pengaturan guru.
                    </x-alert>
                @else
                    @php($earned = (float) $answers->sum('points_earned'))
                    @php($passed = $quiz->pass_score > 0 && $attempt->score !== null && $attempt->score >= $quiz->pass_score)

                    <div class="flex flex-col items-center gap-4 py-4 sm:flex-row sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Nilai Akhir</p>
                            <p class="mt-1 text-4xl font-bold {{ $passed ? 'text-emerald-600' : 'text-slate-800' }}">
                                {{ $attempt->score !== null ? rtrim(rtrim(number_format((float) $attempt->score, 2), '0'), '.') : '—' }}
                                <span class="text-lg font-medium text-slate-400">/ {{ rtrim(rtrim(number_format($totalPoints, 2), '0'), '.') }}</span>
                            </p>
                        </div>
                        <div class="text-center sm:text-right">
                            @if ($attempt->status === \App\Models\QuizAttempt::STATUS_IN_PROGRESS)
                                <span class="badge-amber badge">Masih berjalan</span>
                            @elseif ($attempt->needs_manual_review)
                                <span class="badge-amber badge">Menunggu penilaian manual</span>
                            @elseif ($passed)
                                <span class="badge-green badge"><x-icon name="check-circle" class="h-3.5 w-3.5" /> Lulus</span>
                            @else
                                <span class="badge-red badge">Belum lulus</span>
                            @endif
                            <p class="mt-1 text-xs text-slate-400">
                                Nilai lulus: {{ rtrim(rtrim(number_format($quiz->pass_score, 2), '0'), '.') }} ·
                                Dikirim {{ $attempt->submitted_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-400">Poin terkumpul: {{ rtrim(rtrim(number_format($earned, 2), '0'), '.') }} dari {{ rtrim(rtrim(number_format($totalPoints, 2), '0'), '.') }}.</p>
                @endif
            </x-card>

            {{-- Detail jawaban --}}
            @if ($allowed)
                <form method="POST" action="{{ route('quizzes.manualGrade', [$course, $quiz, $attempt]) }}">
                    @csrf

                    <div class="space-y-4">
                        @foreach ($questions as $index => $question)
                            @php($answer = $answers->get($question->id))
                            <x-card>
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-800">
                                        <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-600">{{ $index + 1 }}</span>
                                        {!! nl2br(e($question->question)) !!}
                                    </p>
                                    <span class="shrink-0 badge-amber badge">{{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} poin</span>
                                </div>

                                {{-- Kunci jawaban (izin guru / setelah hasil ditampilkan) --}}
                                @if ($question->options->isNotEmpty())
                                    <ul class="mt-3 space-y-1.5">
                                        @foreach ($question->options as $option)
                                            @php($chosen = $answer?->option_id === $option->id)
                                            <li class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm
                                                {{ $option->is_correct ? 'bg-emerald-50 text-emerald-800' : ($chosen ? 'bg-rose-50 text-rose-800' : 'bg-slate-50 text-slate-600') }}">
                                                @if ($option->is_correct)
                                                    <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-emerald-500" />
                                                @elseif ($chosen)
                                                    <x-icon name="x-mark" class="h-4 w-4 shrink-0 text-rose-500" />
                                                @else
                                                    <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-slate-300"></span>
                                                @endif
                                                {{ $option->option_text }}
                                                @if ($chosen)<span class="ml-auto text-xs font-medium">jawaban Anda</span>@endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="mt-3 space-y-2">
                                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                            <span class="text-xs font-medium text-slate-400">Jawaban Anda:</span>
                                            {{ $answer?->answer_text ?: '— tidak dijawab —' }}
                                        </p>
                                        <p class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                            <span class="text-xs font-medium text-emerald-600">Kunci jawaban:</span>
                                            {{ $question->correct_answer ?? '—' }}
                                        </p>
                                    </div>
                                @endif

                                {{-- Penilaian --}}
                                <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-3">
                                    @if ($question->type === 'short_answer' && $canManage)
                                        <label class="flex items-center gap-2 text-sm">
                                            <span class="text-slate-500">Poin (maks {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }}):</span>
                                            <input type="number" name="points[{{ $question->id }}]" min="0" max="{{ $question->points }}" step="0.5"
                                                   value="{{ old('points.'.$question->id, $answer?->points_earned ?? 0) }}" class="input !w-24" />
                                        </label>
                                    @endif

                                    <span class="ml-auto text-sm">
                                        @if ($answer?->requires_review)
                                            <span class="badge-amber badge">Menunggu penilaian</span>
                                        @elseif ($answer && $answer->is_correct === true)
                                            <span class="badge-green badge">Benar · {{ rtrim(rtrim(number_format((float) $answer->points_earned, 2), '0'), '.') }} poin</span>
                                        @elseif ($answer && $answer->is_correct === false)
                                            <span class="badge-red badge">Salah · 0 poin</span>
                                        @else
                                            <span class="badge-slate badge">Tidak dijawab</span>
                                        @endif
                                    </span>
                                </div>
                            </x-card>
                        @endforeach
                    </div>

                    @if ($canManage && $questions->contains(fn ($q) => $q->type === 'short_answer'))
                        <div class="card mt-6 flex items-center justify-between px-4 py-4">
                            <p class="text-sm text-slate-500">Isi poin pada soal jawaban singkat, lalu simpan.</p>
                            <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Simpan Penilaian Manual</button>
                        </div>
                        @error('points')<p class="field-error mt-2">{{ $message }}</p>@enderror
                    @endif
                </form>
            @endif
        </div>

        <div>
            <x-card>
                <x-slot name="header"><h2 class="card-title">Info Percobaan</h2></x-slot>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Murid</dt><dd class="font-medium text-slate-700">{{ $attempt->user?->name ?? auth()->user()->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Percobaan ke-</dt><dd class="font-medium text-slate-700">#{{ $attempt->attempt_no }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Mulai</dt><dd class="font-medium text-slate-700">{{ $attempt->started_at?->translatedFormat('d M Y, H:i') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Selesai</dt><dd class="font-medium text-slate-700">{{ $attempt->submitted_at?->translatedFormat('d M Y, H:i') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd class="font-medium text-slate-700">{{ ['in_progress' => 'Berjalan', 'submitted' => 'Terkirim', 'graded' => 'Dinilai'][$attempt->status] ?? $attempt->status }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Batas waktu</dt><dd class="font-medium text-slate-700">{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' menit' : 'Tanpa batas' }}</dd></div>
                </dl>

                @unless ($canManage)
                    @php($remaining = $quiz->max_attempts - \App\Models\QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $attempt->user_id)->count())
                    @if ($remaining > 0 && $quiz->isOpenNow())
                        <form method="POST" action="{{ route('quizzes.start', [$course, $quiz]) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full">Coba Lagi (sisa {{ $remaining }})</button>
                        </form>
                    @endif
                @endunless
            </x-card>
        </div>
    </div>
</x-layouts.app>
