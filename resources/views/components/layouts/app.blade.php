<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@isset($title){{ $title }} — @endif{{ config('app.name', 'LMS') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans text-slate-800">
        @php
            $currentUser = auth()->user();
            $unreadNotifications = $currentUser ? $currentUser->unreadNotifications()->count() : 0;
            $unreadMessages = $currentUser ? $currentUser->totalUnreadMessages() : 0;
        @endphp

        <div
            x-data="{
                sidebarOpen: false,
                collapsed: localStorage.getItem('lms-sidebar-collapsed') === '1',
                toggleCollapse() {
                    this.collapsed = ! this.collapsed;
                    localStorage.setItem('lms-sidebar-collapsed', this.collapsed ? '1' : '0');
                },
                bellOpen: false,
                profileOpen: false,
            }"
            x-init="$watch('collapsed', value => document.documentElement.style.setProperty('--sidebar-w', value ? '72px' : '256px'))"
            class="min-h-screen"
        >
            {{-- Sidebar (desktop) --}}
            <aside
                class="fixed inset-y-0 left-0 z-30 hidden border-r border-slate-200 bg-white transition-all duration-200 lg:block"
                :class="collapsed ? 'w-[76px]' : 'w-64'"
            >
                @include('layouts.partials.sidebar')
            </aside>

            {{-- Backdrop sidebar (mobile) --}}
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                @click="sidebarOpen = false"
                x-cloak
                class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
            ></div>

            {{-- Sidebar (mobile) --}}
            <aside
                x-show="sidebarOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 left-0 z-50 w-64 border-r border-slate-200 bg-white lg:hidden"
            >
                @include('layouts.partials.sidebar')
            </aside>

            {{-- Konten --}}
            <div class="transition-all duration-200" :class="collapsed ? 'lg:pl-[76px]' : 'lg:pl-64'">
                @include('layouts.partials.topbar')

                <main class="mx-auto w-full max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8">
                    @include('layouts.partials.flash')

                    {{ $slot }}
                </main>

                <footer class="mx-auto w-full max-w-[1400px] px-4 pb-8 pt-2 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                    © {{ date('Y') }} {{ config('app.name') }} — Zona waktu {{ config('app.timezone') }}
                </footer>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
