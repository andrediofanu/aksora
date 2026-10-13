<x-layouts.guest :title="'Akses ditolak'">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-500">
            <x-icon name="lock-closed" class="h-7 w-7" />
        </div>
        <h1 class="mt-4 text-xl font-bold text-slate-800">403 — Akses ditolak</h1>
        <p class="mt-2 text-sm text-slate-500">
            Anda tidak punya izin untuk membuka halaman ini.
            @if (! empty($message))
                <span class="block mt-1 text-slate-400">{{ $message }}</span>
            @endif
            Jika Anda merasa ini keliru, hubungi admin sekolah.
        </p>
        <div class="mt-6 flex justify-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Ke dasbor</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">Masuk</a>
            @endauth
        </div>
    </div>
</x-layouts.guest>
