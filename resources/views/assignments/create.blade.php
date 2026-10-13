<x-layouts.app :title="'Buat Tugas'">
    <x-page-header :title="'Tambah Tugas — '.$course->name" subtitle="Buat tugas baru untuk kelas ini">
        <x-slot name="actions">
            <a href="{{ route('assignments.index', $course) }}" class="btn btn-secondary">Batal</a>
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="assignments" class="mb-6" />

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('assignments.store', $course) }}" enctype="multipart/form-data">
            @csrf

            <div>
                <label class="label" for="title">Judul Tugas <span class="text-rose-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" class="input" maxlength="180" required autofocus />
                @error('title')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4">
                <label class="label" for="instructions">Instruksi</label>
                <textarea id="instructions" name="instructions" rows="6" class="textarea" maxlength="20000" placeholder="Jelaskan apa yang harus dikerjakan murid…">{{ old('instructions') }}</textarea>
                @error('instructions')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="submission_type">Jenis Pengumpulan <span class="text-rose-500">*</span></label>
                    <select id="submission_type" name="submission_type" class="select" required>
                        <option value="text" @selected(old('submission_type') === 'text')>Teks</option>
                        <option value="file" @selected(old('submission_type') === 'file')>Berkas</option>
                        <option value="both" @selected(old('submission_type') === 'both')>Teks &amp; Berkas</option>
                    </select>
                    @error('submission_type')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="due_at">Tenggat Waktu</label>
                    <input type="datetime-local" id="due_at" name="due_at" value="{{ old('due_at') }}" class="input" />
                    <p class="mt-1 text-xs text-slate-400">Kosongkan jika tanpa tenggat.</p>
                    @error('due_at')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="label" for="max_score">Nilai Maksimum <span class="text-rose-500">*</span></label>
                    <input type="number" id="max_score" name="max_score" value="{{ old('max_score', 100) }}" class="input" min="1" max="1000" step="0.5" required />
                    @error('max_score')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="weight">Bobot Poin <span class="text-rose-500">*</span></label>
                    <input type="number" id="weight" name="weight" value="{{ old('weight', 1) }}" class="input" min="0.01" max="100" step="0.01" required />
                    @error('weight')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="status">Status <span class="text-rose-500">*</span></label>
                    <select id="status" name="status" class="select" required>
                        <option value="draft" @selected(old('status') === 'draft')>Draf</option>
                        <option value="published" @selected(old('status', 'published') === 'published')>Terbit</option>
                    </select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4 space-y-3">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="allow_late" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('allow_late')) />
                    Izinkan pengumpulan terlambat
                </label>
                <div>
                    <label class="label" for="allowed_extensions">Ekstensi Berkas yang Diizinkan (opsional)</label>
                    <input type="text" id="allowed_extensions" name="allowed_extensions[]" class="input" placeholder="pdf, docx, png" value="{{ old('allowed_extensions.0') }}" />
                    <p class="mt-1 text-xs text-slate-400">Pisahkan dengan koma. Kosongkan untuk mengikuti bawaan sistem (PDF, DOC, DOCX, PPT, PPTX, TXT, PNG, JPG, ZIP).</p>
                    @error('allowed_extensions')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4">
                <label class="label" for="rubric">Rubrik Penilaian (opsional)</label>
                <textarea id="rubric" name="rubric" rows="5" class="textarea" maxlength="20000" placeholder="Contoh: Kelengkapan isi (40), Ketepatan (40), Presentasi (20)">{{ old('rubric') }}</textarea>
                @error('rubric')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4">
                <label class="label" for="file">Lampiran Tugas (opsional)</label>
                <input type="file" id="file" name="file" class="input" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.png,.jpg,.jpeg,.zip" />
                <p class="mt-1 text-xs text-slate-400">Maksimal 10 MB.</p>
                @error('file')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('assignments.index', $course) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Tugas</button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
