<x-layouts.guest :title="'Halaman tidak ditemukan'">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
            <x-icon name="question-mark-circle" class="h-7 w-7" />
        </div>
        <h1 class="mt-4 text-xl font-bold text-slate-800">404 — Halaman tidak ditemukan</h1>
        <p class="mt-2 text-sm text-slate-500">
            Halaman yang Anda cari sudah dipindahkan, dihapus, atau tautannya salah.
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
