{{--
    admin/assessment/create-new.blade.php
    Form multi-step untuk membuat assessment baru dari nol.
    Step 1: Isi assessment (judul, periode, deskripsi, kategori + indikator + scoring rule)
    Step 2: Review & konfirmasi (pilih status: Draft / Published)
    Migrasi dari: /admin/assessment/create/new/page.tsx + CreateAssessmentForm.tsx
--}}
<x-layouts.admin pageTitle="Buat Assessment Baru">
    <div class="space-y-6">
        {{-- Back + heading --}}
        <div>
            <a href="{{ route('admin.assessment.create') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-3">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Daftar Assessment
            </a>
            <h2 class="text-2xl font-bold text-gray-900">Buat Assessment Baru</h2>
            <p class="mt-1 text-sm text-gray-500">
                Definisikan assessment baru dengan kategori dan indikator yang dibutuhkan
            </p>
            <p class="mt-2 text-xs text-sky-700 bg-sky-50 border border-sky-200 rounded-lg px-3 py-2 inline-block">
                Deskripsi, skor, dan dokumen pendukung akan diisi oleh masing-masing kecamatan saat mengisi self assessment.
            </p>
        </div>

        {{-- ── Step indicator ────────────────────────────────────────── --}}
        <div class="flex items-center gap-0 mb-2" id="step-bar">
            <div class="flex items-center flex-1">
                <div class="flex items-center gap-2 text-sm font-semibold step-label" data-step="1">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold border-2 step-circle">1</span>
                    <span class="hidden sm:inline">Isi Assessment</span>
                </div>
                <div class="flex-1 h-0.5 mx-3 bg-gray-200 step-line"></div>
            </div>
            <div class="flex items-center flex-1">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-400 step-label" data-step="2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold border-2 bg-white border-gray-300 text-gray-400 step-circle">2</span>
                    <span class="hidden sm:inline">Review & Konfirmasi</span>
                </div>
            </div>
        </div>

        {{-- ── STEP 1: Form ──────────────────────────────────────────── --}}
        <div id="step-1" class="space-y-6">
            {{-- Info dasar --}}
            <div class="rounded-xl border bg-white p-6 shadow-sm space-y-4">
                <h3 class="font-semibold text-gray-800">Informasi Assessment</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Assessment <span class="text-red-500">*</span></label>
                        <input type="text" id="fld-title" maxlength="255"
                               placeholder="contoh: Self Assessment Kecamatan Berdaya {{ now()->year }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Periode <span class="text-red-500">*</span>
                            <span class="ml-1 text-xs text-gray-400 font-normal">(4 digit tahun, 1 assessment per tahun)</span>
                        </label>
                        <input type="text" id="fld-periode" maxlength="4" value="{{ now()->year }}"
                               placeholder="{{ now()->year }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi <span class="text-gray-400 font-normal">(opsional)</span></label>
                        <input type="text" id="fld-description" maxlength="2000"
                               placeholder="Deskripsi singkat assessment..."
                               class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20">
                    </div>
                </div>
            </div>

            {{-- Kategori container --}}
            <div id="categories-container" class="space-y-4"></div>

            {{-- Tombol tambah kategori --}}
            <button type="button" id="btn-add-category"
                    class="flex items-center gap-2 rounded-xl border-2 border-dashed border-gray-300 px-6 py-4 text-sm font-medium text-gray-500 hover:border-sky-400 hover:text-sky-600 transition-colors w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Kategori Baru
            </button>

            {{-- Next --}}
            <div class="flex justify-end rounded-xl border bg-white px-6 py-4 shadow-sm">
                <button type="button" id="btn-go-review"
                        class="flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Review Assessment
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── STEP 2: Review ─────────────────────────────────────────── --}}
        <div id="step-2" class="space-y-6 hidden">
            {{-- Warning --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 flex items-start gap-3">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                </svg>
                <div>
                    <p class="font-semibold">Periksa kembali seluruh isi assessment.</p>
                    <p class="mt-0.5 text-amber-700">Setelah diisi oleh kecamatan, assessment <strong>tidak dapat diubah</strong>. Pastikan semua indikator sudah benar sebelum melanjutkan.</p>
                </div>
            </div>

            {{-- Review content (dibuat dynamically oleh JS) --}}
            <div id="review-content" class="space-y-5"></div>

            {{-- Action buttons --}}
            <div class="flex items-center justify-between rounded-xl border bg-white px-6 py-4 shadow-sm">
                <button type="button" id="btn-back-edit"
                        class="flex items-center gap-2 rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Kembali Edit
                </button>
                <button type="button" id="btn-confirm"
                        class="flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Konfirmasi & Simpan
                </button>
            </div>
        </div>
    </div>

    {{-- ── Confirm Modal ──────────────────────────────────────────────── --}}
    <div id="confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="px-6 pt-6 pb-4 border-b">
                <div class="flex items-center gap-3 mb-1">
                    <div class="rounded-xl bg-amber-100 p-2">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900">Konfirmasi Akhir</h3>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    Anda akan membuat assessment <strong class="text-gray-800" id="modal-title"></strong> untuk periode <strong class="text-gray-800" id="modal-periode"></strong>.
                </p>
            </div>

            <div class="px-6 py-4 space-y-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Simpan sebagai</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" data-choice="DRAFT"
                                class="rounded-xl border-2 px-4 py-3 text-sm font-medium text-left transition-colors choice-btn">
                            <p class="font-semibold">Draft</p>
                            <p class="text-xs font-normal text-gray-400 mt-0.5">Tersimpan, belum aktif untuk kecamatan</p>
                        </button>
                        <button type="button" data-choice="PUBLISHED"
                                class="rounded-xl border-2 px-4 py-3 text-sm font-medium text-left transition-colors choice-btn">
                            <p class="font-semibold">Publish</p>
                            <p class="text-xs font-normal text-gray-400 mt-0.5">Langsung aktif, kecamatan bisa mengisi</p>
                        </button>
                    </div>
                </div>

                <div id="publish-warning" class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800 leading-relaxed hidden">
                    <svg class="w-3.5 h-3.5 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                    </svg>
                    Setelah assessment diisi oleh kecamatan, <strong>konten tidak dapat diubah</strong>. Pastikan semua indikator sudah benar.
                </div>
            </div>

            <div class="flex gap-2 px-6 pb-6">
                <button type="button" id="modal-cancel"
                        class="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Kembali
                </button>
                <button type="button" id="modal-confirm"
                        class="flex-1 flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors">
                    Ya, Publish Sekarang
                </button>
            </div>
        </div>
    </div>

    {{-- ── Hidden form submit ke store ────────────────────────────────── --}}
    <form id="create-form" method="POST" action="{{ route('admin.assessment.store') }}" class="hidden">
        @csrf
        <input type="hidden" name="title" id="hf-title">
        <input type="hidden" name="description" id="hf-description">
        <input type="hidden" name="periode" id="hf-periode">
        <input type="hidden" name="status" id="hf-status" value="DRAFT">
        <input type="hidden" name="categories_json" id="hf-categories">
    </form>

    @push('scripts')
    <script>
        (function () {
            // --- State -----------------------------------------------------------
            var tempCounter = 0;
            function uid() { return 'tmp_' + (++tempCounter); }

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

            var state = {
                step: 1,
                title: '',
                description: '',
                periode: '{{ now()->year }}',
                categories: [newCategory(0)],
            };
            var modalChoice = 'PUBLISHED';

            // --- DOM helpers ----------------------------------------------------
            var $ = function (s) { return document.querySelector(s); };
            var container = $('#categories-container');

            function escapeHtml(s) {
                var d = document.createElement('div');
                d.textContent = s;
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
                    // Re-render this category's indicators
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
                            '<input type="number" min="0" class="sr-max rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-center focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20" value="' + (rule.max !== undefined ? rule.max : '') + '" placeholder="—">' +
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

            // --- Step bar -------------------------------------------------------
            function updateStepBar() {
                var labels = document.querySelectorAll('.step-label');
                var lines = document.querySelectorAll('.step-line');
                labels.forEach(function (lbl) {
                    var n = parseInt(lbl.dataset.step, 10);
                    var circle = lbl.querySelector('.step-circle');
                    lbl.classList.remove('text-sky-700', 'text-green-600', 'text-gray-400');
                    if (state.step === n) {
                        lbl.classList.add('text-sky-700');
                        circle.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold border-2 bg-sky-600 border-sky-600 text-white step-circle';
                        circle.textContent = n;
                    } else if (state.step > n) {
                        lbl.classList.add('text-green-600');
                        circle.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold border-2 bg-green-500 border-green-500 text-white step-circle';
                        circle.textContent = '✓';
                    } else {
                        lbl.classList.add('text-gray-400');
                        circle.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold border-2 bg-white border-gray-300 text-gray-400 step-circle';
                        circle.textContent = n;
                    }
                });
                lines.forEach(function (ln) { ln.className = state.step > 1 ? 'flex-1 h-0.5 mx-3 bg-green-400 step-line' : 'flex-1 h-0.5 mx-3 bg-gray-200 step-line'; });
            }

            // --- Validation ------------------------------------------------------
            function validate() {
                state.title = $('#fld-title').value.trim();
                state.description = $('#fld-description').value.trim();
                state.periode = $('#fld-periode').value.trim();

                if (!state.title) return 'Judul assessment wajib diisi.';
                if (!state.periode) return 'Periode wajib diisi.';
                if (!/^\d{4}$/.test(state.periode)) return 'Periode harus berupa 4 digit tahun (contoh: 2026).';
                for (var i = 0; i < state.categories.length; i++) {
                    var cat = state.categories[i];
                    if (!cat.name.trim()) return 'Nama kategori "' + cat.code + '" wajib diisi.';
                    for (var j = 0; j < cat.indicators.length; j++) {
                        if (!cat.indicators[j].indicator.trim())
                            return 'Indikator nomor ' + cat.indicators[j].number + ' di kategori "' + cat.code + '" belum diisi.';
                    }
                }
                return null;
            }

            // --- Review step ----------------------------------------------------
            function renderReview() {
                var totalIndicators = state.categories.reduce(function (s, c) { return s + c.indicators.length; }, 0);
                var html = '<div class="rounded-xl border border-sky-200 bg-sky-50 p-5">' +
                    '<p class="text-xs font-semibold text-sky-600 uppercase tracking-wide mb-3">Informasi Assessment</p>' +
                    '<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">' +
                        '<div><p class="text-xs text-gray-500 mb-0.5">Judul</p><p class="font-semibold text-gray-900">' + escapeHtml(state.title) + '</p></div>' +
                        '<div><p class="text-xs text-gray-500 mb-0.5">Periode</p><p class="font-semibold text-gray-900">' + escapeHtml(state.periode) + '</p></div>' +
                        '<div><p class="text-xs text-gray-500 mb-0.5">Total</p><p class="font-semibold text-gray-900">' + state.categories.length + ' kategori · ' + totalIndicators + ' indikator</p></div>';
                if (state.description) {
                    html += '<div class="sm:col-span-3"><p class="text-xs text-gray-500 mb-0.5">Deskripsi</p><p class="text-gray-700">' + escapeHtml(state.description) + '</p></div>';
                }
                html += '</div></div>';

                html += '<div class="space-y-3">';
                state.categories.forEach(function (cat) {
                    html += '<div class="rounded-xl border border-gray-200 bg-white overflow-hidden">' +
                        '<div class="flex items-center gap-3 bg-gray-800 px-5 py-3">' +
                            '<span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/20 text-xs font-bold text-white">' + escapeHtml(cat.code) + '</span>' +
                            '<div><p class="text-sm font-semibold text-white">' + escapeHtml(cat.name) + '</p>';
                    if (cat.description) html += '<p class="text-xs text-gray-300">' + escapeHtml(cat.description) + '</p>';
                    html += '</div><span class="ml-auto text-xs text-gray-400">' + cat.indicators.length + ' indikator</span></div>';
                    html += '<ol class="divide-y divide-gray-100">';
                    cat.indicators.forEach(function (ind) {
                        html += '<li class="flex gap-3 px-5 py-3 text-sm">' +
                            '<span class="text-xs font-semibold text-gray-400 w-6 shrink-0 pt-0.5">' + ind.number + '.</span>' +
                            '<span class="text-gray-700 leading-relaxed">' + escapeHtml(ind.indicator) + '</span></li>';
                    });
                    html += '</ol>';
                    if (cat.scoringRule.length > 0) {
                        html += '<div class="px-5 py-3 bg-amber-50 border-t border-amber-100"><p class="text-xs font-semibold text-amber-700 mb-2">Aturan Penilaian</p><div class="flex flex-wrap gap-2">';
                        cat.scoringRule.forEach(function (r) {
                            html += '<span class="inline-flex items-center gap-1 rounded-full bg-white border border-amber-200 px-2.5 py-0.5 text-xs text-amber-800">' +
                                (r.max !== undefined ? '≤ ' + r.max : 'else') + ' → ' + escapeHtml(r.label) + '</span>';
                        });
                        html += '</div></div>';
                    }
                    html += '</div>';
                });
                html += '</div>';

                $('#review-content').innerHTML = html;
            }

            // --- Confirm modal --------------------------------------------------
            function openConfirmModal() {
                modalChoice = 'PUBLISHED';
                $('#modal-title').textContent = '"' + state.title + '"';
                $('#modal-periode').textContent = state.periode;
                updateChoiceButtons();
                $('#confirm-modal').classList.remove('hidden');
                $('#confirm-modal').classList.add('flex');
            }

            function closeConfirmModal() {
                $('#confirm-modal').classList.add('hidden');
                $('#confirm-modal').classList.remove('flex');
            }

            function updateChoiceButtons() {
                var btns = document.querySelectorAll('.choice-btn');
                btns.forEach(function (btn) {
                    var choice = btn.dataset.choice;
                    if (choice === modalChoice) {
                        if (choice === 'DRAFT') {
                            btn.className = 'choice-btn rounded-xl border-2 px-4 py-3 text-sm font-medium text-left transition-colors border-gray-700 bg-gray-50 text-gray-900';
                        } else {
                            btn.className = 'choice-btn rounded-xl border-2 px-4 py-3 text-sm font-medium text-left transition-colors border-sky-600 bg-sky-50 text-sky-900';
                        }
                    } else {
                        btn.className = 'choice-btn rounded-xl border-2 px-4 py-3 text-sm font-medium text-left transition-colors border-gray-200 text-gray-500 hover:border-gray-300';
                    }
                });

                var warn = $('#publish-warning');
                var confirmBtn = $('#modal-confirm');
                if (modalChoice === 'PUBLISHED') {
                    warn.classList.remove('hidden');
                    confirmBtn.className = 'flex-1 flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors bg-sky-600 hover:bg-sky-700';
                    confirmBtn.textContent = 'Ya, Publish Sekarang';
                } else {
                    warn.classList.add('hidden');
                    confirmBtn.className = 'flex-1 flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors bg-gray-700 hover:bg-gray-800';
                    confirmBtn.textContent = 'Simpan sebagai Draft';
                }
            }

            // --- Submit ---------------------------------------------------------
            function doSubmit() {
                var payload = {
                    title: state.title,
                    description: state.description || undefined,
                    periode: state.periode,
                    status: modalChoice,
                    categories: state.categories.map(function (cat) {
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
                    }),
                };

                $('#hf-title').value = payload.title;
                $('#hf-description').value = payload.description || '';
                $('#hf-periode').value = payload.periode;
                $('#hf-status').value = payload.status;
                $('#hf-categories').value = JSON.stringify(payload.categories);
                $('#create-form').submit();
            }

            // --- Event wiring ---------------------------------------------------
            $('#fld-title').addEventListener('input', function () { state.title = this.value; });
            $('#fld-periode').addEventListener('input', function () { state.periode = this.value; });
            $('#fld-description').addEventListener('input', function () { state.description = this.value; });

            $('#btn-add-category').addEventListener('click', function () {
                state.categories.push(newCategory(state.categories.length));
                renderCategories();
            });

            $('#btn-go-review').addEventListener('click', function () {
                var err = validate();
                if (err) { alert(err); return; }
                state.step = 2;
                $('#step-1').classList.add('hidden');
                $('#step-2').classList.remove('hidden');
                renderReview();
                updateStepBar();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            $('#btn-back-edit').addEventListener('click', function () {
                state.step = 1;
                $('#step-2').classList.add('hidden');
                $('#step-1').classList.remove('hidden');
                updateStepBar();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            $('#btn-confirm').addEventListener('click', openConfirmModal);
            $('#modal-cancel').addEventListener('click', closeConfirmModal);
            $('#confirm-modal').addEventListener('click', function (e) { if (e.target === this) closeConfirmModal(); });

            document.querySelectorAll('.choice-btn').forEach(function (btn) {
                btn.addEventListener('click', function () { modalChoice = btn.dataset.choice; updateChoiceButtons(); });
            });

            $('#modal-confirm').addEventListener('click', doSubmit);

            // --- Init -----------------------------------------------------------
            renderCategories();
            updateStepBar();
        })();
    </script>
    @endpush
</x-layouts.admin>
