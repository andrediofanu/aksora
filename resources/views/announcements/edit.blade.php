<x-layouts.app :title="'Ubah Pengumuman — '.$course->name">
    <x-page-header title="Ubah Pengumuman" :subtitle="$announcement->title" />

    <div class="mb-4"><x-course-nav :course="$course" active="announcements" /></div>

    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('announcements.update', [$course, $announcement]) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="title" class="label">Judul pengumuman</label>
                    <input id="title" type="text" name="title" value="{{ old('title', $announcement->title) }}" required maxlength="180"
                           class="input" />
                    @error('title')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="content" class="label">Isi pengumuman</label>
                    <textarea id="content" name="content" rows="8" required maxlength="20000"
                              class="textarea">{{ old('content', $announcement->content) }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Gunakan baris baru untuk memisahkan paragraf.</p>
                    @error('content')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="published_at" class="label">Jadwal terbit</label>
                    <input id="published_at" type="datetime-local" name="published_at"
                           value="{{ old('published_at', $announcement->published_at?->format('Y-m-d\TH:i')) }}"
                           class="input" />
                    <p class="mt-1 text-xs text-slate-400">
                        @if ($announcement->published_at)
                            Saat ini terbit {{ $announcement->published_at->translatedFormat('d F Y, H:i') }}.
                            Kosongkan untuk mempertahankan jadwal tersebut.
                        @else
                            Pengumuman ini diterbitkan langsung tanpa jadwal.
                        @endif
                    </p>
                    @error('published_at')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="is_pinned" value="1"
                           @checked(old('is_pinned', $announcement->is_pinned))
                           class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm font-medium text-slate-700">Sematkan di atas daftar</span>
                </label>
                @error('is_pinned')<p class="field-error">{{ $message }}</p>@enderror

                <div class="divider"></div>

                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('announcements.show', [$course, $announcement]) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" /> Simpan Perubahan
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
