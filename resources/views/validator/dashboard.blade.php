{{--
    validator/dashboard.blade.php
    Halaman dashboard untuk role Validator (Tim Teknis).
    Di NextJS, /validator langsung redirect ke /validator/validasi.
    Di Laravel kita tampilkan halaman ringkas + CTA ke validasi.
    Data: user, totalMenunggu, totalDivalidasiByMe
--}}
<x-layouts.validator pageTitle="Dashboard">

    <div class="space-y-6">

        {{-- ── Heading ────────────────────────────────────────────────── --}}
        <div>
            <p class="text-xs text-gray-400">Tim Teknis · {{ now()->year }}</p>
            <h2 class="mt-0.5 text-2xl font-bold text-gray-900">Selamat Datang, {{ $user->name }}</h2>
            <p class="mt-1 text-sm text-gray-500">
                Review dan validasi self assessment yang disubmit oleh kecamatan
            </p>
        </div>

        {{-- ── Stats ──────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 gap-3">

            {{-- Menunggu Validasi --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-amber-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalMenunggu }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Menunggu Divalidasi</p>
                </div>
            </div>

            {{-- Sudah Divalidasi oleh Saya --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-green-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalDivalidasiByMe }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Divalidasi oleh Saya</p>
                </div>
            </div>

        </div>

        {{-- ── CTA: Mulai Validasi ─────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="rounded-lg bg-sky-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900">Validasi Assessment Kecamatan</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Lihat semua pengisian self assessment yang sudah disubmit oleh kecamatan
                        dan berikan penilaian validasi Anda.
                    </p>
                    @if ($totalMenunggu > 0)
                        <p class="mt-1.5 text-xs font-medium text-amber-600">
                            {{ $totalMenunggu }} pengisian menunggu validasi
                        </p>
                    @else
                        <p class="mt-1.5 text-xs text-gray-400">Tidak ada pengisian yang menunggu validasi</p>
                    @endif
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('validator.validasi.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    Mulai Validasi
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

    </div>

</x-layouts.validator>
