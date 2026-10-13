<x-layouts.app title="Profil Saya">
    <x-page-header
        title="Profil Saya"
        subtitle="Perbarui informasi akun, kata sandi, atau hapus akun." />

    <div class="max-w-3xl space-y-6">
        @include('profile.partials.update-profile-information-form')

        @include('profile.partials.update-password-form')

        @include('profile.partials.delete-user-form')
    </div>
</x-layouts.app>
