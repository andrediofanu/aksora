<x-layouts.guest :title="'Terjadi kesalahan'">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
            <x-icon name="exclamation-triangle" class="h-7 w-7" />
        </div>
        <h1 class="mt-4 text-xl font-bold text-slate-800">500 — Terjadi kesalahan</h1>
        <p class="mt-2 text-sm text-slate-500">
            Server mengalami masalah saat memproses permintaan Anda. Silakan coba lagi beberapa saat lagi.
            Jika masalah berlanjut, laporkan ke admin sekolah.
        </p>
        <div class="mt-6 flex justify-center gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-secondary">Kembali</a>
            <a href="{{ url('/') }}" class="btn btn-primary">Beranda</a>
        </div>
    </div>
</x-layouts.guest>
