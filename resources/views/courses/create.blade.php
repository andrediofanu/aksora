<x-layouts.app :title="'Buat Kelas'">
    <x-page-header title="Buat Kelas" subtitle="Buat kelas baru, bagikan kode kelasnya, lalu ajak murid bergabung." />

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('courses.store') }}">
            @csrf

            <x-card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="name">Nama kelas <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="name" class="input" maxlength="150" required
                               value="{{ old('name') }}" placeholder="cth. Matematika XI IPA" />
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label" for="subject_id">Mata pelajaran</label>
                        <select name="subject_id" id="subject_id" class="select">
                            <option value="">— Tanpa mata pelajaran —</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected((string) old('subject_id') === (string) $subject->id)>
                                    {{ $subject->name }}{{ $subject->code ? ' ('.$subject->code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label" for="academic_year_id">Tahun ajaran</label>
                        <select name="academic_year_id" id="academic_year_id" class="select">
                            <option value="">— Tanpa tahun ajaran —</option>
                            @foreach ($academicYears as $academicYear)
                                <option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id') === (string) $academicYear->id)>
                                    {{ $academicYear->name }}{{ $academicYear->is_active ? ' (aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_year_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label" for="grade_level">Tingkat / rombel</label>
                        <input type="text" name="grade_level" id="grade_level" class="input" maxlength="60"
                               value="{{ old('grade_level') }}" placeholder="cth. XI IPA" />
                        @error('grade_level')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label" for="room">Ruang kelas</label>
                        <input type="text" name="room" id="room" class="input" maxlength="60"
                               value="{{ old('room') }}" placeholder="cth. R-201" />
                        @error('room')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label" for="max_members">Kapasitas murid <span class="text-rose-500">*</span></label>
                        <input type="number" name="max_members" id="max_members" class="input" required
                               min="1" max="500" value="{{ old('max_members', 40) }}" />
                        @error('max_members')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label" for="description">Deskripsi</label>
                        <textarea name="description" id="description" class="textarea" rows="4" maxlength="2000"
                                  placeholder="cth. Ringkasan materi, aturan kelas, dan tujuan pembelajaran.">{{ old('description') }}</textarea>
                        <p class="mt-1 text-xs text-slate-400">Maksimal 2000 karakter. Kosongkan jika belum ada deskripsi.</p>
                        @error('description')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-start gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="allow_student_private_message" value="1"
                                   class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked(old('allow_student_private_message', true)) />
                            <span>
                                Izinkan murid mengirim pesan pribadi
                                <span class="block text-xs text-slate-400">Murid dapat menghubungi guru melalui percakapan pribadi di dalam aplikasi.</span>
                            </span>
                        </label>
                        @error('allow_student_private_message')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="divider mt-5 flex flex-wrap items-center justify-between gap-3 pt-4">
                    <p class="text-xs text-slate-500">Kode kelas dibuat otomatis setelah kelas tersimpan.</p>
                    <div class="flex gap-2">
                        <a href="{{ route('courses.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="check" class="h-4 w-4" /> Simpan Kelas
                        </button>
                    </div>
                </div>
            </x-card>
        </form>
    </div>
</x-layouts.app>
