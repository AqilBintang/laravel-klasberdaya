{{--
    admin/assessment/results/index.blade.php
    Rekapitulasi hasil assessment semua kecamatan - mirip RekapitulasiClient (NextJS).
    Data dari controller: $results, $allResults, $years, $selectedYear,
                          $currentPage, $totalPages, $total, $perPage
--}}
<x-layouts.admin pageTitle="Rekapitulasi Assessment">
@php
    $categoryCodes = ['A', 'B', 'C', 'D', 'E', 'F'];

    if (!function_exists('findCat')) {
        function findCat(array $categories, string $code): ?array {
            foreach ($categories as $c) {
                if (strtoupper($c['code']) === $code) return $c;
            }
            return null;
        }
    }

    if (!function_exists('klasifikasiBadge')) {
        function klasifikasiBadge(?string $level): string {
            if (!$level) return '<span class="text-xs text-gray-400 italic">N/A</span>';
            $cfg = \App\Helpers\Scoring::getKlasifikasiConfig($level);
            return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold border '
                . e($cfg['bg']) . ' ' . e($cfg['color']) . ' ' . e($cfg['border']) . '">'
                . e($level) . '</span>';
        }
    }
@endphp

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Rekapitulasi Assessment</h2>
            <p class="mt-1 text-sm text-gray-500">
                Data hasil assessment semua kecamatan. Klik nama kecamatan untuk melihat detail isian.
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            {{-- Total count --}}
            <span class="flex items-center gap-1.5 text-sm text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                {{ $total }} kecamatan
            </span>

            {{-- Filter tahun --}}
            @if (count($years) > 0)
                <form method="GET" action="{{ route('admin.assessment.results.index') }}" class="flex items-center gap-2">
                    <label for="tahun-filter" class="text-sm text-gray-500 whitespace-nowrap">Tahun:</label>
                    <select id="tahun-filter" name="tahun"
                            onchange="this.form.submit()"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 outline-none">
                        <option value="">Semua Tahun</option>
                        @foreach ($years as $yr)
                            <option value="{{ $yr }}" {{ $selectedYear === $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                    @if ($selectedYear)
                        <a href="{{ route('admin.assessment.results.index') }}"
                           class="text-xs text-gray-400 hover:text-gray-600 underline whitespace-nowrap">Reset</a>
                    @endif
                </form>
            @endif
        </div>
    </div>

    {{-- Tabel / Empty state --}}
    @if (count($results) > 0)
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-max w-full text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-center whitespace-nowrap">No.</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Kabupaten/Kota</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Kecamatan</th>

                        {{-- Kolom kategori A-D: skor + badge klasifikasi --}}
                        @foreach (['A','B','C','D'] as $code)
                            <th class="px-4 py-3 text-center whitespace-nowrap">Skor Kat. {{ $code }}</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap">Status Kat. {{ $code }}</th>
                        @endforeach

                        {{-- Kolom E-F: hanya skor --}}
                        @foreach (['E','F'] as $code)
                            <th class="px-4 py-3 text-center whitespace-nowrap">Skor Kat. {{ $code }}</th>
                        @endforeach

                        <th class="px-4 py-3 text-center whitespace-nowrap">Verifikasi</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Skor Final</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Klasifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($results as $i => $r)
                        @php
                            $rowNo      = ($currentPage - 1) * $perPage + $i + 1;
                            $cats       = $r['categories'] ?? [];
                            $isSnapshot = ($r['sumber'] ?? 'assessment') === 'snapshot';
                            $detailUrl  = !$isSnapshot
                                ? route('admin.assessment.results.detail', [
                                    $r['userId'],
                                    $r['assessmentId'],
                                  ]) . '?periode=' . urlencode($r['periode'])
                                : null;
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition-colors {{ $isSnapshot ? 'bg-gray-50/30' : '' }}">
                            <td class="px-4 py-3 text-center text-gray-500">{{ $rowNo }}</td>

                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                {{ $r['kabupaten'] ?? 'N/A' }}
                            </td>

                            <td class="px-4 py-3 font-semibold whitespace-nowrap">
                                @if ($isSnapshot)
                                    <span class="text-gray-700">{{ $r['kecamatan'] ?? 'N/A' }}</span>
                                @else
                                    <a href="{{ $detailUrl }}"
                                       class="text-gray-900 hover:text-sky-700 hover:underline">
                                        {{ $r['kecamatan'] ?? 'N/A' }}
                                    </a>
                                @endif
                            </td>

                            {{-- A-D: skor + badge klasifikasi --}}
                            @foreach (['A','B','C','D'] as $code)
                                @php $cat = findCat($cats, $code); @endphp
                                <td class="px-4 py-3 text-center font-medium tabular-nums">
                                    {{ $cat ? $cat['score'] : 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {!! klasifikasiBadge($cat['klasifikasi'] ?? null) !!}
                                </td>
                            @endforeach

                            {{-- E-F: hanya skor --}}
                            @foreach (['E','F'] as $code)
                                @php $cat = findCat($cats, $code); @endphp
                                <td class="px-4 py-3 text-center font-medium tabular-nums">
                                    {{ $cat ? $cat['score'] : 'N/A' }}
                                </td>
                            @endforeach

                            <td class="px-4 py-3 text-center">
                                @if ($isSnapshot)
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                        Rekap Tim Teknis
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">
                                        Terverifikasi
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center font-bold tabular-nums">
                                {{ number_format($r['finalScore'] ?? 0, 2) }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                {!! klasifikasiBadge($r['statusAkhir'] ?? null) !!}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Legend sumber data --}}
        <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="font-medium text-gray-600">Sumber data:</span>
            <span class="flex items-center gap-1.5">
                <span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">Terverifikasi</span>
                — data dari alur assessment (klik nama untuk detail)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">Rekap Tim Teknis</span>
                — data impor rekapitulasi, belum melalui alur assessment
            </span>
        </div>

        {{-- Pagination --}}
        @if ($totalPages > 1)
            <div class="flex items-center justify-between gap-4 pt-1">
                <p class="text-sm text-gray-500">
                    Menampilkan
                    {{ ($currentPage - 1) * $perPage + 1 }}-{{ min($currentPage * $perPage, $total) }}
                    dari {{ $total }} data
                </p>

                <div class="flex items-center gap-1">
                    {{-- Prev --}}
                    @if ($currentPage > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white w-8 h-8 text-gray-600 hover:bg-gray-50 transition-colors shadow-sm"
                           aria-label="Halaman sebelumnya">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                    @else
                        <span class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 w-8 h-8 text-gray-300 cursor-not-allowed" aria-disabled="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </span>
                    @endif

                    {{-- Page numbers --}}
                    @php
                        $window = 2;
                        $start  = max(1, $currentPage - $window);
                        $end    = min($totalPages, $currentPage + $window);
                        if ($end - $start < 4) {
                            if ($start === 1) $end = min($totalPages, $start + 4);
                            else              $start = max(1, $end - 4);
                        }
                    @endphp

                    @if ($start > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white w-8 h-8 text-sm text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">1</a>
                        @if ($start > 2)
                            <span class="inline-flex items-center justify-center w-8 h-8 text-gray-400 text-sm">...</span>
                        @endif
                    @endif

                    @for ($p = $start; $p <= $end; $p++)
                        @if ($p === $currentPage)
                            <span class="inline-flex items-center justify-center rounded-lg border border-sky-500 bg-sky-50 w-8 h-8 text-sm font-semibold text-sky-700 shadow-sm">{{ $p }}</span>
                        @else
                            <a href="{{ request()->fullUrlWithQuery(['page' => $p]) }}"
                               class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white w-8 h-8 text-sm text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">{{ $p }}</a>
                        @endif
                    @endfor

                    @if ($end < $totalPages)
                        @if ($end < $totalPages - 1)
                            <span class="inline-flex items-center justify-center w-8 h-8 text-gray-400 text-sm">...</span>
                        @endif
                        <a href="{{ request()->fullUrlWithQuery(['page' => $totalPages]) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white w-8 h-8 text-sm text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">{{ $totalPages }}</a>
                    @endif

                    {{-- Next --}}
                    @if ($currentPage < $totalPages)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white w-8 h-8 text-gray-600 hover:bg-gray-50 transition-colors shadow-sm"
                           aria-label="Halaman berikutnya">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @else
                        <span class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 w-8 h-8 text-gray-300 cursor-not-allowed" aria-disabled="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
        @endif

    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
            <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            <p class="text-gray-500 font-medium">
                @if ($selectedYear)
                    Belum ada data untuk tahun {{ $selectedYear }}
                @else
                    Belum ada data rekapitulasi
                @endif
            </p>
            <p class="mt-1 text-sm text-gray-400">Muncul setelah kecamatan submit dan validator memvalidasi</p>
            @if ($selectedYear)
                <a href="{{ route('admin.assessment.results.index') }}"
                   class="mt-4 text-sm text-sky-600 hover:text-sky-700 underline">
                    Lihat semua tahun
                </a>
            @endif
        </div>
    @endif

</div>
</x-layouts.admin>
