<x-layouts.app :title="'Percakapan'">
    <x-page-header title="Percakapan" subtitle="{{ $unreadTotal }} pesan belum dibaca">
        <x-slot name="actions">
            <button type="button" @click="$dispatch('open-modal', 'new-conversation')" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Percakapan Baru
            </button>
        </x-slot>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Daftar percakapan --}}
        <div class="lg:col-span-2">
            <x-card :flush="true">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center">
                    <form method="GET" action="{{ route('conversations.index') }}" class="flex flex-1 gap-2">
                        <input type="search" name="q" value="{{ $search }}" placeholder="Cari percakapan atau nama…"
                               class="input" aria-label="Cari percakapan" />
                        @if ($typeFilter)
                            <input type="hidden" name="type" value="{{ $typeFilter }}" />
                        @endif
                        <button type="submit" class="btn btn-secondary shrink-0"><x-icon name="search" class="h-4 w-4" /></button>
                    </form>
                    <div class="flex gap-1" role="tablist" aria-label="Filter jenis">
                        @foreach ([null => 'Semua', 'direct' => 'Pribadi', 'group' => 'Grup', 'class' => 'Kelas'] as $value => $label)
                            <a href="{{ route('conversations.index', array_filter(['q' => $search, 'type' => $value])) }}"
                               @class(['btn btn-sm shrink-0', 'btn-primary' => $typeFilter === $value, 'btn-secondary' => $typeFilter !== $value])>
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <ul class="divide-y divide-slate-100" role="list">
                    @forelse ($conversations as $conversation)
                        @php($unread = $conversation->getAttribute('_unread'))
                        <li>
                            <a href="{{ route('conversations.show', $conversation) }}"
                               class="flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50 {{ $unread ? 'bg-indigo-50/40' : '' }}">
                                <div class="relative shrink-0">
                                    @if ($conversation->isDirect())
                                        @php($other = $conversation->participants->firstWhere('id', '!=', auth()->id()))
                                        <x-avatar :user="$other" size="md" />
                                    @elseif ($conversation->isClass())
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                            <x-icon name="book" class="h-5 w-5" />
                                        </span>
                                    @else
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-violet-600">
                                            <x-icon name="users" class="h-5 w-5" />
                                        </span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm {{ $unread ? 'font-bold text-slate-900' : 'font-medium text-slate-700' }}">
                                            {{ $conversation->getAttribute('_display_title') }}
                                        </p>
                                        <span class="shrink-0 text-xs text-slate-400">
                                            {{ $conversation->last_message_at?->diffForHumans() ?? $conversation->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    <div class="mt-0.5 flex items-center justify-between gap-2">
                                        <p class="truncate text-xs {{ $unread ? 'font-medium text-slate-600' : 'text-slate-400' }}">
                                            @if ($conversation->lastMessage?->sender_id === auth()->id())
                                                <span class="text-slate-400">Anda:</span>
                                            @elseif ($conversation->lastMessage?->sender)
                                                <span>{{ $conversation->lastMessage->sender->name }}:</span>
                                            @endif
                                            {{ \Illuminate\Support\Str::limit((string) $conversation->lastMessage?->body, 60, '…') ?: 'Belum ada pesan' }}
                                        </p>
                                        @if ($unread)
                                            <span class="inline-flex min-w-[20px] shrink-0 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li>
                            <x-empty icon="chat" title="Belum ada percakapan"
                                     message="Mulai percakapan pribadi atau gabung diskusi kelas Anda." />
                        </li>
                    @endforelse
                </ul>

                @if ($conversations->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $conversations->links() }}
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Mulai percakapan --}}
        <div>
            <x-card>
                <x-slot name="header">
                    <h2 class="card-title">Mulai percakapan</h2>
                </x-slot>

                @if ($candidates->isEmpty())
                    <x-empty compact icon="users" title="Tidak ada kandidat"
                             message="@if (auth()->user()->isStudent()) Hanya pengguna satu kelas yang bisa diajak pesan pribadi. @endif" />
                @else
                    <ul class="space-y-1" role="list">
                        @foreach ($candidates as $candidate)
                            <li>
                                <form method="POST" action="{{ route('conversations.store') }}" class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-slate-50">
                                    @csrf
                                    <input type="hidden" name="type" value="direct" />
                                    <input type="hidden" name="user_id" value="{{ $candidate->id }}" />
                                    <x-avatar :user="$candidate" size="sm" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-slate-700">{{ $candidate->name }}</p>
                                        <p class="text-xs text-slate-400">{{ \App\Support\Role::label($candidate->role) }}</p>
                                    </div>
                                    <button type="submit" class="btn btn-secondary btn-sm shrink-0">Chat</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>

    {{-- Modal percakapan baru --}}
    <x-modal id="new-conversation" title="Percakapan Baru" max-width="lg">
        <div x-data="{ mode: 'direct' }">
            <div class="mb-4 flex gap-2">
                <button type="button" @click="mode = 'direct'" :class="mode === 'direct' ? 'btn-primary' : 'btn-secondary'" class="btn">Pribadi</button>
                <button type="button" @click="mode = 'group'" :class="mode === 'group' ? 'btn-primary' : 'btn-secondary'" class="btn">Grup Baru</button>
            </div>

            <form method="POST" action="{{ route('conversations.store') }}" x-show="mode === 'direct'">
                @csrf
                <input type="hidden" name="type" value="direct" />
                <label class="label" for="direct-user">Kepada</label>
                <select name="user_id" id="direct-user" class="select" required>
                    <option value="">— Pilih pengguna —</option>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->id }}" @selected(old('user_id') == $candidate->id)>{{ $candidate->name }} ({{ \App\Support\Role::label($candidate->role) }})</option>
                    @endforeach
                </select>
                @error('user_id')<p class="field-error">{{ $message }}</p>@enderror
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="btn btn-primary">Mulai Chat</button>
                </div>
            </form>

            <form method="POST" action="{{ route('conversations.store') }}" x-show="mode === 'group'" x-cloak>
                @csrf
                <input type="hidden" name="type" value="group" />
                <label class="label" for="group-title">Nama Grup</label>
                <input type="text" name="title" id="group-title" class="input" maxlength="150" placeholder="Contoh: Tugas Kelompok 3" value="{{ old('title') }}" required />
                @error('title')<p class="field-error">{{ $message }}</p>@enderror

                <label class="label mt-3" for="group-members">Anggota</label>
                <select name="user_ids[]" id="group-members" class="select" multiple size="6" required>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->id }}" @selected(in_array($candidate->id, (array) old('user_ids', [])))>{{ $candidate->name }} ({{ \App\Support\Role::label($candidate->role) }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Tahan Ctrl/Cmd untuk memilih lebih dari satu.</p>
                @error('user_ids')<p class="field-error">{{ $message }}</p>@enderror

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="btn btn-primary">Buat Grup</button>
                </div>
            </form>
        </div>
    </x-modal>
</x-layouts.app>
