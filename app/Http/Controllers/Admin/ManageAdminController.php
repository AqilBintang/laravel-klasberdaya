<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
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
    private const MANAGEABLE_ROLES = ['ADMIN', 'VALIDATOR', 'KABUPATEN'];

    public function index(): View
    {
        $admins = User::whereIn('role', ['SUPER_ADMIN', 'ADMIN', 'VALIDATOR', 'KABUPATEN'])
            ->with('kabupatenWilayah:id,nama')
            ->orderByRaw("CASE role
                WHEN 'SUPER_ADMIN' THEN 1
                WHEN 'ADMIN'       THEN 2
                WHEN 'VALIDATOR'   THEN 3
                WHEN 'KABUPATEN'   THEN 4
                ELSE 5 END")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'kabupaten', 'kabupaten_id', 'is_active', 'created_at']);

        return view('admin.manage-admin', compact('admins'));
    }

    public function create(): View
    {
        $kabupatens = Wilayah::where('level', 2)->orderBy('nama')->get(['id', 'nama']);
        return view('admin.manage-admin-create', compact('kabupatens'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'         => ['required', Rule::in(self::MANAGEABLE_ROLES)],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'kabupaten_id' => ['required_if:role,KABUPATEN', 'nullable', 'exists:wilayah,id'],
        ], [
            'name.required'              => 'Nama wajib diisi.',
            'email.required'             => 'Email wajib diisi.',
            'email.unique'               => 'Email sudah digunakan.',
            'role.required'              => 'Role wajib dipilih.',
            'role.in'                    => 'Role tidak valid.',
            'password.required'          => 'Password wajib diisi.',
            'password.min'               => 'Password minimal 8 karakter.',
            'password.confirmed'         => 'Konfirmasi password tidak cocok.',
            'kabupaten_id.required_if'   => 'Kabupaten/kota wajib dipilih untuk role ini.',
            'kabupaten_id.exists'        => 'Kabupaten/kota tidak valid.',
        ]);

        $kabupaten = $request->kabupaten_id
            ? Wilayah::find($request->kabupaten_id)
            : null;

        User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'role'          => $request->role,
            'password_hash' => Hash::make($request->password),
            'kabupaten_id'  => $request->role === 'KABUPATEN' ? $request->kabupaten_id : null,
            'kabupaten'     => $request->role === 'KABUPATEN' ? $kabupaten?->nama : null,
            'is_active'     => true,
        ]);

        return redirect()->route('admin.manage-admin.index')
            ->with('success', "Akun \"{$request->name}\" ({$request->role}) berhasil dibuat.");
    }

    public function edit(int $id): View
    {
        $user       = User::findOrFail($id);
        $kabupatens = Wilayah::where('level', 2)->orderBy('nama')->get(['id', 'nama']);

        // Super admin tidak bisa diedit lewat halaman ini
        abort_if($user->role === 'SUPER_ADMIN' && $user->id !== auth()->id(), 403,
            'Akun Super Admin tidak dapat diedit dari halaman ini.');

        return view('admin.manage-admin-edit', compact('id', 'user', 'kabupatens'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Proteksi: tidak bisa edit super admin lain
        if ($user->role === 'SUPER_ADMIN' && $user->id !== auth()->id()) {
            return back()->withErrors(['role' => 'Akun Super Admin tidak dapat diubah.']);
        }

        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'role'         => ['required', Rule::in(self::MANAGEABLE_ROLES)],
            'is_active'    => ['boolean'],
            'password'     => ['nullable', 'string', 'min:8', 'confirmed'],
            'kabupaten_id' => ['required_if:role,KABUPATEN', 'nullable', 'exists:wilayah,id'],
        ], [
            'name.required'            => 'Nama wajib diisi.',
            'email.required'           => 'Email wajib diisi.',
            'email.unique'             => 'Email sudah digunakan akun lain.',
            'role.required'            => 'Role wajib dipilih.',
            'role.in'                  => 'Role tidak valid.',
            'password.min'             => 'Password minimal 8 karakter.',
            'password.confirmed'       => 'Konfirmasi password tidak cocok.',
            'kabupaten_id.required_if' => 'Kabupaten/kota wajib dipilih untuk role ini.',
            'kabupaten_id.exists'      => 'Kabupaten/kota tidak valid.',
        ]);

        $kabupaten = $request->kabupaten_id
            ? Wilayah::find($request->kabupaten_id)
            : null;

        $data = [
            'name'         => $request->name,
            'email'        => $request->email,
            'role'         => $request->role,
            'is_active'    => $request->boolean('is_active'),
            'kabupaten_id' => $request->role === 'KABUPATEN' ? $request->kabupaten_id : null,
            'kabupaten'    => $request->role === 'KABUPATEN' ? $kabupaten?->nama : null,
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
