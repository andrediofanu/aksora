<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga role-based access control.
 * Penggunaan: ->middleware('role:admin') atau 'role:admin,teacher'.
 * Didaftarkan sebagai alias "role" di bootstrap/app.php.
 */
class EnsureUserHasRole
{
    /**
     * @param  string  $roles  Daftar role yang diizinkan, dipisah koma.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
