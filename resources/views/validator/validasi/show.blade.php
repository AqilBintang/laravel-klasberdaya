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

            {{-- Pesan dari kabupaten/kota --}}
            @if (!empty($kabupatenApproval?->notes))
                <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-start gap-3">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                    </svg>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-amber-700 mb-0.5">
                            Catatan dari Kabupaten/Kota
                            @if ($kabupatenApproval->reviewed_at)
                                <span class="font-normal text-amber-500 ml-1">
                                    — {{ \Carbon\Carbon::parse($kabupatenApproval->reviewed_at)->translatedFormat('d M Y, H:i') }}
                                </span>
                            @endif
                        </p>
                        <p class="text-sm text-amber-800">{{ $kabupatenApproval->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Daftar Indikator dengan Form Validasi ───────────────────── --}}
        @isset($entries)

        {{-- ── Panel Progress Validasi ────────────────────────────────── --}}
        @isset($summary)
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Progress Validasi</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2.5 text-center">
                    <p class="text-xl font-bold text-gray-900">{{ $summary['totalAll'] }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Total Indikator</p>
                </div>
                <div class="rounded-lg bg-green-50 border border-green-100 px-3 py-2.5 text-center">
                    <p class="text-xl font-bold text-green-700">{{ $summary['totalValidated'] }}</p>
                    <p class="text-xs text-green-600 mt-0.5">Tervalidasi</p>
                </div>
                <div class="rounded-lg bg-amber-50 border border-amber-100 px-3 py-2.5 text-center">
                    <p class="text-xl font-bold text-amber-700">{{ $summary['totalPending'] }}</p>
                    <p class="text-xs text-amber-600 mt-0.5">Menunggu Validasi</p>
                </div>
                <div class="rounded-lg bg-red-50 border border-red-100 px-3 py-2.5 text-center">
                    <p class="text-xl font-bold text-red-700">{{ $summary['totalRejected'] + $summary['totalDraft'] }}</p>
                    <p class="text-xs text-red-600 mt-0.5">Ditolak / Perlu Revisi</p>
                </div>
            </div>
            @php
                $pct = $summary['totalAll'] > 0
                    ? round(($summary['totalValidated'] / $summary['totalAll']) * 100)
                    : 0;
            @endphp
            <div class="mt-3">
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span>Progress keseluruhan</span>
                    <span class="font-semibold text-gray-700">{{ $pct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>
        @endisset
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

        {{-- ── Form Bulk (satu form melingkupi semua indikator) ───────── --}}
        <form method="POST"
              action="{{ route('validator.validasi.storeBulk', $id) }}"
              id="form-bulk-validasi">
            @csrf

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

                            // Jika entry sudah di-resubmit setelah validasi terakhir (submitted_at > validated_at),
                            // validasi lama tidak dipakai sebagai default — dropdown kembali ke placeholder.
                            $isResubmitted = $validation
                                && $entry->submitted_at
                                && $entry->submitted_at > $validation->validated_at;

                            // Untuk badge status tampilkan riwayat terakhir apa adanya
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

                                    {{-- Bukti pendukung --}}
                                    @php
                                        $doc = $entry->supporting_doc;
                                        $hasUrl = $doc && $doc !== 'NO_DOC' && $doc !== 'GDRIVE_PENDING';
                                        $docLinks = $hasUrl ? array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $doc)))) : [];
                                    @endphp
                                    @if ($hasUrl && count($docLinks) > 0)
                                        <div class="mt-2 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2">
                                            <div class="flex items-center gap-2 mb-1.5">
                                                <svg class="w-4 h-4 text-sky-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                </svg>
                                                <span class="text-xs text-sky-700 font-medium">
                                                    Bukti Pendukung{{ count($docLinks) > 1 ? ' (' . count($docLinks) . ' dokumen)' : '' }}:
                                                </span>
                                            </div>
                                            <div class="space-y-1 ml-6">
                                                @foreach ($docLinks as $i => $link)
                                                    <div class="flex items-start gap-1.5">
                                                        @if (count($docLinks) > 1)
                                                            <span class="text-xs text-sky-500 font-medium shrink-0 w-4">{{ $i + 1 }}.</span>
                                                        @endif
                                                        <div class="min-w-0 flex-1">
                                                            <a href="{{ $link }}" target="_blank" rel="noopener noreferrer"
                                                               class="inline-flex items-center gap-1 text-xs text-sky-600 hover:text-sky-800 hover:underline font-medium">
                                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                                </svg>
                                                                Buka Google Drive
                                                            </a>
                                                            <p class="text-xs text-gray-400 mt-0.5 break-all">{{ $link }}</p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif ($doc === 'NO_DOC')
                                        <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-400 italic">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                            Tanpa dokumen pendukung
                                        </div>
                                    @else
                                        <div class="mt-2 flex items-center gap-1.5 text-xs text-amber-500 italic">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                            </svg>
                                            Dokumen pendukung belum dilampirkan
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

                            {{-- ── Input validasi per indikator (bagian dari form bulk) ── --}}
                            <div class="mt-4 ml-10">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                                    {{-- Skor validated --}}
                                    <div>
                                        <label for="score_{{ $entry->id }}"
                                               class="block text-xs font-medium text-gray-600 mb-1">
                                            Skor Validator (1–{{ $entry->indicator?->max_score ?? 4 }})
                                            <span class="text-red-500" aria-hidden="true">*</span>
                                        </label>
                                        @php
                                            $savedValidScore = (!$isResubmitted && $validation?->validated_score !== null)
                                                ? (int) $validation->validated_score
                                                : null;
                                        @endphp
                                        <select id="score_{{ $entry->id }}"
                                                name="entries[{{ $entry->id }}][validated_score]"
                                                required
                                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                       focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                            @if ($savedValidScore === null)
                                                <option value="" disabled selected>— Pilih Skor —</option>
                                            @endif
                                            @for ($s = 1; $s <= ($entry->indicator?->max_score ?? 4); $s++)
                                                <option value="{{ $s }}" {{ $savedValidScore === $s ? 'selected' : '' }}>
                                                    {{ $s }}
                                                </option>
                                            @endfor
                                        </select>
                                        <p class="mt-1 text-xs text-gray-400">
                                            Skor kecamatan: <strong>{{ $entry->score }}</strong>
                                        </p>
                                    </div>

                                    {{-- Keputusan --}}
                                    <div>
                                        <label for="status_{{ $entry->id }}"
                                               class="block text-xs font-medium text-gray-600 mb-1">
                                            Keputusan
                                        </label>
                                        <select id="status_{{ $entry->id }}"
                                                name="entries[{{ $entry->id }}][status]"
                                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                       focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                            <option value=""
                                                {{ (!$validation || $isResubmitted) ? 'selected' : '' }}
                                                disabled>
                                                — Isi Keputusan —
                                            </option>
                                            <option value="APPROVED"
                                                {{ (!$isResubmitted && $validation?->status === 'APPROVED') ? 'selected' : '' }}>
                                                ✅ Setujui
                                            </option>
                                            <option value="REVISION_NEEDED"
                                                {{ (!$isResubmitted && $validation?->status === 'REVISION_NEEDED') ? 'selected' : '' }}>
                                                ⚠️ Perlu Revisi
                                            </option>
                                            <option value="REJECTED"
                                                {{ (!$isResubmitted && $validation?->status === 'REJECTED') ? 'selected' : '' }}>
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
                                            <input type="text"
                                                   id="notes_{{ $entry->id }}"
                                                   name="entries[{{ $entry->id }}][notes]"
                                                   value="{{ $validation?->notes }}"
                                                   placeholder="Tulis catatan…"
                                                   class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                          focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                            {{-- Simpan satu per satu (submit form individual via JS) --}}
                                            <button type="button"
                                                    data-single-submit="{{ $entry->id }}"
                                                    class="shrink-0 rounded-lg border border-sky-300 bg-white px-4 py-2 text-xs font-medium
                                                           text-sky-700 hover:bg-sky-50 transition-colors">
                                                Simpan
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>

            </div>
        @endforeach

        {{-- ── Sticky bar: Simpan Seluruh ─────────────────────────────── --}}
        <div class="sticky bottom-0 z-10 -mx-1 bg-white border-t border-gray-200 px-5 py-4 shadow-md">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex flex-col gap-0.5">
                    <p class="text-xs text-gray-500 text-center sm:text-left">
                        Pastikan semua keputusan dan skor sudah diisi sebelum menyimpan seluruh indikator.
                    </p>
                    <p id="status-autosave"
                       class="text-xs text-green-600 font-medium transition-opacity duration-500 opacity-0">
                    </p>
                </div>
                <button type="button" id="btn-bulk-submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-6 py-2.5 text-sm font-semibold
                               text-white hover:bg-sky-700 active:bg-sky-800 transition-colors
                               disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Simpan Seluruh Indikator
                </button>
            </div>
        </div>

        {{-- ── Modal Konfirmasi Simpan Seluruh ────────────────────────── --}}
        <div id="modal-bulk-confirm"
             class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-900">Konfirmasi Simpan Validasi</h2>
                    <button type="button" id="modal-bulk-close"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400
                                   hover:bg-gray-100 hover:text-gray-600 transition-colors"
                            aria-label="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="rounded-lg bg-sky-50 border border-sky-200 px-4 py-3 text-sm text-sky-700 flex items-start gap-3">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                        </svg>
                        <div>
                            <p class="font-medium">Simpan semua validasi sekarang?</p>
                            <p class="mt-0.5 text-sky-600">
                                Seluruh keputusan dan skor untuk indikator yang ditampilkan akan disimpan sekaligus.
                                Pastikan semua sudah terisi dengan benar.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" id="modal-bulk-cancel"
                                class="flex-1 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium
                                       text-gray-600 hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="button" id="modal-bulk-confirm-btn"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-sky-600
                                       px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Ya, Simpan Validasi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        </form>{{-- tutup #form-bulk-validasi --}}

        @endif
        @endisset

        {{-- ── History: Indikator yang Sudah Diproses ─────────────────── --}}
        @if (!empty($historyEntries) && $historyEntries->count() > 0)
        @php
            $historyByCategory = $historyEntries->groupBy(fn ($e) => $e->indicator?->category_id);
        @endphp
        <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
            {{-- Toggle header --}}
            <button type="button" id="btn-toggle-history"
                    class="w-full flex items-center justify-between px-5 py-4 bg-gray-50 border-b border-gray-200
                           hover:bg-gray-100 transition-colors text-left">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                                 M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            Riwayat Validasi
                            <span class="ml-1.5 inline-flex items-center rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600">
                                {{ $historyEntries->count() }} indikator
                            </span>
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Indikator yang sudah tervalidasi, ditolak, atau dikembalikan untuk perbaikan
                        </p>
                    </div>
                </div>
                <svg id="history-chevron" class="w-4 h-4 text-gray-400 transition-transform duration-200 rotate-180 shrink-0"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Content --}}
            <div id="history-content">
                @foreach ($historyByCategory as $catId => $catHistEntries)
                    @php $hCat = $catHistEntries->first()?->indicator?->category; @endphp
                    <div class="border-b border-gray-100 last:border-b-0">
                        {{-- Category sub-header --}}
                        <div class="flex items-center gap-2 px-5 py-2.5 bg-slate-50 border-b border-slate-100">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-500 text-xs font-bold text-white shrink-0">
                                {{ $hCat?->code ?? '—' }}
                            </span>
                            <p class="text-xs font-semibold text-slate-700">{{ $hCat?->name ?? 'Kategori' }}</p>
                            <span class="text-xs text-slate-400">({{ $catHistEntries->count() }} indikator)</span>
                        </div>

                        <div class="divide-y divide-gray-50">
                            @foreach ($catHistEntries as $hEntry)
                                @php
                                    $hVal = $hEntry->validations->first();
                                    $hStatus = $hEntry->status;
                                    [$hBg, $hBadgeCls, $hBadgeLabel] = match($hStatus) {
                                        'VALIDATED' => ['bg-green-50/40', 'bg-green-100 text-green-700', 'Tervalidasi'],
                                        'REJECTED'  => ['bg-red-50/40',   'bg-red-100 text-red-700',   'Ditolak'],
                                        'DRAFT'     => ['bg-yellow-50/40','bg-yellow-100 text-yellow-700','Perlu Revisi'],
                                        default     => ['',               'bg-gray-100 text-gray-600',  $hStatus],
                                    };
                                @endphp
                                <div class="px-5 py-3.5 {{ $hBg }}">
                                    <div class="flex items-start gap-3">
                                        <span class="w-5 text-center text-xs font-bold text-slate-400 mt-0.5 shrink-0">
                                            {{ $hEntry->indicator?->number }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start gap-2 flex-wrap">
                                                <p class="text-sm text-slate-700 leading-snug flex-1">
                                                    {{ $hEntry->indicator?->indicator }}
                                                </p>
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $hBadgeCls }} shrink-0">
                                                    {{ $hBadgeLabel }}
                                                </span>
                                            </div>

                                            {{-- Skor --}}
                                            <div class="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                                <span>
                                                    Skor kecamatan:
                                                    <strong class="text-slate-700">{{ $hEntry->score }}</strong>
                                                    / {{ $hEntry->indicator?->max_score ?? '?' }}
                                                </span>
                                                @if ($hVal?->validated_score !== null)
                                                    <span>
                                                        Skor validator:
                                                        <strong class="text-sky-700">{{ $hVal->validated_score }}</strong>
                                                    </span>
                                                @endif
                                                @if ($hVal?->validated_at)
                                                    <span class="text-slate-400">
                                                        {{ \Carbon\Carbon::parse($hVal->validated_at)->diffForHumans() }}
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Catatan validator --}}
                                            @if ($hVal?->notes)
                                                <div class="mt-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2">
                                                    <p class="text-xs text-amber-600 font-medium mb-0.5">💬 Catatan validator:</p>
                                                    <p class="text-xs text-amber-700">{{ $hVal->notes }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

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
    {{-- Toggle accordion history --}}
    <script>
    (function () {
        var btn     = document.getElementById('btn-toggle-history');
        var content = document.getElementById('history-content');
        var chevron = document.getElementById('history-chevron');
        if (!btn || !content) return;
        btn.addEventListener('click', function () {
            var isOpen = !content.classList.contains('hidden');
            if (isOpen) {
                content.classList.add('hidden');
                chevron?.classList.remove('rotate-180');
            } else {
                content.classList.remove('hidden');
                chevron?.classList.add('rotate-180');
            }
        });
    })();
    </script>

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

    {{-- ── Script: tombol Simpan per-indikator + konfirmasi bulk ─────── --}}
    <script>
    (function () {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        var bulkForm  = document.getElementById('form-bulk-validasi');
        var bulkBtn   = document.getElementById('btn-bulk-submit');

        // ── Tombol "Simpan" per indikator → POST /validator/validasi/{entryId}
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-single-submit]');
            if (!btn) return;

            var entryId = btn.dataset.singleSubmit;
            var scoreEl  = document.getElementById('score_' + entryId);
            var statusEl = document.getElementById('status_' + entryId);
            var notesEl  = document.getElementById('notes_' + entryId);

            if (!scoreEl || !statusEl) return;

            var body = new FormData();
            body.append('_token', csrfToken);
            body.append('validated_score', scoreEl.value);
            body.append('status', statusEl.value);
            if (notesEl) body.append('notes', notesEl.value);

            btn.disabled = true;
            btn.textContent = '…';

            fetch('/validator/validasi/' + entryId, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: body,
            })
            .then(function (res) {
                // Controller redirect → 302; fetch follows, landing on show page → reload
                if (res.ok || res.redirected) {
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Simpan';
                    alert('Gagal menyimpan. Coba lagi.');
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.textContent = 'Simpan';
                alert('Koneksi bermasalah. Coba lagi.');
            });
        });

        // ── Tombol "Simpan Seluruh" → modal konfirmasi sebelum submit
        if (bulkBtn && bulkForm) {
            var modal       = document.getElementById('modal-bulk-confirm');
            var modalClose  = document.getElementById('modal-bulk-close');
            var modalCancel = document.getElementById('modal-bulk-cancel');
            var modalConfirm = document.getElementById('modal-bulk-confirm-btn');

            function openModal()  { modal.classList.remove('hidden'); modal.classList.add('flex'); }
            function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

            bulkBtn.addEventListener('click', function (e) {
                e.preventDefault();
                openModal();
            });

            modalClose?.addEventListener('click', closeModal);
            modalCancel?.addEventListener('click', closeModal);
            modal?.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

            modalConfirm?.addEventListener('click', function () {
                closeModal();
                // Simpan state terbaru sebelum submit (bukan clear)
                // agar tersedia kembali jika server mengembalikan error
                saveNow();
                bulkBtn.disabled = true;
                bulkBtn.innerHTML =
                    '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>' +
                    ' Menyimpan…';
                bulkForm.submit();
            });
        }

        // ── Auto-save ke localStorage ────────────────────────────────────
        // Key unik per halaman: validator-autosave-{entryId anchor dari URL}
        var autoSaveKey = 'validator-autosave-{{ $id }}';
        var saveTimer   = null;
        var statusEl    = document.getElementById('status-autosave');

        function getFormState() {
            var state = {};
            document.querySelectorAll('[data-single-submit]').forEach(function (btn) {
                var eid     = btn.dataset.singleSubmit;
                var scoreEl = document.getElementById('score_'  + eid);
                var stEl    = document.getElementById('status_' + eid);
                var notesEl = document.getElementById('notes_'  + eid);
                state[eid] = {
                    score:  scoreEl  ? scoreEl.value  : '',
                    status: stEl     ? stEl.value     : '',
                    notes:  notesEl  ? notesEl.value  : '',
                };
            });
            return state;
        }

        function restoreFormState() {
            try {
                var saved = localStorage.getItem(autoSaveKey);
                if (!saved) return;
                var state = JSON.parse(saved);
                Object.keys(state).forEach(function (eid) {
                    var d       = state[eid];
                    var scoreEl = document.getElementById('score_'  + eid);
                    var stEl    = document.getElementById('status_' + eid);
                    var notesEl = document.getElementById('notes_'  + eid);
                    if (scoreEl && d.score  !== undefined) scoreEl.value = d.score;
                    if (stEl    && d.status !== undefined) stEl.value    = d.status;
                    if (notesEl && d.notes  !== undefined) notesEl.value = d.notes;
                });
                showStatus('Draft dipulihkan');
            } catch (e) { /* abaikan jika JSON rusak */ }
        }

        function saveNow() {
            try {
                localStorage.setItem(autoSaveKey, JSON.stringify(getFormState()));
                showStatus('Draft tersimpan otomatis');
            } catch (e) {}
        }

        function clearAutoSave() {
            localStorage.removeItem(autoSaveKey);
        }

        function showStatus(msg) {
            if (!statusEl) return;
            statusEl.textContent = msg;
            statusEl.classList.remove('opacity-0');
            clearTimeout(statusEl._hideTimer);
            statusEl._hideTimer = setTimeout(function () {
                statusEl.classList.add('opacity-0');
            }, 2500);
        }

        function scheduleAutoSave() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveNow, 800);
        }

        // Pasang listener pada semua input dalam form bulk
        if (bulkForm) {
            bulkForm.addEventListener('input',  scheduleAutoSave);
            bulkForm.addEventListener('change', scheduleAutoSave);
        }

        // ── Kapan clear vs restore ───────────────────────────────────────
        // - Jika halaman dimuat dengan session SUCCESS → submit berhasil → clear
        // - Jika halaman dimuat dengan session ERROR atau tanpa session → restore
        @if (session('success'))
        clearAutoSave(); // submit berhasil, draft tidak diperlukan lagi
        @else
        restoreFormState();
        @endif

        // Clear saat single-save berhasil (sebelum reload)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-single-submit]');
            if (btn) clearAutoSave();
        });
    })();
    </script>
    @endpush

</x-layouts.validator>
