@php
    $user = auth()->user();
    $searchQuery = trim((string) request()->query('q'));
@endphp

<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
        {{-- Tombol menu mobile --}}
        <button
            type="button"
            @click="sidebarOpen = ! sidebarOpen"
            class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden"
            aria-label="Buka menu navigasi"
        >
            <x-icon name="menu" class="h-6 w-6" />
        </button>

        {{-- Pencarian --}}
        <form action="{{ route('search.index') }}" method="GET" class="min-w-0 flex-1 max-w-xl" role="search">
            <label for="global-search" class="sr-only">Cari di aplikasi</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input
                    id="global-search"
                    type="search"
                    name="q"
                    value="{{ $searchQuery }}"
                    placeholder="Cari kelas, materi, tugas, kuis…"
                    class="input !pl-9"
                    autocomplete="off"
                >
            </div>
        </form>

        <div class="flex items-center gap-1 sm:gap-2">
            {{-- Notifikasi --}}
            <div class="relative" @click.outside="bellOpen = false">
                <button
                    type="button"
                    @click="bellOpen = ! bellOpen"
                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"
                    aria-label="Notifikasi"
                >
                    <x-icon name="bell" class="h-5 w-5" />
                    @if ($unreadNotifications > 0)
                        <span class="absolute right-1.5 top-1.5 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold leading-none text-white">
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    @endif
                </button>

                <div
                    x-show="bellOpen"
                    x-cloak
                    x-transition
                    class="absolute right-0 mt-2 w-80 rounded-xl border border-slate-200 bg-white shadow-lg"
                >
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <span class="text-sm font-semibold text-slate-700">Notifikasi</span>
                        <form method="POST" action="{{ route('notifications.readAll') }}">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-indigo-600 hover:underline">Tandai semua</button>
                        </form>
                    </div>

                    <div
                        x-data="notificationFeed()"
                        x-init="load()"
                        class="max-h-80 overflow-y-auto"
                    >
                        <template x-if="loading">
                            <div class="space-y-3 p-4">
                                <div class="skeleton h-4 w-3/4"></div>
                                <div class="skeleton h-4 w-1/2"></div>
                                <div class="skeleton h-4 w-2/3"></div>
                            </div>
                        </template>

                        <template x-if="!loading && items.length === 0">
                            <p class="p-4 text-center text-sm text-slate-500">Belum ada notifikasi.</p>
                        </template>

                        <template x-if="!loading && items.length > 0">
                            <ul class="divide-y divide-slate-100">
                                <template x-for="item in items" :key="item.id">
                                    <li>
                                        <a :href="item.url || '#'"
                                           @click="markRead(item)"
                                           class="block px-4 py-3 hover:bg-slate-50"
                                           :class="item.read ? 'opacity-70' : ''">
                                            <p class="text-sm font-medium text-slate-800" x-text="item.title"></p>
                                            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500" x-text="item.body"></p>
                                            <p class="mt-1 text-[11px] text-slate-400" x-text="item.created_at"></p>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </template>
                    </div>

                    <div class="border-t border-slate-100 px-4 py-2 text-center">
                        <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-indigo-600 hover:underline" @click="bellOpen = false">
                            Lihat semua notifikasi
                        </a>
                    </div>
                </div>
            </div>

            {{-- Pesan --}}
            <a href="{{ route('conversations.index') }}"
               class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"
               aria-label="Percakapan">
                <x-icon name="chat" class="h-5 w-5" />
                @if ($unreadMessages > 0)
                    <span class="absolute right-1.5 top-1.5 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-indigo-500 px-1 text-[9px] font-bold leading-none text-white">
                        {{ $unreadMessages > 9 ? '9+' : $unreadMessages }}
                    </span>
                @endif
            </a>

            {{-- Profil --}}
            <div class="relative" @click.outside="profileOpen = false">
                <button
                    type="button"
                    @click="profileOpen = ! profileOpen"
                    class="flex items-center gap-2 rounded-lg py-1 pl-1 pr-2 hover:bg-slate-100"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                        {{ $user->initials() }}
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block max-w-[140px] truncate text-sm font-medium leading-4 text-slate-700">{{ $user->name }}</span>
                        <span class="block text-[11px] leading-4 text-slate-400">{{ $user->roleLabel() }}</span>
                    </span>
                    <x-icon name="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block" />
                </button>

                <div
                    x-show="profileOpen"
                    x-cloak
                    x-transition
                    class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-1 shadow-lg"
                >
                    <div class="border-b border-slate-100 px-3 py-2">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                        <x-icon name="user" class="h-4 w-4" /> Profil &amp; Password
                    </a>
                    @if ($user->isStudent())
                        <a href="{{ route('grades.mine') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                            <x-icon name="chart" class="h-4 w-4" /> Nilai Saya
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">
                            <x-icon name="logout" class="h-4 w-4" /> Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Breadcrumb + judul halaman --}}
    @if (! empty($breadcrumbs) || isset($title))
        <div class="border-t border-slate-100 px-4 py-3 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-[1400px] flex-wrap items-center justify-between gap-2">
                @if (! empty($breadcrumbs))
                    <nav aria-label="NavigasiBreadcrumb" class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                        @foreach ($breadcrumbs as $index => $crumb)
                            @if ($loop->iteration === count($breadcrumbs))
                                <span class="font-medium text-slate-700" aria-current="page">{{ $crumb['label'] }}</span>
                            @else
                                @if (! empty($crumb['url']))
                                    <a href="{{ $crumb['url'] }}" class="hover:text-indigo-600 hover:underline">{{ $crumb['label'] }}</a>
                                @else
                                    <span>{{ $crumb['label'] }}</span>
                                @endif
                                <span class="text-slate-300">/</span>
                            @endif
                        @endforeach
                    </nav>
                @endif

                @if (isset($actions))
                    <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
                @endif
            </div>
        </div>
    @endif
</header>

@once
    @push('scripts')
        <script>
            function notificationFeed() {
                return {
                    loading: false,
                    items: [],
                    async load() {
                        this.loading = true;
                        try {
                            const response = await fetch('{{ route('notifications.summary') }}', {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            const data = await response.json();
                            this.items = data.items || [];
                        } catch (error) {
                            this.items = [];
                        } finally {
                            this.loading = false;
                        }
                    },
                    async markRead(item) {
                        if (item.read || ! item.id) return;
                        item.read = true;
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        await fetch('{{ url('/notifikasi') }}/' + item.id + '/baca', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        }).catch(() => {});
                    },
                };
            }
        </script>
    @endpush
@endonce
