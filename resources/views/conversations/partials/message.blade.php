@php($mine = $message->sender_id === $me->id)

<div id="msg-{{ $message->id }}" class="group flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
    <div class="max-w-[75%]">
        @unless ($mine)
            <div class="mb-1 flex items-center gap-2">
                <x-avatar :user="$message->sender" size="sm" class="!h-6 !w-6 !text-[9px]" />
                <span class="text-xs font-medium text-slate-500">{{ $message->sender?->name }}</span>
            </div>
        @endunless

        <div class="rounded-2xl px-3.5 py-2 text-sm shadow-sm {{ $mine ? 'rounded-br-md bg-indigo-600 text-white' : 'rounded-bl-md bg-slate-100 text-slate-800' }}">
            @if ($message->replyTo)
                <div class="mb-1 border-l-2 {{ $mine ? 'border-indigo-300' : 'border-slate-300' }} pl-2 text-xs {{ $mine ? 'text-indigo-200' : 'text-slate-500' }}">
                    {{ $message->replyTo->sender?->name }}: {{ \Illuminate\Support\Str::limit((string) $message->replyTo->body, 80, '…') }}
                </div>
            @endif

            @if ($message->body)
                <div class="whitespace-pre-wrap break-words">{!! nl2br(e($message->body)) !!}</div>
            @endif

            @if ($message->attachments->isNotEmpty())
                <div class="mt-1 space-y-0.5 text-xs">
                    @foreach ($message->attachments as $attachment)
                        <a href="{{ route('attachments.download', $attachment) }}"
                           class="inline-flex items-center gap-1 underline {{ $mine ? 'text-indigo-100 hover:text-white' : 'text-indigo-600 hover:text-indigo-800' }}">
                            <x-icon name="paperclip" class="h-3.5 w-3.5" />
                            {{ $attachment->original_name }}
                            <span class="opacity-70">({{ $attachment->size >= 1048576 ? round($attachment->size / 1048576, 1).' MB' : round($attachment->size / 1024).' KB' }})</span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if (! $message->body && $message->attachments->isEmpty())
                <span class="italic opacity-70">Pesan dihapus</span>
            @endif
        </div>

        <div class="mt-0.5 flex items-center gap-2 text-[10px] {{ $mine ? 'justify-end' : '' }} text-slate-400">
            <span>{{ $message->created_at->translatedFormat('H:i') }}</span>
            @if ($mine)
                <span class="read-indicator" data-message="{{ $message->id }}">· {{ ($readLabels ?? [])[$message->id] ?? 'terkirim' }}</span>

                <span class="opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">
                    <button type="button" class="hover:text-indigo-500" title="Balas"
                            @click="$dispatch('reply-to', { id: {{ $message->id }}, sender: @js($message->sender?->name), body: @js(\Illuminate\Support\Str::limit((string) $message->body, 60, '…')) })">
                        <x-icon name="chat" class="h-3.5 w-3.5" />
                    </button>
                    <button type="button" class="ml-1 hover:text-rose-500" title="Hapus"
                            @click="$dispatch('delete-message', {{ $message->id }})">
                        <x-icon name="trash" class="h-3.5 w-3.5" />
                    </button>
                </span>
            @else
                <span class="opacity-0 transition group-hover:opacity-100">
                    <button type="button" class="hover:text-rose-500" title="Laporkan pesan"
                            onclick="document.getElementById('report-{{ $message->id }}')?.showModal()">
                        <x-icon name="flag" class="h-3.5 w-3.5" />
                    </button>
                </span>
            @endif
        </div>

        @if (! $mine)
            <dialog id="report-{{ $message->id }}" class="w-full max-w-md rounded-2xl p-0 backdrop:bg-slate-900/50">
                <form method="POST" action="{{ route('messages.report', $message) }}" class="rounded-2xl bg-white p-5">
                    @csrf
                    <h3 class="text-base font-semibold text-slate-800">Laporkan pesan</h3>
                    <p class="mt-1 text-sm text-slate-500">Pesan dari {{ $message->sender?->name }} akan ditinjau admin.</p>
                    <label class="label mt-3" for="reason-{{ $message->id }}">Alasan</label>
                    <textarea name="reason" id="reason-{{ $message->id }}" class="textarea" rows="3" maxlength="500" required placeholder="Jelaskan pelanggarannya…"></textarea>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" onclick="this.closest('dialog').close()" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-danger">Kirim laporan</button>
                    </div>
                </form>
            </dialog>
        @endif
    </div>
</div>
