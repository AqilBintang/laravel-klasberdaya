{{--
    kabupaten/review/index.blade.php
    Daftar assessment kecamatan dalam wilayah kabupaten ini.
    Data dari ApprovalController::index():
      $approvals            — Collection<KabupatenApproval>
      $allKecamatanUsers    — Collection<User> semua kecamatan di wilayah ini
      $kecamatanBelumSubmit — Collection<User> kecamatan yang belum pernah submit
--}}
<x-layouts.kabupaten pageTitle="Review Assessment">

    <div class="space-y-6">

        {{-- ── Heading ─────────────────────────────────────────────────── --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Review Assessment Kecamatan</h2>
            <p class="mt-1 text-sm text-gray-500">
                Tinjau jawaban kecamatan dan berikan persetujuan sebelum diteruskan ke tim teknis
            </p>
        </div>

        @php
            $pending       = $approvals->where('status', 'PENDING');
            $needsRevision = $approvals->where('status', 'NEEDS_REVISION');
            $approved      = $approvals->where('status', 'APPROVED');
            $totalSubmit   = $approvals->count();
            $totalAll      = $allKecamatanUsers->count();
        @endphp

        {{-- ── Ringkasan Progress ──────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center">
                <p class="text-2xl font-bold text-amber-700">{{ $pending->count() }}</p>
                <p class="text-xs text-amber-600 font-medium mt-0.5">Menunggu Review</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-center">
                <p class="text-2xl font-bold text-red-600">{{ $needsRevision->count() }}</p>
                <p class="text-xs text-red-500 font-medium mt-0.5">Perlu Perbaikan</p>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-center">
                <p class="text-2xl font-bold text-green-700">{{ $approved->count() }}</p>
                <p class="text-xs text-green-600 font-medium mt-0.5">Disetujui</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-center">
                <p class="text-2xl font-bold text-gray-500">{{ $kecamatanBelumSubmit->count() }}</p>
                <p class="text-xs text-gray-400 font-medium mt-0.5">Belum Submit</p>
            </div>
        </div>

        @if ($pending->isNotEmpty())
            <div class="flex items-center gap-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                </svg>
                <span>
                    <strong>{{ $pending->count() }}</strong> kecamatan menunggu review Anda
                </span>
            </div>
        @endif

        {{-- ── Filter ────────────────────────────────────────────────── --}}
        @if ($approvals->isNotEmpty() || $kecamatanBelumSubmit->isNotEmpty())
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input type="search" id="search-kecamatan" placeholder="Cari kecamatan…"
                       class="w-full rounded-lg border border-gray-200 bg-white pl-9 pr-4 py-2 text-sm
                              placeholder:text-gray-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                       autocomplete="off">
            </div>
            <select id="filter-status"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm
                           focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <option value="">Semua Status</option>
                <option value="pending">Menunggu Review</option>
                <option value="revision">Perlu Perbaikan</option>
                <option value="approved">Disetujui</option>
                <option value="belum">Belum Submit</option>
            </select>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SEKSI 1: MENUNGGU REVIEW (PENDING)                          --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div class="section-group" data-group="pending">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Menunggu Review
                </h3>
                <span class="ml-1 inline-flex items-center justify-center rounded-full bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5">
                    {{ $pending->count() }}
                </span>
            </div>

            @if ($pending->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-400">
                    Tidak ada assessment yang menunggu review
                </div>
            @else
                <div class="space-y-3" id="list-pending">
                    @foreach ($pending as $approval)
                        @php
                            $kecamatanUser = $approval->kecamatanUser;
                            $namaKecamatan = $kecamatanUser?->kecamatanWilayah?->nama
                                ?? $kecamatanUser?->kecamatan
                                ?? $kecamatanUser?->name
                                ?? '—';
                            $namaKabupaten = $kecamatanUser?->kabupatenWilayah?->nama
                                ?? $kecamatanUser?->kabupaten;
                        @endphp
                        <div class="approval-card rounded-xl border border-amber-200 bg-white shadow-sm overflow-hidden"
                             data-kecamatan="{{ strtolower($namaKecamatan) }}"
                             data-status="pending">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 bg-amber-50 border-b border-amber-100">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-amber-100 p-2 shrink-0">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $namaKecamatan }}</p>
                                        @if ($namaKabupaten)
                                            <p class="text-xs text-gray-500">{{ $namaKabupaten }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Menunggu Review
                                    </span>
                                    <div class="text-right text-xs text-gray-400">
                                        <p>{{ $approval->assessment?->title ?? '—' }}</p>
                                        <p>Periode {{ $approval->periode }}</p>
                                    </div>
                                    <a href="{{ route('kabupaten.review.show', $approval->id) }}"
                                       class="flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium transition-colors whitespace-nowrap
                                              bg-indigo-600 text-white hover:bg-indigo-700">
                                        Review
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="px-5 py-3 flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-gray-500">
                                <span>Dikirim {{ \Carbon\Carbon::parse($approval->updated_at)->diffForHumans() }}</span>
                                @if ($approval->reviewed_at)
                                    <span class="text-amber-600 font-medium">Pernah direview sebelumnya — dikirim ulang</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SEKSI 2: PERLU PERBAIKAN (NEEDS_REVISION)                   --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div class="section-group" data-group="revision">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Dikembalikan untuk Perbaikan
                </h3>
                <span class="ml-1 inline-flex items-center justify-center rounded-full bg-red-100 text-red-700 text-xs font-bold px-2 py-0.5">
                    {{ $needsRevision->count() }}
                </span>
            </div>

            @if ($needsRevision->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-400">
                    Tidak ada assessment yang perlu perbaikan
                </div>
            @else
                <div class="space-y-3" id="list-revision">
                    @foreach ($needsRevision as $approval)
                        @php
                            $kecamatanUser = $approval->kecamatanUser;
                            $namaKecamatan = $kecamatanUser?->kecamatanWilayah?->nama
                                ?? $kecamatanUser?->kecamatan
                                ?? $kecamatanUser?->name
                                ?? '—';
                            $namaKabupaten = $kecamatanUser?->kabupatenWilayah?->nama
                                ?? $kecamatanUser?->kabupaten;
                        @endphp
                        <div class="approval-card rounded-xl border border-red-200 bg-white shadow-sm overflow-hidden"
                             data-kecamatan="{{ strtolower($namaKecamatan) }}"
                             data-status="revision">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 bg-red-50 border-b border-red-100">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-red-100 p-2 shrink-0">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $namaKecamatan }}</p>
                                        @if ($namaKabupaten)
                                            <p class="text-xs text-gray-500">{{ $namaKabupaten }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Perlu Perbaikan
                                    </span>
                                    <div class="text-right text-xs text-gray-400">
                                        <p>{{ $approval->assessment?->title ?? '—' }}</p>
                                        <p>Periode {{ $approval->periode }}</p>
                                    </div>
                                    <a href="{{ route('kabupaten.review.show', $approval->id) }}"
                                       class="flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium transition-colors whitespace-nowrap
                                              border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                        Lihat Detail
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="px-5 py-3 flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-gray-500">
                                @if ($approval->reviewed_at)
                                    <span>Direview {{ \Carbon\Carbon::parse($approval->reviewed_at)->diffForHumans() }}</span>
                                @endif
                                @if ($approval->notes)
                                    <span class="italic text-red-400 truncate max-w-xs">Catatan: {{ $approval->notes }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SEKSI 3: SUDAH DISETUJUI (APPROVED)                         --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div class="section-group" data-group="approved">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Sudah Disetujui
                </h3>
                <span class="ml-1 inline-flex items-center justify-center rounded-full bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5">
                    {{ $approved->count() }}
                </span>
            </div>

            @if ($approved->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-400">
                    Belum ada assessment yang disetujui
                </div>
            @else
                <div class="space-y-3" id="list-approved">
                    @foreach ($approved as $approval)
                        @php
                            $kecamatanUser = $approval->kecamatanUser;
                            $namaKecamatan = $kecamatanUser?->kecamatanWilayah?->nama
                                ?? $kecamatanUser?->kecamatan
                                ?? $kecamatanUser?->name
                                ?? '—';
                            $namaKabupaten = $kecamatanUser?->kabupatenWilayah?->nama
                                ?? $kecamatanUser?->kabupaten;
                        @endphp
                        <div class="approval-card rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden"
                             data-kecamatan="{{ strtolower($namaKecamatan) }}"
                             data-status="approved">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 bg-green-50 border-b border-green-100">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-green-100 p-2 shrink-0">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $namaKecamatan }}</p>
                                        @if ($namaKabupaten)
                                            <p class="text-xs text-gray-500">{{ $namaKabupaten }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Disetujui
                                    </span>
                                    <div class="text-right text-xs text-gray-400">
                                        <p>{{ $approval->assessment?->title ?? '—' }}</p>
                                        <p>Periode {{ $approval->periode }}</p>
                                    </div>
                                    <a href="{{ route('kabupaten.review.show', $approval->id) }}"
                                       class="flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium transition-colors whitespace-nowrap
                                              border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                        Lihat Detail
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="px-5 py-3 flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-gray-500">
                                @if ($approval->reviewed_at)
                                    <span>Disetujui {{ \Carbon\Carbon::parse($approval->reviewed_at)->diffForHumans() }}</span>
                                @endif
                                @if ($approval->notes)
                                    <span class="italic text-gray-400 truncate max-w-xs">Catatan: {{ $approval->notes }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- SEKSI 4: BELUM SUBMIT                                        --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div class="section-group" data-group="belum">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full bg-gray-400 shrink-0"></span>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Belum Submit
                </h3>
                <span class="ml-1 inline-flex items-center justify-center rounded-full bg-gray-100 text-gray-600 text-xs font-bold px-2 py-0.5">
                    {{ $kecamatanBelumSubmit->count() }}
                </span>
            </div>

            @if ($kecamatanBelumSubmit->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-400">
                    Semua kecamatan di wilayah Anda sudah mengirimkan assessment 🎉
                </div>
            @else
                <div class="space-y-2" id="list-belum">
                    @foreach ($kecamatanBelumSubmit as $kec)
                        @php
                            $namaKecamatan = $kec->kecamatanWilayah?->nama
                                ?? $kec->kecamatan
                                ?? $kec->name
                                ?? '—';
                            $namaKabupaten = $kec->kabupatenWilayah?->nama
                                ?? $kec->kabupaten;
                        @endphp
                        <div class="approval-card rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden"
                             data-kecamatan="{{ strtolower($namaKecamatan) }}"
                             data-status="belum">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-3.5 bg-gray-50 border-b border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-gray-100 p-2 shrink-0">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-700">{{ $namaKecamatan }}</p>
                                        @if ($namaKabupaten)
                                            <p class="text-xs text-gray-400">{{ $namaKabupaten }}</p>
                                        @endif
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Belum mengirimkan assessment
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    @push('scripts')
    <script>
        (function () {
            var searchEl  = document.getElementById('search-kecamatan');
            var filterEl  = document.getElementById('filter-status');
            var cards     = document.querySelectorAll('.approval-card');
            var groups    = document.querySelectorAll('.section-group');

            function applyFilters() {
                var q      = (searchEl?.value ?? '').toLowerCase().trim();
                var status = filterEl?.value ?? '';

                // Filter tiap grup (section) berdasarkan data-group vs pilihan filter
                groups.forEach(function (group) {
                    var groupKey = group.dataset.group ?? '';
                    // Jika filter status aktif dan grup ini tidak cocok, sembunyikan seluruh grup
                    if (status && groupKey !== status) {
                        group.style.display = 'none';
                        return;
                    }
                    group.style.display = '';
                });

                // Filter tiap card berdasarkan nama kecamatan
                cards.forEach(function (card) {
                    var name   = card.dataset.kecamatan ?? '';
                    var cardSt = card.dataset.status ?? '';

                    var matchQ  = !q || name.includes(q);
                    var matchSt = !status || cardSt === status;

                    card.style.display = (matchQ && matchSt) ? '' : 'none';
                });
            }

            searchEl?.addEventListener('input', applyFilters);
            filterEl?.addEventListener('change', applyFilters);
        })();
    </script>
    @endpush

</x-layouts.kabupaten>
