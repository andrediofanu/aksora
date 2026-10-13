<x-layouts.app :title="'Materi'">
    <x-page-header :title="'Materi — '.$course->name" :subtitle="$canManage ? 'Kelola materi pembelajaran kelas ini' : 'Daftar materi yang tersedia untuk Anda'">
        <x-slot name="actions">
            <form method="GET" action="{{ route('materials.index', $course) }}" class="flex gap-2">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari judul/topik…" class="input !w-48" aria-label="Cari materi" />
                <button type="submit" class="btn btn-secondary"><x-icon name="search" class="h-4 w-4" /></button>
            </form>
            @can('create', [\App\Models\LearningMaterial::class, $course])
                <a href="{{ route('materials.create', $course) }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Materi</a>
            @endcan
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="materials" class="mb-6" />

    @if ($topics->isNotEmpty() && ! $canManage)
        <div class="mb-4 flex flex-wrap gap-1.5">
            @foreach ($topics as $topic)
                <span class="badge-slate badge">{{ $topic }}</span>
            @endforeach
        </div>
    @endif

    <x-card :flush="true">
        <ul class="divide-y divide-slate-100" role="list">
            @forelse ($materials as $material)
                @php($completed = $completedIds->has($material->id))
                <li>
                    <div class="flex items-center gap-3 px-5 py-4 transition hover:bg-slate-50">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $material->isPublished() ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-100 text-slate-400' }}">
                            <x-icon name="document" class="h-5 w-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('materials.show', [$course, $material]) }}" class="truncate text-sm font-semibold text-slate-800 hover:text-indigo-600">
                                    {{ $material->title }}
                                </a>
                                @if ($material->status === 'draft')
                                    <span class="badge-slate badge">Draf</span>
                                @elseif ($material->status === 'scheduled')
                                    <span class="badge-amber badge">Terjadwal</span>
                                @else
                                    <span class="badge-green badge">Terbit</span>
                                @endif
                                @if ($completed)
                                    <span class="badge-indigo badge"><x-icon name="check" class="h-3 w-3" /> Selesai</span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                @if ($material->topic)
                                    <span class="font-medium text-slate-600">{{ $material->topic }}</span> ·
                                @endif
                                @if ($material->status === 'scheduled' && $material->published_at)
                                    Terbit {{ $material->published_at->translatedFormat('d M Y, H:i') }}
                                @elseif ($material->published_at)
                                    {{ $material->published_at->diffForHumans() }}
                                @endif
                                @if ($material->attachments_count > 0)
                                    · {{ $material->attachments_count }} lampiran
                                @endif
                                @if ($material->external_url)
                                    · <span class="text-indigo-500">tautan eksternal</span>
                                @endif
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @if (! $canManage)
                                @if ($completed)
                                    <form method="POST" action="{{ route('materials.uncomplete', [$course, $material]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm"><x-icon name="x" class="h-3.5 w-3.5" /> Batalkan</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('materials.complete', [$course, $material]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="check" class="h-3.5 w-3.5" /> Selesai</button>
                                    </form>
                                @endif
                            @endif
                            <a href="{{ route('materials.show', [$course, $material]) }}" class="btn btn-ghost btn-sm" aria-label="Buka materi">
                                <x-icon name="chevron-right" class="h-4 w-4" />
                            </a>
                        </div>
                    </div>
                </li>
            @empty
                <li>
                    <x-empty icon="folder" title="Belum ada materi"
                             :message="$canManage ? 'Mulai dengan menambahkan materi pembelajaran pertama.' : 'Guru belum menerbitkan materi untuk kelas ini.'" />
                </li>
            @endforelse
        </ul>
    </x-card>
</x-layouts.app>
