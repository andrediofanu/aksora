<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * Manajemen pengguna (khusus admin).
 *
 * Catatan keamanan: TIDAK tersedia endpoint untuk mengubah password
 * pengguna lain secara langsung. Admin hanya dapat mengirim tautan
 * pengaturan ulang password melalui mekanisme bawaan Laravel yang
 * tervalidasi lewat email.
 */
class UserController extends Controller
{
    public function __construct()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function index(Request $request)
    {
        $query = User::query()->orderBy('name');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if (in_array($request->query('role'), Role::ALL, true)) {
            $query->where('role', $request->query('role'));
        }

        if (in_array($request->query('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->query('status'));
        }

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        return view('admin.users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'q' => trim((string) $request->query('q')),
                'role' => $request->query('role'),
                'status' => $request->query('status'),
                'trashed' => $request->boolean('trashed'),
            ],
            'roles' => Role::ALL,
            'stats' => [
                'total' => User::whereNull('deleted_at')->count(),
                'active' => User::whereNull('deleted_at')->where('status', 'active')->count(),
                'inactive' => User::whereNull('deleted_at')->where('status', 'inactive')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.users.create', ['roles' => Role::ALL]);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'status' => $data['status'],
                'phone' => $data['phone'] ?? null,
                'nis' => $data['nis'] ?? null,
                'nip' => $data['nip'] ?? null,
                'title' => $data['title'] ?? null,
                'email_verified_at' => now(),
            ]);
        });

        AuditLogger::log('admin.user.create', "Membuat akun: {$user->email} ({$user->role})", $user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$user->name} berhasil dibuat.");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', ['target' => $user, 'roles' => Role::ALL]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'],
            'phone' => $data['phone'] ?? null,
            'nis' => $data['nis'] ?? null,
            'nip' => $data['nip'] ?? null,
            'title' => $data['title'] ?? null,
        ]);

        // Password hanya boleh diubah oleh pemilik akun (ProfileController),
        // atau melalui tautan reset email yang tervalidasi.
        if ($request->boolean('send_reset_link')) {
            Password::sendResetLink(['email' => $user->email]);
            AuditLogger::log('admin.user.reset_link', "Mengirim tautan reset password ke {$user->email}", $user);

            return redirect()
                ->route('admin.users.edit', $user)
                ->with('success', "Tautan pengaturan ulang password dikirim ke {$user->email} (periksa log mail pada environment lokal).");
        }

        AuditLogger::log('admin.user.update', "Memperbarui akun: {$user->email}", $user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    /** Aktifkan/nonaktifkan akun. */
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Tidak dapat menonaktifkan akun Anda sendiri.']);
        }

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        AuditLogger::log('admin.user.status', "Status akun {$user->email} → {$user->status}", $user);

        return back()->with('success', "Akun {$user->name} kini berstatus {$user->status}.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Tidak dapat menghapus akun Anda sendiri.']);
        }

        AuditLogger::log('admin.user.delete', "Menonaktifkan (soft delete) akun: {$user->email}", $user);
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$user->name} dinonaktifkan. Data tetap tersimpan (soft delete).");
    }

    public function restore(User $user)
    {
        $user->restore();

        AuditLogger::log('admin.user.restore', "Memulihkan akun: {$user->email}", $user);

        return back()->with('success', "Akun {$user->name} dipulihkan.");
    }
}
