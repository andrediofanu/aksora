@php($user = auth()->user())
<x-layouts.app :title="$thread->title">
    <x-page-header :title="$thread->title" :subtitle="'Diskusi kelas: '.$course->name">
        <x-slot name="actions">
            @if ($canModerate)
                <form method="POST" action="{{ route('discussions.pin', [$course, $thread]) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">
                        <x-icon name="pin" class="h-4 w-4" />
                        {{ $thread->is_pinned ? 'Lepas Sematan' : 'Sematkan' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('discussions.lock', [$course, $thread]) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">
                        <x-icon name="lock" class="h-4 w-4" />
                        {{ $thread->is_locked ? 'Buka Kunci' : 'Kunci Diskusi' }}
                    </button>
                </form>
                <x-confirm-delete label="Hapus Diskusi" icon="trash"
                                  title="Hapus diskusi ini?"
                                  message="Seluruh balasan dalam diskusi ini akan ikut dihapus permanen."
                                  :url="route('discussions.destroy', [$course, $thread])" />
            @endif
        </x-slot>
    </x-page-header>

    <div class="mb-4"><x-course-nav :course="$course" active="discussions" /></div>

    <div class="max-w-3xl space-y-4">
        {{-- Kartu diskusi induk --}}
        <x-card>
            <div class="flex flex-wrap items-center gap-2">
                @if ($thread->is_pinned)
                    <x-badge color="indigo" label="Disematkan" icon="pin" />
                @endif
                @if ($thread->is_locked)
                    <x-badge color="red" label="Terkunci" icon="lock" />
                @endif
            </div>
            <div class="mt-4 flex items-center gap-3">
                <x-avatar :user="$thread->author" size="sm" />
                <div>
                    <p class="text-sm font-medium text-slate-700">{{ $thread->author?->name ?? '—' }}</p>
                    <p class="text-xs text-slate-500">Dimulai {{ $thread->created_at->translatedFormat('d F Y, H:i') }}</p>
                </div>
            </div>
        </x-card>

        {{-- Daftar balasan --}}
        <x-card title="Balasan">
            <div class="divide-y divide-slate-100">
                @forelse ($posts as $post)
                    <div class="py-4 first:pt-0 last:pb-0">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <x-avatar :user="$post->author" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-700">{{ $post->author?->name ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $post->created_at->translatedFormat('d F Y, H:i') }}</p>
                                </div>
                            </div>
                            @if (! $post->is_removed && ($canModerate || $post->user_id === $user->id))
                                <form method="POST" action="{{ route('discussions.post.destroy', [$course, $thread, $post]) }}"
                                      onsubmit="return confirm('Hapus balasan ini? Jika ini satu-satunya balasan, diskusi ikut dihapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm text-rose-600 hover:bg-rose-50" aria-label="Hapus balasan">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if ($post->is_removed)
                            <p class="mt-2 text-sm italic text-slate-400">Balasan ini telah disembunyikan.</p>
                        @else
                            @if ($post->parent_id)
                                <p class="mt-2 text-xs text-slate-400">Membalas percakapan sebelumnya:</p>
                            @endif
                            <div class="prose-content mt-2">
                                {!! nl2br(e($post->body)) !!}
                            </div>
                        @endif
                    </div>
                @empty
                    <x-empty compact icon="chat" title="Belum ada balasan" />
                @endforelse
            </div>

            @if ($posts->hasPages())
                <div class="mt-4">{{ $posts->links() }}</div>
            @endif
        </x-card>

        {{-- Form balasan --}}
        @if ($canReply)
            <x-card title="Tulis Balasan">
                <form method="POST" action="{{ route('discussions.reply', [$course, $thread]) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="body" class="label">Balasan Anda</label>
                        <textarea id="body" name="body" rows="5" required maxlength="20000"
                                  placeholder="Tulis balasan yang membantu…"
                                  class="textarea">{{ old('body') }}</textarea>
                        @error('body')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="send" class="h-4 w-4" /> Kirim Balasan
                        </button>
                    </div>
                </form>
            </x-card>
        @else
            <x-alert type="info">
                Diskusi ini dikunci oleh pengampu kelas. Balasan baru tidak dapat dikirim.
            </x-alert>
        @endif

        <a href="{{ route('discussions.index', $course) }}" class="link text-sm">← Kembali ke daftar diskusi</a>
    </div>
</x-layouts.app>
