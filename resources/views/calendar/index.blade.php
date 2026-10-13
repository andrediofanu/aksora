<x-layouts.app :title="'Kalender'">
    @php
        $user = auth()->user();

        // Hanya kelas yang bisa dikelola (guru/admin) yang boleh menerima agenda baru.
        $manageableCourses = $courses->filter(fn ($c) => $user->can('manageContent', $c))->values();
        $storeUrls = $manageableCourses->mapWithKeys(fn ($c) => [$c->id => route('calendar.store', $c)]);

        // Seragamkan data event untuk ditampilkan di Alpine.
        $manageableIds = $manageableCourses->pluck('id');
        $calendarItems = collect($events)->map(function ($e) use ($manageableIds) {
            $numericId = (int) substr((string) $e['id'], 4); // format 'evt-123'
            $canDelete = $e['type'] === \App\Models\CalendarEvent::TYPE_AGENDA
                && $manageableIds->contains($e['course_id']);

            return [
                'id' => $e['id'],
                'date' => $e['starts_at']->toDateString(),
                'title' => $e['title'],
                'time' => $e['starts_at']->format('H:i'),
                'ends' => $e['ends_at']?->format('H:i'),
                'type' => $e['type'],
                'course' => $e['course'],
                'url' => $e['url'],
                'delete_url' => $canDelete ? route('calendar.destroy', $numericId) : null,
            ];
        })->values();

        $eventsByDate = $calendarItems->groupBy('date');

        // Grid bulanan (mulai Senin) untuk bulan $from.
        $monthStart = $from->copy()->startOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek();
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek();
        $gridDays = [];
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $gridDays[] = [
                'date' => $d->toDateString(),
                'day' => $d->day,
                'inMonth' => $d->month === $monthStart->month,
                'isToday' => $d->isToday(),
            ];
        }

        $typeMeta = [
            'agenda' => ['label' => 'Agenda', 'dot' => 'bg-indigo-500', 'badge' => 'indigo'],
            'assignment' => ['label' => 'Tenggat tugas', 'dot' => 'bg-rose-500', 'badge' => 'red'],
            'quiz' => ['label' => 'Kuis', 'dot' => 'bg-amber-500', 'badge' => 'amber'],
        ];

        $prevFrom = $from->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $nextFrom = $from->copy()->addMonthNoOverflow()->startOfMonth()->toDateString();
    @endphp

    <x-page-header title="Kalender" subtitle="Tenggat tugas, jadwal kuis, dan agenda kelas Anda.">
        <x-slot name="actions">
            @if ($manageableCourses->isNotEmpty())
                <button type="button" @click="$dispatch('open-modal', 'agenda-modal')" class="btn btn-primary">
                    <x-icon name="plus" class="h-4 w-4" /> Tambah Agenda
                </button>
            @endif
        </x-slot>
    </x-page-header>

    <div
        x-data="{
            open: null,
            pendingDelete: null,
            selectedDate: null,
            events: @js($calendarItems),
            monthLabel: @js($from->translatedFormat('F Y')),
            storeUrls: @js($storeUrls),
            form: {
                course: {{ ($manageableCourses->first()?->id) ?? 'null' }},
                title: '',
                description: '',
                starts_at: '',
                ends_at: '',
            },
            eventsFor(date) {
                return this.events.filter(e => e.date === date);
            },
            openDay(date) {
                this.selectedDate = date;
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'day-modal' }));
            },
            prettyDate(date) {
                if (!date) return '';
                const [y, m, d] = date.split('-').map(Number);
                return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
                    .format(new Date(y, m - 1, d));
            },
            typeMeta(type) {
                return {
                    agenda: { label: 'Agenda', dot: 'bg-indigo-500' },
                    assignment: { label: 'Tenggat tugas', dot: 'bg-rose-500' },
                    quiz: { label: 'Kuis', dot: 'bg-amber-500' },
                }[type] || { label: 'Acara', dot: 'bg-slate-400' };
            },
        }"
    >
        <x-card :flush="true">
            {{-- Navigasi bulan --}}
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                <a href="{{ route('calendar.index', ['from' => $prevFrom]) }}" class="btn btn-secondary btn-sm">
                    <x-icon name="chevron-left" class="h-4 w-4" /> Bulan lalu
                </a>
                <h2 class="text-sm font-semibold capitalize text-slate-800">{{ $from->translatedFormat('F Y') }}</h2>
                <a href="{{ route('calendar.index', ['from' => $nextFrom]) ?>" class="btn btn-secondary btn-sm">
                    Bulan berikutnya <x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            </div>

            {{-- Grid bulan --}}
            <div class="p-4">
                <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">
                    @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
                        <div class="py-1">{{ $weekday }}</div>
                    @endforeach
                </div>
                <div class="mt-1 grid grid-cols-7 gap-1">
                    @foreach ($gridDays as $gridDay)
                        @php($dayEvents = $eventsByDate->get($gridDay['date']))
                        <button type="button"
                                @click="openDay('{{ $gridDay['date'] }}')"
                                @class([
                                    'min-h-[5.5rem] rounded-lg border p-1.5 text-left transition align-top hover:border-indigo-300 hover:bg-indigo-50/40',
                                    'border-slate-200 bg-white' => $gridDay['inMonth'],
                                    'border-slate-100 bg-slate-50/60' => ! $gridDay['inMonth'],
                                    'ring-2 ring-indigo-500' => $gridDay['isToday'],
                                ])>
                            <span @class([
                                'inline-flex h-6 min-w-6 items-center justify-center rounded-full px-1 text-xs font-semibold',
                                'bg-indigo-600 text-white' => $gridDay['isToday'],
                                'text-slate-700' => ! $gridDay['isToday'] && $gridDay['inMonth'],
                                'text-slate-400' => ! $gridDay['inMonth'] && ! $gridDay['isToday'],
                            ])>{{ $gridDay['day'] }}</span>

                            <div class="mt-1 space-y-1">
                                @if ($dayEvents)
                                    @foreach ($dayEvents->take(2) as $dayEvent)
                                        <div class="flex items-center gap-1 rounded px-1 py-0.5 text-[10px] leading-tight @if ($dayEvent['type'] === 'assignment') bg-rose-50 text-rose-700 @elseif ($dayEvent['type'] === 'quiz') bg-amber-50 text-amber-800 @else bg-indigo-50 text-indigo-700 @endif">
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $typeMeta[$dayEvent['type']]['dot'] ?? 'bg-slate-400' }}"></span>
                                            <span class="truncate">{{ $dayEvent['title'] }}</span>
                                        </div>
                                    @endforeach
                                    @if ($dayEvents->count() > 2)
                                        <p class="px-1 text-[10px] font-medium text-slate-400">+{{ $dayEvents->count() - 2 }} lagi</p>
                                    @endif
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Legenda --}}
                <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    @foreach ($typeMeta as $meta)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full {{ $meta['dot'] }}"></span> {{ $meta['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </x-card>

        {{-- Modal detail hari --}}
        <x-modal id="day-modal" title="Agenda Hari Ini" max-width="lg">
            <p class="text-sm font-semibold text-slate-700" x-text="prettyDate(selectedDate)"></p>
            <ul class="mt-3 divide-y divide-slate-100">
                <template x-if="eventsFor(selectedDate).length === 0">
                    <li class="py-4 text-center text-sm text-slate-500">Tidak ada agenda pada hari ini.</li>
                </template>
                <template x-for="e in eventsFor(selectedDate)" :key="e.id">
                    <li class="flex items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="h-2 w-2 shrink-0 rounded-full" :class="typeMeta(e.type).dot"></span>
                                <p class="truncate text-sm font-medium text-slate-800" x-text="e.title"></p>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">
                                <span x-text="e.course || '—'"></span>
                                · <span x-text="e.time"></span>
                                <template x-if="e.ends"><span> – <span x-text="e.ends"></span></span></template>
                                · <span x-text="typeMeta(e.type).label"></span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <a x-show="e.url" :href="e.url" class="btn btn-secondary btn-sm">Lihat</a>
                            <button type="button" x-show="e.delete_url"
                                    @click="pendingDelete = e.delete_url; $dispatch('open-modal', 'delete-event-modal')"
                                    class="btn btn-ghost btn-sm text-rose-600 hover:bg-rose-50">
                                <x-icon name="trash" class="h-4 w-4" />
                            </button>
                        </div>
                    </li>
                </template>
            </ul>
        </x-modal>

        {{-- Modal konfirmasi hapus agenda --}}
        <x-modal id="delete-event-modal" title="Hapus Agenda?">
            <p class="text-sm text-slate-600">Agenda ini akan dihapus permanen dari kalender kelas.</p>
            <form :action="pendingDelete" method="POST" class="mt-4">
                @csrf
                @method('DELETE')
                <div class="flex justify-end gap-2">
                    <button type="button" @click="open = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, hapus</button>
                </div>
            </form>
        </x-modal>

        {{-- Modal tambah agenda --}}
        @if ($manageableCourses->isNotEmpty())
            <x-modal id="agenda-modal" title="Tambah Agenda" max-width="lg">
                <form :action="storeUrls[form.course]" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="agenda-course" class="label">Kelas</label>
                        <select id="agenda-course" name="course" x-model.number="form.course" class="select" required>
                            @foreach ($manageableCourses as $manageableCourse)
                                <option value="{{ $manageableCourse->id }}">{{ $manageableCourse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="agenda-title" class="label">Judul agenda</label>
                        <input id="agenda-title" type="text" name="title" x-model="form.title" required maxlength="180"
                               placeholder="Contoh: Latihan soal bab 4" class="input" />
                    </div>
                    <div>
                        <label for="agenda-description" class="label">Keterangan (opsional)</label>
                        <textarea id="agenda-description" name="description" rows="3" x-model="form.description" maxlength="2000"
                                  class="textarea"></textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="agenda-starts" class="label">Waktu mulai</label>
                            <input id="agenda-starts" type="datetime-local" name="starts_at" x-model="form.starts_at" required
                                   class="input" />
                        </div>
                        <div>
                            <label for="agenda-ends" class="label">Waktu selesai (opsional)</label>
                            <input id="agenda-ends" type="datetime-local" name="ends_at" x-model="form.ends_at"
                                   class="input" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" @click="open = false" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="calendar" class="h-4 w-4" /> Simpan Agenda
                        </button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>
</x-layouts.app>
