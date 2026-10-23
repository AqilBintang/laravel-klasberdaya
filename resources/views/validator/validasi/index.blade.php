{{--
    validator/validasi/index.blade.php
    Daftar kecamatan yang punya self-assessment SUBMITTED — terkelompok per kecamatan.
    Data dari ValidationController::index():
      $groups       — array of { key, userId, representativeId, kecamatan, kabupaten,
                                  assessmentId, assessmentTitle, periode,
                                  totalIndicators, pendingCount, overallStatus,
                                  isOutdated, submittedAt }
      $totalPending — int total indikator menunggu validasi
--}}
<x-layouts.validator pageTitle="Validasi Assessment">

    <div class="space-y-6">

        {{-- ── Heading ─────────────────────────────────────────────────── --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Validasi Assessment</h2>
            <p class="mt-1 text-sm text-gray-500">
                Review dan validasi self assessment yang disubmit oleh kecamatan
            </p>
        </div>

        @if (!empty($groups) && count($groups) > 0)

            {{-- ── Banner ringkasan ────────────────────────────────────── --}}
            @if ($totalPending > 0)
                <div class="flex items-center gap-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                    </svg>
                    <span>
                        <strong>{{ $totalPending }}</strong> indikator menunggu validasi dari
                        <strong>{{ count($groups) }}</strong> kecamatan
                    </span>
                </div>
            @endif

            {{-- ── Filter / Search ─────────────────────────────────────── --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <input type="search" id="search-kecamatan" placeholder="Cari kecamatan…"
                           class="w-full rounded-lg border border-gray-200 bg-white pl-9 pr-4 py-2 text-sm
                                  placeholder:text-gray-400 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100"
                           autocomplete="off">
                </div>
                <select id="filter-status"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm
                               focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                    <option value="">Semua Status</option>
                    <option value="pending">Menunggu Validasi</option>
                    <option value="outdated">Menunggu Revisi Kecamatan</option>
                    <option value="validated">Sudah Divalidasi</option>
                </select>
            </div>

            {{-- ── Card list per kecamatan ─────────────────────────────── --}}
            <div class="space-y-3" id="group-list">
                @foreach ($groups as $g)
                    @php
                        $statusConfig = [
                            'VALIDATED' => ['label' => 'Tervalidasi',      'cls' => 'bg-green-100 text-green-700'],
                            'REJECTED'  => ['label' => 'Ditolak',          'cls' => 'bg-red-100 text-red-700'],
                            'SUBMITTED' => ['label' => 'Menunggu Validasi', 'cls' => 'bg-amber-100 text-amber-700'],
                        ];
                        $sc = $statusConfig[$g['overallStatus']] ?? $statusConfig['SUBMITTED'];
                    @endphp
                    <div class="group-card rounded-xl border bg-white shadow-sm overflow-hidden
                                {{ $g['isOutdated'] ? 'border-orange-300' : 'border-gray-200' }}"
                         data-kecamatan="{{ strtolower($g['kecamatan'] ?? '') }}"
                         data-status="{{ $g['isOutdated'] ? 'outdated' : ($g['pendingCount'] > 0 ? 'pending' : 'validated') }}">

                        {{-- Card header --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4
                                    {{ $g['isOutdated'] ? 'bg-orange-50 border-b border-orange-200' : 'bg-gray-50 border-b border-gray-100' }}">
                            <div class="flex items-center gap-3">
                                {{-- Ikon lokasi --}}
                                <div class="rounded-lg p-2 {{ $g['isOutdated'] ? 'bg-orange-100' : 'bg-sky-100' }} shrink-0">
                                    <svg class="w-4 h-4 {{ $g['isOutdated'] ? 'text-orange-600' : 'text-sky-600' }}"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $g['kecamatan'] ?? '—' }}</p>
                                    @if ($g['kabupaten'])
                                        <p class="text-xs text-gray-500">{{ $g['kabupaten'] }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                {{-- Badge status --}}
                                @if ($g['isOutdated'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-semibold text-orange-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                        </svg>
                                        Menunggu Revisi Kecamatan
                                    </span>
                                @elseif ($g['pendingCount'] > 0)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                        {{ $g['pendingCount'] }} menunggu
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full {{ $sc['cls'] }} px-2.5 py-0.5 text-xs font-medium">
                                        {{ $sc['label'] }}
                                    </span>
                                @endif

                                {{-- Meta assessment --}}
                                <div class="text-right text-xs text-gray-400">
                                    <p>{{ $g['assessmentTitle'] }}</p>
                                    <p>Periode {{ $g['periode'] }}</p>
                                </div>

                                {{-- Tombol review --}}
                                @if (!$g['isOutdated'])
                                    <a href="{{ route('validator.validasi.show', $g['representativeId']) }}"
                                       class="flex items-center gap-1.5 rounded-lg bg-sky-600 px-3.5 py-2 text-xs font-medium text-white hover:bg-sky-700 transition-colors whitespace-nowrap">
                                        Review
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                @else
                                    <a href="{{ route('validator.validasi.show', $g['representativeId']) }}"
                                       class="flex items-center gap-1.5 rounded-lg border border-orange-300 bg-white px-3.5 py-2 text-xs font-medium text-orange-700 hover:bg-orange-50 transition-colors whitespace-nowrap">
                                        Lihat Detail
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Outdated notice --}}
                        @if ($g['isOutdated'])
                            <div class="px-5 py-2.5 bg-orange-50 text-xs text-orange-700 flex items-start gap-2">
                                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                </svg>
                                <span>
                                    Admin telah memperbarui template assessment. Kecamatan ini perlu menyelesaikan revisi indikator yang berubah sebelum dapat divalidasi.
                                </span>
                            </div>
                        @endif

                        {{-- Stats bar --}}
                        <div class="px-5 py-3 flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-gray-500 border-t border-gray-100">
                            <span>
                                <strong class="text-gray-700">{{ $g['totalIndicators'] }}</strong> indikator
                            </span>
                            @if ($g['pendingCount'] > 0)
                                <span>
                                    <strong class="text-amber-600">{{ $g['pendingCount'] }}</strong> belum divalidasi
                                </span>
                                <span>
                                    <strong class="text-green-600">{{ $g['totalIndicators'] - $g['pendingCount'] }}</strong> sudah divalidasi
                                </span>
                            @endif
                            @if ($g['submittedAt'])
                                <span class="ml-auto">Dikirim {{ \Carbon\Carbon::parse($g['submittedAt'])->diffForHumans() }}</span>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>

        @else
            {{-- ── Empty state ─────────────────────────────────────────── --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed
                        border-gray-200 bg-white py-20 text-center">
                <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2
                             m-6 9l2 2 4-4"/>
                </svg>
                <p class="text-sm font-medium text-gray-500">Tidak ada assessment yang menunggu validasi</p>
                <p class="text-xs text-gray-400 mt-1">Semua pengisian sudah divalidasi atau belum ada yang disubmit</p>
            </div>
        @endif

    </div>

    @push('scripts')
    <script>
        (function () {
            var searchEl  = document.getElementById('search-kecamatan');
            var filterEl  = document.getElementById('filter-status');
            var cards     = document.querySelectorAll('.group-card');

            function applyFilters() {
                var q      = (searchEl?.value ?? '').toLowerCase().trim();
                var status = filterEl?.value ?? '';

                cards.forEach(function (card) {
                    var name     = card.dataset.kecamatan ?? '';
                    var cardSt   = card.dataset.status ?? '';
                    var matchQ   = !q || name.includes(q);
                    var matchSt  = !status || cardSt === status;
                    card.style.display = (matchQ && matchSt) ? '' : 'none';
                });
            }

            searchEl?.addEventListener('input', applyFilters);
            filterEl?.addEventListener('change', applyFilters);
        })();
    </script>
    @endpush

</x-layouts.validator>
