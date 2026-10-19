{{--
    admin/assessment/show.blade.php
    Detail assessment: judul, periode, status, kategori, dan indikator.
    Data: $id (int) — ID assessment
--}}
<x-layouts.admin pageTitle="Detail Assessment">
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
                <h2 class="text-2xl font-bold text-gray-900">Detail Assessment</h2>
                <div class="flex items-center gap-2">
                    {{-- Tombol Mulai Revisi (hanya PUBLISHED) --}}
                    @if ($assessment->status === 'PUBLISHED')
                        <form method="POST" action="{{ route('admin.assessment.update', $id) }}" class="inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="title" value="{{ $assessment->title }}">
                            <input type="hidden" name="description" value="{{ $assessment->description ?? '' }}">
                            <input type="hidden" name="periode" value="{{ $assessment->periode }}">
                            <input type="hidden" name="status" value="REVISION">
                            <button type="submit"
                                    class="flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3.5 py-2 text-sm font-medium text-amber-700 hover:bg-amber-100 transition-colors"
                                    title="Kunci assessment untuk mengedit struktur indikator">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Mulai Revisi
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('admin.assessment.edit', $id) }}"
                       class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>

                    {{-- Tombol Hapus --}}
                    <button type="button" id="btn-delete-assessment"
                            class="flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3.5 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
                            title="Hapus assessment">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif
        @error('delete')
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        {{-- Info card --}}
        @isset($assessment)
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $assessment->title }}</h3>
                    @if ($assessment->description)
                        <p class="mt-2 text-sm text-gray-500">{{ $assessment->description }}</p>
                    @endif
                </div>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-400 text-xs uppercase tracking-wide">Periode</span>
                        <p class="font-medium text-gray-800">{{ $assessment->periode }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs uppercase tracking-wide">Status</span>
                        @php
                            $statusMap = [
                                'DRAFT'     => ['label' => 'Draft',     'cls' => 'bg-gray-100 text-gray-600'],
                                'PUBLISHED' => ['label' => 'Published', 'cls' => 'bg-green-100 text-green-700'],
                                'REVISION'  => ['label' => 'Revisi',    'cls' => 'bg-yellow-100 text-yellow-700'],
                                'ARCHIVED'  => ['label' => 'Arsip',     'cls' => 'bg-slate-100 text-slate-600'],
                            ];
                            $s = $statusMap[$assessment->status] ?? $statusMap['DRAFT'];
                        @endphp
                        <span class="inline-block mt-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $s['cls'] }}">{{ $s['label'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs uppercase tracking-wide">Kategori</span>
                        <p class="font-medium text-gray-800">{{ $assessment->categories->count() }} kategori</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kategori & Indikator --}}
        @foreach ($assessment->categories->sortBy('order') as $category)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                {{-- Header kategori --}}
                <div class="flex items-center gap-3 px-5 py-4 bg-sky-600">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/20 text-xs font-bold text-white shrink-0">
                        {{ $category->code }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-white">{{ $category->name }}</p>
                        @if ($category->description)
                            <p class="text-xs text-sky-100 mt-0.5">{{ $category->description }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 text-xs text-sky-200">{{ $category->indicators->count() }} indikator</span>
                </div>

                {{-- Daftar indikator --}}
                <div class="divide-y divide-gray-100">
                    @foreach ($category->indicators->sortBy('number') as $ind)
                        <div class="px-5 py-3 flex items-start gap-3">
                            <span class="w-6 text-center text-xs font-bold text-gray-400 mt-0.5 shrink-0">{{ $ind->number }}</span>
                            <p class="flex-1 text-sm text-gray-800">{{ $ind->indicator }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Scoring rule (threshold) --}}
                @if (!empty($category->scoring_rule))
                    <div class="border-t border-gray-100 bg-gray-50 px-5 py-3">
                        @php
                            $rules = $category->scoring_rule;
                            $dotColors = [
                                'Rintisan'   => 'bg-red-400',
                                'Berkembang' => 'bg-orange-400',
                                'Maju'       => 'bg-amber-400',
                                'Berdaya'    => 'bg-green-500',
                            ];
                        @endphp
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm">
                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Threshold</span>
                            @foreach ($rules as $i => $rule)
                                @php
                                    $dotCls  = $dotColors[$rule['label']] ?? 'bg-gray-400';
                                    $isLast  = $i === count($rules) - 1;
                                    $prevMax = $i > 0 ? (int)($rules[$i - 1]['max'] ?? 0) + 1 : 1;
                                    $thisMax = isset($rule['max']) ? (int)$rule['max'] : null;
                                @endphp
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full {{ $dotCls }}"></span>
                                    <span class="font-medium text-gray-700">{{ $rule['label'] }}</span>
                                    <span class="text-gray-400">
                                        @if ($isLast && $thisMax === null)
                                            &gt;{{ $prevMax - 1 }}
                                        @else
                                            {{ $prevMax }}–{{ $thisMax }}
                                        @endif
                                    </span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
        @else
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <p class="text-sm text-gray-500">Assessment tidak ditemukan.</p>
            </div>
        @endisset
    </div>

    {{-- ── Modal Konfirmasi Hapus ──────────────────────────────────── --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Hapus Assessment</h2>
                <button type="button" id="del-close"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                        aria-label="Tutup">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="del-form" method="POST" action="{{ route('admin.assessment.destroy', $id) }}" class="px-6 py-5 space-y-4">
                @csrf
                @method('DELETE')
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 flex items-start gap-3">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                    </svg>
                    <div>
                        <p class="font-medium">Tindakan ini tidak dapat dibatalkan.</p>
                        <p class="mt-0.5 text-red-600">
                            Assessment <strong>{{ $assessment->title ?? '' }}</strong> beserta seluruh kategori dan indikator akan dihapus permanen.
                        </p>
                    </div>
                </div>
                @if (isset($assessment) && $assessment->status === 'PUBLISHED')
                    <p class="text-xs text-gray-500">
                        Catatan: jika sudah ada kecamatan yang mengisi, penghapusan akan ditolak. Pertimbangkan mengarsipkan sebagai gantinya.
                    </p>
                @endif
                <div class="flex gap-3 pt-1">
                    <button type="button" id="del-cancel"
                            class="flex-1 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="flex-1 flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                        </svg>
                        Hapus Permanen
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            var modal = document.getElementById('delete-modal');
            var btn   = document.getElementById('btn-delete-assessment');
            if (!modal || !btn) return;

            function open()  { modal.classList.remove('hidden'); modal.classList.add('flex'); }
            function close() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

            btn.addEventListener('click', open);
            document.getElementById('del-close')?.addEventListener('click', close);
            document.getElementById('del-cancel')?.addEventListener('click', close);
            modal?.addEventListener('click', function (e) { if (e.target === modal) close(); });
        })();
    </script>
    @endpush
</x-layouts.admin>
