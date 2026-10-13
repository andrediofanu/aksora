<x-layouts.app :title="'Tugas'">
    <x-page-header :title="'Tugas — '.$course->name" :subtitle="$canManage ? 'Kelola tugas dan pengumpulan kelas ini' : 'Daftar tugas yang perlu Anda kerjakan'">
        <x-slot name="actions">
            <div class="flex gap-1" role="tablist" aria-label="Filter status">
                @foreach ([null => 'Semua', 'open' => 'Masih Buka', 'overdue' => 'Lewat'] as $value => $label)
                    <a href="{{ route('assignments.index', array_filter(['course' => $course, 'status' => $value], fn ($v) => $v !== null)) }}"
                       @class(['btn btn-sm', 'btn-primary' => $statusFilter === $value, 'btn-secondary' => $statusFilter !== $value])>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            @can('create', [\App\Models\Assignment::class, $course])
                <a href="{{ route('assignments.create', $course) }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Tugas</a>
            @endcan
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="assignments" class="mb-6" />

    <div class="space-y-4">
        @forelse ($assignments as $assignment)
            @php($mySubmission = $mySubmissions->get($assignment->id))
            <x-card>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('assignments.show', [$course, $assignment]) }}" class="text-sm font-semibold text-slate-800 hover:text-indigo-600">
                                {{ $assignment->title }}
                            </a>
                            @if ($assignment->status === 'draft')
                                <span class="badge-slate badge">Draf</span>
                            @endif
                            @if ($assignment->due_at && $assignment->due_at->isPast())
                                <span class="badge-red badge">Lewat</span>
                            @elseif ($assignment->due_at && $assignment->due_at->diffInDays(now()) <= 3)
                                <span class="badge-amber badge">Segera</span>
                            @endif
                        </div>

                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                            @if ($assignment->due_at)
                                <span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> Tenggat: {{ $assignment->due_at->translatedFormat('d M Y, H:i') }}</span>
                            @else
                                <span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> Tanpa tenggat</span>
                            @endif
                            <span>Bobot: {{ rtrim(rtrim(number_format($assignment->weight, 2), '0'), '.') }} poin</span>
                            <span>Maks: {{ $assignment->max_score }}</span>
                            @if ($assignment->allow_late)
                                <span class="text-amber-600">Terlambat diizinkan</span>
                            @endif
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($canManage)
                            <span class="badge-slate badge">{{ $assignment->submissions_count }} pengumpulan</span>
                            <a href="{{ route('assignments.submissions', [$course, $assignment]) }}" class="btn btn-secondary btn-sm">Nilai</a>
                        @else
                            @if ($mySubmission)
                                @if ($mySubmission->status === \App\Models\AssignmentSubmission::STATUS_REVISION)
                                    <span class="badge-amber badge">Perlu Revisi</span>
                                @elseif ($mySubmission->score !== null)
                                    <span class="badge-green badge">Dinilai: {{ $mySubmission->score }}/{{ $assignment->max_score }}</span>
                                @else
                                    <span class="badge-green badge">Terkumpul</span>
                                @endif
                            @else
                                <span class="badge-slate badge">Belum dikumpulkan</span>
                            @endif
                            <a href="{{ route('assignments.show', [$course, $assignment]) }}" class="btn btn-primary btn-sm">Buka</a>
                        @endif
                    </div>
                </div>
            </x-card>
        @empty
            <x-card>
                <x-empty icon="clipboard" title="Belum ada tugas"
                         :message="$canManage ? 'Buat tugas pertama untuk kelas ini.' : 'Guru belum menerbitkan tugas.'" />
            </x-card>
        @endforelse
    </div>
</x-layouts.app>
