<x-layouts.app :title="'Dasbor'">
    <x-page-header title="Dasbor Admin" subtitle="Ringkasan aktivitas sistem hari ini" />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Total Pengguna" :value="$stats['users']" icon="users" />
        <x-stat-card label="Guru" :value="$stats['teachers']" icon="academic" />
        <x-stat-card label="Murid" :value="$stats['students']" icon="user" />
        <x-stat-card label="Kelas Aktif" :value="$stats['courses']" icon="book" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card>
                <x-slot name="header">
                    <h2 class="card-title">Pengguna terbaru</h2>
                    <a href="{{ route('admin.users.index') }}" class="link text-sm">Lihat semua</a>
                </x-slot>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Bergabung</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($recentUsers as $u)
                                <tr>
                                    <td class="font-medium text-slate-800">
                                        <div class="flex items-center gap-2">
                                            <x-avatar :user="$u" size="sm" />
                                            {{ $u->name }}
                                        </div>
                                    </td>
                                    <td class="text-slate-500">{{ $u->email }}</td>
                                    <td><x-badge :label="\App\Support\Role::label($u->role)" :color="\App\Support\Role::badgeColor($u->role)" /></td>
                                    <td>
                                        @if ($u->trashed())
                                            <span class="badge-red badge">Dihapus</span>
                                        @elseif ($u->status === 'active')
                                            <span class="badge-green badge">Aktif</span>
                                        @else
                                            <span class="badge-amber badge">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-slate-500">{{ $u->created_at->translatedFormat('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-empty compact title="Belum ada pengguna" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card class="mt-6">
                <x-slot name="header">
                    <h2 class="card-title">Kelas aktif</h2>
                    <a href="{{ route('courses.index') }}" class="link text-sm">Semua kelas</a>
                </x-slot>
                <div class="divide-y divide-slate-100">
                    @forelse ($courses as $course)
                        <a href="{{ route('courses.show', $course) }}" class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0 hover:bg-slate-50 -mx-5 px-5 rounded-lg transition">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $course->name }}</p>
                                <p class="text-xs text-slate-500">{{ $course->subject?->name }} · {{ $course->teacher?->name }} · {{ $course->academicYear?->name }}</p>
                            </div>
                            <x-badge :label="\App\Support\CourseStatus::label($course->status)" :color="\App\Support\CourseStatus::badgeColor($course->status)" />
                        </a>
                    @empty
                        <x-empty compact title="Belum ada kelas aktif" />
                    @endforelse
                </div>
            </x-card>
        </div>

        <div>
            <x-card>
                <x-slot name="header"><h2 class="card-title">Log aktivitas terbaru</h2></x-slot>
                <div class="space-y-4">
                    @forelse ($recentLogs as $log)
                        <div class="flex gap-3">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <x-icon name="document" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm text-slate-700">{{ $log->description }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <x-empty compact title="Belum ada aktivitas" />
                    @endforelse
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary mt-4 w-full">Lihat semua log</a>
            </x-card>

            <x-card class="mt-6">
                <x-slot name="header"><h2 class="card-title">Aksi cepat</h2></x-slot>
                <div class="space-y-2">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-secondary w-full justify-start"><x-icon name="plus" class="h-4 w-4" /> Tambah pengguna</a>
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary w-full justify-start"><x-icon name="academic" class="h-4 w-4" /> Kelola mata pelajaran</a>
                    <a href="{{ route('admin.settings.edit') }}" class="btn btn-secondary w-full justify-start"><x-icon name="cog" class="h-4 w-4" /> Pengaturan sekolah</a>
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.app>
