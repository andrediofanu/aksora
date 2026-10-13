<x-guest-layout title="Lupa Kata Sandi">
    <div class="mb-4 text-sm text-slate-600">
        Lupa kata sandi? Masukkan alamat email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <a class="text-sm text-indigo-600 hover:underline" href="{{ route('login') }}">Kembali ke halaman masuk</a>

            <x-primary-button>
                Kirim Tautan Reset
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
