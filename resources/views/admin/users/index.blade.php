<x-layouts.app :title="'Manajemen Pengguna'">
    <x-page-header title="Manajemen Pengguna" subtitle="Kelola akun admin, guru, dan murid">
        <x-slot name="actions">
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Pengguna</a>
        </x-slot>
    </x-page-header>

    <div class="mb-6 grid grid-cols-3 gap-4">
        <x-stat-card label="Total" :value="$stats['total']" icon="users" color="indigo" />
        <x-stat-card label="Aktif" :value="$stats['active']" icon="check-circle" color="emerald" />
        <x-stat-card label="Nonaktif" :value="$stats['inactive']" icon="lock-closed" color="rose" />
    </div>

    <x-card :flush="true">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-3 border-b border-slate-100 px-4 py-3">
            <div class="flex-1 min-w-48">
                <label class="label" for="q">Cari</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nama, email, NIS, atau NIP…" class="input" />
            </div>
            <div>
                <label class="label" for="role">Peran</label>
                <select id="role" name="role" class="select !w-36">
                    <option value="">Semua</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected($filters['role'] === $role)>{{ \App\Support\Role::label($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="select !w-36">
                    <option value="">Semua</option>
                    <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                </select>
            </div>
            <label class="flex items-center gap-2 pb-2 text-sm text-slate-600">
                <input type="checkbox" name="trashed" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked($filters['trashed']) />
                Tampilkan yang dihapus
            </label>
            <button type="submit" class="btn btn-secondary"><x-icon name="search" class="h-4 w-4" /> Filter</button>
            @if ($filters['q'] || $filters['role'] || $filters['status'] || $filters['trashed'])
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Dibuat</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-avatar :user="$user" size="sm" />
                                    <div>
                                        <p class="font-medium text-slate-800">{{ $user->name }}</p>
                                        @if ($user->nis || $user->nip)
                                            <p class="text-xs text-slate-400">{{ $user->nis ? 'NIS: '.$user->nis : 'NIP: '.$user->nip }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-slate-600">{{ $user->email }}</td>
                            <td><x-badge :label="\App\Support\Role::label($user->role)" :color="\App\Support\Role::badgeColor($user->role)" /></td>
                            <td>
                                @if ($user->trashed())
                                    <span class="badge-red badge">Dihapus</span>
                                @elseif ($user->status === 'active')
                                    <span class="badge-green badge">Aktif</span>
                                @else
                                    <span class="badge-amber badge">Nonaktif</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-slate-500">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    @if ($user->trashed())
                                        <form method="POST" action="{{ route('admin.users.restore', $user) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm"><x-icon name="refresh" class="h-3.5 w-3.5" /> Pulihkan</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary btn-sm"><x-icon name="edit" class="h-3.5 w-3.5" /> Ubah</a>
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-ghost btn-sm">
                                                    {{ $user->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                            <x-confirm-delete label="Hapus" icon="trash" class="btn-sm"
                                                              title="Hapus akun {{ $user->name }}?"
                                                              message="Akun dinonaktifkan (soft delete) dan dapat dipulihkan."
                                                              :url="route('admin.users.destroy', $user)" />
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty compact icon="users" title="Tidak ada pengguna" message="Ubah filter atau tambahkan pengguna baru." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
    </x-card>
</x-layouts.app>
