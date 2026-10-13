<x-card title="Hapus Akun">
    <div class="alert alert-error mb-4">
        <x-icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
        <span>
            Setelah akun dihapus, seluruh data dan sumber daya terkait dihapus secara permanen.
            Unduh terlebih dahulu data yang ingin Anda simpan.
        </span>
    </div>

    <x-button variant="danger" data-open-modal="confirm-user-deletion">Hapus Akun Saya</x-button>

    <x-modal id="confirm-user-deletion" title="Yakin ingin menghapus akun?">
        <form method="post" action="{{ route('profile.destroy') }}" class="space-y-4">
            @csrf
            @method('DELETE')

            <p class="text-sm text-slate-600">
                Tindakan ini tidak dapat dibatalkan. Masukkan kata sandi Anda untuk mengonfirmasi.
            </p>

            <x-input name="password" label="Kata sandi" type="password"
                placeholder="Masukkan kata sandi" autocomplete="current-password" />
            @if ($errors->userDeletion->isNotEmpty())
                <p class="field-error">{{ $errors->userDeletion->first() }}</p>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" data-dismiss-modal="confirm-user-deletion">Batal</x-button>
                <x-button type="submit" variant="danger">Ya, Hapus Akun</x-button>
            </div>
        </form>
    </x-modal>
</x-card>
