<x-layouts.app :title="'Anggota Kelas'">
    <x-page-header title="Anggota Kelas" :subtitle="$course->name.' · '.$memberships->total().' anggota aktif'" />

    <x-course-nav :course="$course" active="members" />

    @if ($canManage)
        <div class="mt-6">
            <x-card>
                <x-slot name="header"><h2 class="card-title">Tambah anggota</h2></x-slot>

                <form method="POST" action="{{ route('course.members.store', $course) }}"
                      class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label class="label" for="identifier">Email, NIS, atau nama murid</label>
                        <input type="text" name="identifier" id="identifier" class="input" maxlength="255" required
                               value="{{ old('identifier') }}" placeholder="cth. budi@sekolah.id atau 2024001" />
                        @error('identifier')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="plus" class="h-4 w-4" /> Tambahkan
                    </button>
                </form>
            </x-card>
        </div>
    @endif

    <div class="mt-6">
        <x-card flush>
            <x-slot name="header">
                <h2 class="card-title">Daftar anggota</h2>
                <form method="GET" action="{{ route('course.members', $course) }}" class="flex items-center gap-2">
                    <input type="search" name="q" value="{{ $search }}" class="input w-56"
                           placeholder="Cari nama atau email" aria-label="Cari anggota" />
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <x-icon name="search" class="h-4 w-4" /> Cari
                    </button>
                    @if ($search !== '')
                        <a href="{{ route('course.members', $course) }}" class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                </form>
            </x-slot>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Anggota</th>
                            <th>Peran</th>
                            <th>Bergabung</th>
                            <th>Status</th>
                            @if ($canManage)
                                <th><span class="sr-only">Aksi</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($memberships as $membership)
                            @php($member = $membership->user)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$member" size="sm" />
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-800">
                                                {{ $member?->name ?? 'Pengguna terhapus' }}
                                            </p>
                                            <p class="truncate text-xs text-slate-400">{{ $member?->email ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <x-badge :color="\App\Support\Role::badgeColor($membership->role)"
                                             :label="\App\Support\Role::label($membership->role)" />
                                </td>
                                <td class="whitespace-nowrap text-slate-500">
                                    {{ $membership->joined_at?->translatedFormat('d M Y') ?? '—' }}
                                </td>
                                <td>
                                    <x-badge color="green" label="Aktif" />
                                </td>
                                @if ($canManage)
                                    <td class="text-right">
                                        @if ($member && $membership->user_id !== $course->teacher_id)
                                            <x-confirm-delete label="Keluarkan" icon="trash"
                                                              title="Keluarkan anggota ini?"
                                                              :message="'Keluarkan '.$member->name.' dari kelas? Ia akan kehilangan akses ke materi dan tugas kelas ini.'"
                                                              :url="route('course.members.destroy', [$course, $member])"
                                                              class="btn btn-danger btn-sm" />
                                        @else
                                            <span class="text-xs text-slate-400">Guru pengampu</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 5 : 4 }}">
                                    <x-empty compact icon="users" title="Tidak ada anggota"
                                             message="Tidak ada anggota yang cocok dengan pencarian Anda." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($memberships->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $memberships->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
