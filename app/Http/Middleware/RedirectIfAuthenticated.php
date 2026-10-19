<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RedirectIfAuthenticated
 *
 * Setara dengan logika NextJS middleware.ts untuk halaman login:
 *
 *   if (pathname === '/admin/login' && session) {
 *     if (role === 'ADMIN' || role === 'SUPER_ADMIN') → /admin/dashboard
 *     if (role === 'VALIDATOR')                       → /validator/dashboard
 *     else                                            → /kecamatan/login  (USER salah portal)
 *   }
 *
 *   if (pathname === '/kecamatan/login' && session) {
 *     if (role === 'ADMIN' || role === 'SUPER_ADMIN') → /admin/dashboard
 *     if (role === 'VALIDATOR')                       → /validator/dashboard
 *     else                                            → /kecamatan/dashboard
 *   }
 *
 * Alias di bootstrap/app.php  :  'guest_redirect'
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $role = Auth::user()->role;

        return match ($role) {
            'SUPER_ADMIN', 'ADMIN' => redirect()->route('admin.dashboard'),
            'VALIDATOR'            => redirect()->route('validator.dashboard'),
            default                => redirect()->route('kecamatan.dashboard'), // USER
        };
    }
}
