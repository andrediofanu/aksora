<x-layouts.app :title="'Ringkasan Sistem'">
    <x-page-header title="Ringkasan Sistem" subtitle="Gambaran umum pengguna, kelas, dan aktivitas panel admin">
        <x-slot name="actions">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">
                <x-icon name="chart" class="h-4 w-4" /> Laporan
            </a>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Tambah Pengguna
            </a>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Total Pengguna" :value="$stats['users']" icon="users" color="indigo" />
        <x-stat-card label="Guru" :value="$stats['teachers']" icon="academic" color="emerald" />
        <x-stat-card label="Murid" :value="$stats['students']" icon="user" color="sky" />
        <x-stat-card label="Kelas Aktif" :value="$stats['courses']" icon="book" color="amber" />
        <x-stat-card label="Mata Pelajaran" :value="$stats['subjects']" icon="clipboard" color="emerald" />
        <x-stat-card label="Tahun Ajaran" :value="$stats['academicYears']" icon="calendar" color="sky" />
        <x-stat-card label="Akun Nonaktif" :value="$stats['inactive']" icon="lock-closed" color="rose" />
        <x-stat-card label="Pengguna Baru (Bulan Ini)" :value="$newUsers" icon="chart" color="slate" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <x-slot name="header">
                    <h2 class="card-title">Sebaran peran pengguna</h2>
                    <a href="{{ route('admin.users.index') }}" class="link text-sm">Kelola pengguna</a>
                </x-slot>

                @php($roleTotal = (int) $roleDistribution->sum())
                <div class="space-y-4">
                    @forelse ($roleDistribution as $role => $total)
                        @php($count = (int) $total)
                        @php($pct = $roleTotal > 0 ? round(($count / $roleTotal) * 100) : 0)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <x-badge :label="\App\Support\Role::label((string) $role)" :color="\App\Support\Role::badgeColor((string) $role)" />
                                <span class="text-sm font-semibold text-slate-700">{{ $count }} pengguna <span class="font-normal text-slate-400">({{ $pct }}%)</span></span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <x-empty compact title="Belum ada data peran" />
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <x-slot name="header">
                    <h2 class="card-title">Aktivitas terbaru</h2>
                    <a href="{{ route('admin.audit-logs.index') }}" class="link text-sm">Lihat semua log</a>
                </x-slot>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Aksi</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentLogs as $log)
                                <tr>
                                    <td class="whitespace-nowrap text-slate-500">{{ $log->created_at->diffForHumans() }}</td>
                                    <td class="whitespace-nowrap">
                                        <span class="font-medium text-slate-700">{{ $log->user?->name ?? 'Sistem' }}</span>
                                    </td>
                                    <td><x-badge :label="$log->action" color="slate" /></td>
                                    <td class="text-slate-600">{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty compact icon="document" title="Belum ada aktivitas tercatat" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <x-slot name="header"><h2 class="card-title">Tahun ajaran aktif</h2></x-slot>

                @if ($activeAcademicYear)
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $activeAcademicYear->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $activeAcademicYear->starts_on->translatedFormat('d M Y') }}
                                —
                                {{ $activeAcademicYear->ends_on->translatedFormat('d M Y') }}
                            </p>
                        </div>
                        <x-badge label="Aktif" color="green" icon="check-circle" />
                    </div>
                    @if ($activeAcademicYear->description)
                        <p class="mt-3 text-sm text-slate-500">{{ $activeAcademicYear->description }}</p>
                    @endif
                    <p class="mt-3 text-xs text-slate-400">Kelas diarsipkan: {{ $stats['archived'] }}</p>
                @else
                    <x-alert type="warning">Belum ada tahun ajaran yang diaktifkan. Tetapkan tahun ajaran aktif agar penanggalan kelas mengikuti tahun berjalan.</x-alert>
                @endif

                <a href="{{ route('admin.years.index') }}" class="btn btn-secondary mt-4 w-full">Kelola tahun ajaran</a>
            </x-card>

            <x-card>
                <x-slot name="header"><h2 class="card-title">Aksi cepat</h2></x-slot>
                <div class="space-y-2">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-secondary w-full justify-start">
                        <x-icon name="plus" class="h-4 w-4" /> Tambah pengguna
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary w-full justify-start">
                        <x-icon name="users" class="h-4 w-4" /> Manajemen pengguna
                    </a>
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary w-full justify-start">
                        <x-icon name="academic" class="h-4 w-4" /> Mata pelajaran
                    </a>
                    <a href="{{ route('admin.years.index') }}" class="btn btn-secondary w-full justify-start">
                        <x-icon name="calendar" class="h-4 w-4" /> Tahun ajaran
                    </a>
                    <a href="{{ route('admin.settings.edit') }}" class="btn btn-secondary w-full justify-start">
                        <x-icon name="cog" class="h-4 w-4" /> Pengaturan sistem
                    </a>
                    <a href="{{ route('admin.message-reports.index') }}" class="btn btn-secondary w-full justify-start">
                        <x-icon name="flag" class="h-4 w-4" /> Laporan pesan
                    </a>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary w-full justify-start">
                        <x-icon name="shield" class="h-4 w-4" /> Log aktivitas
                    </a>
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.app>
