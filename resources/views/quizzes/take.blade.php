<x-layouts.app :title="'Mengerjakan Kuis'">
    @php($deadline = $attempt->expires_at)

    <div x-data="takeQuiz()" x-init="init()" class="mx-auto max-w-3xl">

        {{-- Bar status --}}
        <div class="card sticky top-20 z-10 mb-6 flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div class="min-w-0">
                <h1 class="truncate text-sm font-bold text-slate-800">{{ $quiz->title }}</h1>
                <p class="text-xs text-slate-500">{{ $course->name }} · Percobaan #{{ $attempt->attempt_no }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span x-show="saveState" x-text="saveState" x-cloak class="text-xs text-slate-400" aria-live="polite"></span>
                @if ($deadline)
                    <span class="inline-flex items-center gap-2 rounded-lg bg-rose-50 px-3 py-1.5 font-mono text-sm font-bold text-rose-700"
                          role="timer" aria-label="Sisa waktu">
                        <x-icon name="clock" class="h-4 w-4" />
                        <span x-text="clock">{{ $deadline->diffInSeconds(now()) > 0 ? gmdate('H:i:s', max(0, $deadline->diffInSeconds(now()))) : '00:00:00' }}</span>
                    </span>
                @else
                    <span class="badge-slate badge">Tanpa batas waktu</span>
                @endif
            </div>
        </div>

        @if ($quiz->description)
            <x-alert type="info" class="mb-4">{{ $quiz->description }}</x-alert>
        @endif

        <form id="quiz-form" method="POST" action="{{ route('quizzes.submit', [$course, $quiz, $attempt]) }}" @submit.prevent="submit()">
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

                        <div class="mt-3" data-question="{{ $question->id }}">
                            @if ($question->type === 'multiple_choice')
                                <div class="space-y-2" role="radiogroup" aria-label="Pilihan jawaban soal {{ $index + 1 }}">
                                    @foreach ($question->options as $option)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50/40 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                            <input type="radio" name="q{{ $question->id }}" value="{{ $option->id }}"
                                                   data-question="{{ $question->id }}"
                                                   class="text-indigo-600 focus:ring-indigo-500"
                                                   @checked($answer?->option_id === $option->id)
                                                   onchange="answerChanged({{ $question->id }}, {{ $option->id }}, null)" />
                                            {{ $option->option_text }}
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <input type="text" name="q{{ $question->id }}" data-question="{{ $question->id }}"
                                       value="{{ old('q'.$question->id, $answer?->answer_text) }}" maxlength="1000"
                                       class="input" placeholder="Ketik jawaban Anda…"
                                       onchange="answerChanged({{ $question->id }}, null, this.value)"
                                       aria-label="Jawaban soal {{ $index + 1 }}" />
                            @endif
                        </div>
                    </x-card>
                @endforeach
            </div>

            <div class="card mt-6 flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    Jawaban tersimpan otomatis setiap kali Anda memilih/mengetik. Klik "Kumpulkan" untuk menyelesaikan.
                </p>
                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('Yakin mengumpulkan? Jawaban tidak dapat diubah lagi.')">
                    <x-icon name="check-circle" class="h-4 w-4" /> Kumpulkan Jawaban
                </button>
            </div>

            <p x-show="error" x-text="error" x-cloak class="mt-2 text-sm text-rose-600" role="alert"></p>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('takeQuiz', () => ({
                clock: null,
                timerId: null,
                saveState: '',
                error: null,
                deadline: @js($deadline?->toISOString()),

                init() {
                    if (this.deadline) {
                        this.tick();
                        this.timerId = setInterval(() => this.tick(), 1000);
                    }

                    // Jembatan dari penyimpanan AJAX per soal.
                    document.addEventListener('quiz:save-state', (e) => { this.saveState = e.detail; });
                    document.addEventListener('quiz:error', (e) => { this.error = e.detail; });
                },

                tick() {
                    const remaining = Math.max(0, Math.floor((new Date(this.deadline).getTime() - Date.now()) / 1000));
                    const h = String(Math.floor(remaining / 3600)).padStart(2, '0');
                    const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, '0');
                    const s = String(remaining % 60).padStart(2, '0');
                    this.clock = `${h}:${m}:${s}`;

                    if (remaining === 0) {
                        clearInterval(this.timerId);
                        document.getElementById('quiz-form')?.requestSubmit();
                    }
                },

                async submit() {
                    clearInterval(this.timerId);
                    document.getElementById('quiz-form')?.submit();
                },
            }));
        });

        // Simpan jawaban per soal via AJAX (endpoint quizzes.answer).
        const ANSWER_URL = @js(route('quizzes.answer', [$course, $quiz, $attempt]));
        const CSRF = @js(csrf_token());
        const pending = {};

        function answerChanged(questionId, optionId, text) {
            clearTimeout(pending[questionId]);

            document.dispatchEvent(new CustomEvent('quiz:save-state', { detail: 'Menyimpan jawaban…' }));

            pending[questionId] = setTimeout(async () => {
                try {
                    const res = await fetch(ANSWER_URL, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ question_id: questionId, option_id: optionId, answer_text: text }),
                    });

                    if (! res.ok) {
                        const data = await res.json().catch(() => ({}));
                        document.dispatchEvent(new CustomEvent('quiz:error', {
                            detail: data.message ?? data.errors?.option_id?.[0] ?? data.errors?.answer?.[0] ?? 'Gagal menyimpan jawaban.',
                        }));
                        return;
                    }

                    document.dispatchEvent(new CustomEvent('quiz:save-state', { detail: 'Tersimpan ✓' }));
                } catch (e) {
                    document.dispatchEvent(new CustomEvent('quiz:save-state', { detail: 'Gagal tersimpan — periksa jaringan' }));
                }
            }, 400);
        }
    </script>
    @endpush
</x-layouts.app>
