<x-layouts.app :title="$conversation->displayTitleFor(auth()->user())">
    @php($me = auth()->user())
    @php($title = $conversation->displayTitleFor($me))

    <div x-data="chatApp()" class="flex h-[calc(100vh-9rem)] flex-col">

        {{-- Header percakapan --}}
        <div class="card flex items-center gap-3 rounded-b-none border-b-0 px-4 py-3">
            <a href="{{ route('conversations.index') }}" class="btn btn-ghost -m-1.5 p-1.5" aria-label="Kembali ke daftar">
                <x-icon name="chevron-left" class="h-5 w-5" />
            </a>

            @if ($conversation->isDirect())
                @php($other = $conversation->activeParticipants()->where('users.id', '!=', $me->id)->first())
                <x-avatar :user="$other" size="md" />
            @elseif ($conversation->isClass())
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                    <x-icon name="book" class="h-5 w-5" />
                </span>
            @else
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-600">
                    <x-icon name="users" class="h-5 w-5" />
                </span>
            @endif

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-slate-800">{{ $title }}</p>
                <p class="truncate text-xs text-slate-400">
                    @if ($conversation->isDirect())
                        {{ $other?->name }} · {{ \App\Support\Role::label($other?->role ?? 'student') }}
                    @elseif ($conversation->isClass())
                        Diskusi kelas — {{ $participants->count() }} anggota
                    @else
                        Grup — {{ $participants->count() }} anggota
                    @endif
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <button type="button" @click="panel = panel === 'info' ? null : 'info'" class="btn btn-ghost -m-1.5 p-1.5" aria-label="Info percakapan">
                    <x-icon name="information-circle" class="h-5 w-5" />
                </button>
                <div x-data="{ menu: false }" class="relative">
                    <button type="button" @click="menu = !menu" @click.outside="menu = false" class="btn btn-ghost -m-1.5 p-1.5" aria-label="Menu lainnya" aria-haspopup="menu">
                        <x-icon name="chevron-down" class="h-5 w-5" />
                    </button>
                    <div x-show="menu" x-cloak x-transition.origin.top.right
                         class="absolute right-0 z-20 mt-1 w-56 rounded-xl border border-slate-200 bg-white py-1 shadow-lg" role="menu">
                        @if ($conversation->isGroup() && $canManage)
                            <button type="button" @click="menu = false; $dispatch('open-modal', 'rename-group')" class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50" role="menuitem">Ganti nama grup</button>
                        @endif
                        @if ($canLeave)
                            <form method="POST" action="{{ route('conversations.leave', $conversation) }}" onsubmit="return confirm('Keluar dari percakapan ini?')">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50" role="menuitem">Keluar dari percakapan</button>
                            </form>
                        @endif
                        @if (! $conversation->isClass())
                            @php($otherUser = $conversation->isDirect() ? $conversation->activeParticipants()->where('users.id', '!=', $me->id)->first() : null)
                            @if ($otherUser)
                                @if ($blockedUsers->contains($otherUser->id))
                                    <form method="POST" action="{{ route('users.unblock', $otherUser) }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50" role="menuitem">Batalkan blokir {{ $otherUser->name }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('users.block', $otherUser) }}" onsubmit="return confirm('Blokir {{ $otherUser->name }}? Pesan pribadi dua arah akan dihentikan.')">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-rose-600 hover:bg-rose-50" role="menuitem">Blokir {{ $otherUser->name }}</button>
                                    </form>
                                @endif
                            @endif
                        @endif
                        @if ($canManage && ! $conversation->isClass())
                            <x-confirm-delete label="Hapus percakapan" title="Hapus percakapan ini?"
                                              message="Seluruh riwayat pesan akan dihapus permanen."
                                              :url="route('conversations.destroy', $conversation)" class="w-full rounded-none border-0 px-4 py-2 text-left text-sm font-normal hover:bg-rose-50" />
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel info --}}
        <div x-show="panel === 'info'" x-cloak x-transition class="card mb-3 px-4 py-3">
            <h3 class="card-title mb-2">Anggota aktif ({{ $participants->count() }})</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($participants as $participant)
                    <span class="badge-slate badge gap-2 py-1">
                        <x-avatar :user="$participant" size="sm" class="!h-5 !w-5" />
                        {{ $participant->name }}
                        @if ($participant->participant_role === 'owner')
                            <span class="text-indigo-500">· pemilik</span>
                        @endif
                    </span>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-slate-400">
                Percakapan ini bersifat privat. Hanya peserta yang dapat membaca dan mengirim pesan.
            </p>
        </div>

        {{-- Pesan --}}
        <div class="card flex min-h-0 flex-1 flex-col rounded-b-none border-b-0">
            <div class="flex-1 space-y-1 overflow-y-auto px-4 py-4" x-ref="scroller" aria-live="polite">
                @if ($hasMore)
                    <div class="text-center">
                        <button type="button" @click="loadOlder()" x-bind:disabled="loadingOlder"
                                class="btn btn-secondary btn-sm">
                            <span x-show="loadingOlder" x-cloak>Memuat…</span>
                            <span x-show="!loadingOlder">Muat pesan sebelumnya</span>
                        </button>
                    </div>
                @endif

                @php($lastDate = null)
                @foreach ($messages as $message)
                    @php($date = $message->created_at->toDateString())
                    @if ($date !== $lastDate)
                        @php($lastDate = $date)
                        <div class="my-3 flex items-center gap-3" role="separator">
                            <div class="h-px flex-1 bg-slate-100"></div>
                            <span class="text-xs font-medium text-slate-400">{{ $message->created_at->translatedFormat('d F Y') }}</span>
                            <div class="h-px flex-1 bg-slate-100"></div>
                        </div>
                    @endif
                    @include('conversations.partials.message', ['message' => $message, 'me' => $me, 'canModerate' => $canManage, 'readLabels' => $readLabels])
                @endforeach

                @if ($messages->isEmpty())
                    <x-empty compact icon="chat" title="Belum ada pesan" message="Mulai percakapan dengan mengirim pesan pertama." />
                @endif

                {{-- Hasil polling (diisi Alpine) --}}
                <template x-for="m in incoming" :key="'inc-' + m.id" x-cloak>
                    <div x-html="renderMessage(m)"></div>
                </template>
            </div>

            @if (! $canSend)
                <div class="border-t border-slate-100 px-4 py-3">
                    <x-alert type="warning">Anda tidak dapat mengirim pesan di percakapan ini.</x-alert>
                </div>
            @else
                {{-- Input pesan --}}
                <form method="POST" action="{{ route('messages.store', $conversation) }}"
                      enctype="multipart/form-data" class="border-t border-slate-100 px-4 py-3"
                      x-ref="form" @submit.prevent="send()">
                    @csrf
                    <div x-show="replyTo" x-cloak class="mb-2 flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-xs">
                        <span class="truncate text-slate-600">Membalas <strong x-text="replyTo?.sender"></strong>: <span x-text="replyTo?.body" class="text-slate-400"></span></span>
                        <button type="button" @click="replyTo = null" class="ml-2 text-slate-400 hover:text-slate-600" aria-label="Batalkan balasan">
                            <x-icon name="x-mark" class="h-4 w-4" />
                        </button>
                    </div>

                    <div x-show="fileName" x-cloak class="mb-2 flex items-center justify-between rounded-lg bg-indigo-50 px-3 py-2 text-xs text-indigo-700">
                        <span class="truncate"><x-icon name="paperclip" class="inline h-3.5 w-3.5" /> <span x-text="fileName"></span></span>
                        <button type="button" @click="clearFile()" class="ml-2 text-indigo-400 hover:text-indigo-600" aria-label="Lampirkan berkas dibatalkan">
                            <x-icon name="x-mark" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex items-end gap-2">
                        <input type="hidden" name="reply_to_id" x-bind:value="replyTo?.id ?? ''" />
                        <label class="btn btn-ghost shrink-0 cursor-pointer" title="Lampirkan berkas (maks 10 MB)">
                            <x-icon name="paperclip" class="h-5 w-5" />
                            <span class="sr-only">Lampirkan berkas</span>
                            <input type="file" name="file" class="sr-only" x-ref="file"
                                   accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.csv,.png,.jpg,.jpeg,.gif,.zip,.mp3,.mp4"
                                   @change="fileName = $refs.file.files[0]?.name ?? null" />
                        </label>

                        <textarea name="body" x-ref="input" rows="1" placeholder="Tulis pesan… (Enter untuk kirim, Shift+Enter untuk baris baru)"
                                  class="textarea max-h-32 flex-1 resize-none"
                                  @keydown.enter.shift.prevent="$el.dispatchEvent(new Event('submit-bubble'))"
                                  @keydown.enter.prevent="send()"
                                  aria-label="Tulis pesan"></textarea>

                        <button type="submit" class="btn btn-primary shrink-0" x-bind:disabled="sending || (! messageBody && ! fileName)">
                            <x-icon name="send" class="h-4 w-4" />
                            <span class="sr-only">Kirim</span>
                        </button>
                    </div>

                    <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                        <span>
                            @if ($me->isAdmin())
                                Mode admin: Anda hanya dapat membaca/mengirim pada percakapan Anda sendiri.
                            @else
                                Maksimal 10 MB per lampiran.
                            @endif
                        </span>
                        <span x-show="lastError" x-text="lastError" class="text-rose-600"></span>
                    </div>
                </form>
            @endif
        </div>

        <p x-show="typingLabel" x-cloak x-text="typingLabel" class="mt-1 h-4 text-xs italic text-slate-400" aria-live="polite"></p>
    </div>

    {{-- Modal ganti nama grup --}}
    @if ($conversation->isGroup() && $canManage)
        <x-modal id="rename-group" title="Ganti Nama Grup">
            <form method="POST" action="{{ route('conversations.update', $conversation) }}">
                @csrf
                @method('PUT')
                <label class="label" for="rename-title">Nama grup</label>
                <input type="text" name="title" id="rename-title" class="input" maxlength="150" required value="{{ old('title', $conversation->title) }}" />
                @error('title')<p class="field-error">{{ $message }}</p>@enderror
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="open = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </x-modal>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('chatApp', () => ({
                panel: null,
                incoming: [],
                sending: false,
                lastError: null,
                fileName: null,
                replyTo: null,
                loadingOlder: false,
                hasMoreOlder: @js($hasMore),
                oldestId: @js($oldestId),
                latestId: @js($messages->max('id') ?? 0),
                pollTimer: null,
                typingLabel: null,
                typingTimer: null,
                endpoint: @js(route('messages.poll', $conversation)),
                historyEndpoint: @js(route('messages.history', $conversation)),
                csrf: @js(csrf_token()),

                get messageBody() {
                    return (this.$refs.input?.value ?? '').trim();
                },

                init() {
                    this.scrollBottom();
                    this.poll();
                    this.pollTimer = setInterval(() => this.poll(), 5000);
                    this.$refs.input?.addEventListener('input', () => this.autoGrow());

                    document.addEventListener('reply-to', (e) => {
                        this.replyTo = e.detail;
                        this.$refs.input?.focus();
                    });
                    document.addEventListener('delete-message', (e) => this.removeMessage(e.detail));
                },

                autoGrow() {
                    const el = this.$refs.input;
                    if (! el) return;
                    el.style.height = 'auto';
                    el.style.height = Math.min(el.scrollHeight, 128) + 'px';
                },

                scrollBottom() {
                    const el = this.$refs.scroller;
                    if (el) el.scrollTop = el.scrollHeight;
                },

                async poll() {
                    try {
                        const res = await fetch(`${this.endpoint}?after=${this.latestId}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (! res.ok) return;
                        const data = await res.json();
                        if (data.messages && data.messages.length) {
                            this.incoming.push(...data.messages);
                            this.latestId = Math.max(this.latestId, data.latest_id || 0);
                            this.$nextTick(() => this.scrollBottom());
                        }
                        this.refreshReadLabels(data.read_labels);
                    } catch (e) { /* jaringan bermasalah; coba lagi pada siklus berikutnya */ }
                },

                /** Perbarui indikator status baca pesan milik saya yang sudah dirender. */
                refreshReadLabels(labels) {
                    if (! labels) return;
                    Object.entries(labels).forEach(([id, label]) => {
                        const el = document.querySelector('.read-indicator[data-message="' + id + '"]');
                        if (el && el.textContent.trim() !== '· ' + label) el.textContent = '· ' + label;
                    });
                },

                async loadOlder() {
                    if (! this.hasMoreOlder || this.loadingOlder) return;
                    this.loadingOlder = true;
                    const el = this.$refs.scroller;
                    const prevHeight = el.scrollHeight;

                    try {
                        const res = await fetch(`${this.historyEndpoint}?before=${this.oldestId}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        const data = await res.json();
                        const container = document.createElement('div');
                        container.innerHTML = data.messages.map((m) => this.renderMessage(m)).join('');
                        el.insertBefore(container, el.firstChild);
                        this.oldestId = data.oldest_id;
                        this.hasMoreOlder = data.has_more;
                        this.$nextTick(() => { el.scrollTop = el.scrollHeight - prevHeight; });
                    } catch (e) {
                        this.lastError = 'Gagal memuat pesan lama.';
                    } finally {
                        this.loadingOlder = false;
                    }
                },

                renderMessage(m) {
                    const mine = m.is_mine;
                    const align = mine ? 'justify-end' : 'justify-start';
                    const bubble = mine
                        ? 'bg-indigo-600 text-white rounded-br-md'
                        : 'bg-slate-100 text-slate-800 rounded-bl-md';
                    const time = new Date(m.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                    const body = m.body ? esc(m.body).replace(/\n/g, '<br>') : '';
                    const files = (m.attachments || []).map((a) =>
                        `<a href="${esc(a.url)}" class="underline ${mine ? 'text-indigo-100' : 'text-indigo-600'}">${esc(a.name)}</a>`
                    ).join('<br>');
                    const reply = m.reply_to
                        ? `<div class="mb-1 border-l-2 ${mine ? 'border-indigo-300' : 'border-slate-300'} pl-2 text-xs ${mine ? 'text-indigo-200' : 'text-slate-500'}">${esc(m.reply_to.sender)}: ${esc(m.reply_to.body)}</div>`
                        : '';

                    return `<div class="flex ${align}"><div class="max-w-[75%]">
                        <div class="rounded-2xl px-3.5 py-2 text-sm shadow-sm ${bubble}">${reply}${body}${files ? `<div class="mt-1 text-xs">${files}</div>` : ''}</div>
                        <div class="mt-0.5 flex items-center gap-2 text-[10px] ${mine ? 'text-slate-400 justify-end' : 'text-slate-400'}">
                            <span>${esc(m.sender?.name ?? '')}</span><span>${time}</span>${mine ? `<span class="read-indicator" data-message="${m.id}">· ${esc(m.read_label ?? 'terkirim')}</span>` : ''}
                        </div>
                    </div></div>`;
                },

                async send() {
                    const body = this.messageBody;
                    const file = this.$refs.file?.files?.[0] ?? null;

                    if (! body && ! file) { this.lastError = 'Tulis pesan atau lampirkan berkas.'; return; }
                    if (file && file.size > 10 * 1024 * 1024) { this.lastError = 'Ukuran lampiran melebihi 10 MB.'; return; }

                    this.sending = true;
                    this.lastError = null;

                    const fd = new FormData(this.$refs.form);
                    fd.set('body', body);
                    if (this.replyTo) fd.set('reply_to_id', this.replyTo.id);
                    if (! file) fd.delete('file');

                    try {
                        const res = await fetch(@js(route('messages.store', $conversation)), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrf },
                            credentials: 'same-origin',
                            body: fd,
                        });

                        const data = await res.json().catch(() => ({}));

                        if (! res.ok) {
                            this.lastError = data.message ?? data.errors?.body?.[0] ?? 'Gagal mengirim pesan.';
                            return;
                        }

                        this.incoming.push(data.message);
                        this.latestId = Math.max(this.latestId, data.message.id);
                        this.$refs.input.value = '';
                        this.autoGrow();
                        this.replyTo = null;
                        this.clearFile();
                        this.$nextTick(() => this.scrollBottom());
                    } catch (e) {
                        this.lastError = 'Jaringan bermasalah, pesan tidak terkirim.';
                    } finally {
                        this.sending = false;
                    }
                },

                clearFile() {
                    if (this.$refs.file) this.$refs.file.value = '';
                    this.fileName = null;
                },

                async removeMessage(id) {
                    if (! confirm('Hapus pesan ini?')) return;
                    try {
                        const res = await fetch(@js(url('/percakapan/'.$conversation->id.'/pesan')) + '/' + id, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrf },
                            credentials: 'same-origin',
                        });
                        if (res.ok) {
                            document.getElementById('msg-' + id)?.remove();
                            this.incoming = this.incoming.filter((m) => m.id !== id);
                        }
                    } catch (e) { /* noop */ }
                },
            }));
        });
    </script>
    @endpush
</x-layouts.app>
