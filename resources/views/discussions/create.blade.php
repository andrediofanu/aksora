<x-layouts.app :title="'Buat Diskusi — '.$course->name">
    <x-page-header title="Buat Diskusi" :subtitle="'Kelas: '.$course->name" />

    <div class="mb-4"><x-course-nav :course="$course" active="discussions" /></div>

    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('discussions.store', $course) }}" class="space-y-5">
                @csrf

                <div>
                    <label for="title" class="label">Judul diskusi</label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" required maxlength="180"
                           placeholder="Contoh: Ada yang mengerti materi bab 3?"
                           class="input" />
                    @error('title')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="body" class="label">Pertanyaan atau topik</label>
                    <textarea id="body" name="body" rows="8" required maxlength="20000"
                              placeholder="Jelaskan topik yang ingin Anda diskusikan…"
                              class="textarea">{{ old('body') }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Gunakan baris baru untuk memisahkan paragraf.</p>
                    @error('body')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="divider"></div>

                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('discussions.index', $course) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="send" class="h-4 w-4" /> Kirim Diskusi
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
