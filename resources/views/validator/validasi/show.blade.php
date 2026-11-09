{{--
    validator/validasi/show.blade.php
    Detail validasi 1 kecamatan — hanya indikator yang PERLU divalidasi (status SUBMITTED).
    Data yang diharapkan dari ValidationController::show($id):
      - $selfAssessment    : SelfAssessment (anchor — konteks user + assessment + periode)
      - $entries           : Collection<SelfAssessment> — hanya indikator status SUBMITTED
      - $summary           : [totalAll, totalValidated, totalRejected, totalDraft, totalPending]
      - $rubric            : AssessmentRubric|null
      - $rubricByIndicator : array<int, RubricItem> — keyed by indicator_id entries aktif
--}}
@php
    // Susun $rubricCatGroups untuk tab "Semua Rubrik" di drawer
    $rubricCatGroups = [];
    if (!empty($rubric)) {
        foreach ($rubric->items as $item) {
            $cat = $item->indicator->category;
            $key = $cat->id;
            if (!isset($rubricCatGroups[$key])) {
                $rubricCatGroups[$key] = ['code' => $cat->code, 'name' => $cat->name, 'order' => $cat->order, 'items' => []];
            }
            $rubricCatGroups[$key]['items'][] = $item;
        }
        usort($rubricCatGroups, fn ($a, $b) => $a['order'] <=> $b['order']);
    }
