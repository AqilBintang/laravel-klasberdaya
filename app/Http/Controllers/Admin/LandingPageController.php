<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    /**
     * Default data untuk banner slides.
     */
    private const DEFAULT_BANNER = [
        'slides' => [
            ['id' => 'banner-1', 'imageUrl' => '', 'alt' => 'Banner 1'],
            ['id' => 'banner-2', 'imageUrl' => '', 'alt' => 'Banner 2'],
            ['id' => 'banner-3', 'imageUrl' => '', 'alt' => 'Banner 3'],
        ],
    ];

    /**
     * Default data untuk tentang platform.
     */
    private const DEFAULT_TENTANG = [
        'heading'     => 'Apa itu Klasifikasi Berdaya?',
        'description' => 'Klasifikasi Kecamatan Berdaya adalah sistem penilaian mandiri berbasis indikator yang mengukur kapasitas dan kinerja kecamatan dalam menjalankan program pemberdayaan masyarakat di Jawa Tengah.',
        'points'      => [
            'Penilaian dilakukan oleh kecamatan secara mandiri melalui self-assessment',
            'Setiap indikator divalidasi oleh tim admin untuk menjamin akurasi data',
            'Hasil klasifikasi dipublikasikan secara transparan kepada masyarakat',
            'Data digunakan sebagai dasar pengambilan kebijakan pemberdayaan wilayah',
        ],
        'imageUrl'    => '',
    ];

    public function index(): View
    {
        $banner  = $this->getSetting('landing_banner', self::DEFAULT_BANNER);
        $tentang = $this->getSetting('landing_tentang_platform', self::DEFAULT_TENTANG);

        return view('admin.landing-page', [
            'banner'  => $banner,
            'tentang' => $tentang,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $section = $request->input('section', 'banner');

        if ($section === 'banner') {
            return $this->updateBanner($request);
        }

        if ($section === 'tentang') {
            return $this->updateTentang($request);
        }

        return back()->with('error', 'Section tidak dikenali.');
    }

    private function updateBanner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slides'              => 'nullable|array',
            'slides.*.id'         => 'nullable|string',
            'slides.*.imageUrl'   => 'nullable|string',
            'slides.*.alt'        => 'nullable|string',
            'slide_files'         => 'nullable|array',
            'slide_files.*'       => 'nullable|image|max:5120',
        ]);

        $slides    = $validated['slides'] ?? [];
        $slideFiles = $request->file('slide_files', []);

        // Proses file upload untuk setiap slide yang punya file
        foreach ($slideFiles as $idx => $file) {
            if ($file && $file->isValid()) {
                $path = $file->store('banners', 'public');
                $slides[$idx]['imageUrl'] = Storage::url($path);
            }
        }

        // Bersihkan slide kosong
        $slides = array_values(array_filter($slides, fn ($s) => !empty($s['imageUrl']) || !empty($s['alt'])));

        $this->setSetting('landing_banner', ['slides' => $slides]);

        return back()
            ->with('success', 'Banner berhasil diperbarui.')
            ->with('active_tab', 'banner');
    }

    private function updateTentang(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tentang_heading'        => 'nullable|string|max:255',
            'tentang_description'    => 'nullable|string',
            'tentang_points'         => 'nullable|array',
            'tentang_points.*'       => 'nullable|string',
            'tentang_image'          => 'nullable|image|max:5120',
            'tentang_image_existing' => 'nullable|string',
        ]);

        // Ambil data yang sudah ada
        $tentang = $this->getSetting('landing_tentang_platform', self::DEFAULT_TENTANG);

        $tentang['heading']     = $validated['tentang_heading'] ?? '';
        $tentang['description'] = $validated['tentang_description'] ?? '';

        // Filter poin kosong
        $points = $validated['tentang_points'] ?? [];
        $tentang['points'] = array_values(array_filter($points, fn ($p) => !empty(trim($p))));

        // Handle upload gambar tentang
        if ($request->hasFile('tentang_image') && $request->file('tentang_image')->isValid()) {
            // Hapus gambar lama jika ada dan bukan URL eksternal
            if (!empty($tentang['imageUrl']) && str_starts_with($tentang['imageUrl'], '/storage/')) {
                $oldPath = str_replace('/storage/', '', $tentang['imageUrl']);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('tentang_image')->store('banners', 'public');
            $tentang['imageUrl'] = Storage::url($path);
        } else {
            // Pertahankan URL lama
            $tentang['imageUrl'] = $validated['tentang_image_existing'] ?? ($tentang['imageUrl'] ?? '');
        }

        $this->setSetting('landing_tentang_platform', $tentang);

        return back()
            ->with('success', 'Tentang Platform berhasil diperbarui.')
            ->with('active_tab', 'tentang');
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:5120',
        ]);

        $path = $request->file('file')->store('banners', 'public');
        $url  = Storage::url($path);

        return response()->json(['url' => $url]);
    }

    /**
     * Helper: ambil setting dari DB atau kembalikan default.
     * Model SiteSetting otomatis meng-cast kolom value sebagai array.
     */
    private function getSetting(string $key, array $default): array
    {
        $value = SiteSetting::get($key);
        return is_array($value) ? $value : $default;
    }

    /**
     * Helper: simpan setting ke DB menggunakan static helper di model.
     */
    private function setSetting(string $key, array $value): void
    {
        SiteSetting::set($key, $value);
    }
}
