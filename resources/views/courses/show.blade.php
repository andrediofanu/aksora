<x-layouts.app :title="$course->name">
    @php
        $subtitle = implode(' · ', array_filter([
            $course->subject?->name,
            $course->teacher?->name,
            $course->academicYear?->name,
        ], fn ($part) => $part !== null && $part !== ''));

        $previewMembers = $course->memberships()
            ->with('user:id,name,email,role,avatar_path')
            ->where('status', 'active')
            ->orderBy('role')
            ->orderBy('joined_at')
            ->limit(6)
            ->get();
    @endphp

    <x-page-header :title="$course->name" :subtitle="$subtitle !== '' ? $subtitle : 'Kelas'">
        <x-slot name="actions">
            @can('update', $course)
                <a href="{{ route('courses.edit', $course) }}" class="btn btn-secondary">
                    <x-icon name="edit" class="h-4 w-4" /> Ubah
                </a>
                <form method="POST" action="{{ route('courses.code', $course) }}"
                      onsubmit="return confirm('Buat kode kelas baru? Kode lama tidak akan bisa digunakan lagi oleh murid.')">
                    @csrf
                    <button type="submit" class="btn btn-secondary">
                        <x-icon name="refresh" class="h-4 w-4" /> Kode Baru
                    </button>
                </form>
            @endcan

            @can('delete', $course)
                <x-confirm-delete label="Hapus" icon="trash" title="Hapus kelas ini?"
                                  message="Seluruh materi, tugas, kuis, dan nilai pada kelas ini akan ikut terhapus permanen."
                                  :url="route('courses.destroy', $course)" />
            @endcan

            @can('leave', $course)
                <form method="POST" action="{{ route('courses.leave', $course) }}"
                      onsubmit="return confirm('Keluar dari kelas ini? Anda memerlukan kode baru untuk bergabung kembali.')">
                    @csrf
                    <button type="submit" class="btn btn-secondary">
                        <x-icon name="logout" class="h-4 w-4" /> Keluar Kelas
                    </button>
                </form>
            @endcan
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="overview" />

    <div class="mt-6 grid grid-cols-2 gap-4">
        <x-stat-card label="Murid" :value="$studentCount" icon="users" />
        @if ($isManager)
            <x-stat-card label="Menunggu Penilaian" :value="$pendingSubmissions" icon="document"
                         :tone="$pendingSubmissions > 0 ? 'amber' : 'slate'" />
        @else
            <x-stat-card label="Tugas Belum Dikerjakan" :value="$mySubmissionCounts['pending']" icon="clipboard"
                         :tone="$mySubmissionCounts['pending'] > 0 ? 'rose' : 'slate'" />
        @endif
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <x-slot name="header"><h2 class="card-title">Tentang kelas</h2></x-slot>

                @if ($course->description)
                    <p class="prose-content whitespace-pre-line">{{ $course->description }}</p>
                @else
                    <p class="text-sm italic text-slate-400">Belum ada deskripsi kelas.</p>
                @endif

                <dl class="mt-4 grid gap-x-6 gap-y-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Mata pelajaran</dt>
                        <dd class="mt-0.5 text-sm text-slate-700">{{ $course->subject?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Tahun ajaran</dt>
                        <dd class="mt-0.5 text-sm text-slate-700">{{ $course->academicYear?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Guru pengampu</dt>
                        <dd class="mt-0.5 text-sm text-slate-700">{{ $course->teacher?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Tingkat / rombel</dt>
                        <dd class="mt-0.5 text-sm text-slate-700">{{ $course->grade_level ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Ruang kelas</dt>
                        <dd class="mt-0.5 text-sm text-slate-700">{{ $course->room ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Status</dt>
                        <dd class="mt-0.5">
                            <x-badge :color="\App\Support\CourseStatus::badgeColor($course->status)"
                                     :label="\App\Support\CourseStatus::label($course->status)" />
                        </dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <div class="space-y-6">
            @can('update', $course)
                <x-card>
                    <x-slot name="header"><h2 class="card-title">Kode kelas</h2></x-slot>

                    <div x-data="{ copied: false }">
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-4 py-3">
                            <span class="font-mono text-xl font-bold tracking-[0.2em] text-slate-800">{{ $course->code }}</span>
                            <button type="button" class="btn btn-secondary btn-sm"
                                    @click="navigator.clipboard.writeText('{{ $course->code }}')
                                        .then(() => { copied = true; setTimeout(() => copied = false, 2000); })
                                        .catch(() => null)">
                                <span x-show="!copied" x-cloak class="flex items-center gap-2">
                                    <x-icon name="clipboard" class="h-4 w-4" /> Salin
                                </span>
                                <span x-show="copied" class="flex items-center gap-2">
                                    <x-icon name="check" class="h-4 w-4 text-emerald-600" /> Tersalin
                                </span>
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            Bagikan kode ini kepada murid agar dapat bergabung melalui menu
                            <strong>Gabung Kelas</strong>.
                        </p>
                    </div>
                </x-card>
            @endcan

            <x-card>
                <x-slot name="header">
                    <h2 class="card-title">Anggota</h2>
                    <a href="{{ route('course.members', $course) }}" class="link text-sm">Lihat semua</a>
                </x-slot>

                <div class="divide-y divide-slate-100">
                    @forelse ($previewMembers as $membership)
                        <div class="-mx-5 flex items-center gap-3 px-5 py-2.5">
                            <x-avatar :user="$membership->user" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-700">
                                    {{ $membership->user?->name ?? 'Pengguna terhapus' }}
                                </p>
                                <p class="truncate text-xs text-slate-400">{{ $membership->user?->email ?? '—' }}</p>
                            </div>
                            <x-badge :color="\App\Support\Role::badgeColor($membership->role)"
                                     :label="\App\Support\Role::label($membership->role)" />
                        </div>
                    @empty
                        <x-empty compact icon="users" title="Belum ada anggota"
                                 message="Anggota kelas akan tampil di sini setelah bergabung." />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.app>