@endphp
<x-layouts.validator pageTitle="Review Validasi">

    <div class="space-y-6">

        {{-- ── Breadcrumb ──────────────────────────────────────────────── --}}
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('validator.validasi.index') }}" class="hover:text-sky-600 transition-colors">
                Validasi Assessment
            </a>
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 font-medium truncate">
                {{ $selfAssessment?->submittedBy?->kecamatan ?? 'Detail Validasi' }}
            </span>
        </nav>

        @if(isset($selfAssessment))
        {{-- ── Info Header ─────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $selfAssessment->submittedBy?->kecamatan ?? $selfAssessment->submittedBy?->name }}
                    </h2>
                    @if ($selfAssessment->submittedBy?->kabupaten)
                        <p class="text-sm text-gray-500">{{ $selfAssessment->submittedBy->kabupaten }}</p>
                    @endif
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 shrink-0">
                    <div class="flex flex-col items-end gap-1 text-sm text-gray-500">
                        <span>Periode <strong class="text-gray-800">{{ $selfAssessment->periode }}</strong></span>
                        <span>Assessment <strong class="text-gray-800">
                            {{ $selfAssessment->indicator?->category?->assessment?->title ?? '—' }}
                        </strong></span>
                    </div>
                    {{-- Tombol buka panduan rubrik (level atas) --}}
                    @if (!empty($rubric))
                    <button type="button" id="btn-open-rubrik"
                            class="inline-flex items-center gap-2 rounded-lg border border-sky-200 bg-sky-50
                                   px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-100 transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168
                                     18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5
                                     5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746
                                     0-3.332.477-4.5 1.253"/>
                        </svg>
                        Panduan Rubrik
                    </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Daftar Indikator dengan Form Validasi ───────────────────── --}}
        @isset($entries)
        @if ($entries->isEmpty())
            {{-- Semua indikator sudah divalidasi --}}
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-12 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-500">Semua indikator sudah divalidasi</p>
                <p class="text-xs text-gray-400 mt-1">
                    Indikator yang sudah divalidasi tidak ditampilkan kembali.
                    Jika kecamatan melakukan revisi, indikator yang diubah akan muncul di sini.
                </p>
                <a href="{{ route('validator.validasi.index') }}"
                   class="mt-4 inline-flex items-center gap-1.5 text-sm text-sky-600 hover:text-sky-700 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Kembali ke Daftar Validasi
                </a>
            </div>
        @else
            {{-- Note: hanya indikator SUBMITTED yang ditampilkan --}}
            <div class="rounded-lg bg-sky-50 border border-sky-100 px-4 py-2.5 text-xs text-sky-700 flex items-start gap-2">
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                    Hanya menampilkan indikator yang <strong>menunggu validasi</strong>.
                    Indikator yang sudah divalidasi/ditolak tidak ditampilkan kembali.
                </span>
            </div>

        @php
            $categories = $entries->groupBy(fn($e) => $e->indicator?->category_id);
        @endphp

        @foreach ($categories as $catId => $catEntries)
            @php $cat = $catEntries->first()?->indicator?->category; @endphp
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">

                {{-- Category header --}}
                <div class="flex items-center gap-3 px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white shrink-0">
                        {{ $cat?->code ?? '—' }}
                    </span>
                    <div>
                        <p class="font-semibold text-slate-900 text-sm">{{ $cat?->name ?? 'Kategori' }}</p>
                        <p class="text-xs text-slate-500">{{ $catEntries->count() }} indikator</p>
                    </div>
                </div>

                {{-- Indikator rows --}}
                <div class="divide-y divide-slate-100">
                    @foreach ($catEntries as $entry)
                        @php
                            $validation    = $entry->validations->sortByDesc('validated_at')->first();
                            $currentStatus = $validation?->status ?? 'PENDING';
                            $statusCls = match ($currentStatus) {
                                'APPROVED'        => 'bg-green-100 text-green-700',
                                'REJECTED'        => 'bg-red-100 text-red-700',
                                'REVISION_NEEDED' => 'bg-yellow-100 text-yellow-700',
                                default           => 'bg-gray-100 text-gray-600',
                            };
                            $statusLabel = match ($currentStatus) {
                                'APPROVED'        => 'Disetujui',
                                'REJECTED'        => 'Ditolak',
                                'REVISION_NEEDED' => 'Perlu Revisi',
                                default           => 'Belum Divalidasi',
                            };
                        @endphp
                        <div class="px-5 py-4">
                            <div class="flex items-start gap-4">

                                {{-- Nomor indikator --}}
                                <div class="w-6 text-center text-xs font-bold text-slate-400 mt-0.5 shrink-0">
                                    {{ $entry->indicator?->number }}
                                </div>

                                {{-- Konten indikator --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start gap-2 flex-wrap">
                                        <p class="text-sm font-medium text-slate-800 leading-snug flex-1">
                                            {{ $entry->indicator?->indicator }}
                                        </p>
                                        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                                            {{-- Badge status validasi --}}
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusCls }}">
                                                {{ $statusLabel }}
                                            </span>
                                            {{-- Tombol toggle rubrik per indikator --}}
                                            @if (!empty($rubric))
                                                <button type="button"
                                                        data-rubrik-indicator="{{ $entry->indicator?->id }}"
                                                        class="btn-rubrik-indicator inline-flex items-center gap-1 rounded-full border border-sky-200 bg-white px-2 py-0.5 text-xs text-sky-600 hover:bg-sky-50 transition-colors">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                                    </svg>
                                                    Rubrik
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Jawaban kecamatan --}}
                                    @if ($entry->description)
                                        <div class="mt-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                                            <p class="text-xs text-slate-500 font-medium mb-1">Jawaban Kecamatan:</p>
                                            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $entry->description }}</p>
                                        </div>
                                    @endif

                                    {{-- Skor yang diberikan kecamatan --}}
                                    <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                        <span>
                                            Skor diisi:
                                            <strong class="text-slate-800">{{ $entry->score }}</strong>
                                            / {{ $entry->indicator?->max_score ?? '?' }}
                                        </span>
                                        @if ($validation?->validated_score !== null)
                                            <span>
                                                Skor validator:
                                                <strong class="text-sky-700">{{ $validation->validated_score }}</strong>
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Catatan validator sebelumnya --}}
                                    @if ($validation?->notes)
                                        <div class="mt-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2">
                                            <p class="text-xs text-amber-600 font-medium mb-1">Catatan validator:</p>
                                            <p class="text-xs text-amber-700">{{ $validation->notes }}</p>
                                        </div>
                                    @endif
                                </div>

                            </div>

                            {{-- ── Form validasi per indikator ─────────────────── --}}
                            <form method="POST"
                                  action="{{ route('validator.validasi.store', $entry->id) }}"
                                  class="mt-4 ml-10">
                                @csrf
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                                    {{-- Skor validated --}}
                                    <div>
                                        <label for="score_{{ $entry->id }}"
                                               class="block text-xs font-medium text-gray-600 mb-1">
                                            Skor Validator (1–{{ $entry->indicator?->max_score ?? 4 }})
                                        </label>
                                        <input type="number" id="score_{{ $entry->id }}" name="validated_score"
                                               value="{{ $validation?->validated_score ?? $entry->score }}"
                                               min="1" max="{{ $entry->indicator?->max_score ?? 4 }}"
                                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                      focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                    </div>

                                    {{-- Keputusan --}}
                                    <div>
                                        <label for="status_{{ $entry->id }}"
                                               class="block text-xs font-medium text-gray-600 mb-1">
                                            Keputusan
                                        </label>
                                        <select id="status_{{ $entry->id }}" name="status"
                                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                       focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                            <option value="APPROVED"
                                                {{ ($validation?->status === 'APPROVED') ? 'selected' : '' }}>
                                                ✅ Setujui
                                            </option>
                                            <option value="REVISION_NEEDED"
                                                {{ ($validation?->status === 'REVISION_NEEDED') ? 'selected' : '' }}>
                                                ⚠️ Perlu Revisi
                                            </option>
                                            <option value="REJECTED"
                                                {{ ($validation?->status === 'REJECTED') ? 'selected' : '' }}>
                                                ❌ Tolak
                                            </option>
                                        </select>
                                    </div>

                                    {{-- Catatan --}}
                                    <div>
                                        <label for="notes_{{ $entry->id }}"
                                               class="block text-xs font-medium text-gray-600 mb-1">
                                            Catatan (opsional)
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="text" id="notes_{{ $entry->id }}" name="notes"
                                                   value="{{ $validation?->notes }}"
                                                   placeholder="Tulis catatan…"
                                                   class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                          focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                            <button type="submit"
                                                    class="shrink-0 rounded-lg bg-sky-600 px-4 py-2 text-xs font-medium
                                                           text-white hover:bg-sky-700 transition-colors">
                                                Simpan
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </form>

                        </div>
                    @endforeach
                </div>

            </div>
        @endforeach
        @endif
        @endisset

        {{-- ── Tombol Kembali ──────────────────────────────────────────── --}}
        <div class="flex justify-start">
            <a href="{{ route('validator.validasi.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2
                      text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Daftar
            </a>
        </div>

        @else
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <p class="text-sm text-gray-500">Data validasi tidak ditemukan.</p>
                <a href="{{ route('validator.validasi.index') }}"
                   class="mt-4 inline-block text-sm text-sky-600 hover:text-sky-700 font-medium">
                    ← Kembali ke daftar
                </a>
            </div>
        @endif

    </div>

    @push('scripts')
    {{-- ════════════════════════════════════════════════════════════════
         DRAWER RUBRIK — slide-in dari kanan, dua tab
         (Indikator Aktif | Semua Rubrik)
    ════════════════════════════════════════════════════════════════ --}}
    @if (!empty($rubric))

    {{-- Backdrop --}}
    <div id="rubrik-backdrop"
         class="fixed inset-0 bg-black/40 z-40 hidden"
         style="opacity:0;transition:opacity .3s"
         aria-hidden="true"></div>

    {{-- Drawer panel --}}
    <aside id="rubrik-drawer"
           role="dialog" aria-modal="true" aria-label="Panduan Rubrik Penilaian"
           style="transition:transform .3s ease-in-out"
           class="fixed top-0 right-0 h-full w-full sm:w-[480px] lg:w-[520px]
                  bg-white shadow-2xl z-50 flex flex-col translate-x-full">

        {{-- Header drawer --}}
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 bg-sky-600 shrink-0">
            <div class="flex items-center gap-2 text-white">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168
                             18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5
                             5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746
                             0-3.332.477-4.5 1.253"/>
                </svg>
                <div>
                    <p class="font-semibold text-sm leading-tight">Panduan Rubrik</p>
                    <p class="text-sky-200 text-xs leading-tight">{{ Str::title($rubric->title) }}</p>
                </div>
            </div>
            <button type="button" id="btn-close-rubrik"
                    class="rounded-lg p-1.5 text-sky-200 hover:bg-sky-700 hover:text-white transition-colors"
                    aria-label="Tutup panduan rubrik">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Tab bar --}}
        <div class="flex border-b border-slate-200 shrink-0 bg-white">
            <button type="button" id="tab-aktif" data-tab="aktif"
                    class="rubrik-tab flex-1 py-2.5 text-xs font-semibold text-sky-700 border-b-2 border-sky-600">
                Indikator Aktif
            </button>
            <button type="button" id="tab-semua" data-tab="semua"
                    class="rubrik-tab flex-1 py-2.5 text-xs font-medium text-slate-500 border-b-2 border-transparent hover:text-slate-700">
                Semua Rubrik
            </button>
        </div>

        {{-- Tab panels --}}
        <div class="flex-1 overflow-y-auto">

            {{-- Panel: Indikator Aktif --}}
            <div id="panel-aktif" class="rubrik-panel p-4">
                <div id="rubrik-aktif-empty" class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="w-10 h-10 text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                    </svg>
                    <p class="text-sm text-slate-400 font-medium">Klik tombol "Rubrik"</p>
                    <p class="text-xs text-slate-400 mt-1">di samping indikator untuk melihat panduan skor</p>
                </div>
                <div id="rubrik-aktif-content" class="hidden space-y-3"></div>
            </div>

            {{-- Panel: Semua Rubrik (server-rendered) --}}
            <div id="panel-semua" class="rubrik-panel hidden">
                <div class="divide-y divide-slate-100">
                    @foreach ($rubricCatGroups as $cat)
                        <div>
                            <div class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200 px-4 py-2.5">
                                <p class="text-xs font-semibold text-slate-700">{{ $cat['code'] }}. {{ $cat['name'] }}</p>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @foreach ($cat['items'] as $item)
                                    <div class="px-4 py-3" data-rubrik-row="{{ $item->indicator_id }}">
                                        <p class="text-xs font-semibold text-slate-700 mb-2 leading-snug">
                                            <span class="text-slate-400 mr-1">{{ $item->indicator->number }}.</span>
                                            {{ $item->indicator->indicator }}
                                        </p>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            @foreach ([1 => $item->score1, 2 => $item->score2, 3 => $item->score3, 4 => $item->score4] as $skor => $desc)
                                                @if ($desc)
                                                    <div class="rounded-lg border border-slate-200 p-2">
                                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-sky-600 text-white text-xs font-bold mb-1">{{ $skor }}</span>
                                                        <p class="text-xs text-slate-600 leading-snug">{{ $desc }}</p>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </aside>

    {{-- Data rubrik di-embed sebagai JSON untuk akses JS --}}
    <script id="rubrik-data" type="application/json">
    {!! json_encode(
        collect($rubricByIndicator)->map(fn ($item, $activeIndicatorId) => [
            'indicator_id'   => (int) $activeIndicatorId,
            'indicator_text' => $item->indicator->indicator,
            'indicator_num'  => $item->indicator->number,
            'score1'         => $item->score1,
            'score2'         => $item->score2,
            'score3'         => $item->score3,
            'score4'         => $item->score4,
        ])->values()
    ) !!}
    </script>

    <script>
    (function () {
        // ── Parse data rubrik ────────────────────────────────────────
        var rubrikMap = {};
        try {
            var raw = document.getElementById('rubrik-data');
            if (raw) {
                JSON.parse(raw.textContent).forEach(function (item) {
                    rubrikMap[item.indicator_id] = item;
                });
            }
        } catch (e) { /* biarkan rubrikMap kosong */ }

        // ── Elemen drawer ────────────────────────────────────────────
        var drawer   = document.getElementById('rubrik-drawer');
        var backdrop = document.getElementById('rubrik-backdrop');

        // ── Buka / tutup drawer ──────────────────────────────────────
        function openDrawer() {
            if (!drawer) return;
            backdrop.classList.remove('hidden');
            // rAF agar transition berjalan setelah display:block
            requestAnimationFrame(function () {
                backdrop.style.opacity = '1';
                drawer.classList.remove('translate-x-full');
            });
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            if (!drawer) return;
            backdrop.style.opacity = '0';
            drawer.classList.add('translate-x-full');
            setTimeout(function () { backdrop.classList.add('hidden'); }, 300);
            document.body.style.overflow = '';
        }

        // ── Tab switching ────────────────────────────────────────────
        function switchTab(tabName) {
            document.querySelectorAll('.rubrik-tab').forEach(function (btn) {
                var active = btn.dataset.tab === tabName;
                btn.classList.toggle('text-sky-700',   active);
                btn.classList.toggle('font-semibold',  active);
                btn.classList.toggle('border-sky-600', active);
                btn.classList.toggle('text-slate-500', !active);
                btn.classList.toggle('font-medium',    !active);
                btn.classList.toggle('border-transparent', !active);
            });
            document.querySelectorAll('.rubrik-panel').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.id !== 'panel-' + tabName);
            });
        }

        document.querySelectorAll('.rubrik-tab').forEach(function (btn) {
            btn.addEventListener('click', function () { switchTab(btn.dataset.tab); });
        });

        // ── Tampilkan rubrik untuk satu indikator ────────────────────
        function showRubrikForIndicator(indicatorId) {
            var item = rubrikMap[indicatorId];
            var emptyEl   = document.getElementById('rubrik-aktif-empty');
            var contentEl = document.getElementById('rubrik-aktif-content');
            if (!item || !emptyEl || !contentEl) return;

            // Bersihkan dan isi konten
            contentEl.innerHTML = '';
            var header = document.createElement('div');
            header.className = 'mb-3';
            header.innerHTML =
                '<p class="text-xs font-bold text-slate-700 leading-snug">' +
                    '<span class="text-slate-400 mr-1">' + item.indicator_num + '.</span>' +
                    escHtml(item.indicator_text) +
                '</p>';
            contentEl.appendChild(header);

            var grid = document.createElement('div');
            grid.className = 'grid grid-cols-2 gap-2';

            [[1, item.score1], [2, item.score2], [3, item.score3], [4, item.score4]].forEach(function (pair) {
                var skor = pair[0], desc = pair[1];
                if (!desc) return;
                var card = document.createElement('div');
                card.className = 'rounded-lg border border-slate-200 p-2.5';
                card.innerHTML =
                    '<span class="inline-flex items-center justify-center w-5 h-5 rounded-full ' +
                    'bg-sky-600 text-white text-xs font-bold mb-1.5">' + skor + '</span>' +
                    '<p class="text-xs text-slate-600 leading-snug">' + escHtml(desc) + '</p>';
                grid.appendChild(card);
            });

            contentEl.appendChild(grid);
            emptyEl.classList.add('hidden');
            contentEl.classList.remove('hidden');

            // Highlight baris di tab semua rubrik
            highlightRow(indicatorId);
        }

        function highlightRow(indicatorId) {
            // Reset semua highlight
            document.querySelectorAll('[data-rubrik-row]').forEach(function (el) {
                el.classList.remove('bg-sky-50', 'ring-1', 'ring-sky-200');
            });
            // Set highlight pada baris yang dipilih
            var target = document.querySelector('[data-rubrik-row="' + indicatorId + '"]');
            if (target) {
                target.classList.add('bg-sky-50', 'ring-1', 'ring-sky-200');
            }
        }

        function escHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        // ── Event: tombol rubrik per indikator ───────────────────────
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-rubrik-indicator');
            if (!btn) return;
            var indicatorId = parseInt(btn.dataset.rubrikIndicator, 10);
            switchTab('aktif');
            showRubrikForIndicator(indicatorId);
            openDrawer();
        });

        // ── Event: tombol buka rubrik di header ──────────────────────
        var btnOpen = document.getElementById('btn-open-rubrik');
        if (btnOpen) {
            btnOpen.addEventListener('click', openDrawer);
        }

        // ── Event: tutup drawer ──────────────────────────────────────
        var btnClose = document.getElementById('btn-close-rubrik');
        if (btnClose) btnClose.addEventListener('click', closeDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDrawer();
        });

    })();
    </script>

    @endif
    @endpush

</x-layouts.validator>
