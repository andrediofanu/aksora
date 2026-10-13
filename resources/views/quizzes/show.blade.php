<x-layouts.app :title="$quiz->title">
    <x-page-header :title="$quiz->title" :subtitle="$course->name.' · '.rtrim(rtrim(number_format($totalPoints, 2), '0'), '.').' poin'">
        <x-slot name="actions">
            @if ($canManage)
                <a href="{{ route('quizzes.edit', [$course, $quiz]) }}" class="btn btn-secondary"><x-icon name="edit" class="h-4 w-4" /> Ubah</a>
                <x-confirm-delete label="Hapus" icon="trash" title="Hapus kuis ini?"
                                  message="Kuis beserta soal dan seluruh percobaan murid akan dihapus permanen."
                                  :url="route('quizzes.destroy', [$course, $quiz])" />
            @else
                @if ($activeAttempt)
                    <a href="{{ route('quizzes.take', [$course, $quiz, $activeAttempt]) }}" class="btn btn-primary">Lanjutkan Pengerjaan</a>
                @elseif ($canAttempt && $questionsCount > 0)
                    <form method="POST" action="{{ route('quizzes.start', [$course, $quiz]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary"><x-icon name="chevron-right" class="h-4 w-4" /> Mulai Kerjakan</button>
                    </form>
                @endif
            @endif
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="quizzes" class="mb-6" />

    <div class="grid gap-6 {{ $canManage ? 'lg:grid-cols-3' : '' }}">
        <div class="space-y-6 {{ $canManage ? 'lg:col-span-2' : '' }}">

            {{-- Info kuis --}}
            <x-card>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($quiz->status === 'draft')
                        <span class="badge-slate badge">Draf — hanya terlihat oleh guru</span>
                    @endif
                    @if ($quiz->isUpcoming())
                        <span class="badge-amber badge">Belum dibuka</span>
                    @elseif ($quiz->hasClosed())
                        <span class="badge-red badge">Sudah ditutup</span>
                    @else
                        <span class="badge-green badge">Sedang berjalan</span>
                    @endif
                </div>

                @if ($quiz->description)
                    <div class="prose-content mt-3">{!! nl2br(e($quiz->description)) !!}</div>
                @endif
            </x-card>

            {{-- Guru: daftar soal --}}
            @if ($canManage)
                <div x-data="quizManager()" class="space-y-6">
                    @foreach ($questions as $index => $question)
                        <x-card>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="badge-indigo badge">Soal {{ $index + 1 }}</span>
                                        <span class="badge-slate badge">{{ $question->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Jawaban Singkat' }}</span>
                                        <span class="badge-amber badge">{{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} poin</span>
                                    </div>
                                    <p class="mt-2 text-sm font-medium text-slate-800">{!! nl2br(e($question->question)) !!}</p>
                                </div>

                                <div class="flex shrink-0 gap-1">
                                    <button type="button" @click="editingId = editingId === {{ $question->id }} ? null : {{ $question->id }}"
                                            class="btn btn-ghost btn-sm" aria-label="Ubah soal" :aria-expanded="editingId === {{ $question->id }}">
                                        <x-icon name="edit" class="h-4 w-4" />
                                    </button>
                                    <form method="POST" action="{{ route('quiz.questions.destroy', [$course, $quiz, $question]) }}"
                                          onsubmit="return confirm('Hapus soal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm text-rose-600" aria-label="Hapus soal">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </form>
                                </div>
                            </div>

                            @if ($question->options->isNotEmpty())
                                <ul class="mt-3 space-y-1.5">
                                    @foreach ($question->options as $option)
                                        <li class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm {{ $option->is_correct ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-50 text-slate-600' }}">
                                            @if ($option->is_correct)
                                                <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-emerald-500" />
                                            @else
                                                <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-slate-300"></span>
                                            @endif
                                            {{ $option->option_text }}
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif ($question->correct_answer !== null)
                                <p class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                    <x-icon name="check-circle" class="inline h-4 w-4 text-emerald-500" />
                                    Kunci jawaban: <strong>{{ $question->correct_answer }}</strong>
                                    @if ($question->case_sensitive) <span class="text-xs text-emerald-600">(peka huruf besar/kecil)</span> @endif
                                </p>
                            @endif

                            {{-- Form ubah soal --}}
                            <div x-data="questionForm(@js($question->type), @js(array_values($question->options->pluck('option_text')->all())), {{ max(0, $question->options->search(fn ($o) => $o->is_correct)) }})"
                                 x-show="editingId === {{ $question->id }}" x-cloak x-transition class="mt-4 border-t border-slate-100 pt-4">
                                <form method="POST" action="{{ route('quiz.questions.update', [$course, $quiz, $question]) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid gap-4 sm:grid-cols-3">
                                        <div>
                                            <label class="label" for="type-{{ $question->id }}">Jenis Soal</label>
                                            <select id="type-{{ $question->id }}" name="type" class="select" x-model="type">
                                                <option value="multiple_choice">Pilihan Ganda</option>
                                                <option value="short_answer">Jawaban Singkat</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="label" for="points-{{ $question->id }}">Poin</label>
                                            <input id="points-{{ $question->id }}" type="number" name="points" step="0.5" min="0.5" max="1000"
                                                   value="{{ old('points', $question->points) }}" class="input" required />
                                        </div>
                                        <div class="flex items-end pb-1">
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="case_sensitive" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                       @checked(old('case_sensitive', $question->case_sensitive)) />
                                                Peka huruf besar/kecil
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label class="label" for="question-{{ $question->id }}">Pertanyaan</label>
                                        <textarea id="question-{{ $question->id }}" name="question" rows="3" class="textarea" maxlength="5000" required>{{ old('question', $question->question) }}</textarea>
                                    </div>

                                    <div x-show="type === 'multiple_choice'" class="mt-4 space-y-2">
                                        <p class="label">Pilihan Jawaban <span class="text-xs font-normal text-slate-400">(tandai jawaban benar)</span></p>
                                        <template x-for="(option, i) in options" :key="i">
                                            <div class="flex items-center gap-2">
                                                <input type="radio" name="correct_option" :value="i" x-model.number="correct"
                                                       class="mt-2 shrink-0 text-emerald-600 focus:ring-emerald-500" :aria-label="'Kunci jawaban opsi ' + (i + 1)" />
                                                <input type="text" :name="'options[' + i + ']'" x-model="options[i]" maxlength="500"
                                                       class="input" :placeholder="'Opsi ' + (i + 1)" required />
                                                <button type="button" x-show="options.length > 2" @click="removeOption(i)"
                                                        class="btn btn-ghost btn-sm shrink-0 text-rose-600" aria-label="Hapus opsi">
                                                    <x-icon name="x-mark" class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </template>
                                        <button type="button" x-show="options.length < 8" @click="options.push('')"
                                                class="btn btn-secondary btn-sm"><x-icon name="plus" class="h-3.5 w-3.5" /> Tambah Opsi</button>
                                    </div>

                                    <div x-show="type === 'short_answer'" x-cloak class="mt-4">
                                        <label class="label" for="correct-answer-{{ $question->id }}">Kunci Jawaban</label>
                                        <input id="correct-answer-{{ $question->id }}" type="text" name="correct_answer" maxlength="300"
                                               value="{{ old('correct_answer', $question->correct_answer) }}" class="input" />
                                    </div>

                                    <div class="mt-4 flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm">Simpan Soal</button>
                                        <button type="button" @click="editingId = null" class="btn btn-ghost btn-sm">Batal</button>
                                    </div>
                                </form>
                            </div>
                        </x-card>
                    @endforeach

                    @if ($questions->isEmpty())
                        <x-card>
                            <x-empty icon="clipboard" title="Belum ada soal" message="Tambahkan soal pertama menggunakan formulir di bawah." />
                        </x-card>
                    @endif

                    {{-- Tambah soal --}}
                    <x-card>
                        <x-slot name="header"><h2 class="card-title">Tambah Soal</h2></x-slot>
                        <form method="POST" action="{{ route('quiz.questions.store', [$course, $quiz]) }}"
                              x-data="questionForm('multiple_choice', ['', ''])">
                            @csrf

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="label" for="new-type">Jenis Soal</label>
                                    <select id="new-type" name="type" class="select" x-model="type">
                                        <option value="multiple_choice">Pilihan Ganda</option>
                                        <option value="short_answer">Jawaban Singkat</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label" for="new-points">Poin</label>
                                    <input id="new-points" type="number" name="points" step="0.5" min="0.5" max="1000" value="{{ old('points', 10) }}" class="input" required />
                                    @error('points')<p class="field-error">{{ $message }}</p>@enderror
                                </div>
                                <div class="flex items-end pb-1">
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="case_sensitive" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('case_sensitive')) />
                                        Peka huruf besar/kecil
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="label" for="new-question">Pertanyaan</label>
                                <textarea id="new-question" name="question" rows="3" class="textarea" maxlength="5000" required>{{ old('question') }}</textarea>
                                @error('question')<p class="field-error">{{ $message }}</p>@enderror
                            </div>

                            <div x-show="type === 'multiple_choice'" class="mt-4 space-y-2">
                                <p class="label">Pilihan Jawaban <span class="text-xs font-normal text-slate-400">(tandai jawaban benar)</span></p>
                                <template x-for="(option, i) in options" :key="i">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="correct_option" :value="i" x-model.number="correct"
                                               class="mt-2 shrink-0 text-emerald-600 focus:ring-emerald-500" :aria-label="'Kunci jawaban opsi ' + (i + 1)" />
                                        <input type="text" :name="'options[' + i + ']'" x-model="options[i]" maxlength="500"
                                               class="input" :placeholder="'Opsi ' + (i + 1)" required />
                                        <button type="button" x-show="options.length > 2" @click="removeOption(i)"
                                                class="btn btn-ghost btn-sm shrink-0 text-rose-600" aria-label="Hapus opsi">
                                            <x-icon name="x-mark" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </template>
                                <button type="button" x-show="options.length < 8" @click="options.push('')"
                                        class="btn btn-secondary btn-sm"><x-icon name="plus" class="h-3.5 w-3.5" /> Tambah Opsi</button>
                            </div>

                            <div x-show="type === 'short_answer'" x-cloak class="mt-4">
                                <label class="label" for="new-correct-answer">Kunci Jawaban</label>
                                <input id="new-correct-answer" type="text" name="correct_answer" maxlength="300" value="{{ old('correct_answer') }}" class="input" />
                                @error('correct_answer')<p class="field-error">{{ $message }}</p>@enderror
                            </div>

                            @error('options')<p class="field-error mt-2">{{ $message }}</p>@enderror
                            @error('correct_option')<p class="field-error mt-2">{{ $message }}</p>@enderror

                            <button type="submit" class="btn btn-primary mt-4"><x-icon name="plus" class="h-4 w-4" /> Simpan Soal</button>
                        </form>
                    </x-card>
                </div>
            @endif

            {{-- Murid: riwayat percobaan --}}
            @unless ($canManage)
                <x-card>
                    <x-slot name="header"><h2 class="card-title">Riwayat Percobaan Saya</h2></x-slot>
                    @if ($myAttempts->isEmpty())
                        <x-empty compact icon="clipboard" title="Belum pernah mencoba" message="Mulai kerjakan kuis untuk melihat hasil di sini." />
                    @else
                        <div class="table-wrap">
                            <table class="table">
                                <thead><tr><th>Percobaan</th><th>Mulai</th><th>Selesai</th><th>Nilai</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($myAttempts as $attempt)
                                        <tr>
                                            <td class="font-medium text-slate-700">#{{ $attempt->attempt_no }}</td>
                                            <td class="text-slate-500">{{ $attempt->started_at?->translatedFormat('d M, H:i') }}</td>
                                            <td class="text-slate-500">{{ $attempt->submitted_at?->translatedFormat('d M, H:i') ?? '—' }}</td>
                                            <td>
                                                @if ($attempt->status === \App\Models\QuizAttempt::STATUS_IN_PROGRESS)
                                                    <span class="badge-amber badge">Berjalan</span>
                                                @elseif ($quiz->canShowResults($attempt))
                                                    <strong class="text-slate-800">{{ $attempt->score ?? 0 }}/{{ $attempt->total_points ?? $totalPoints }}</strong>
                                                @else
                                                    <span class="badge-slate badge">Belum ditampilkan</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                @if ($attempt->status === \App\Models\QuizAttempt::STATUS_IN_PROGRESS)
                                                    <a href="{{ route('quizzes.take', [$course, $quiz, $attempt]) }}" class="btn btn-primary btn-sm">Lanjutkan</a>
                                                @elseif ($quiz->canShowResults($attempt))
                                                    <a href="{{ route('quizzes.results', [$course, $quiz, $attempt]) }}" class="btn btn-secondary btn-sm">Lihat</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            @endunless
        </div>

        <div>
            <x-card>
                <x-slot name="header"><h2 class="card-title">Pengaturan</h2></x-slot>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Batas waktu</dt><dd class="font-medium text-slate-700">{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' menit' : 'Tanpa batas' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Jumlah soal</dt><dd class="font-medium text-slate-700">{{ $canManage ? $questions->count() : $questionsCount }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Total poin</dt><dd class="font-medium text-slate-700">{{ rtrim(rtrim(number_format($totalPoints, 2), '0'), '.') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Percobaan</dt><dd class="font-medium text-slate-700">Maks. {{ $quiz->max_attempts }}×</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Nilai lulus</dt><dd class="font-medium text-slate-700">{{ rtrim(rtrim(number_format($quiz->pass_score, 2), '0'), '.') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Hasil</dt><dd class="font-medium text-slate-700">{{ ['immediately' => 'Langsung', 'after_deadline' => 'Setelah tutup', 'never' => 'Tidak ditampilkan'][$quiz->show_results] ?? $quiz->show_results }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Buka</dt><dd class="font-medium text-slate-700">{{ $quiz->opens_at?->translatedFormat('d M, H:i') ?? 'Kapan saja' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Tutup</dt><dd class="font-medium text-slate-700">{{ $quiz->closes_at?->translatedFormat('d M, H:i') ?? 'Tanpa batas' }}</dd></div>
                    @unless ($canManage)
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Sisa percobaan</dt><dd class="font-medium text-slate-700">{{ max(0, $quiz->max_attempts - $myAttempts->count()) }}</dd></div>
                    @endunless
                </dl>
            </x-card>

            {{-- Guru: daftar percobaan murid --}}
            @if ($canManage)
                <x-card class="mt-6">
                    <x-slot name="header"><h2 class="card-title">Percobaan Murid</h2></x-slot>
                    @if ($attempts->isEmpty())
                        <x-empty compact title="Belum ada percobaan" />
                    @else
                        <div class="table-wrap">
                            <table class="table">
                                <thead><tr><th>Murid</th><th>Nilai</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($attempts as $attempt)
                                        <tr>
                                            <td>
                                                <p class="font-medium text-slate-700">{{ $attempt->user?->name }}</p>
                                                <p class="text-xs text-slate-400">#{{ $attempt->attempt_no }} · {{ $attempt->submitted_at?->diffForHumans() ?? 'Berjalan' }}</p>
                                            </td>
                                            <td>
                                                @if ($attempt->status === \App\Models\QuizAttempt::STATUS_IN_PROGRESS)
                                                    <span class="badge-amber badge">Berjalan</span>
                                                @else
                                                    <strong class="text-slate-800">{{ $attempt->score ?? 0 }}/{{ $attempt->total_points ?? $totalPoints }}</strong>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('quizzes.results', [$course, $quiz, $attempt]) }}" class="btn btn-secondary btn-sm">Detail</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($attempts->hasPages())
                            <div class="border-t border-slate-100 px-4 py-3">{{ $attempts->links() }}</div>
                        @endif
                    @endif
                </x-card>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            // State global halaman: soal mana yang sedang diedit.
            Alpine.data('quizManager', () => ({ editingId: null }));

            // State per-form soal: jenis + opsi dinamis.
            Alpine.data('questionForm', (initialType, initialOptions, initialCorrect) => ({
                type: initialType ?? 'multiple_choice',
                options: (initialOptions && initialOptions.length >= 2) ? initialOptions : ['', ''],
                correct: initialCorrect ?? 0,
                removeOption(i) {
                    this.options.splice(i, 1);
                    if (this.correct >= this.options.length) this.correct = 0;
                },
            }));
        });
    </script>
    @endpush
</x-layouts.app>
