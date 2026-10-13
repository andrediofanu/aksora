<x-layouts.app :title="'Buat Kuis'">
    <x-page-header :title="'Buat Kuis — '.$course->name" subtitle="Atur detail, jadwal, dan pengaturan penilaian kuis.">
        <x-slot name="actions">
            <a href="{{ route('quizzes.index', $course) }}" class="btn btn-secondary">
                <x-icon name="chevron-left" class="h-4 w-4" /> Kembali
            </a>
        </x-slot>
    </x-page-header>

    <div class="mx-auto max-w-3xl">
        <x-card title="Detail Kuis">
            <form action="{{ route('quizzes.store', $course) }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="title" class="label">Judul kuis <span class="text-rose-500">*</span></label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="180"
                           class="input" placeholder="Contoh: Kuis Bab 1 — Aljabar" />
                    @error('title')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="description" class="label">Deskripsi</label>
                    <textarea id="description" name="description" rows="3" class="textarea" maxlength="5000"
                              placeholder="Petunjuk singkat untuk murid (opsional).">{{ old('description') }}</textarea>
                    @error('description')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="time_limit_minutes" class="label">Batas waktu (menit)</label>
                        <input id="time_limit_minutes" name="time_limit_minutes" type="number" min="1" max="600"
                               value="{{ old('time_limit_minutes') }}" class="input" placeholder="Kosongkan = tanpa batas" />
                        @error('time_limit_minutes')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="max_attempts" class="label">Jumlah percobaan <span class="text-rose-500">*</span></label>
                        <input id="max_attempts" name="max_attempts" type="number" min="1" max="10" required
                               value="{{ old('max_attempts', 1) }}" class="input" />
                        @error('max_attempts')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="opens_at" class="label">Waktu buka</label>
                        <input id="opens_at" name="opens_at" type="datetime-local" value="{{ old('opens_at') }}" class="input" />
                        @error('opens_at')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="closes_at" class="label">Waktu tutup</label>
                        <input id="closes_at" name="closes_at" type="datetime-local" value="{{ old('closes_at') }}" class="input" />
                        @error('closes_at')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="pass_score" class="label">Nilai kelulusan <span class="text-rose-500">*</span></label>
                        <input id="pass_score" name="pass_score" type="number" min="0" max="100" step="0.5" required
                               value="{{ old('pass_score', 70) }}" class="input" />
                        @error('pass_score')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="show_results" class="label">Tampilan hasil <span class="text-rose-500">*</span></label>
                        <select id="show_results" name="show_results" class="select" required>
                            @foreach ([
                                'immediately' => 'Langsung setelah selesai',
                                'after_deadline' => 'Setelah waktu tutup',
                                'never' => 'Tidak pernah ditampilkan',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('show_results', 'immediately') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('show_results')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="shuffle_questions" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        @checked(old('shuffle_questions')) />
                    Acak urutan soal untuk setiap murid
                </label>

                <div>
                    <label for="status" class="label">Status <span class="text-rose-500">*</span></label>
                    <select id="status" name="status" class="select" required>
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>Draf — belum terlihat murid</option>
                        <option value="published" @selected(old('status') === 'published')>Terbit — langsung tersedia murid</option>
                    </select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="divider"></div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="h-4 w-4" /> Simpan Kuis
                    </button>
                    <a href="{{ route('quizzes.index', $course) }}" class="btn btn-ghost">Batal</a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
