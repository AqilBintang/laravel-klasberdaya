{{--
    admin/panduan/index.blade.php
    Tampil ketika belum ada rubrik sama sekali.
    Data: $assessments
--}}
<x-layouts.admin pageTitle="Panduan Rubrik">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Panduan Rubrik</h2>
                <p class="mt-1 text-sm text-gray-500">Panduan penilaian yang ditampilkan ke kecamatan</p>
            </div>
        </div>

        <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white py-20 text-center px-6">
            <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13
                         C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477
                         14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247
                         18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <p class="text-gray-500 font-medium">Belum ada panduan rubrik</p>
            <p class="mt-1 text-sm text-gray-400 max-w-sm">
                Buat panduan rubrik penilaian agar kecamatan bisa melihat kriteria skor 1–4 tiap indikator.
            </p>
            <a href="{{ route('admin.panduan.create') }}"
               class="mt-5 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Panduan Rubrik
            </a>
        </div>
    </div>
</x-layouts.admin>
