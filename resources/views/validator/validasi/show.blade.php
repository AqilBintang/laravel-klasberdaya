{{--
    validator/validasi/show.blade.php
    Detail validasi 1 kecamatan — hanya indikator yang PERLU divalidasi (status SUBMITTED).
    Data yang diharapkan dari ValidationController::show($id):
      - $selfAssessment  : SelfAssessment (anchor — konteks user + assessment + periode)
      - $entries         : Collection<SelfAssessment> — hanya indikator status SUBMITTED
      - $summary         : [totalAll, totalValidated, totalRejected, totalDraft, totalPending]
--}}
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
                <div class="flex flex-col items-end gap-1 text-sm text-gray-500 shrink-0">
                    <span>Periode <strong class="text-gray-800">{{ $selfAssessment->periode }}</strong></span>
                    <span>Assessment <strong class="text-gray-800">
                        {{ $selfAssessment->indicator?->category?->assessment?->title ?? '—' }}
                    </strong></span>
                </div>
            </div>
        </div>

        {{-- ── Ringkasan Status ─────────────────────────────────────────── --}}
        @isset($summary)
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-600">
                {{ $summary['totalAll'] }} total indikator
            </span>
            @if (($summary['totalValidated'] ?? 0) > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-green-700">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $summary['totalValidated'] }} divalidasi
                </span>
            @endif
            @if (($summary['totalRejected'] ?? 0) > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-red-700">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ $summary['totalRejected'] }} ditolak
                </span>
            @endif
            @if (($summary['totalDraft'] ?? 0) > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-3 py-1 text-yellow-700">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                    </svg>
                    {{ $summary['totalDraft'] }} perlu revisi kecamatan
                </span>
            @endif
            @if (($summary['totalPending'] ?? 0) > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-amber-700">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $summary['totalPending'] }} menunggu validasi
                </span>
            @endif
        </div>
        @endisset

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
                                    <p class="text-sm font-medium text-slate-800 leading-snug">
                                        {{ $entry->indicator?->indicator }}
                                    </p>

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

                                {{-- Status badge --}}
                                <div class="shrink-0 text-right">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusCls }}">
                                        {{ $statusLabel }}
                                    </span>
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

</x-layouts.validator>
