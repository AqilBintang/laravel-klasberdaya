<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\View\View;

class WilayahController extends Controller
{
    public function index(): View
    {
        // Kabupaten/kota level 2, dengan anak kecamatan level 3
        $kabupatens = Wilayah::kabupaten()
            ->with(['children' => fn ($q) => $q->where('level', 3)->select('id', 'parent_id', 'nama', 'kode')])
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode', 'level']);

        // Tambahkan jumlah akun terdaftar per kabupaten
        $kabupatens->each(function ($kab) {
            $kab->registeredCount = User::where('kabupaten_id', $kab->id)
                ->where('role', 'USER')
                ->count();
        });

        $totalAkun = User::where('role', 'USER')->count();

        return view('admin.wilayah', compact('kabupatens', 'totalAkun'));
    }
}
