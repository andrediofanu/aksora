<x-layouts.app :title="$material->title">
    <x-page-header :title="$material->title" :subtitle="$course->name.' · '.($material->topic ?? 'Tanpa topik')">
        <x-slot name="actions">
            @if ($canManage)
                <a href="{{ route('materials.edit', [$course, $material]) }}" class="btn btn-secondary"><x-icon name="edit" class="h-4 w-4" /> Ubah</a>
                <x-confirm-delete label="Hapus" icon="trash" title="Hapus materi ini?"
                                  message="Materi beserta lampirannya akan dihapus permanen."
                                  :url="route('materials.destroy', [$course, $material])" />
            @elseif ($isCompleted)
                <form method="POST" action="{{ route('materials.uncomplete', [$course, $material]) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary"><x-icon name="x" class="h-4 w-4" /> Batalkan Tandai Selesai</button>
                </form>
            @else
                <form method="POST" action="{{ route('materials.complete', [$course, $material]) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Tandai Selesai</button>
                </form>
            @endif
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="materials" class="mb-6" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($material->status === 'draft')
                        <span class="badge-slate badge">Draf</span>
                    @elseif ($material->status === 'scheduled')
                        <span class="badge-amber badge">Terjadwal</span>
                    @else
                        <span class="badge-green badge">Terbit</span>
                    @endif
                    @if ($isCompleted)
                        <span class="badge-indigo badge"><x-icon name="check" class="h-3 w-3" /> Sudah selesai</span>
                    @endif
                </div>

                @if ($material->description)
                    <p class="mt-3 text-sm font-medium text-slate-600">{{ $material->description }}</p>
                @endif

                @if ($material->content)
                    <div class="prose-content mt-4 border-t border-slate-100 pt-4">{!! nl2br(e($material->content)) !!}</div>
                @endif

                @if ($material->external_url)
                    <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary mt-4 inline-flex">
                        <x-icon name="send" class="h-4 w-4" /> Buka Tautan Eksternal
                    </a>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <x-slot name="header"><h2 class="card-title">Lampiran</h2></x-slot>
                @if ($material->attachments->isEmpty())
                    <x-empty compact icon="paperclip" title="Tidak ada lampiran" />
                @else
                    <ul class="space-y-2">
                        @foreach ($material->attachments as $attachment)
                            <li>
                                <a href="{{ route('attachments.download', $attachment) }}"
                                   class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2 transition hover:bg-slate-50">
                                    <x-icon name="document" class="h-5 w-5 shrink-0 text-slate-400" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-slate-700">{{ $attachment->original_name }}</span>
                                        <span class="block text-xs text-slate-400">{{ $attachment->size >= 1048576 ? round($attachment->size / 1048576, 1).' MB' : round($attachment->size / 1024).' KB' }}</span>
                                    </span>
                                    <x-icon name="download" class="h-4 w-4 shrink-0 text-slate-400" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card>
                <x-slot name="header"><h2 class="card-title">Informasi</h2></x-slot>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Kelas</dt>
                        <dd class="font-medium text-slate-700">{{ $course->name }}</dd>
                    </div>
                    @if ($material->topic)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Topik</dt>
                            <dd class="font-medium text-slate-700">{{ $material->topic }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Dibuat oleh</dt>
                        <dd class="font-medium text-slate-700">{{ $material->creator?->name ?? '—' }}</dd>
                    </div>
                    @if ($material->published_at)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Terbit</dt>
                            <dd class="font-medium text-slate-700">{{ $material->published_at->translatedFormat('d M Y, H:i') }}</dd>
                        </div>
                    @endif
                    @if ($material->sort_order > 0)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Urutan</dt>
                            <dd class="font-medium text-slate-700">{{ $material->sort_order }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts.app>
