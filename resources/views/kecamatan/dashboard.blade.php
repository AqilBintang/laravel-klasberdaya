{{--
    kecamatan/dashboard.blade.php
    Halaman dashboard untuk role Kecamatan (USER).
    Data: user, location, totalMenunggu, totalDivalidasi, publishedAssessments
--}}
<x-layouts.kecamatan pageTitle="Dashboard">

    <div class="space-y-6">

        {{-- ── Welcome ────────────────────────────────────────────────── --}}
        <div>
            <p class="text-xs text-gray-400">Portal Kecamatan · {{ now()->year }}</p>
            <h2 class="mt-0.5 text-2xl font-bold text-gray-900">
                {{ $location ?: $user->name }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Kelola self assessment dan pantau perkembangan kecamatan Anda
            </p>
        </div>

        {{-- ── Stats 3-kolom ──────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">

            {{-- Perlu Tindakan --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-red-50 p-3 shrink-0">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ count($actionableAssessments) }}</p>
                    <p class="text-xs text-gray-500">Perlu Tindakan</p>
                </div>
            </div>

            {{-- Menunggu Validasi --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-amber-50 p-3 shrink-0">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalMenunggu }}</p>
                    <p class="text-xs text-gray-500">Menunggu Validasi</p>
                </div>
            </div>

            {{-- Sudah Divalidasi --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-green-50 p-3 shrink-0">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalDivalidasi }}</p>
                    <p class="text-xs text-gray-500">Sudah Divalidasi</p>
                </div>
            </div>

        </div>

        {{-- ── Assessment Perlu Tindakan ─────────────────────────────────── --}}
        <section>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Assessment Perlu Tindakan
                </h3>
                <a href="{{ route('kecamatan.assessment.index') }}"
                   class="flex items-center gap-1 text-xs text-sky-600 hover:text-sky-700 font-medium">
                    Lihat Semua
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            @if (empty($actionableAssessments))
                <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-10 text-center">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-500">Tidak ada assessment yang perlu tindakan</p>
                    <p class="text-xs text-gray-400 mt-1">Semua assessment sudah diisi atau menunggu validasi</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($actionableAssessments as $a)
                        @php
                            $statusConfig = [
                                'BELUM_DIISI'    => ['label' => 'Belum Diisi',       'cls' => 'bg-slate-100 text-slate-600 border-slate-200',       'border' => 'border-slate-200 hover:border-sky-300'],
                                'DRAFT'          => ['label' => 'Draft',            'cls' => 'bg-yellow-50 text-yellow-700 border-yellow-200',    'border' => 'border-yellow-200 hover:border-yellow-300'],
                                'PARTIAL'        => ['label' => 'Diisi Sebagian',    'cls' => 'bg-orange-50 text-orange-700 border-orange-200',    'border' => 'border-orange-200 hover:border-orange-300'],
                                'NEEDS_REVISION' => ['label' => 'Perlu Revisi',      'cls' => 'bg-red-50 text-red-700 border-red-200',            'border' => 'border-red-300 hover:border-red-400'],
                                'HAS_UPDATE'     => ['label' => 'Ada Update',        'cls' => 'bg-blue-50 text-blue-700 border-blue-200',         'border' => 'border-blue-300 hover:border-blue-400'],
                                'UPDATING'       => ['label' => 'Sedang Diperbarui','cls' => 'bg-amber-50 text-amber-700 border-amber-200',      'border' => 'border-amber-200 opacity-75 cursor-not-allowed'],
                            ];
                            $sc = $statusConfig[$a['status']] ?? $statusConfig['BELUM_DIISI'];
                            $isUpdating = $a['status'] === 'UPDATING';
                        @endphp

                        @if ($isUpdating)
                            {{-- Sedang diperbarui admin — tidak bisa diklik --}}
                            <div class="rounded-xl border bg-white p-4 {{ $sc['border'] }}">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $sc['cls'] }}">
                                        {{ $sc['label'] }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $a['periode'] }}</span>
                                </div>
                                <h4 class="font-semibold text-gray-500 text-sm leading-snug">{{ $a['title'] }}</h4>
                                <p class="mt-1.5 text-xs text-amber-600">{{ $a['message'] }}</p>
                            </div>
                        @else
                            <a href="{{ route('kecamatan.assessment.show', $a['id']) }}"
                               class="rounded-xl border bg-white p-4 transition-all group {{ $sc['border'] }} hover:shadow-sm">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $sc['cls'] }}">
                                        {{ $sc['label'] }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $a['periode'] }}</span>
                                </div>
                                <h4 class="font-semibold text-gray-800 text-sm leading-snug group-hover:text-sky-600 transition-colors">
                                    {{ $a['title'] }}
                                </h4>
                                @if ($a['message'])
                                    <p class="mt-1.5 text-xs {{ $a['status'] === 'NEEDS_REVISION' ? 'text-red-600' : ($a['status'] === 'HAS_UPDATE' ? 'text-blue-600' : 'text-gray-500') }}">
                                        {{ $a['message'] }}
                                    </p>
                                @endif
                                <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
                                    <span>{{ $a['categoryCount'] }} kategori · {{ $a['totalInd'] }} indikator</span>
                                    @if ($a['entryCount'] > 0 && $a['status'] !== 'BELUM_DIISI')
                                        <span>{{ $a['entryCount'] }}/{{ $a['totalInd'] }} diisi</span>
                                    @endif
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ── Shortcut: Lihat Hasil Nilai ────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 flex items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-3 min-w-0">
                <div class="rounded-lg bg-gray-100 p-2.5 shrink-0">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800 text-sm">Lihat Hasil Nilai</p>
                    <p class="text-xs text-gray-400">Rekap lengkap hasil assessment per kategori</p>
                </div>
            </div>
            <a href="{{ route('kecamatan.hasil') }}"
               class="shrink-0 flex items-center gap-1.5 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                Lihat
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

    </div>

</x-layouts.kecamatan>
