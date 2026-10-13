<x-layouts.app title="Laporan">
    <x-page-header
        title="Laporan Akademik"
        description="Ringkasan pengguna, kelas, tugas, kuis, dan nilai di seluruh sekolah.">
        <x-slot name="actions">
            <x-button variant="primary" tag="a" href="{{ route('admin.reports.export') }}">Unduh CSV</x-button>
        </x-slot>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Murid" :value="$summary['total_students']" icon="users" color="indigo" />
        <x-stat-card label="Guru" :value="$summary['total_teachers']" icon="academic-cap" color="emerald" />
        <x-stat-card label="Kelas" :value="$summary['total_courses']" icon="book" color="sky" />
        <x-stat-card label="Tugas" :value="$summary['total_assignments']" icon="document-text" color="amber" />
        <x-stat-card label="Pengumpulan Dinilai" :value="$summary['graded_submissions']" icon="check-circle" color="emerald" />
        <x-stat-card label="Menunggu Penilaian" :value="$summary['pending_submissions']" icon="clock" color="rose" />
        <x-stat-card label="Attempt Kuis Selesai" :value="$summary['quiz_attempts']" icon="question-mark-circle" color="violet" />
        <x-stat-card label="Rerata Nilai Tugas" :value="$summary['avg_score']" icon="chart-bar" color="indigo" />
    </div>

    <x-card>
        <x-slot name="title">Ringkasan per Kelas</x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.reports.export') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Export CSV</a>
        </x-slot>

        @if ($courses->isEmpty())
            <x-empty icon="academic-cap" title="Belum ada kelas" description="Buat kelas terlebih dahulu untuk melihat laporan." />
        @else
            <x-table>
                <x-slot name="head">
                    <x-th>Kelas</x-th>
                    <x-th>Mata Pelajaran</x-th>
                    <x-th>Guru</x-th>
                    <x-th class="text-right">Murid</x-th>
                </x-slot>
                <x-slot name="body">
                    @foreach ($courses as $course)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                            <x-td class="font-medium text-slate-800">
                                {{ $course->name }}
                                <span class="ml-2 font-mono text-xs text-slate-400">{{ $course->code }}</span>
                            </x-td>
                            <x-td>{{ $course->subject?->name ?? '-' }}</x-td>
                            <x-td>{{ $course->teacher?->name ?? '-' }}</x-td>
                            <x-td class="text-right">{{ $course->student_count }}</x-td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-table>

            <div class="mt-4">{{ $courses->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
