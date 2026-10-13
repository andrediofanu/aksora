<x-layouts.app :title="'Gabung Kelas'">
    <x-page-header title="Gabung Kelas" subtitle="Masukkan kode kelas dari guru Anda untuk mulai belajar di kelas tersebut." />

    <div class="mx-auto max-w-xl">
        @if (! auth()->user()->isStudent())
            <x-alert type="warning">
                Hanya akun murid yang dapat bergabung ke kelas. Silakan masuk dengan akun murid Anda.
            </x-alert>

        @elseif (isset($course))
            {{-- Undangan melalui tautan --}}
            <x-card>
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <x-icon name="book" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-slate-800">{{ $course->name }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ $course->subject?->name ?? 'Tanpa mata pelajaran' }}
                            · {{ $course->academicYear?->name ?? 'Tanpa tahun ajaran' }}
                        </p>
                        <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                            <x-icon name="user" class="h-3.5 w-3.5" />
                            {{ $course->teacher?->name ?? 'Guru belum ditentukan' }}
                        </p>
                    </div>
                </div>

                @error('code')
                    <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
                @enderror

                <form method="POST" action="{{ route('courses.join.token.store', $token) }}" class="mt-5">
                    @csrf
                    <button type="submit" class="btn btn-primary w-full">
                        <x-icon name="check" class="h-4 w-4" /> Bergabung ke Kelas Ini
                    </button>
                </form>

                <p class="mt-3 text-center text-xs text-slate-500">
                    Dengan bergabung, Anda mendapat akses ke materi, tugas, kuis, dan diskusi kelas ini.
                </p>
            </x-card>

        @else
            {{-- Bergabung dengan kode kelas --}}
            <x-card>
                <form method="POST" action="{{ route('courses.join.store') }}">
                    @csrf

                    <label class="label" for="code">Kode kelas</label>
                    <input type="text" name="code" id="code" class="input text-center font-mono text-lg uppercase tracking-[0.3em]"
                           maxlength="12" required autofocus autocomplete="off" spellcheck="false"
                           value="{{ old('code') }}" placeholder="ABC1234" />
                    @error('code')<p class="field-error">{{ $message }}</p>@enderror

                    <button type="submit" class="btn btn-primary mt-4 w-full">
                        <x-icon name="check" class="h-4 w-4" /> Gabung Kelas
                    </button>
                </form>

                <p class="mt-3 text-center text-xs text-slate-500">
                    Kode kelas terdiri dari 7 karakter huruf dan angka yang dibagikan oleh guru Anda.
                </p>
            </x-card>
        @endif

        <p class="mt-4 text-center text-sm text-slate-500">
            <a href="{{ route('courses.index') }}" class="link">Kembali ke daftar kelas</a>
        </p>
    </div>
</x-layouts.app>
