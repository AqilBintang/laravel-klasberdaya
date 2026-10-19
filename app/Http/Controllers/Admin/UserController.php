<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::where('role', 'USER')
            ->with(['kabupatenWilayah:id,nama', 'kecamatanWilayah:id,nama'])
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $kabupatens = Wilayah::kabupaten()
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        return view('admin.users.create', compact('kabupatens'));
    }

    /**
     * AJAX: kembalikan daftar kecamatan berdasarkan kabupaten_id.
     */
    public function kecamatanByKabupaten(Request $request): JsonResponse
    {
        $kabupatenId = $request->query('kabupaten_id');

        if (!$kabupatenId) {
            return response()->json([]);
        }

        $kecamatans = Wilayah::where('parent_id', $kabupatenId)
            ->where('level', 3)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        return response()->json($kecamatans);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'email', 'max:150', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'kabupaten_id'          => ['required', 'exists:wilayah,id'],
            'kecamatan_id'          => ['required', 'exists:wilayah,id'],
        ], [
            'name.required'         => 'Nama lengkap wajib diisi.',
            'email.required'        => 'Email wajib diisi.',
            'email.unique'          => 'Email sudah digunakan.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal 8 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
            'kabupaten_id.required' => 'Kabupaten/kota wajib dipilih.',
            'kabupaten_id.exists'   => 'Kabupaten/kota tidak valid.',
            'kecamatan_id.required' => 'Kecamatan wajib dipilih.',
            'kecamatan_id.exists'   => 'Kecamatan tidak valid.',
        ]);

        // Ambil nama kabupaten dan kecamatan untuk kolom legacy
        $kabupaten = Wilayah::find($validated['kabupaten_id']);
        $kecamatan = Wilayah::find($validated['kecamatan_id']);

        User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password_hash'=> Hash::make($validated['password']),
            'role'         => 'USER',
            'kabupaten_id' => $validated['kabupaten_id'],
            'kecamatan_id' => $validated['kecamatan_id'],
            'kabupaten'    => $kabupaten?->nama,   // kolom legacy
            'kecamatan'    => $kecamatan?->nama,   // kolom legacy
            'is_active'    => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$validated['name']} berhasil ditambahkan.");
    }

    public function show(string $id): View
    {
        return view('admin.users.show', compact('id'));
    }

    public function edit(string $id): View
    {
        return view('admin.users.edit', compact('id'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        // TODO: update user
        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        // TODO: hapus user
        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }
}
