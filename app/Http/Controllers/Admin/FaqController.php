<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use App\Models\FaqItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    // =========================================================
    //  CATEGORIES
    // =========================================================

    /**
     * Halaman utama: daftar kategori + item FAQ.
     */
    public function index(): View
    {
        $categories = FaqCategory::withCount('items')
                                  ->orderBy('sort_order')
                                  ->orderBy('id')
                                  ->get();

        return view('admin.faq.index', compact('categories'));
    }

    /**
     * Simpan kategori baru.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        FaqCategory::create([
            'name'       => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        // Jika dikirim dari tab FAQ di landing-page, kembali ke sana
        if ($request->input('_from_landing')) {
            return redirect()->route('admin.landing-page.index', ['tab' => 'faq'])
                             ->with('success', 'Kategori FAQ berhasil ditambahkan.');
        }

        return redirect()->route('admin.faq.index')
                         ->with('success', 'Kategori FAQ berhasil ditambahkan.');
    }

    /**
     * Update kategori.
     */
    public function updateCategory(Request $request, FaqCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $category->update([
            'name'       => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        // Jika dikirim dari tab FAQ di landing-page, kembali ke sana
        if ($request->input('_from_landing')) {
            return redirect()->route('admin.landing-page.index', ['tab' => 'faq'])
                             ->with('success', 'Kategori FAQ berhasil diperbarui.');
        }

        return redirect()->route('admin.faq.index')
                         ->with('success', 'Kategori FAQ berhasil diperbarui.');
    }

    /**
     * Hapus kategori beserta semua item di dalamnya.
     */
    public function destroyCategory(Request $request, FaqCategory $category): RedirectResponse
    {
        $category->delete(); // items terhapus otomatis lewat cascadeOnDelete

        // Jika dikirim dari tab FAQ di landing-page, kembali ke sana
        if ($request->input('_from_landing')) {
            return redirect()->route('admin.landing-page.index', ['tab' => 'faq'])
                             ->with('success', 'Kategori FAQ berhasil dihapus.');
        }

        return redirect()->route('admin.faq.index')
                         ->with('success', 'Kategori FAQ berhasil dihapus.');
    }

    // =========================================================
    //  ITEMS
    // =========================================================

    /**
     * Halaman kelola item dalam satu kategori.
     */
    public function showCategory(FaqCategory $category): View
    {
        $items = $category->items()->get();

        return view('admin.faq.category', compact('category', 'items'));
    }

    /**
     * Simpan item FAQ baru.
     */
    public function storeItem(Request $request, FaqCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'question'   => 'required|string',
            'answer'     => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $category->items()->create([
            'question'   => $validated['question'],
            'answer'     => $validated['answer'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.faq.category.show', $category)
                         ->with('success', 'Pertanyaan FAQ berhasil ditambahkan.');
    }

    /**
     * Update item FAQ.
     */
    public function updateItem(Request $request, FaqCategory $category, FaqItem $item): RedirectResponse
    {
        // Pastikan item memang milik kategori ini
        abort_if($item->faq_category_id !== $category->id, 404);

        $validated = $request->validate([
            'question'   => 'required|string',
            'answer'     => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $item->update([
            'question'   => $validated['question'],
            'answer'     => $validated['answer'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.faq.category.show', $category)
                         ->with('success', 'Pertanyaan FAQ berhasil diperbarui.');
    }

    /**
     * Hapus item FAQ.
     */
    public function destroyItem(FaqCategory $category, FaqItem $item): RedirectResponse
    {
        abort_if($item->faq_category_id !== $category->id, 404);

        $item->delete();

        return redirect()->route('admin.faq.category.show', $category)
                         ->with('success', 'Pertanyaan FAQ berhasil dihapus.');
    }
}
