{{--
    kabupaten/dashboard.blade.php
    Halaman dashboard untuk role KABUPATEN.
--}}
<x-layouts.kabupaten pageTitle="Dashboard">

    <div class="space-y-6">

        {{-- ── Heading ────────────────────────────────────────────────── --}}
        <div>
            <p class="text-xs text-gray-400">Kabupaten / Kota · {{ now()->year }}</p>
            <h2 class="mt-0.5 text-2xl font-bold text-gray-900">Selamat Datang, {{ $user->name }}</h2>
            <p class="mt-1 text-sm text-gray-500">
                Review dan setujui assessment kecamatan sebelum diteruskan ke tim teknis
            </p>
        </div>

        {{-- ── Stats ──────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

            {{-- Menunggu Review --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-amber-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalMenunggu }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Menunggu Review</p>
                </div>
            </div>

            {{-- Sudah Disetujui --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-green-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalDisetujui }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Disetujui</p>
                </div>
            </div>

            {{-- Perlu Perbaikan --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-red-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalRevisi }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Perlu Perbaikan</p>
                </div>
            </div>

        </div>

        {{-- ── CTA ─────────────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="rounded-lg bg-indigo-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                                 M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900">Review Assessment Kecamatan</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Tinjau jawaban kecamatan yang ada di wilayah Anda dan berikan persetujuan
                        sebelum diteruskan ke tim teknis untuk divalidasi.
                    </p>
                    @if ($totalMenunggu > 0)
                        <p class="mt-1.5 text-xs font-medium text-amber-600">
                            {{ $totalMenunggu }} kecamatan menunggu review Anda
                        </p>
                    @else
                        <p class="mt-1.5 text-xs text-gray-400">Tidak ada assessment yang menunggu review</p>
                    @endif
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('kabupaten.review.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                    Mulai Review
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

    </div>

</x-layouts.kabupaten>
