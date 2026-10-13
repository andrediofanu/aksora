<x-layouts.app :title="'Pengumpulan'">
    <x-page-header :title="'Pengumpulan — '.$assignment->title" :subtitle="$course->name">
        <x-slot name="actions">
            <div class="flex gap-1" role="tablist" aria-label="Filter status">
                @foreach (['all' => 'Semua', 'pending' => 'Belum Dinilai', 'graded' => 'Dinilai', 'late' => 'Terlambat'] as $value => $label)
                    <a href="{{ route('assignments.submissions', [$course, $assignment, 'status' => $value]) }}"
                       @class(['btn btn-sm', 'btn-primary' => $filter === $value, 'btn-secondary' => $filter !== $value])>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="assignments" class="mb-6" />

    <div class="mb-6 grid grid-cols-3 gap-4">
        <x-stat-card label="Murid" :value="$totalStudents" icon="users" color="slate" />
        <x-stat-card label="Terkumpul" :value="$submittedCount" icon="inbox" color="emerald" />
        <x-stat-card label="Belum Dinilai" :value="$submittedCount - $gradedCount" icon="clipboard" :tone="$submittedCount - $gradedCount > 0 ? 'amber' : 'slate'" />
    </div>

    <x-card :flush="true">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Murid</th>
                        <th>Dikumpulkan</th>
                        <th>Jawaban / Berkas</th>
                        <th class="w-72">Penilaian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-avatar :user="$submission->user" size="sm" />
                                    <div>
                                        <p class="font-medium text-slate-800">{{ $submission->user?->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $submission->user?->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-slate-500">
                                {{ $submission->submitted_at?->translatedFormat('d M, H:i') }}
                                @if ($submission->is_late)
                                    <span class="badge-amber badge ml-1">Terlambat</span>
                                @endif
                            </td>
                            <td>
                                @if ($submission->content)
                                    <details class="text-sm text-slate-600">
                                        <summary class="link cursor-pointer">Lihat jawaban teks</summary>
                                        <div class="prose-content mt-2 whitespace-pre-wrap rounded-lg bg-slate-50 p-3">{{ $submission->content }}</div>
                                    </details>
                                @endif
                                @foreach ($submission->attachments as $attachment)
                                    <a href="{{ route('attachments.download', $attachment) }}" class="link block text-xs">{{ $attachment->original_name }}</a>
                                @endforeach
                                @if (! $submission->content && $submission->attachments->isEmpty())
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('assignments.submission.grade', [$course, $assignment, $submission]) }}">
                                    @csrf
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="score" min="0" max="{{ $assignment->max_score }}" step="0.5"
                                               value="{{ old('score.'.$submission->id, $submission->score) }}" class="input !w-20" required aria-label="Nilai" />
                                        <span class="text-xs text-slate-400">/ {{ $assignment->max_score }}</span>
                                    </div>
                                    <input type="text" name="feedback" maxlength="5000" class="input mt-2"
                                           placeholder="Umpan balik (opsional)"
                                           value="{{ old('feedback.'.$submission->id, $submission->feedback) }}" aria-label="Umpan balik" />
                                    <select name="status" class="select mt-2" aria-label="Status">
                                        @foreach (['graded' => 'Dinilai', 'revision' => 'Minta Revisi', 'returned' => 'Dikembalikan'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status.'.$submission->id, $submission->status) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('score.'.$submission->id)<p class="field-error">{{ $message }}</p>@enderror
                                    <button type="submit" class="btn btn-primary btn-sm mt-2 w-full">Simpan Nilai</button>
                                </form>

                                <form method="POST" action="{{ route('assignments.submission.destroy', [$course, $assignment, $submission]) }}" class="mt-2"
                                      onsubmit="return confirm('Hapus pengumpulan {{ $submission->user?->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm w-full text-rose-600"><x-icon name="trash" class="h-3.5 w-3.5" /> Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty compact title="Belum ada pengumpulan" message="Murid belum mengumpulkan tugas ini." /></td></tr>
                    @endforelse

                    @foreach ($missing as $student)
                        <tr class="opacity-70">
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-avatar :user="$student" size="sm" />
                                    <div>
                                        <p class="font-medium text-slate-700">{{ $student->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $student->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td colspan="2" class="text-sm italic text-slate-400">Belum dikumpulkan</td>
                            <td><span class="badge-slate badge">Menunggu</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($submissions->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $submissions->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
