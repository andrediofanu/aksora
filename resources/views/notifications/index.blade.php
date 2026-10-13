<x-layouts.app :title="'Notifikasi'">
    <x-page-header title="Notifikasi" :subtitle="$unreadCount > 0 ? $unreadCount.' notifikasi belum dibaca' : 'Semua notifikasi sudah dibaca'">
        <x-slot name="actions">
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary"><x-icon name="check" class="h-4 w-4" /> Tandai Semua Dibaca</button>
                </form>
            @endif
        </x-slot>
    </x-page-header>

    <x-card :flush="true">
        <ul class="divide-y divide-slate-100" role="list">
            @forelse ($notifications as $notification)
                @php($data = $notification->data ?? [])
                @php($isUnread = $notification->read_at === null)
                <li>
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        <button type="submit" class="flex w-full items-start gap-3 px-5 py-4 text-left transition hover:bg-slate-50 {{ $isUnread ? 'bg-indigo-50/40' : '' }}">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $isUnread ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-400' }}">
                                <x-icon :name="match ($data['kind'] ?? null) {
                                    'message' => 'chat',
                                    'material' => 'folder',
                                    'assignment' => 'clipboard',
                                    'quiz' => 'quiz',
                                    'grade' => 'chart',
                                    'announcement' => 'megaphone',
                                    default => 'bell',
                                }" class="h-4.5 w-4.5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="truncate text-sm {{ $isUnread ? 'font-bold text-slate-900' : 'font-medium text-slate-700' }}">
                                        {{ $data['title'] ?? 'Notifikasi' }}
                                    </span>
                                    @if ($isUnread)
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-indigo-500" aria-label="Belum dibaca"></span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block text-sm text-slate-500">{{ $data['body'] ?? '' }}</span>
                                <span class="mt-1 block text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                            </span>
                            <x-icon name="chevron-right" class="mt-1 h-4 w-4 shrink-0 text-slate-300" />
                        </button>
                    </form>
                </li>
            @empty
                <li>
                    <x-empty icon="bell" title="Belum ada notifikasi" message="Pemberitahuan tentang materi, tugas, nilai, dan pesan akan muncul di sini." />
                </li>
            @endforelse
        </ul>

        @if ($notifications->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $notifications->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
