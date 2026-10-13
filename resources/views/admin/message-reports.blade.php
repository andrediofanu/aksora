@php($statuses = ['open' => 'Menunggu', 'resolved' => 'Selesai', 'dismissed' => 'Ditolak'])

<x-layouts.app title="Laporan Pesan">
    <x-page-header
        title="Laporan Pesan"
        description="Tinjau pesan yang dilaporkan pengguna dan ambil tindakan." />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Menunggu Tinjauan" :value="$counts['open']" icon="exclamation-triangle" color="amber" />
        <x-stat-card label="Selesai" :value="$counts['resolved']" icon="check-circle" color="emerald" />
        <x-stat-card label="Ditolak" :value="$counts['dismissed']" icon="x-mark" color="slate" />
    </div>

    <x-card>
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($statuses as $value => $label)
                <a href="{{ route('admin.message-reports.index', ['status' => $value]) }}"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition
                        {{ $status === $value ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                    <span class="ml-1 opacity-75">{{ $counts[$value] }}</span>
                </a>
            @endforeach
        </div>

        @if ($reports->isEmpty())
            <x-empty icon="chat-bubble-left-right" title="Tidak ada laporan"
                description="Belum ada laporan pesan dengan status ini." />
        @else
            <div class="space-y-4">
                @foreach ($reports as $report)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <x-badge :label="$statuses[$report->status] ?? $report->status"
                                        color="{{ $report->status === 'open' ? 'amber' : ($report->status === 'resolved' ? 'emerald' : 'slate') }}" />
                                    <span class="text-xs text-slate-500">{{ $report->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-700">
                                    Dilaporkan oleh <span class="font-medium">{{ $report->reporter?->name ?? '-' }}</span>
                                    @if ($report->reason)
                                        — <span class="italic">"{{ $report->reason }}"</span>
                                    @endif
                                </p>
                            </div>

                            @if ($report->status === 'open' && $report->message)
                                <a href="{{ route('conversations.show', $report->message->conversation_id) }}"
                                    target="_blank" rel="noopener"
                                    class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200">
                                    Lihat Percakapan
                                </a>
                            @endif
                        </div>

                        @if ($report->message)
                            <div class="mt-3 rounded-lg bg-slate-50 p-3">
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Pesan dari {{ $report->message->sender?->name ?? 'Pengguna terhapus' }}
                                    · percakapan {{ $report->message->conversation?->title ?? ($report->message->conversation?->isClass() ? 'kelas' : 'pribadi') }}
                                </p>
                                <p class="mt-1 text-sm text-slate-700">{{ \Illuminate\Support\Str::limit($report->message->body, 400) }}</p>
                            </div>
                        @else
                            <p class="mt-3 text-sm text-slate-500">Pesan sudah dihapus.</p>
                        @endif

                        @if ($report->status === 'open')
                            <form method="POST" action="{{ route('admin.message-reports.resolve', $report) }}"
                                class="mt-4 space-y-3 border-t border-slate-100 pt-3">
                                @csrf
                                <div>
                                    <label for="note-{{ $report->id }}" class="mb-1 block text-xs font-medium text-slate-500">
                                        Catatan penyelesaian (opsional)
                                    </label>
                                    <textarea id="note-{{ $report->id }}" name="resolution_note" rows="2" maxlength="1000"
                                        class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Contoh: pesan melanggar aturan, peringatan dikirim.">{{ old('resolution_note') }}</textarea>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="submit" name="status" value="dismissed"
                                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                                        Tolak Laporan
                                    </button>
                                    <button type="submit" name="status" value="resolved"
                                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                        Selesaikan
                                    </button>
                                </div>
                            </form>
                        @elseif ($report->resolution_note)
                            <p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-500">
                                Catatan: {{ $report->resolution_note }}
                                @if ($report->resolver)
                                    · oleh {{ $report->resolver->name }}
                                @endif
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $reports->links() }}</div>
        @endif
    </x-card>
</x-layouts.app>
