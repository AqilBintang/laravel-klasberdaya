{{--
    kabupaten/review/index.blade.php
    Daftar assessment kecamatan dalam wilayah kabupaten ini.
    Data dari ApprovalController::index():
      $approvals — Collection<KabupatenApproval>
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
            $pending  = $approvals->where('status', 'PENDING');
            $reviewed = $approvals->whereIn('status', ['APPROVED', 'NEEDS_REVISION']);
        @endphp

        @if ($pending->isNotEmpty())
            {{-- Banner menunggu --}}
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

        @if ($approvals->isEmpty())
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed
                        border-gray-200 bg-white py-20 text-center">
                <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm font-medium text-gray-500">Belum ada assessment yang perlu direview</p>
                <p class="text-xs text-gray-400 mt-1">Assessment dari kecamatan di wilayah Anda akan muncul di sini</p>
            </div>

        @else

            {{-- ── Filter ────────────────────────────────────────────── --}}
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
                    <option value="approved">Disetujui</option>
                    <option value="revision">Perlu Perbaikan</option>
                </select>
            </div>

            {{-- ── Card list ─────────────────────────────────────────── --}}
            <div class="space-y-3" id="approval-list">
                @foreach ($approvals as $approval)
                    @php
                        $kecamatanUser = $approval->kecamatanUser;
                        $namaKecamatan = $kecamatanUser?->kecamatanWilayah?->nama
                            ?? $kecamatanUser?->kecamatan
                            ?? $kecamatanUser?->name
                            ?? '—';
                        $namaKabupaten = $kecamatanUser?->kabupatenWilayah?->nama
                            ?? $kecamatanUser?->kabupaten;

                        $statusCfg = match($approval->status) {
                            'APPROVED'       => ['label' => 'Disetujui',       'cls' => 'bg-green-100 text-green-700',  'dot' => 'bg-green-500', 'filter' => 'approved'],
                            'NEEDS_REVISION' => ['label' => 'Perlu Perbaikan', 'cls' => 'bg-red-100 text-red-700',     'dot' => 'bg-red-500',   'filter' => 'revision'],
                            default          => ['label' => 'Menunggu Review', 'cls' => 'bg-amber-100 text-amber-700', 'dot' => 'bg-amber-500', 'filter' => 'pending'],
                        };
                    @endphp

                    <div class="approval-card rounded-xl border bg-white shadow-sm overflow-hidden
                                {{ $approval->status === 'PENDING' ? 'border-amber-200' : 'border-gray-200' }}"
                         data-kecamatan="{{ strtolower($namaKecamatan) }}"
                         data-status="{{ $statusCfg['filter'] }}">

                        {{-- Card header --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4
                                    {{ $approval->status === 'PENDING' ? 'bg-amber-50 border-b border-amber-100' : 'bg-gray-50 border-b border-gray-100' }}">

                            <div class="flex items-center gap-3">
                                <div class="rounded-lg p-2 {{ $approval->status === 'PENDING' ? 'bg-amber-100' : 'bg-indigo-100' }} shrink-0">
                                    <svg class="w-4 h-4 {{ $approval->status === 'PENDING' ? 'text-amber-600' : 'text-indigo-600' }}"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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

                                {{-- Badge status --}}
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusCfg['cls'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusCfg['dot'] }}"></span>
                                    {{ $statusCfg['label'] }}
                                </span>

                                {{-- Meta --}}
                                <div class="text-right text-xs text-gray-400">
                                    <p>{{ $approval->assessment?->title ?? '—' }}</p>
                                    <p>Periode {{ $approval->periode }}</p>
                                </div>

                                {{-- Tombol --}}
                                <a href="{{ route('kabupaten.review.show', $approval->id) }}"
                                   class="flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium transition-colors whitespace-nowrap
                                          {{ $approval->status === 'PENDING'
                                              ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                                              : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
                                    {{ $approval->status === 'PENDING' ? 'Review' : 'Lihat Detail' }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="px-5 py-3 flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-gray-500">
                            <span>Dikirim {{ \Carbon\Carbon::parse($approval->created_at)->diffForHumans() }}</span>
                            @if ($approval->reviewed_at)
                                <span>Direview {{ \Carbon\Carbon::parse($approval->reviewed_at)->diffForHumans() }}</span>
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

    @push('scripts')
    <script>
        (function () {
            var searchEl = document.getElementById('search-kecamatan');
            var filterEl = document.getElementById('filter-status');
            var cards    = document.querySelectorAll('.approval-card');

            function applyFilters() {
                var q      = (searchEl?.value ?? '').toLowerCase().trim();
                var status = filterEl?.value ?? '';
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
