<x-layouts.app :title="'Buku Nilai'">
    <x-page-header :title="'Buku Nilai — '.$course->name" subtitle="Nilai akhir dihitung dengan rumus berbobot (lihat keterangan di bawah)">
        <x-slot name="actions">
            <form method="GET" action="{{ route('grades.index', $course) }}" class="flex gap-2">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama/NIS…" class="input !w-44" aria-label="Cari murid" />
                <button type="submit" class="btn btn-secondary"><x-icon name="search" class="h-4 w-4" /></button>
            </form>
            <a href="{{ route('grades.export', $course) }}" class="btn btn-secondary"><x-icon name="download" class="h-4 w-4" /> Ekspor CSV</a>
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="grades" class="mb-6" />

    {{-- Rumus --}}
    <x-card class="mb-6">
        <x-slot name="header"><h2 class="card-title">Cara Hitung Nilai Akhir</h2></x-slot>
        <div class="prose-content">
            <ol>
                <li><strong>Rata-rata tugas (WA)</strong> — setiap tugas dinormalkan ke skala 0–100% (nilai ÷ nilai maksimum), lalu dirata-ratakan berdasarkan bobot poin masing-masing tugas.</li>
                <li><strong>Rata-rata kuis (WK)</strong> — memakai percobaan terbaik setiap kuis, dinormalkan terhadap total poin soal, lalu dirata-ratakan.</li>
                <li><strong>Nilai akhir</strong> = (WA × bobot tugas + WK × bobot kuis) ÷ (bobot tugas + bobot kuis) × 100.</li>
            </ol>
            <p class="text-slate-500">Komponen yang belum dinilai <strong>tidak dihitung sebagai nol</strong>, melainkan dikecualikan sehingga bobot kategori dinormalkan ulang. Bila belum ada komponen bernilai sama sekali, nilai akhir ditampilkan “—”.</p>
        </div>
    </x-card>

    {{-- Bobot kategori --}}
    <x-card class="mb-6">
        <x-slot name="header">
            <h2 class="card-title">Bobot Kategori (total 100%)</h2>
        </x-slot>
        <form method="POST" action="{{ route('grades.weights', $course) }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="label" for="weight-assignment">Tugas (%)</label>
                <input type="number" id="weight-assignment" name="assignment" min="0" max="100" step="0.5"
                       value="{{ old('assignment', $weights['assignment'] ?? 60) }}" class="input !w-28" required />
            </div>
            <div>
                <label class="label" for="weight-quiz">Kuis (%)</label>
                <input type="number" id="weight-quiz" name="quiz" min="0" max="100" step="0.5"
                       value="{{ old('quiz', $weights['quiz'] ?? 40) }}" class="input !w-28" required />
            </div>
            <button type="submit" class="btn btn-primary">Simpan Bobot</button>
            @error('assignment')<p class="field-error w-full">{{ $message }}</p>@enderror
            @error('quiz')<p class="field-error w-full">{{ $message }}</p>@enderror
        </form>
    </x-card>

    <x-card :flush="true">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="sticky left-0 bg-slate-50">Murid</th>
                        @foreach ($assignments as $assignment)
                            <th class="whitespace-nowrap" title="{{ $assignment->title }}">{{ \Illuminate\Support\Str::limit($assignment->title, 18, '…') }}</th>
                        @endforeach
                        @foreach ($quizzes as $quiz)
                            <th class="whitespace-nowrap" title="{{ $quiz->title }}">Kuis: {{ \Illuminate\Support\Str::limit($quiz->title, 18, '…') }}</th>
                        @endforeach
                        <th>Rata-rata Tugas</th>
                        <th>Rata-rata Kuis</th>
                        <th class="text-slate-800">Nilai Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($student = $row['student'])
                        <tr>
                            <td class="sticky left-0 bg-white">
                                <p class="font-medium text-slate-800">{{ $student->name }}</p>
                                <p class="text-xs text-slate-400">{{ $student->nis ?? $student->email }}</p>
                            </td>

                            @foreach ($assignments as $assignment)
                                @php($submission = $submissions->get($assignment->id)?->firstWhere('user_id', $student->id))
                                <td class="text-center">
                                    @if ($submission && $submission->score !== null)
                                        <span class="font-medium text-slate-700">{{ rtrim(rtrim(number_format((float) $submission->score, 2), '0'), '.') }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            @endforeach

                            @foreach ($quizzes as $quiz)
                                @php($best = $attempts->get($quiz->id)?->firstWhere('user_id', $student->id))
                                <td class="text-center">
                                    @if ($best)
                                        <span class="font-medium text-slate-700">{{ rtrim(rtrim(number_format((float) $best->score, 2), '0'), '.') }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            @endforeach

                            <td class="text-center text-slate-600">
                                @if ($row['assignment']['average'] !== null)
                                    {{ number_format($row['assignment']['average'] * 100, 1) }}%
                                    <span class="block text-[10px] text-slate-400">{{ $row['assignment']['graded'] }}/{{ $row['assignment']['total'] }} dinilai</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="text-center text-slate-600">
                                @if ($row['quiz']['average'] !== null)
                                    {{ number_format($row['quiz']['average'] * 100, 1) }}%
                                    <span class="block text-[10px] text-slate-400">{{ $row['quiz']['graded'] }}/{{ $row['quiz']['total'] }} dinilai</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row['final'] !== null)
                                    <span class="badge-green badge text-sm">{{ number_format($row['final'], 1) }}</span>
                                @else
                                    <span class="badge-slate badge">Belum dinilai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 1 + $assignments->count() + $quizzes->count() + 3 }}">
                            <x-empty compact title="Belum ada murid" message="Tambahkan murid ke kelas ini untuk melihat buku nilai." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
