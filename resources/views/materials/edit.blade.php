<x-layouts.app :title="'Ubah Materi'">
    <x-page-header :title="'Ubah Materi — '.$course->name" :subtitle="$material->title">
        <x-slot name="actions">
            <a href="{{ route('materials.show', [$course, $material]) }}" class="btn btn-secondary">Batal</a>
        </x-slot>
    </x-page-header>

    <x-course-nav :course="$course" active="materials" class="mb-6" />

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('materials.update', [$course, $material]) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div>
                <label class="label" for="title">Judul Materi <span class="text-rose-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $material->title) }}" class="input" maxlength="180" required autofocus />
                @error('title')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="topic">Topik</label>
                    <input type="text" id="topic" name="topic" value="{{ old('topic', $material->topic) }}" class="input" maxlength="120" />
                    @error('topic')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="sort_order">Urutan Tampil</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $material->sort_order) }}" class="input" min="0" max="9999" />
                    @error('sort_order')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4">
                <label class="label" for="description">Ringkasan</label>
                <textarea id="description" name="description" rows="2" class="textarea" maxlength="2000">{{ old('description', $material->description) }}</textarea>
                @error('description')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4">
                <label class="label" for="content">Isi Materi</label>
                <textarea id="content" name="content" rows="10" class="textarea" maxlength="65000">{{ old('content', $material->content) }}</textarea>
                @error('content')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4">
                <label class="label" for="external_url">Tautan Eksternal (opsional)</label>
                <input type="url" id="external_url" name="external_url" value="{{ old('external_url', $material->external_url) }}" class="input" maxlength="500" placeholder="https://…" />
                @error('external_url')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2" x-data="materialForm()">
                <div>
                    <label class="label" for="status">Status Publikasi <span class="text-rose-500">*</span></label>
                    <select id="status" name="status" class="select" x-model="status">
                        <option value="draft" @selected(old('status', $material->status) === 'draft')>Draf</option>
                        <option value="published" @selected(old('status', $material->status) === 'published')>Terbit Sekarang</option>
                        <option value="scheduled" @selected(old('status', $material->status) === 'scheduled')>Terjadwal</option>
                    </select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div x-show="status === 'scheduled'" x-cloak>
                    <label class="label" for="published_at">Jadwal Terbit <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" id="published_at" name="published_at"
                           value="{{ old('published_at', $material->published_at?->format('Y-m-d\TH:i')) }}" class="input" />
                    @error('published_at')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            @if ($material->attachments->isNotEmpty())
                <div class="mt-4">
                    <p class="label">Lampiran Saat Ini</p>
                    <ul class="space-y-1">
                        @foreach ($material->attachments as $attachment)
                            <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                <a href="{{ route('attachments.download', $attachment) }}" class="link truncate">{{ $attachment->original_name }}</a>
                                <span class="shrink-0 text-xs text-slate-400">{{ $attachment->size >= 1048576 ? round($attachment->size / 1048576, 1).' MB' : round($attachment->size / 1024).' KB' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-4">
                <label class="label" for="file">Tambah Lampiran (opsional)</label>
                <input type="file" id="file" name="file" class="input" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.png,.jpg,.jpeg,.zip" />
                <p class="mt-1 text-xs text-slate-400">Format: PDF, DOC, DOCX, PPT, PPTX, TXT, PNG, JPG, ZIP. Maksimal 10 MB. Lampiran baru ditambahkan ke lampiran yang sudah ada.</p>
                @error('file')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('materials.show', [$course, $material]) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </x-card>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('materialForm', () => ({ status: @js(old('status', $material->status)) }));
        });
    </script>
    @endpush
</x-layouts.app>
