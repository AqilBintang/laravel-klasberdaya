<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Periksa apakah user yang login memiliki salah satu dari role yang diizinkan.
     *
     * Role di DB         : SUPER_ADMIN | ADMIN | VALIDATOR | USER
     * Alias di route     : super_admin | admin | validator  | kecamatan
     *
     * Contoh pemakaian di route:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,super_admin')
     *   ->middleware('role:kecamatan')
     *
     * @param  string  ...$roles  Satu atau lebih alias role yang diizinkan
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            // Arahkan ke halaman login yang sesuai berdasarkan URL prefix
            if ($request->is('admin/*') || $request->is('validator/*')) {
                return redirect()->route('admin.login');
            }
            return redirect()->route('kecamatan.login');
        }

        $userRole = Auth::user()->role; // nilai DB: SUPER_ADMIN, ADMIN, VALIDATOR, USER

        // Peta dari alias route → nilai role di DB
        $roleMap = [
            'super_admin' => 'SUPER_ADMIN',
            'admin'       => 'ADMIN',
            'validator'   => 'VALIDATOR',
            'kecamatan'   => 'USER',        // role USER di DB = kecamatan di aplikasi
        ];

        // Resolve alias ke nilai DB; jika alias tidak dikenal, coba cocokkan langsung
        $allowedDbRoles = array_map(
            fn(string $alias) => $roleMap[$alias] ?? strtoupper($alias),
            $roles
        );

        // SUPER_ADMIN selalu bisa akses semua area admin & validator
        if ($userRole === 'SUPER_ADMIN' && !in_array('kecamatan', $roles)) {
            return $next($request);
        }

        if (!in_array($userRole, $allowedDbRoles)) {
            // Redirect ke area yang benar berdasarkan role user
            return match ($userRole) {
                'SUPER_ADMIN', 'ADMIN' => redirect()->route('admin.dashboard'),
                'VALIDATOR'            => redirect()->route('validator.dashboard'),
                default                => redirect()->route('kecamatan.dashboard'),
            };
        }

        return $next($request);
    }
}
