<x-layouts.app :title="'Buat Pengumuman — '.$course->name">
    <x-page-header title="Buat Pengumuman" :subtitle="'Kelas: '.$course->name" />

    <div class="mb-4"><x-course-nav :course="$course" active="announcements" /></div>

    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('announcements.store', $course) }}" class="space-y-5">
                @csrf

                <div>
                    <label for="title" class="label">Judul pengumuman</label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" required maxlength="180"
                           placeholder="Contoh: Penilaian tengah semester"
                           class="input" />
                    @error('title')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="content" class="label">Isi pengumuman</label>
                    <textarea id="content" name="content" rows="8" required maxlength="20000"
                              placeholder="Tuliskan isi pengumuman…"
                              class="textarea">{{ old('content') }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Gunakan baris baru untuk memisahkan paragraf.</p>
                    @error('content')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div x-data="{ publishNow: true }" class="space-y-4">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="publish_now" value="1" x-model="publishNow"
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm font-medium text-slate-700">Terbitkan sekarang</span>
                    </label>

                    <div x-show="! publishNow" x-cloak>
                        <label for="published_at" class="label">Jadwal terbit (opsional)</label>
                        <input id="published_at" type="datetime-local" name="published_at"
                               value="{{ old('published_at') }}" :disabled="publishNow"
                               class="input" />
                        <p class="mt-1 text-xs text-slate-400">Jika dikosongkan, pengumuman langsung diterbitkan.</p>
                        @error('published_at')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned'))
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-sm font-medium text-slate-700">Sematkan di atas daftar</span>
                    </label>
                    @error('is_pinned')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="divider"></div>

                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('announcements.index', $course) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="megaphone" class="h-4 w-4" /> Terbitkan Pengumuman
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
