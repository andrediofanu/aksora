<x-layouts.app title="Mata Pelajaran">
    <x-page-header
        title="Mata Pelajaran"
        description="Kelola daftar mata pelajaran yang tersedia di sekolah.">
        <x-slot name="actions">
            <x-button variant="primary" data-action="create">+ Tambah Pelajaran</x-button>
        </x-slot>
    </x-page-header>

    <x-card>
        @if ($subjects->isEmpty())
            <x-empty icon="academic-cap" title="Belum ada mata pelajaran"
                description="Tambahkan pelajaran pertama untuk mulai membuat kelas." />
        @else
            <x-table>
                <x-slot name="head">
                    <x-th>Kode</x-th>
                    <x-th>Nama</x-th>
                    <x-th>Kelas</x-th>
                    <x-th>Keterangan</x-th>
                    <x-th class="text-right">Aksi</x-th>
                </x-slot>
                <x-slot name="body">
                    @foreach ($subjects as $subject)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                            <x-td class="whitespace-nowrap font-mono text-xs">{{ $subject->code }}</x-td>
                            <x-td class="font-medium text-slate-800">{{ $subject->name }}</x-td>
                            <x-td>{{ $subject->courses_count ?? $subject->courses()->count() }}</x-td>
                            <x-td class="max-w-xs text-slate-500">
                                {{ \Illuminate\Support\Str::limit($subject->description, 60) }}
                            </x-td>
                            <x-td class="text-right whitespace-nowrap">
                                <x-button variant="ghost" size="sm" data-action="edit"
                                    data-id="{{ $subject->id }}"
                                    data-name="{{ $subject->name }}"
                                    data-code="{{ $subject->code }}"
                                    data-description="{{ $subject->description }}">Edit</x-button>

                                @can('delete', $subject)
                                    <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}"
                                        class="inline"
                                        onsubmit="return confirm('Hapus pelajaran {{ $subject->name }}? Kelas yang sudah dibuat tidak ikut terhapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button variant="ghost" size="sm" type="submit"
                                            class="text-rose-600">Hapus</x-button>
                                    </form>
                                @endcan
                            </x-td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-table>

            <div class="mt-4">{{ $subjects->links() }}</div>
        @endif
    </x-card>

    {{-- Modal tambah --}}
    <x-modal id="create-subject" title="Tambah Mata Pelajaran" size="md">
        <form method="POST" action="{{ route('admin.subjects.store') }}" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="name" label="Nama pelajaran" :value="old('name')" placeholder="Matematika" required />
                <x-input name="code" label="Kode" :value="old('code')" placeholder="MTK" required />
            </div>
            <x-textarea name="description" label="Keterangan" rows="3">{{ old('description') }}</x-textarea>

            <div class="flex justify-end gap-3 pt-2">
                <x-button type="button" variant="secondary" data-dismiss-modal="create-subject">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal edit --}}
    <x-modal id="edit-subject" title="Edit Mata Pelajaran" size="md">
        <form method="POST" action="" id="edit-subject-form" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="name" label="Nama pelajaran" id="edit-subject-name" required />
                <x-input name="code" label="Kode" id="edit-subject-code" required />
            </div>
            <x-textarea name="description" label="Keterangan" id="edit-subject-description" rows="3"></x-textarea>

            <div class="flex justify-end gap-3 pt-2">
                <x-button type="button" variant="secondary" data-dismiss-modal="edit-subject">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan Perubahan</x-button>
            </div>
        </form>
    </x-modal>

    <script>
        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('[data-action]');
            if (!trigger) return;

            if (trigger.dataset.action === 'create') {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'create-subject' }));
            }

            if (trigger.dataset.action === 'edit') {
                const form = document.getElementById('edit-subject-form');
                form.action = '/admin/mata-pelajaran/' + trigger.dataset.id;
                document.getElementById('edit-subject-name').value = trigger.dataset.name;
                document.getElementById('edit-subject-code').value = trigger.dataset.code;
                document.getElementById('edit-subject-description').value = trigger.dataset.description;
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'edit-subject' }));
            }
        });
    </script>
</x-layouts.app>
