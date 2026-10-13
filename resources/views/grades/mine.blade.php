<x-layouts.app :title="'Nilai Saya'">
    <x-page-header title="Nilai Saya" subtitle="Ringkasan nilai seluruh kelas yang Anda ikuti" />

    @php($overall = $overall ?? null)

    @if ($overall !== null)
        <div class="mb-6">
            <x-stat-card label="Rata-rata Nilai Akhir" :value="number_format($overall, 1)" icon="chart" color="emerald" />
        </div>
    @endif

    <div class="space-y-6">
        @forelse ($rows as $row)
            @php($course = $row['course'])
            @php($grade = $row['grade'])
            <x-card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <a href="{{ route('courses.show', $course) }}" class="text-sm font-bold text-slate-800 hover:text-indigo-600">
                            {{ $course->name }}
                        </a>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $course->subject?->name }} · {{ $course->teacher?->name }} · {{ $course->academicYear?->name }}
                        </p>
                    </div>
                    <div class="text-right">
                        @if ($grade['final'] !== null)
                            <p class="text-3xl font-bold text-slate-800">{{ number_format($grade['final'], 1) }}</p>
                            <p class="text-xs text-slate-400">Nilai akhir</p>
                        @else
                            <span class="badge-slate badge">Belum dinilai</span>
                        @endif
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rata-rata Tugas</p>
                            <p class="text-lg font-bold text-slate-800">
                                @if ($grade['assignment']['average'] !== null)
                                    {{ number_format($grade['assignment']['average'] * 100, 1) }}%
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $grade['assignment']['graded'] }} dari {{ $grade['assignment']['total'] }} tugas sudah dinilai</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rata-rata Kuis</p>
                            <p class="text-lg font-bold text-slate-800">
                                @if ($grade['quiz']['average'] !== null)
                                    {{ number_format($grade['quiz']['average'] * 100, 1) }}%
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $grade['quiz']['graded'] }} dari {{ $grade['quiz']['total'] }} kuis sudah dinilai</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                    <p class="text-xs text-slate-400">
                        Bobot: Tugas {{ rtrim(rtrim(number_format($grade['weights']['assignment'] ?? 60, 2), '0'), '.') }}% ·
                        Kuis {{ rtrim(rtrim(number_format($grade['weights']['quiz'] ?? 40, 2), '0'), '.') }}%
                    </p>
                    <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary btn-sm">Lihat Detail Kelas</a>
                </div>
            </x-card>
        @empty
            <x-card>
                <x-empty icon="chart" title="Belum ada nilai" message="Anda belum bergabung dengan kelas mana pun." />
            </x-card>
        @endforelse
    </div>

    <x-card class="mt-6">
        <x-slot name="header"><h2 class="card-title">Cara Hitung Nilai Akhir</h2></x-slot>
        <div class="prose-content">
            <ol>
                <li><strong>Rata-rata tugas (WA)</strong> — nilai tiap tugas dinormalkan ke persentase lalu dirata-ratakan menurut bobot poin tugas.</li>
                <li><strong>Rata-rata kuis (WK)</strong> — memakai percobaan terbaik tiap kuis dibagi total poin soal, lalu dirata-ratakan.</li>
                <li><strong>Nilai akhir</strong> = (WA × bobot tugas + WK × bobot kuis) ÷ (bobot tugas + bobot kuis) × 100.</li>
            </ol>
            <p class="text-slate-500">Komponen yang belum dinilai dikecualikan (bukan nol), sehingga bobotnya tidak menurunkan nilai Anda.</p>
        </div>
    </x-card>
</x-layouts.app>
