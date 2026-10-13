@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $isTeacher = $user->isTeacher();
    $routeIs = fn (string $pattern) => request()->routeIs($pattern);
@endphp

<div class="flex h-full flex-col" x-data>
    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 px-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">L</span>
            <span x-show="!collapsed" x-cloak class="truncate text-sm font-bold text-slate-800">
                {{ config('app.name') }}
            </span>
        </a>
    </div>

    {{-- Navigasi --}}
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        <div class="space-y-1">
            <p x-show="!collapsed" x-cloak class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Menu utama</p>

            <a href="{{ route('dashboard') }}"
               class="{{ $routeIs('dashboard') ? 'nav-item-active' : 'nav-item' }}"
               title="Beranda">
                <x-icon name="home" class="h-5 w-5 shrink-0" />
                <span x-show="!collapsed" x-cloak class="truncate">Beranda</span>
            </a>

            <a href="{{ route('courses.index') }}"
               class="{{ $routeIs('courses.*') || $routeIs('materials.*') || $routeIs('assignments.*') || $routeIs('quizzes.*') || $routeIs('announcements.*') || $routeIs('discussions.*') || $routeIs('grades.index') ? 'nav-item-active' : 'nav-item' }}"
               title="Kelas Saya">
                <x-icon name="book" class="h-5 w-5 shrink-0" />
                <span x-show="!collapsed" x-cloak class="truncate">Kelas Saya</span>
            </a>

            <a href="{{ route('conversations.index') }}"
               class="{{ $routeIs('conversations.*') ? 'nav-item-active' : 'nav-item' }}"
               title="Percakapan">
                <x-icon name="chat" class="h-5 w-5 shrink-0" />
                <span x-show="!collapsed" x-cloak class="truncate flex-1">Percakapan</span>
                @if ($unreadMessages > 0)
                    <span class="ml-auto inline-flex min-w-[20px] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadMessages > 99 ? '99+' : $unreadMessages }}</span>
                @endif
            </a>

            <a href="{{ route('calendar.index') }}"
               class="{{ $routeIs('calendar.*') ? 'nav-item-active' : 'nav-item' }}"
               title="Kalender">
                <x-icon name="calendar" class="h-5 w-5 shrink-0" />
                <span x-show="!collapsed" x-cloak class="truncate">Kalender</span>
            </a>

            @if ($user->isStudent())
                <a href="{{ route('grades.mine') }}"
                   class="{{ $routeIs('grades.mine') ? 'nav-item-active' : 'nav-item' }}"
                   title="Nilai Saya">
                    <x-icon name="chart" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Nilai Saya</span>
                </a>
            @endif

            <a href="{{ route('notifications.index') }}"
               class="{{ $routeIs('notifications.*') ? 'nav-item-active' : 'nav-item' }}"
               title="Notifikasi">
                <x-icon name="bell" class="h-5 w-5 shrink-0" />
                <span x-show="!collapsed" x-cloak class="truncate flex-1">Notifikasi</span>
                @if ($unreadNotifications > 0)
                    <span class="ml-auto inline-flex min-w-[20px] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                @endif
            </a>
        </div>

        @if ($isTeacher)
            <div class="space-y-1">
                <p x-show="!collapsed" x-cloak class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Guru</p>

                <a href="{{ route('courses.create') }}"
                   class="{{ $routeIs('courses.create') ? 'nav-item-active' : 'nav-item' }}"
                   title="Buat Kelas">
                    <x-icon name="plus" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Buat Kelas</span>
                </a>
            </div>
        @endif

        @if ($isAdmin)
            <div class="space-y-1">
                <p x-show="!collapsed" x-cloak class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administrasi</p>

                <a href="{{ route('admin.dashboard') }}"
                   class="{{ $routeIs('admin.dashboard') ? 'nav-item-active' : 'nav-item' }}"
                   title="Panel Admin">
                    <x-icon name="shield" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Panel Admin</span>
                </a>

                <a href="{{ route('admin.users.index') }}"
                   class="{{ $routeIs('admin.users.*') ? 'nav-item-active' : 'nav-item' }}"
                   title="Pengguna">
                    <x-icon name="users" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Pengguna</span>
                </a>

                <a href="{{ route('admin.subjects.index') }}"
                   class="{{ $routeIs('admin.subjects.*') ? 'nav-item-active' : 'nav-item' }}"
                   title="Mata Pelajaran">
                    <x-icon name="academic" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Mata Pelajaran</span>
                </a>

                <a href="{{ route('admin.years.index') }}"
                   class="{{ $routeIs('admin.years.*') ? 'nav-item-active' : 'nav-item' }}"
                   title="Tahun Ajaran">
                    <x-icon name="folder" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Tahun Ajaran</span>
                </a>

                <a href="{{ route('admin.reports.index') }}"
                   class="{{ $routeIs('admin.reports.*') ? 'nav-item-active' : 'nav-item' }}"
                   title="Laporan">
                    <x-icon name="chart" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Laporan</span>
                </a>

                <a href="{{ route('admin.message-reports.index') }}"
                   class="{{ $routeIs('admin.message-reports.*') ? 'nav-item-active' : 'nav-item' }}"
                   title="Laporan Pesan">
                    <x-icon name="flag" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Laporan Pesan</span>
                </a>

                <a href="{{ route('admin.audit-logs.index') }}"
                   class="{{ $routeIs('admin.audit-logs.*') ? 'nav-item-active' : 'nav-item' }}"
                   title="Log Aktivitas">
                    <x-icon name="document" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Log Aktivitas</span>
                </a>

                <a href="{{ route('admin.settings.edit') }}"
                   class="{{ $routeIs('admin.settings.*') ? 'nav-item-active' : 'nav-item' }}"
                   title="Pengaturan">
                    <x-icon name="cog" class="h-5 w-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="truncate">Pengaturan</span>
                </a>
            </div>
        @endif
    </nav>

    {{-- Kaki sidebar --}}
    <div class="border-t border-slate-100 p-3">
        <button
            type="button"
            @click="toggleCollapse()"
            class="nav-item w-full justify-center"
            :title="collapsed ? 'Perluas menu' : 'Ciutkan menu'"
        >
            <span class="inline-flex transition-transform" :class="collapsed ? 'rotate-180' : ''">
                <x-icon name="chevron-left" class="h-5 w-5 shrink-0" />
            </span>
            <span x-show="!collapsed" x-cloak class="truncate">Ciutkan menu</span>
        </button>

        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="nav-item w-full text-rose-600 hover:bg-rose-50 hover:text-rose-700" title="Keluar">
                <x-icon name="logout" class="h-5 w-5 shrink-0" />
                <span x-show="!collapsed" x-cloak class="truncate">Keluar</span>
            </button>
        </form>
    </div>
</div>
