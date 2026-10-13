<x-layouts.app title="Log Audit">
    <x-page-header
        title="Log Audit"
        description="Riwayat aktivitas penting di sistem untuk keperluan keamanan." />

    <x-card>
        <form method="GET" action="{{ route('admin.audit-logs.index') }}"
            class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <label for="filter-action" class="mb-1 block text-xs font-medium text-slate-500">Aksi</label>
                <select id="filter-action" name="action"
                    class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua aksi</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected($filters['action'] ?? '' === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-user" class="mb-1 block text-xs font-medium text-slate-500">Pengguna</label>
                <input id="filter-user" type="text" name="user_id" value="{{ $filters['user_id'] ?? '' }}"
                    placeholder="ID pengguna"
                    class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="filter-q" class="mb-1 block text-xs font-medium text-slate-500">Kata kunci</label>
                <input id="filter-q" type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                    placeholder="Cari di deskripsi…"
                    class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="filter-from" class="mb-1 block text-xs font-medium text-slate-500">Dari tanggal</label>
                <input id="filter-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                    class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="filter-to" class="mb-1 block text-xs font-medium text-slate-500">Sampai tanggal</label>
                <input id="filter-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                    class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="flex items-end gap-2">
                <x-button type="submit" variant="primary" size="sm" class="flex-1">Terapkan</x-button>
                <a href="{{ route('admin.audit-logs.index') }}"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100">Reset</a>
            </div>
        </form>

        @if ($logs->isEmpty())
            <x-empty icon="clipboard-document-list" title="Tidak ada log"
                description="Belum ada aktivitas yang cocok dengan filter ini." />
        @else
            <x-table>
                <x-slot name="head">
                    <x-th>Waktu</x-th>
                    <x-th>Pengguna</x-th>
                    <x-th>Aksi</x-th>
                    <x-th>Deskripsi</x-th>
                    <x-th>IP</x-th>
                </x-slot>
                <x-slot name="body">
                    @foreach ($logs as $log)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                            <x-td class="whitespace-nowrap text-sm text-slate-500">
                                {{ $log->created_at->format('d/m/Y H:i') }}
                            </x-td>
                            <x-td class="text-sm">
                                {{ $log->user?->name ?? 'Sistem' }}
                                @if ($log->user)
                                    <span class="block text-xs text-slate-400">{{ $log->user->email }}</span>
                                @endif
                            </x-td>
                            <x-td>
                                <x-badge :label="$log->action" color="indigo" />
                            </x-td>
                            <x-td class="text-sm text-slate-600">{{ $log->description }}</x-td>
                            <x-td class="whitespace-nowrap font-mono text-xs text-slate-400">{{ $log->ip_address }}</x-td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-table>

            <div class="mt-4">{{ $logs->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
