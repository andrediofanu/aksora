<x-layouts.guest title="Beranda">
        <header class="mb-10 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white">
                <x-icon name="academic" class="h-7 w-7" />
            </div>
            <h1 class="text-2xl font-bold text-slate-800 sm:text-3xl">{{ config('app.name', 'LMS') }}</h1>
            <p class="mt-2 text-sm text-slate-500">
                Platform belajar mengajar modern untuk sekolah — kelas, materi, tugas, kuis, nilai, dan diskusi dalam satu tempat.
            </p>
        </header>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="card p-5">
                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <x-icon name="book" class="h-5 w-5" />
                </div>
                <h2 class="text-sm font-semibold text-slate-800">Kelas & Materi</h2>
                <p class="mt-1 text-sm text-slate-500">Buat kelas dengan kode undangan, bagikan materi bertopik, dan pantau progres belajar murid.</p>
            </div>

            <div class="card p-5">
                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <x-icon name="clipboard" class="h-5 w-5" />
                </div>
                <h2 class="text-sm font-semibold text-slate-800">Tugas & Kuis</h2>
                <p class="mt-1 text-sm text-slate-500">Tenggat waktu, rubrik, penilaian berdampingan, kuis bernilai waktu dengan penilaian otomatis.</p>
            </div>

            <div class="card p-5">
                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <x-icon name="chart" class="h-5 w-5" />
                </div>
                <h2 class="text-sm font-semibold text-slate-800">Nilai Transparan</h2>
                <p class="mt-1 text-sm text-slate-500">Rumus nilai akhir berbobot yang jelas, ekspor CSV, dan murid hanya melihat nilai miliknya sendiri.</p>
            </div>

            <div class="card p-5">
                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                    <x-icon name="chat" class="h-5 w-5" />
                </div>
                <h2 class="text-sm font-semibold text-slate-800">Percakapan & Diskusi</h2>
                <p class="mt-1 text-sm text-slate-500">Chat pribadi, obrolan kelas, pengumuman, dan forum diskusi dengan laporan pesan.</p>
            </div>
        </div>

        <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary w-full sm:w-auto">Masuk ke Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary w-full sm:w-auto">Masuk</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-secondary w-full sm:w-auto">Daftar Akun Baru</a>
                @endif
            @endauth
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            Zona waktu {{ config('app.timezone') }} · {{ date('Y') }} {{ config('app.name') }}
        </p>
</x-layouts.guest>
