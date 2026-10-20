<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use Illuminate\View\View;

class FaqController extends Controller
{
    /**
     * Halaman FAQ publik.
     * Mengambil data dari database: kategori aktif dengan item aktif.
     */
    public function index(): View
    {
        // Ambil kategori aktif beserta item aktif, urut berdasarkan sort_order
        $categories = FaqCategory::active()
                                 ->with(['activeItems' => function ($query) {
                                     $query->orderBy('sort_order')->orderBy('id');
                                 }])
                                 ->get();

        // Transform data ke format yang dibutuhkan view
        $faqData = $categories->map(function ($category) {
            return [
                'category' => $category->name,
                'items'    => $category->activeItems->map(function ($item) {
                    return [
                        'question' => $item->question,
                        'answer'   => $item->answer,
                    ];
                })->toArray(),
            ];
        })->toArray();

        return view('landing.faq', compact('faqData'));
    }
}
