{{--
    admin/assessment/edit.blade.php
    Form edit assessment. Dua bagian:
      1. Metadata (judul, deskripsi, periode, status) — selalu editable
      2. Struktur (kategori + indikator + scoring rule)
         - DRAFT: editable, simpan ke v1 via update()
         - PUBLISHED: read-only + tombol "Mulai Revisi" (set REVISION)
         - REVISION: editable + tombol "Publish Versi Baru" (publishVersion)
         - ARCHIVED: read-only
    Data: $id, $assessment, $structure (array JSON untuk pre-populate editor)
--}}
<x-layouts.admin pageTitle="Edit Assessment">
    <div class="space-y-6">
        {{-- Breadcrumb --}}
        <div>
            <a href="{{ route('admin.assessment.create') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Daftar
            </a>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-2xl font-bold text-gray-900">Edit Assessment</h2>
                <a href="{{ route('admin.assessment.show', $id) }}"
                   class="flex items-center gap-1.5 text-sm text-sky-600 hover:text-sky-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Lihat Detail
                </a>
            </div>
        </div>

        @error('status')
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror
        @error('categories_json')
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror
        @error('delete')
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        @php
            $isDraft     = $assessment->status === 'DRAFT';
            $isPublished = $assessment->status === 'PUBLISHED';
            $isRevision  = $assessment->status === 'REVISION';
            $isArchived  = $assessment->status === 'ARCHIVED';
            $canEditStructure = $isDraft || $isRevision;
        @endphp

        {{-- ── Banner mode revisi ─────────────────────────────────────── --}}
        @if ($isRevision)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm">
                        <p class="font-medium text-amber-800">Mode Revisi Aktif</p>
                        <p class="text-amber-700 mt-0.5">
                            Kecamatan yang <strong>sedang mengisi</strong> tetap bisa melanjutkan. Kecamatan yang <strong>belum mulai</strong> tidak bisa mengakses assessment hingga versi baru dipublish. Edit struktur di bawah lalu klik <strong>Publish Versi Baru</strong>.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Bagian 1: Metadata ───────────────────────────────────────--}}
        <form method="POST" action="{{ route('admin.assessment.update', $id) }}" class="space-y-0">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-semibold text-gray-800 text-sm">Informasi Dasar</h3>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Judul Assessment <span class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" required
                               value="{{ old('title', $assessment->title ?? '') }}"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('title') border-red-300 @enderror">
                        @error('title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 resize-none">{{ old('description', $assessment->description ?? '') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="periode" class="block text-sm font-medium text-gray-700 mb-1">Periode <span class="text-red-500">*</span></label>
                            <input type="text" id="periode" name="periode" required
                                   value="{{ old('periode', $assessment->periode ?? '') }}"
                                   class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('periode') border-red-300 @enderror">
                            @error('periode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" name="status"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                @php
                                    $statusOptions = [
                                        'DRAFT'     => 'Draft',
                                        'PUBLISHED' => 'Published',
                                        'REVISION'  => 'Revisi (lock)',
                                        'ARCHIVED'  => 'Arsip',
                                    ];
                                    // Transisi yang diizinkan dari status saat ini
                                    $allowed = match($assessment->status) {
                                        'DRAFT'     => ['DRAFT', 'PUBLISHED', 'ARCHIVED'],
                                        'PUBLISHED' => ['PUBLISHED', 'REVISION', 'ARCHIVED'],
                                        'REVISION'  => ['REVISION', 'PUBLISHED'],
                                        'ARCHIVED'  => ['ARCHIVED', 'DRAFT'],
                                        default     => ['DRAFT'],
                                    };
                                @endphp
                                @foreach ($statusOptions as $val => $label)
                                    @php $disabled = !in_array($val, $allowed, true) @endphp
                                    <option value="{{ $val }}" {{ old('status', $assessment->status) === $val ? 'selected' : '' }} {{ $disabled ? 'disabled' : '' }}>
                                        {{ $label }}@if ($disabled) (tidak tersedia)@endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400">
                                Pilih <strong>Revisi</strong> untuk mengunci dan mengedit struktur indikator.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                    <p class="text-xs text-gray-400">Perubahan metadata langsung berlaku.</p>
                    <button type="submit"
                            class="rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                        Simpan Metadata
                    </button>
                </div>
            </div>
        </form>

        {{-- ── Bagian 2: Struktur Kategori & Indikator ────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-800 text-sm">Struktur Kategori & Indikator</h3>
                    @if ($assessment->current_version > 1)
                        <p class="text-xs text-gray-400 mt-0.5">Versi saat ini: v{{ $assessment->current_version }}</p>
                    @endif
                </div>
                @if ($isPublished)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 border border-green-200 px-2.5 py-1 text-xs font-medium text-green-700">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Published — Read Only
                    </span>
                @elseif ($isRevision)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-1 text-xs font-medium text-amber-700">
                        Sedang Direvisi
                    </span>
                @elseif ($isArchived)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600">
                        Diarsipkan
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600">
                        Draft — Bisa Diedit
                    </span>
                @endif
            </div>

            @if ($canEditStructure)
                {{-- Mode editable (DRAFT / REVISION) --}}
                <div class="p-6 space-y-6">
                    @if ($isRevision)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ringkasan Perubahan</label>
                            <input type="text" id="fld-changes-summary" maxlength="2000"
                                   placeholder="contoh: Menambahkan indikator pemerintahan terbuka, mengubah skor maks kategori B"
                                   class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        </div>
                    @endif

                    {{-- Info dasar hanya untuk DRAFT (judul/periode sudah di form metadata) --}}
                    @if ($isDraft)
                        <div class="rounded-lg bg-sky-50 border border-sky-100 px-4 py-3 text-xs text-sky-700">
                            Assessment masih Draft. Semua perubahan struktur langsung tersimpan ke versi 1.
                        </div>
                    @endif

                    {{-- Kategori container (diisi JS) --}}
                    <div id="categories-container" class="space-y-4"></div>

                    {{-- Tombol tambah kategori --}}
                    <button type="button" id="btn-add-category"
                            class="flex items-center gap-2 rounded-xl border-2 border-dashed border-gray-300 px-6 py-4 text-sm font-medium text-gray-500 hover:border-sky-400 hover:text-sky-600 transition-colors w-full justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Kategori Baru
                    </button>
                </div>

                {{-- Action bar --}}
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between gap-3">
                    @if ($isRevision)
                        <p class="text-xs text-amber-600">
                            Saat publish, kecamatan yang sudah mengisi akan mendapat notifikasi indikator baru.
                        </p>
                        <button type="button" id="btn-publish-version"
                                class="flex items-center gap-2 rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Publish Versi Baru
                        </button>
                    @else
                        <p class="text-xs text-gray-400">Struktur tersimpan ke versi 1 saat Simpan Struktur diklik.</p>
                        <button type="button" id="btn-save-structure"
                                class="flex items-center gap-2 rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan Struktur
                        </button>
                    @endif
                </div>

            @else
                {{-- Mode read-only (PUBLISHED / ARCHIVED) --}}
                <div class="p-6 space-y-4">
                    @if ($isPublished)
                        <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800 flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                            </svg>
                            <div>
                                <p class="font-medium">Assessment sudah dipublish.</p>
                                <p class="mt-0.5 text-amber-700">Untuk mengubah struktur indikator, ubah status ke <strong>Revisi</strong> di form Informasi Dasar di atas. Kecamatan yang sedang mengisi tetap bisa melanjutkan, yang belum mulai akan terkunci.</p>
                            </div>
                        </div>
                    @endif

                    @foreach ($assessment->categories->sortBy('order') as $category)
                        <div class="rounded-lg border border-gray-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-5 py-3 bg-sky-600">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/20 text-xs font-bold text-white shrink-0">
                                    {{ $category->code }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-white text-sm">{{ $category->name }}</p>
                                    @if ($category->description)
                                        <p class="text-xs text-sky-100 mt-0.5">{{ $category->description }}</p>
                                    @endif
                                </div>
                                <span class="text-xs text-sky-200">{{ $category->indicators->count() }} indikator</span>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @foreach ($category->indicators->sortBy('number') as $ind)
                                    <div class="px-5 py-3 flex items-start gap-3">
                                        <span class="w-6 text-center text-xs font-bold text-gray-400 mt-0.5 shrink-0">{{ $ind->number }}</span>
                                        <p class="flex-1 text-sm text-gray-800">{{ $ind->indicator }}</p>
                                        <span class="text-xs text-gray-400 shrink-0">maks {{ $ind->max_score }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Hidden forms untuk submit struktur ──────────────────────── --}}
        {{-- Form publish versi baru (REVISION) --}}
        <form id="publish-form" method="POST" action="{{ route('admin.assessment.publish-version', $id) }}" class="hidden">
            @csrf
            <input type="hidden" name="changes_summary" id="hf-changes-summary">
            <input type="hidden" name="categories_json" id="hf-categories-publish">
        </form>

        {{-- Form simpan struktur DRAFT (via update dengan categories_json) --}}
        <form id="structure-form" method="POST" action="{{ route('admin.assessment.update', $id) }}" class="hidden">
            @csrf
            @method('PUT')
            <input type="hidden" name="title" value="{{ $assessment->title }}">
            <input type="hidden" name="description" value="{{ $assessment->description ?? '' }}">
            <input type="hidden" name="periode" value="{{ $assessment->periode }}">
            <input type="hidden" name="status" value="{{ $assessment->status }}">
            <input type="hidden" name="categories_json" id="hf-categories-structure">
        </form>
    </div>

    @push('scripts')
    <script>
        (function () {
            // Hanya jalankan editor jika mode editable
            var canEdit = @json($canEditStructure);
            if (!canEdit) return;

            // --- Pre-populate state dari data existing ---
            var tempCounter = 0;
            function uid() { return 'tmp_' + (++tempCounter); }

            var initialStructure = @json($structure);

            function newIndicator(n) {
                return { tempId: uid(), number: n, indicator: '', maxScore: 4 };
            }
            function newCategory(order) {
                return {
                    tempId: uid(),
                    code: String.fromCharCode(65 + order),
                    name: '', description: '', order: order,
                    indicators: [newIndicator(1)],
                    scoringRule: [],
                    showScoringRule: false,
                };
            }

            // Build state dari struktur existing (atau default 1 kategori kosong)
            var state;
            if (initialStructure && initialStructure.length > 0) {
                state = {
                    categories: initialStructure.map(function (cat, idx) {
                        return {
                            tempId: uid(),
                            code: cat.code,
                            name: cat.name || '',
                            description: cat.description || '',
                            order: idx,
                            indicators: (cat.indicators || []).map(function (ind) {
                                return {
                                    tempId: uid(),
                                    number: ind.number,
                                    indicator: ind.indicator || '',
                                    maxScore: ind.maxScore || 4,
                                };
                            }),
                            scoringRule: cat.scoringRule || [],
                            showScoringRule: (cat.scoringRule || []).length > 0,
                        };
                    }),
                };
            } else {
                state = { categories: [newCategory(0)] };
            }

            // --- DOM helpers ----------------------------------------------------
            var $ = function (s) { return document.querySelector(s); };
            var container = $('#categories-container');

            function escapeHtml(s) {
                var d = document.createElement('div');
                d.textContent = s == null ? '' : s;
                return d.innerHTML;
            }

            // --- Render: Categories --------------------------------------------
            function renderCategories() {
                container.innerHTML = '';
                state.categories.forEach(function (cat) {
                    container.appendChild(renderCategoryBlock(cat));
                });
            }

            function renderCategoryBlock(cat) {
                var div = document.createElement('div');
                div.className = 'rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden';
                div.dataset.tempId = cat.tempId;

                // Header
                var header = document.createElement('div');
                header.className = 'flex items-center justify-between bg-sky-600 px-6 py-3';
                header.innerHTML =
                    '<div class="flex items-center gap-3">' +
                        '<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/20 text-sm font-bold text-white cat-code">' + escapeHtml(cat.code) + '</span>' +
                        '<input type="text" class="cat-name bg-transparent border-b border-white/40 text-white placeholder:text-white/60 text-sm font-medium focus:outline-none focus:border-white w-72" placeholder="Nama kategori..." maxlength="255" value="' + escapeHtml(cat.name) + '">' +
                    '</div>' +
                    '<button type="button" class="cat-remove flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs text-white/70 hover:bg-white/10 hover:text-white ' + (state.categories.length === 1 ? 'opacity-30 cursor-not-allowed' : '') + '">' +
                        '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>' +
                        'Hapus Kategori' +
                    '</button>';

                div.appendChild(header);

                // Body
                var body = document.createElement('div');
                body.className = 'px-6 pb-6';

                // Description
                var descDiv = document.createElement('div');
                descDiv.className = 'pt-4 pb-2';
                descDiv.innerHTML =
                    '<input type="text" class="cat-desc w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20" placeholder="Deskripsi kategori (opsional)..." maxlength="2000" value="' + escapeHtml(cat.description) + '">';
                body.appendChild(descDiv);

                // Indicators
                body.appendChild(renderIndicators(cat));

                // Scoring rule toggle
                var srDiv = document.createElement('div');
                srDiv.className = 'mt-4';
                srDiv.innerHTML =
                    '<button type="button" class="cat-sr-toggle flex items-center gap-1.5 text-xs text-gray-500 hover:text-amber-700 transition-colors">' +
                        '<svg class="w-3.5 h-3.5 sr-chevron-down" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>' +
                        '<svg class="w-3.5 h-3.5 sr-chevron-up hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>' +
                        'Aturan penilaian kategori' +
                        '<span class="sr-badge ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 hidden">0 threshold</span>' +
                    '</button>';
                var srEditor = document.createElement('div');
                srEditor.className = 'mt-4 hidden';
                body.appendChild(srDiv);
                body.appendChild(srEditor);

                div.appendChild(body);

                // --- Wire events ---
                var nameInput = header.querySelector('.cat-name');
                nameInput.addEventListener('input', function () { cat.name = this.value; });

                var descInput = descDiv.querySelector('.cat-desc');
                descInput.addEventListener('input', function () { cat.description = this.value; });

                header.querySelector('.cat-remove').addEventListener('click', function () {
                    if (state.categories.length === 1) return;
                    state.categories = state.categories.filter(function (c) { return c.tempId !== cat.tempId; })
                        .map(function (c, i) { c.order = i; c.code = String.fromCharCode(65 + i); return c; });
                    renderCategories();
                });

                // Indicator handlers
                body.querySelector('.ind-add').addEventListener('click', function () {
                    cat.indicators.push(newIndicator(cat.indicators.length + 1));
                    var indList = body.querySelector('.ind-list');
                    indList.appendChild(renderIndicatorRow(cat, cat.indicators[cat.indicators.length - 1]));
                });

                // Scoring rule toggle
                var toggle = srDiv.querySelector('.cat-sr-toggle');
                toggle.addEventListener('click', function () {
                    cat.showScoringRule = !cat.showScoringRule;
                    if (cat.showScoringRule) {
                        srEditor.classList.remove('hidden');
                        toggle.querySelector('.sr-chevron-down').classList.add('hidden');
                        toggle.querySelector('.sr-chevron-up').classList.remove('hidden');
                        renderScoringRuleEditor(cat, srEditor);
                    } else {
                        srEditor.classList.add('hidden');
                        toggle.querySelector('.sr-chevron-down').classList.remove('hidden');
                        toggle.querySelector('.sr-chevron-up').classList.add('hidden');
                    }
                });

                if (cat.showScoringRule) {
                    srEditor.classList.remove('hidden');
                    toggle.querySelector('.sr-chevron-down').classList.add('hidden');
                    toggle.querySelector('.sr-chevron-up').classList.remove('hidden');
                    renderScoringRuleEditor(cat, srEditor);
                }

                return div;
            }

            function renderIndicators(cat) {
                var wrap = document.createElement('div');
                wrap.className = 'mt-4';

                var header = document.createElement('div');
                header.className = 'grid grid-cols-[32px_1fr_auto] gap-2 px-2 mb-1';
                header.innerHTML =
                    '<span class="text-xs font-semibold text-gray-500 text-center">No</span>' +
                    '<span class="text-xs font-semibold text-gray-500">Indikator</span><span></span>';
                wrap.appendChild(header);

                var list = document.createElement('div');
                list.className = 'ind-list flex flex-col gap-2';
                cat.indicators.forEach(function (ind) {
                    list.appendChild(renderIndicatorRow(cat, ind));
                });
                wrap.appendChild(list);

                var addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'ind-add mt-3 flex items-center gap-2 rounded-lg border border-dashed border-gray-300 px-4 py-2 text-sm text-gray-500 hover:border-sky-400 hover:text-sky-600 transition-colors w-full justify-center';
                addBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Tambah Indikator';
                wrap.appendChild(addBtn);

                return wrap;
            }

            function renderIndicatorRow(cat, ind) {
                var row = document.createElement('div');
                row.className = 'grid grid-cols-[32px_1fr_auto] gap-2 items-start';
                row.dataset.tempId = ind.tempId;
                row.innerHTML =
                    '<div class="flex items-center justify-center pt-2"><span class="text-sm font-medium text-gray-500 ind-num">' + ind.number + '</span></div>' +
                    '<textarea class="ind-text w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20" placeholder="Tuliskan indikator penilaian..." rows="2" maxlength="2000">' + escapeHtml(ind.indicator) + '</textarea>' +
                    '<button type="button" class="ind-remove mt-1.5 flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors ' + (cat.indicators.length === 1 ? 'opacity-30 cursor-not-allowed' : '') + '">' +
                        '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>' +
                    '</button>';

                row.querySelector('.ind-text').addEventListener('input', function () { ind.indicator = this.value; });
                row.querySelector('.ind-remove').addEventListener('click', function () {
                    if (cat.indicators.length === 1) return;
                    cat.indicators = cat.indicators.filter(function (i) { return i.tempId !== ind.tempId; })
                        .map(function (i, idx) { i.number = idx + 1; return i; });
                    var wrap = container.querySelector('[data-temp-id="' + cat.tempId + '"] .ind-list');
                    wrap.innerHTML = '';
                    cat.indicators.forEach(function (i) { wrap.appendChild(renderIndicatorRow(cat, i)); });
                });

                return row;
            }

            function renderScoringRuleEditor(cat, container) {
                container.innerHTML =
                    '<div class="rounded-lg border border-dashed border-amber-300 bg-amber-50/50 p-4">' +
                        '<div class="flex items-center justify-between mb-3">' +
                            '<div><p class="text-xs font-semibold text-amber-800">Aturan Penilaian Kategori</p>' +
                            '<p class="text-xs text-amber-600 mt-0.5">Tentukan label klasifikasi berdasarkan total skor. Baris terakhir tanpa batas maks = fallback.</p></div>' +
                        '</div>' +
                        '<div class="sr-rules-list mb-2"></div>' +
                        '<button type="button" class="sr-add flex items-center gap-1.5 text-xs text-amber-700 hover:text-amber-900 transition-colors">' +
                            '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Tambah threshold' +
                        '</button>' +
                    '</div>';

                var list = container.querySelector('.sr-rules-list');

                function renderRules() {
                    list.innerHTML = '';
                    if (cat.scoringRule.length === 0) return;
                    var hdr = document.createElement('div');
                    hdr.className = 'grid grid-cols-[80px_1fr_32px] gap-2 px-1 mb-1';
                    hdr.innerHTML = '<span class="text-xs text-gray-400 font-medium">Skor maks</span><span class="text-xs text-gray-400 font-medium">Label klasifikasi</span><span></span>';
                    list.appendChild(hdr);

                    cat.scoringRule.forEach(function (rule, idx) {
                        var row = document.createElement('div');
                        row.className = 'grid grid-cols-[80px_1fr_32px] gap-2 items-center mb-2';
                        row.innerHTML =
                            '<input type="number" min="0" class="sr-max rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-center focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20" value="' + (rule.max !== undefined && rule.max !== null ? rule.max : '') + '" placeholder="—">' +
                            '<input type="text" class="sr-label rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20" value="' + escapeHtml(rule.label) + '" placeholder="' + (idx === cat.scoringRule.length - 1 ? 'contoh: Berdaya (fallback)' : 'contoh: Rintisan') + '" maxlength="100">' +
                            '<button type="button" class="sr-remove flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors">' +
                                '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>' +
                            '</button>';

                        row.querySelector('.sr-max').addEventListener('input', function () {
                            var n = parseInt(this.value, 10);
                            rule.max = isNaN(n) ? undefined : n;
                        });
                        row.querySelector('.sr-label').addEventListener('input', function () { rule.label = this.value; });
                        row.querySelector('.sr-remove').addEventListener('click', function () {
                            cat.scoringRule.splice(idx, 1);
                            renderRules();
                            updateBadge();
                        });

                        list.appendChild(row);
                    });
                }

                function updateBadge() {
                    var badge = container.parentElement.querySelector('.sr-badge');
                    if (cat.scoringRule.length > 0) {
                        badge.textContent = cat.scoringRule.length + ' threshold';
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }

                container.querySelector('.sr-add').addEventListener('click', function () {
                    cat.scoringRule.push({ label: '' });
                    renderRules();
                    updateBadge();
                });

                renderRules();
                updateBadge();
            }

            // --- Validation ------------------------------------------------------
            function validate() {
                for (var i = 0; i < state.categories.length; i++) {
                    var cat = state.categories[i];
                    if (!cat.name.trim()) return 'Nama kategori "' + cat.code + '" wajib diisi.';
                    if (cat.indicators.length === 0) return 'Kategori "' + cat.code + '" harus punya minimal 1 indikator.';
                    for (var j = 0; j < cat.indicators.length; j++) {
                        if (!cat.indicators[j].indicator.trim())
                            return 'Indikator nomor ' + cat.indicators[j].number + ' di kategori "' + cat.code + '" belum diisi.';
                    }
                }
                return null;
            }

            // --- Build payload --------------------------------------------------
            function buildPayload() {
                return state.categories.map(function (cat) {
                    return {
                        code: cat.code,
                        name: cat.name.trim(),
                        description: cat.description.trim() || undefined,
                        order: cat.order,
                        scoringRule: cat.scoringRule.length > 0 ? cat.scoringRule : null,
                        indicators: cat.indicators.map(function (ind) {
                            return { number: ind.number, indicator: ind.indicator.trim(), maxScore: ind.maxScore };
                        }),
                    };
                });
            }

            // --- Submit handlers -------------------------------------------------
            var isRevision = @json($isRevision);

            $('#btn-add-category').addEventListener('click', function () {
                state.categories.push(newCategory(state.categories.length));
                renderCategories();
            });

            if (isRevision) {
                $('#btn-publish-version').addEventListener('click', function () {
                    var err = validate();
                    if (err) { alert(err); return; }
                    var summary = $('#fld-changes-summary').value.trim();
                    if (!summary) {
                        if (!confirm('Ringkasan perubahan kosong. Lanjutkan publish tanpa ringkasan?')) return;
                    }
                    $('#hf-changes-summary').value = summary;
                    $('#hf-categories-publish').value = JSON.stringify(buildPayload());
                    $('#publish-form').submit();
                });
            } else {
                $('#btn-save-structure').addEventListener('click', function () {
                    var err = validate();
                    if (err) { alert(err); return; }
                    $('#hf-categories-structure').value = JSON.stringify(buildPayload());
                    $('#structure-form').submit();
                });
            }

            // --- Init -----------------------------------------------------------
            renderCategories();
        })();
    </script>
    @endpush
</x-layouts.admin>
