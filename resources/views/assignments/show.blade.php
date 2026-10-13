<x-layouts.app :title="$assignment->title">
    <x-page-header :title="$assignment->title" :subtitle="$course->name">
        <x-slot name="actions">
            @if ($canManage)
                <a href="{{ route('assignments.submissions', [$course, $assignment]) }}" class="btn btn-secondary">
                    <x-icon name="inbox" class="h-4 w-4" /> Pengumpulan ({{ $submissionCount }})
                </a>
                <a href="{{ route('assignments.edit', [$course, $assignment]) }}" class="btn btn-secondary"><x-icon name="edit" class="h-4 w-4" /> Ubah</a>
                <x-confirm-delete label="Hapus" icon="trash" title="Hapus tugas ini?"
                                  message="Tugas beserta seluruh pengumpulannya akan dihapus permanen."
                                  :url="route('assignments.destroy', [$course, $assignment])" />
            @endif
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="assignments" class="mb-6" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($assignment->status === 'draft')
                        <span class="badge-slate badge">Draf — hanya terlihat oleh guru</span>
                    @endif
                    @if ($assignment->due_at && $assignment->due_at->isPast())
                        <span class="badge-red badge">Tenggat lewat</span>
                    @endif
                    @if ($assignment->allow_late)
                        <span class="badge-amber badge">Pengumpulan terlambat diizinkan</span>
                    @endif
                </div>

                @if ($assignment->instructions)
                    <div class="prose-content mt-4 border-t border-slate-100 pt-4">{!! nl2br(e($assignment->instructions)) !!}</div>
                @endif

                @if ($assignment->attachments->isNotEmpty())
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <p class="label">Lampiran Tugas</p>
                        <ul class="space-y-2">
                            @foreach ($assignment->attachments as $attachment)
                                <li>
                                    <a href="{{ route('attachments.download', $attachment) }}"
                                       class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2 transition hover:bg-slate-50">
                                        <x-icon name="document" class="h-5 w-5 shrink-0 text-slate-400" />
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">{{ $attachment->original_name }}</span>
                                        <span class="shrink-0 text-xs text-slate-400">{{ $attachment->size >= 1048576 ? round($attachment->size / 1048576, 1).' MB' : round($attachment->size / 1024).' KB' }}</span>
                                        <x-icon name="download" class="h-4 w-4 shrink-0 text-slate-400" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($assignment->rubric)
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <p class="label">Rubrik Penilaian</p>
                        <div class="prose-content whitespace-pre-wrap rounded-lg bg-slate-50 p-4">{{ $assignment->rubric }}</div>
                    </div>
                @endif
            </x-card>

            {{-- Pengumpulan murid --}}
            @unless ($canManage)
                <x-card>
                    <x-slot name="header">
                        <h2 class="card-title">Pengumpulan Saya</h2>
                        @if ($mySubmission)
                            @if ($mySubmission->status === \App\Models\AssignmentSubmission::STATUS_REVISION)
                                <span class="badge-amber badge">Perlu Revisi</span>
                            @elseif ($mySubmission->score !== null)
                                <span class="badge-green badge">Dinilai</span>
                            @else
                                <span class="badge-green badge">Terkumpul</span>
                            @endif
                        @endif
                    </x-slot>

                    @if ($mySubmission)
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Dikumpulkan</dt>
                                <dd class="font-medium text-slate-700">
                                    {{ $mySubmission->submitted_at?->translatedFormat('d M Y, H:i') }}
                                    @if ($mySubmission->is_late)<span class="badge-amber badge ml-1">Terlambat</span>@endif
                                </dd>
                            </div>
                            @if ($mySubmission->score !== null)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Nilai</dt>
                                    <dd class="font-bold text-slate-800">{{ $mySubmission->score }} / {{ $assignment->max_score }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if ($mySubmission->content)
                            <div class="prose-content mt-3 whitespace-pre-wrap rounded-lg bg-slate-50 p-4">{{ $mySubmission->content }}</div>
                        @endif

                        @if ($mySubmission->attachments->isNotEmpty())
                            <ul class="mt-3 space-y-2">
                                @foreach ($mySubmission->attachments as $attachment)
                                    <li>
                                        <a href="{{ route('attachments.download', $attachment) }}" class="link text-sm">{{ $attachment->original_name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($mySubmission->feedback)
                            <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Umpan Balik Guru</p>
                                <div class="prose-content mt-1 text-sky-900">{{ $mySubmission->feedback }}</div>
                            </div>
                        @endif

                        @if ($canSubmit && $mySubmission->score === null)
                            <form method="POST" action="{{ route('assignments.submission.destroy', [$course, $assignment, $mySubmission]) }}"
                                  class="mt-4" onsubmit="return confirm('Hapus pengumpulan Anda? Anda bisa mengumpulkan ulang.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost text-rose-600"><x-icon name="trash" class="h-4 w-4" /> Hapus Pengumpulan</button>
                            </form>
                        @endif
                    @endif

                    @if ($canSubmit)
                        <form method="POST" action="{{ route('assignments.submit', [$course, $assignment]) }}"
                              enctype="multipart/form-data" class="mt-4 border-t border-slate-100 pt-4">
                            @csrf
                            <p class="label">{{ $mySubmission ? 'Kumpulkan Ulang' : 'Kumpulkan Jawaban' }}</p>

                            @if ($assignment->submission_type !== 'file')
                                <textarea name="content" rows="6" class="textarea" maxlength="40000"
                                          placeholder="Tulis jawaban Anda di sini…"
                                          @required($assignment->submission_type !== 'file' && ! $mySubmission)>{{ old('content', $mySubmission?->content) }}</textarea>
                                @error('content')<p class="field-error">{{ $message }}</p>@enderror
                            @endif

                            @if ($assignment->submission_type !== 'text')
                                <div class="mt-3">
                                    <label class="label" for="file">Berkas</label>
                                    <input type="file" id="file" name="file" class="input" accept="{{ $assignment->allowedExtensions() ? '.'.implode(',.', $assignment->allowedExtensions()) : '' }}" />
                                    <p class="mt-1 text-xs text-slate-400">
                                        Diizinkan: {{ $assignment->allowedExtensions() ? strtoupper(implode(', ', $assignment->allowedExtensions())) : 'semua format' }} · Maks 10 MB.
                                    </p>
                                    @error('file')<p class="field-error">{{ $message }}</p>@enderror
                                </div>
                            @endif

                            @error('content')
                                <p class="field-error mt-2">{{ $message }}</p>
                            @enderror

                            <button type="submit" class="btn btn-primary mt-4">
                                <x-icon name="upload" class="h-4 w-4" /> {{ $mySubmission ? 'Perbarui Pengumpulan' : 'Kumpulkan' }}
                            </button>
                        </form>
                    @elseif (! $mySubmission)
                        <x-alert type="warning" class="mt-4">Pengumpulan ditutup karena tenggat sudah lewat.</x-alert>
                    @endif
                </x-card>
            @else
                <x-card>
                    <x-slot name="header"><h2 class="card-title">Ringkasan Penilaian</h2></x-slot>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-2xl font-bold text-slate-800">{{ $submissionCount }}</p>
                            <p class="text-xs text-slate-500">Terkumpul</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-emerald-600">{{ $gradedCount }}</p>
                            <p class="text-xs text-slate-500">Sudah dinilai</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-amber-600">{{ $submissionCount - $gradedCount }}</p>
                            <p class="text-xs text-slate-500">Menunggu</p>
                        </div>
                    </div>
                    <a href="{{ route('assignments.submissions', [$course, $assignment]) }}" class="btn btn-primary mt-4 w-full">
                        Kelola Penilaian
                    </a>
                </x-card>
            @endunless
        </div>

        <div>
            <x-card>
                <x-slot name="header"><h2 class="card-title">Detail</h2></x-slot>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Tenggat</dt>
                        <dd class="font-medium {{ $assignment->due_at?->isPast() ? 'text-rose-600' : 'text-slate-700' }}">
                            {{ $assignment->due_at?->translatedFormat('d M Y, H:i') ?? 'Tanpa tenggat' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Sisa waktu</dt>
                        <dd class="font-medium text-slate-700">
                            @if ($assignment->due_at && $assignment->due_at->isFuture())
                                {{ $assignment->due_at->diffForHumans() }}
                            @elseif ($assignment->due_at)
                                Lewat {{ $assignment->due_at->diffForHumans() }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Bobot poin</dt>
                        <dd class="font-medium text-slate-700">{{ rtrim(rtrim(number_format($assignment->weight, 2), '0'), '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Nilai maksimum</dt>
                        <dd class="font-medium text-slate-700">{{ $assignment->max_score }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Jenis pengumpulan</dt>
                        <dd class="font-medium text-slate-700">
                            {{ ['text' => 'Teks', 'file' => 'Berkas', 'both' => 'Teks & Berkas'][$assignment->submission_type] ?? $assignment->submission_type }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Terlambat</dt>
                        <dd class="font-medium {{ $assignment->allow_late ? 'text-amber-600' : 'text-slate-700' }}">{{ $assignment->allow_late ? 'Diizinkan' : 'Tidak diizinkan' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Dibuat oleh</dt>
                        <dd class="font-medium text-slate-700">{{ $assignment->creator?->name ?? '—' }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts.app>
