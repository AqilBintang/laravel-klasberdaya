<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Redirect setelah login berhasil berdasarkan role.
     * Sesuai logika middleware NextJS:
     *   SUPER_ADMIN | ADMIN  → /admin/dashboard
     *   VALIDATOR            → /validator/dashboard
     *   USER (kecamatan)     → /kecamatan/dashboard
     */
    private function redirectByRole(): RedirectResponse
    {
        $role = Auth::user()->role;

        return match ($role) {
            'SUPER_ADMIN', 'ADMIN' => redirect()->route('admin.dashboard'),
            'VALIDATOR'            => redirect()->route('validator.dashboard'),
            default                => redirect()->route('kecamatan.dashboard'),
        };
    }

    // ── Login Kecamatan (/kecamatan/login) ────────────────────────────────

    public function createKecamatan(): View
    {
        return view('auth.kecamatan-login');
    }

    public function storeKecamatan(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException) {
            return redirect()->route('kecamatan.login')
                ->withInput($request->only('email'))
                ->with('error', 'Email atau password salah.');
        }

        $request->session()->regenerate();

        // Halaman ini khusus kecamatan (USER).
        // Tolak ADMIN, SUPER_ADMIN, dan VALIDATOR — setara logika isAdminError di NextJS.
        $role = Auth::user()->role;
        if (in_array($role, ['ADMIN', 'SUPER_ADMIN', 'VALIDATOR'])) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('kecamatan.login')
                ->with('error', 'Email atau password salah.');
        }

        return $this->redirectByRole();
    }

    public function destroyKecamatan(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('kecamatan.login');
    }

    // ── Login Admin & Validator (/admin/login) ────────────────────────────

    public function createAdmin(): View
    {
        return view('auth.admin-login');
    }

    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException) {
            return redirect()->route('admin.login')
                ->withInput($request->only('email'))
                ->with('error', 'Email atau password salah.');
        }

        $request->session()->regenerate();

        // Halaman ini untuk ADMIN, SUPER_ADMIN, dan VALIDATOR.
        // Tolak USER (kecamatan) — setara logika di AdminLoginForm.tsx.
        $role = Auth::user()->role;
        if ($role === 'USER') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')
                ->with('error', 'Akun ini tidak memiliki akses.');
        }

        return $this->redirectByRole();
    }

    public function destroyAdmin(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
