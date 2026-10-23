{{--
    admin/assessment/create.blade.php
    Card grid view: daftar assessment yang sudah ada dengan opsi detail/edit & duplikat.
    Tombol "Buat Assessment" mengarah ke create.new (form dari nol).
    Migrasi dari: /admin/assessment/create/page.tsx + CreateAssessmentListClient.tsx
    Data: $assessments (Collection<Assessment>) dengan categories.indicators
--}}
<x-layouts.admin pageTitle="Create Assessment">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Create Assessment</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Daftar template assessment yang telah dibuat
                </p>
            </div>
            <a href="{{ route('admin.assessment.create.new') }}"
               class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Assessment
            </a>
        </div>

        @if (isset($assessments) && $assessments->isNotEmpty())
            {{-- Card grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($assessments as $a)
                    @php
                        $statusConfig = [
                            'DRAFT'     => ['label' => 'Draft',     'cls' => 'bg-gray-100 text-gray-600'],
                            'PUBLISHED' => ['label' => 'Published', 'cls' => 'bg-green-100 text-green-700'],
                            'ARCHIVED'  => ['label' => 'Archived',  'cls' => 'bg-amber-100 text-amber-700'],
                            'REVISION'  => ['label' => 'Revision',  'cls' => 'bg-orange-100 text-orange-700'],
                        ];
                        $cfg = $statusConfig[$a->status] ?? $statusConfig['DRAFT'];
                        $totalIndicators = $a->categories->sum(fn ($c) => $c->indicators->count());
                    @endphp
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                        {{-- Status + periode --}}
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $cfg['cls'] }}">
                                {{ $cfg['label'] }}
                            </span>
                            <span class="flex items-center gap-1 text-xs text-gray-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $a->periode }}
                            </span>
                        </div>

                        {{-- Title --}}
                        <h3 class="font-semibold text-gray-900 leading-snug">{{ $a->title }}</h3>
                        @if ($a->description)
                            <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ $a->description }}</p>
                        @endif

                        {{-- Meta --}}
                        <div class="mt-3 flex items-center gap-3 text-xs text-gray-400">
                            <span>{{ $a->categories->count() }} kategori</span>
                            <span>·</span>
                            <span>{{ $totalIndicators }} indikator</span>
                        </div>

                        {{-- Actions --}}
                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('admin.assessment.show', $a->id) }}"
                               class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Detail / Edit
                            </a>
                            <button type="button"
                                    data-duplicate-id="{{ $a->id }}"
                                    data-duplicate-title="{{ $a->title }}"
                                    data-duplicate-categories="{{ $a->categories->count() }}"
                                    data-duplicate-indicators="{{ $totalIndicators }}"
                                    title="Duplikat ke periode baru"
                                    class="flex items-center justify-center gap-1.5 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-medium text-sky-700 hover:bg-sky-100 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-1M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                                </svg>
                                Duplikat
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white py-20 text-center">
                <svg class="w-14 h-14 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-gray-500 font-medium">Belum ada assessment</p>
                <p class="mt-1 text-sm text-gray-400 max-w-xs">
                    Buat template assessment pertama untuk mulai mengelola penilaian kecamatan
                </p>
                <a href="{{ route('admin.assessment.create.new') }}"
                   class="mt-5 flex items-center gap-2 rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Assessment Pertama
                </a>
            </div>
        @endif
    </div>

    {{-- Modal Duplikat --}}
    <div id="dup-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Duplikat Assessment</h2>
                <button type="button" id="dup-close"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                        aria-label="Tutup">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="dup-form" method="POST" action="" class="px-6 py-5 space-y-4">
                @csrf
                <input type="hidden" name="source_id" id="dup-source-id" value="">
                <div class="rounded-lg bg-sky-50 border border-sky-100 px-4 py-3 text-sm text-sky-700">
                    <p class="font-medium" id="dup-title-text"></p>
                    <p class="text-xs text-sky-500 mt-0.5" id="dup-meta-text"></p>
                </div>
                <p class="text-sm text-gray-500">
                    Semua kategori dan indikator akan disalin ke assessment baru dengan status <strong>Draft</strong>.
                </p>
                <div class="space-y-1.5">
                    <label for="dup-periode" class="block text-sm font-medium text-gray-700">
                        Periode Baru
                    </label>
                    <input type="text" id="dup-periode" name="periode"
                           placeholder="contoh: {{ now()->year + 1 }}"
                           class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500/50 focus:border-sky-400 transition-colors"
                           autofocus>
                    <p id="dup-error" class="text-xs text-red-500 hidden"></p>
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" id="dup-cancel"
                            class="flex-1 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="flex-1 flex items-center justify-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-1M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                        </svg>
                        Duplikat
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            var modal = document.getElementById('dup-modal');
            var form  = document.getElementById('dup-form');
            var errEl = document.getElementById('dup-error');

            function openModal(btn) {
                var id        = btn.dataset.duplicateId;
                var title     = btn.dataset.duplicateTitle;
                var cats      = btn.dataset.duplicateCategories;
                var inds      = btn.dataset.duplicateIndicators;

                document.getElementById('dup-source-id').value = id;
                document.getElementById('dup-title-text').textContent = title;
                document.getElementById('dup-meta-text').textContent  = cats + ' kategori · ' + inds + ' indikator';
                form.action = '{{ route("admin.assessment.store") }}';
                errEl.classList.add('hidden');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.getElementById('dup-periode').value = '';
            }

            document.querySelectorAll('[data-duplicate-id]').forEach(function (btn) {
                btn.addEventListener('click', function () { openModal(btn); });
            });
            document.getElementById('dup-close')?.addEventListener('click', closeModal);
            document.getElementById('dup-cancel')?.addEventListener('click', closeModal);
            modal?.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

            form?.addEventListener('submit', function (e) {
                var periode = document.getElementById('dup-periode').value.trim();
                if (!periode) {
                    e.preventDefault();
                    errEl.textContent = 'Periode wajib diisi';
                    errEl.classList.remove('hidden');
                }
            });
        })();
    </script>
    @endpush
</x-layouts.admin>
