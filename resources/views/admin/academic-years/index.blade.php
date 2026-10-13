<x-layouts.app title="Tahun Akademik">
    <x-page-header
        title="Tahun Akademik"
        description="Kelola periode tahun ajaran dan tahun akademik aktif.">
        <x-slot name="actions">
            <x-button variant="primary" data-open-modal="create-year">+ Tahun Akademik</x-button>
        </x-slot>
    </x-page-header>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Tahun Akademik" :value="$years->total()" icon="calendar" color="indigo" />
        <x-stat-card label="Status Aktif" :value="$activeCount" icon="check-circle" color="emerald"
            hint="Hanya satu yang aktif" />
        <x-stat-card label="Tahun Ini" :value="now()->year" icon="clock" color="amber" />
    </div>

    <x-card>
        @if ($years->isEmpty())
            <x-empty icon="calendar" title="Belum ada tahun akademik"
                description="Tambahkan tahun akademik pertama, lalu aktifkan." />
        @else
            <x-table>
                <x-slot name="head">
                    <x-th>Nama</x-th>
                    <x-th>Periode</x-th>
                    <x-th>Status</x-th>
                    <x-th class="text-right">Aksi</x-th>
                </x-slot>
                <x-slot name="body">
                    @foreach ($years as $year)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                            <x-td class="font-medium text-slate-800">{{ $year->name }}</x-td>
                            <x-td class="whitespace-nowrap text-slate-600">
                                {{ \Illuminate\Support\Carbon::parse($year->starts_on)->isoFormat('D MMM Y') }}
                                –
                                {{ \Illuminate\Support\Carbon::parse($year->ends_on)->isoFormat('D MMM Y') }}
                            </x-td>
                            <x-td>
                                @if ($year->is_active)
                                    <x-badge label="Aktif" color="emerald" />
                                @else
                                    <x-badge label="Nonaktif" color="slate" />
                                @endif
                            </x-td>
                            <x-td class="text-right whitespace-nowrap">
                                @if (! $year->is_active)
                                    <form method="POST" action="{{ route('admin.years.activate', $year) }}"
                                        class="inline">
                                        @csrf
                                        <x-button variant="secondary" size="sm" type="submit">Aktifkan</x-button>
                                    </form>
                                @endif

                                <x-button variant="ghost" size="sm"
                                    data-action="edit"
                                    data-id="{{ $year->id }}"
                                    data-name="{{ $year->name }}"
                                    data-starts="{{ \Illuminate\Support\Carbon::parse($year->starts_on)->format('Y-m-d') }}"
                                    data-ends="{{ \Illuminate\Support\Carbon::parse($year->ends_on)->format('Y-m-d') }}"
                                    data-description="{{ $year->description }}">Edit</x-button>

                                @can('delete', $year)
                                    <form method="POST" action="{{ route('admin.years.destroy', $year) }}"
                                        class="inline"
                                        onsubmit="return confirm('Hapus tahun akademik {{ $year->name }}?')">
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

            <div class="mt-4">{{ $years->links() }}</div>
        @endif
    </x-card>

    {{-- Modal tambah --}}
    <x-modal id="create-year" title="Tambah Tahun Akademik" size="md">
        <form method="POST" action="{{ route('admin.years.store') }}" class="space-y-4">
            @csrf
            <x-input name="name" label="Nama" :value="old('name')" placeholder="2026/2027" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input type="date" name="starts_on" label="Mulai" :value="old('starts_on')" required />
                <x-input type="date" name="ends_on" label="Selesai" :value="old('ends_on')" required />
            </div>
            <x-checkbox name="is_active" label="Jadikan tahun akademik aktif" :checked="old('is_active')" />
            <x-textarea name="description" label="Deskripsi (opsional)" rows="2">{{ old('description') }}</x-textarea>

            <div class="flex justify-end gap-3 pt-2">
                <x-button type="button" variant="secondary" data-dismiss-modal="create-year">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal edit --}}
    <x-modal id="edit-year" title="Edit Tahun Akademik" size="md">
        <form method="POST" action="" id="edit-year-form" class="space-y-4">
            @csrf
            @method('PUT')
            <x-input name="name" label="Nama" id="edit-year-name" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input type="date" name="starts_on" label="Mulai" id="edit-year-starts" required />
                <x-input type="date" name="ends_on" label="Selesai" id="edit-year-ends" required />
            </div>
            <x-textarea name="description" label="Deskripsi" id="edit-year-description" rows="2"></x-textarea>

            <div class="flex justify-end gap-3 pt-2">
                <x-button type="button" variant="secondary" data-dismiss-modal="edit-year">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan Perubahan</x-button>
            </div>
        </form>
    </x-modal>

    <script>
        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('[data-action="edit"]');
            if (!trigger) return;

            const form = document.getElementById('edit-year-form');
            form.action = '/admin/tahun-ajaran/' + trigger.dataset.id;
            document.getElementById('edit-year-name').value = trigger.dataset.name;
            document.getElementById('edit-year-starts').value = trigger.dataset.starts;
            document.getElementById('edit-year-ends').value = trigger.dataset.ends;
            document.getElementById('edit-year-description').value = trigger.dataset.description;
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'edit-year' }));
        });
    </script>
</x-layouts.app>
