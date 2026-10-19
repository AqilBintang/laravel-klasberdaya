<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * ManageAdminController
 * Hanya SUPER_ADMIN yang bisa mengakses (middleware role:super_admin di routes).
 * Mengelola user internal: ADMIN dan VALIDATOR.
 * SUPER_ADMIN tidak bisa dihapus atau diubah rolenya menjadi lebih rendah oleh controller ini.
 */
class ManageAdminController extends Controller
{
    // Role yang bisa dikelola oleh super admin
    private const MANAGEABLE_ROLES = ['ADMIN', 'VALIDATOR'];

    public function index(): View
    {
        $admins = User::whereIn('role', ['SUPER_ADMIN', 'ADMIN', 'VALIDATOR'])
            ->orderByRaw("CASE role
                WHEN 'SUPER_ADMIN' THEN 1
                WHEN 'ADMIN'       THEN 2
                WHEN 'VALIDATOR'   THEN 3
                ELSE 4 END")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']);

        return view('admin.manage-admin', compact('admins'));
    }

    public function create(): View
    {
        return view('admin.manage-admin-create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'     => ['required', Rule::in(self::MANAGEABLE_ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'         => 'Nama wajib diisi.',
            'email.required'        => 'Email wajib diisi.',
            'email.unique'          => 'Email sudah digunakan.',
            'role.required'         => 'Role wajib dipilih.',
            'role.in'               => 'Role tidak valid.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal 8 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
        ]);

        User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'role'          => $request->role,
            'password_hash' => Hash::make($request->password),
            'is_active'     => true,
        ]);

        return redirect()->route('admin.manage-admin.index')
            ->with('success', "Akun \"{$request->name}\" ({$request->role}) berhasil dibuat.");
    }

    public function edit(int $id): View
    {
        $user = User::findOrFail($id);

        // Super admin tidak bisa diedit lewat halaman ini
        abort_if($user->role === 'SUPER_ADMIN' && $user->id !== auth()->id(), 403,
            'Akun Super Admin tidak dapat diedit dari halaman ini.');

        return view('admin.manage-admin-edit', compact('id', 'user'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Proteksi: tidak bisa edit super admin lain
        if ($user->role === 'SUPER_ADMIN' && $user->id !== auth()->id()) {
            return back()->withErrors(['role' => 'Akun Super Admin tidak dapat diubah.']);
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'role'     => ['required', Rule::in(self::MANAGEABLE_ROLES)],
            'is_active' => ['boolean'],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.unique'       => 'Email sudah digunakan akun lain.',
            'role.required'      => 'Role wajib dipilih.',
            'role.in'            => 'Role tidak valid.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.manage-admin.index')
            ->with('success', "Akun \"{$user->name}\" berhasil diperbarui.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Tidak bisa hapus diri sendiri
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'Tidak dapat menghapus akun Anda sendiri.']);
        }

        // Tidak bisa hapus super admin
        if ($user->role === 'SUPER_ADMIN') {
            return back()->withErrors(['delete' => 'Akun Super Admin tidak dapat dihapus.']);
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.manage-admin.index')
            ->with('success', "Akun \"{$name}\" berhasil dihapus.");
    }
}
