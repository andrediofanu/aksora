@php($target = $target ?? $user)
<x-layouts.app :title="'Ubah Pengguna'">
    <x-page-header :title="'Ubah Pengguna — '.$target->name" :subtitle="$target->email">
        <x-slot name="actions">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Kembali</a>
        </x-slot>
    </x-page-header>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.users.update', $target) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="label" for="name">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $target->name) }}" class="input" required maxlength="120" />
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="email">Email <span class="text-rose-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $target->email) }}" class="input" required maxlength="255" />
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="role">Peran <span class="text-rose-500">*</span></label>
                    <select id="role" name="role" class="select" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', $target->role) === $role)>{{ \App\Support\Role::label($role) }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="status">Status <span class="text-rose-500">*</span></label>
                    <select id="status" name="status" class="select" required>
                        <option value="active" @selected(old('status', $target->status) === 'active')>Aktif</option>
                        <option value="inactive" @selected(old('status', $target->status) === 'inactive')>Nonaktif</option>
                    </select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="label" for="nis">NIS (murid)</label>
                    <input type="text" id="nis" name="nis" value="{{ old('nis', $target->nis) }}" class="input" maxlength="30" />
                    @error('nis')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="nip">NIP (guru)</label>
                    <input type="text" id="nip" name="nip" value="{{ old('nip', $target->nip) }}" class="input" maxlength="30" />
                    @error('nip')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="title">Gelar</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $target->title) }}" class="input" maxlength="60" />
                    @error('title')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="label" for="phone">Telepon</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $target->phone) }}" class="input" maxlength="30" />
                @error('phone')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-700">Password</p>
                <p class="mt-1 text-xs text-slate-500">
                    Admin tidak dapat mengubah password pengguna secara langsung. Kosongkan kolom ini kecuali Anda mengganti password
                    atas permintaan pemilik akun, atau gunakan tautan reset email.
                </p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="password">Password Baru</label>
                        <input type="password" id="password" name="password" class="input" minlength="8" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah" />
                        @error('password')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="password_confirmation">Konfirmasi Password Baru</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="input" minlength="8" autocomplete="new-password" />
                    </div>
                </div>
                <label class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="send_reset_link" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                    Kirim tautan pengaturan ulang password ke email {{ $target->email }}
                </label>
                @error('send_reset_link')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
