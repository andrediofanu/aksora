<x-layouts.app title="Pengaturan">
    <x-page-header
        title="Pengaturan Sistem"
        description="Konfigurasi umum LMS: identitas sekolah, pendaftaran, pesan, dan batas unggah." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                <x-card title="Identitas Sekolah">
                    <div class="space-y-4">
                        <x-input name="school_name" label="Nama sekolah / lembaga"
                            :value="$settings['school_name'] ?? ''" required />
                        <x-textarea name="app_description" label="Deskripsi singkat" rows="2">{{ $settings['app_description'] ?? '' }}</x-textarea>
                    </div>
                </x-card>

                <x-card title="Pendaftaran & Keamanan">
                    <div class="space-y-4">
                        <label class="flex items-start gap-3">
                            <input type="hidden" name="registration_open" value="0">
                            <input type="checkbox" name="registration_open" value="1"
                                class="mt-1 rounded-slate-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(old('registration_open', $settings['registration_open'] ?? false))>
                            <span class="text-sm">
                                <span class="font-medium text-slate-800">Buka pendaftaran mandiri</span>
                                <span class="block text-slate-500">Izinkan pengguna baru mendaftar sendiri melalui halaman registrasi.</span>
                            </span>
                        </label>
                    </div>
                </x-card>

                <x-card title="Pesan & Interaksi Murid">
                    <div class="space-y-4">
                        <label class="flex items-start gap-3">
                            <input type="hidden" name="allow_student_private_message" value="0">
                            <input type="checkbox" name="allow_student_private_message" value="1"
                                class="mt-1 rounded-slate-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(old('allow_student_private_message', $settings['allow_student_private_message'] ?? false))>
                            <span class="text-sm">
                                <span class="font-medium text-slate-800">Murid boleh chat pribadi</span>
                                <span class="block text-slate-500">Jika dimatikan, murid hanya dapat berdiskusi di dalam kelas dan dengan guru.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3">
                            <input type="hidden" name="allow_student_join_by_code" value="0">
                            <input type="checkbox" name="allow_student_join_by_code" value="1"
                                class="mt-1 rounded-slate-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(old('allow_student_join_by_code', $settings['allow_student_join_by_code'] ?? false))>
                            <span class="text-sm">
                                <span class="font-medium text-slate-800">Murid boleh bergabung dengan kode</span>
                                <span class="block text-slate-500">Jika dimatikan, murid hanya dapat ditambahkan ke kelas oleh guru/admin.</span>
                            </span>
                        </label>
                    </div>
                </x-card>

                <x-card title="Akademik & File">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input type="number" name="max_upload_mb" label="Batas unggah (MB)"
                            :value="$settings['max_upload_mb'] ?? 10" min="1" max="64" required />
                        <div>
                            <label for="grade_rounding" class="mb-1 block text-sm font-medium text-slate-700">Pembulatan nilai</label>
                            <select id="grade_rounding" name="grade_rounding"
                                class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ([0 => 'Bilangan bulat (90)', 1 => '1 desimal (90.0)', 2 => '2 desimal (90.00)'] as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected(old('grade_rounding', $settings['grade_rounding'] ?? 0) == $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Batas unggah juga dibatasi oleh konfigurasi PHP (upload_max_filesize / post_max_size).</p>
                </x-card>

                <div class="flex justify-end gap-3">
                    <x-button type="button" variant="secondary" onclick="window.location.reload()">Batal</x-button>
                    <x-button type="submit" variant="primary">Simpan Pengaturan</x-button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <x-card title="Aktivitas Terakhir">
                @if ($logs->isEmpty())
                    <x-empty icon="clipboard-document-list" title="Belum ada aktivitas" description="" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($logs as $log)
                            <li class="py-3">
                                <p class="text-sm text-slate-700">
                                    <span class="font-medium">{{ $log->user?->name ?? 'Sistem' }}</span>
                                    {{ $log->action }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $log->created_at->diffForHumans() }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-3 text-right">
                        <a href="{{ route('admin.audit-logs.index') }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Lihat semua log →</a>
                    </div>
                @endif
            </x-card>

            <x-card title="Informasi Lingkungan">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Versi Laravel</dt>
                        <dd class="font-medium text-slate-700">{{ app()->version() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">PHP</dt>
                        <dd class="font-medium text-slate-700">{{ PHP_VERSION }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Zona waktu</dt>
                        <dd class="font-medium text-slate-700">{{ config('app.timezone') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Mode aplikasi</dt>
                        <dd class="font-medium text-slate-700">{{ ucfirst(config('app.env')) }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts.app>
