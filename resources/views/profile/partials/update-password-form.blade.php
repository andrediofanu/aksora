<x-card title="Ubah Kata Sandi">
    <p class="mb-4 text-sm text-slate-500">
        Gunakan kata sandi panjang dan acak agar akun Anda tetap aman.
    </p>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-3">
            <x-input name="current_password" label="Kata sandi saat ini" type="password"
                autocomplete="current-password" />
            <x-input name="password" label="Kata sandi baru" type="password"
                autocomplete="new-password" />
            <x-input name="password_confirmation" label="Konfirmasi kata sandi" type="password"
                autocomplete="new-password" />
        </div>

        @if ($errors->updatePassword->isNotEmpty())
            <p class="field-error">{{ $errors->updatePassword->first() }}</p>
        @endif

        <div class="flex items-center gap-4">
            <x-button type="submit" variant="primary">Simpan Kata Sandi</x-button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm font-medium text-emerald-600">Tersimpan.</p>
            @endif
        </div>
    </form>
</x-card>
