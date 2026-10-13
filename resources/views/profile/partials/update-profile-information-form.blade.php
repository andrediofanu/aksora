<x-card title="Informasi Profil">
    <p class="mb-4 text-sm text-slate-500">
        Perbarui nama dan alamat email akun Anda. Jika email berubah, Anda perlu memverifikasi ulang.
    </p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('PATCH')

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input name="name" label="Nama lengkap" type="text"
                :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input name="email" label="Email" type="email"
                :value="old('email', $user->email)" required autocomplete="username" />
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="alert alert-info">
                <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0" />
                <span>
                    Email Anda belum diverifikasi.
                    <button form="send-verification" class="font-medium underline">Kirim ulang email verifikasi.</button>
                    @if (session('status') === 'verification-link-sent')
                        <span class="mt-1 block font-medium">Tautan verifikasi baru telah dikirim.</span>
                    @endif
                </span>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-button type="submit" variant="primary">Simpan Perubahan</x-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm font-medium text-emerald-600">Tersimpan.</p>
            @endif
        </div>
    </form>
</x-card>
