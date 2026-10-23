{{--
    kecamatan/assessment/show.blade.php
    Form pengisian self-assessment per indikator untuk satu assessment.
    Data yang diharapkan dari AssessmentController::show($id):
      - $assessment     : Assessment (dengan categories.indicators, status, periode)
      - $existingEntries: Collection<SelfAssessment> — jawaban yang sudah ada
      - $needsRevision  : bool — ada catatan revisi dari validator
      - $hasUpdate      : bool — ada indikator baru setelah update admin
      - $newIndicatorIds: array<int>
      - $validatorRevisionIndicatorIds : array<int>
      - $validatorRejectedIndicatorIds : array<int>
      - $rubric         : AssessmentRubric|null — rubrik panduan (optional)
--}}

@php
    // Persiapan data rubrik (jika ada) — dilakukan di luar loop view
    $rubricByIndicator = [];
    $rubricCatGroups   = [];
    if (!empty($rubric)) {
        foreach ($rubric->items as $item) {
            $rubricByIndicator[$item->indicator_id] = $item;
        }
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

<x-layouts.kecamatan pageTitle="Isi Assessment">

    <div class="space-y-6">

        {{-- ── Breadcrumb ──────────────────────────────────────────────── --}}
        <div>
            <a href="{{ route('kecamatan.assessment.index') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-3">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Daftar Assessment
            </a>

            @isset($assessment)
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $assessment->title }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Periode: {{ $assessment->periode }}</p>
                </div>

                {{-- Tombol buka panduan rubrik --}}
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
            @endisset
        </div>

        {{-- ── Flash ───────────────────────────────────────────────────── --}}
        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @isset($assessment)

        @if (!empty($blocked))
            {{-- ── Blocked ── --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-amber-200 bg-amber-50 py-20 text-center px-6">
                <svg class="w-12 h-12 text-amber-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="font-semibold text-amber-800 text-lg mb-2">Assessment Sedang Diperbarui</h3>
                <p class="text-amber-700 text-sm max-w-md">
                    Admin sedang melakukan pembaruan pada assessment <strong>{{ $assessment->title }}</strong>.
                    Silakan kembali beberapa saat lagi setelah pembaruan selesai dan versi baru dipublish.
                </p>
                <a href="{{ route('kecamatan.assessment.index') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-amber-700 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Kembali ke Daftar Assessment
                </a>
            </div>
        @else

        @if ($assessment->status === 'REVISION')
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm">
                        <p class="font-medium text-amber-800">Assessment Sedang Diperbarui</p>
                        <p class="text-amber-700 mt-0.5">Admin sedang melakukan pembaruan pada assessment ini. Anda dapat menyelesaikan pengisian versi saat ini.</p>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($hasUpdate) && empty($needsRevision))
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm">
                        <p class="font-medium text-blue-800">Assessment Diperbarui — Ada Indikator Baru</p>
                        <p class="text-blue-700 mt-0.5">
                            Admin telah memperbarui assessment ini. Jawaban Anda sebelumnya tetap tersimpan.
                            @if (!empty($newIndicatorIds))
                                Ada <strong>{{ count($newIndicatorIds) }}</strong> indikator baru yang perlu Anda isi (ditandai biru di bawah).
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($needsRevision))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm">
                        <p class="font-medium text-red-800">Assessment Diperbarui — Perlu Diisi Ulang</p>
                        <p class="text-red-700 mt-0.5">
                            Admin telah memperbarui assessment dengan indikator yang berubah atau ditambah.
                            Silakan lengkapi indikator yang ditandai merah/biru di bawah, lalu kirim ulang assessment Anda.
                            @if (!empty($newIndicatorIds))
                                Ada <strong>{{ count($newIndicatorIds) }}</strong> indikator yang perlu perhatian.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Form ── --}}
        <form method="POST"
              action="{{ route('kecamatan.assessment.submit', $assessment->id) }}"
              id="form-assessment">
            @csrf

            <div class="space-y-6">

                @foreach ($assessment->categories->sortBy('order') as $category)
                    @php $indicators = $category->indicators->sortBy('number'); @endphp

                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                        <div class="flex items-center gap-3 px-5 py-4 bg-slate-50 border-b border-slate-200">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white shrink-0">
                                {{ $category->code }}
                            </span>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">{{ $category->name }}</p>
                                <p class="text-xs text-slate-500">{{ $indicators->count() }} indikator</p>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach ($indicators as $indicator)
                                @php
                                    $entry      = $existingEntries?->firstWhere('indicator_id', $indicator->id);
                                    $validation = $entry?->validations->sortByDesc('validated_at')->first();
                                    $isNew      = in_array($indicator->id, $newIndicatorIds ?? []);
                                    $isRevision = in_array($indicator->id, $validatorRevisionIndicatorIds ?? []);
                                    $isRejected = in_array($indicator->id, $validatorRejectedIndicatorIds ?? []);
                                    $isLocked   = $entry && $entry->status === 'VALIDATED' && !$isRevision && !$isRejected;
                                    $rowBg      = match(true) {
                                        $isRejected => 'bg-red-50',
                                        $isRevision => 'bg-yellow-50',
                                        $isNew      => 'bg-blue-50',
                                        default     => '',
                                    };
                                    $savedDoc  = $entry?->supporting_doc ?? null;
                                    $docChoice = null;
                                    if ($savedDoc === 'NO_DOC') { $docChoice = 'no_doc'; }
                                    elseif ($savedDoc !== null && $savedDoc !== '') { $docChoice = 'gdrive'; }
                                @endphp

                                <div class="px-5 py-4 {{ $rowBg }}" data-indicator-id="{{ $indicator->id }}">

                                    {{-- Header indikator --}}
                                    <div class="flex items-start gap-3 mb-3">
                                        <span class="w-6 text-center text-xs font-bold text-slate-400 mt-0.5 shrink-0">
                                            {{ $indicator->number }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start gap-2 flex-wrap">
                                                <p class="text-sm text-slate-800 font-medium leading-snug flex-1">
                                                    {{ $indicator->indicator }}
                                                </p>
                                                <div class="flex items-center gap-1 flex-wrap shrink-0">
                                                    @if ($isNew)
                                                        <span class="rounded-full bg-blue-100 text-blue-700 border border-blue-200 px-2 py-0.5 text-xs font-medium">Baru</span>
                                                    @endif
                                                    @if ($isRevision)
                                                        <span class="rounded-full bg-yellow-100 text-yellow-700 border border-yellow-200 px-2 py-0.5 text-xs font-medium">Perlu Revisi</span>
                                                    @endif
                                                    @if ($isRejected)
                                                        <span class="rounded-full bg-red-100 text-red-700 border border-red-200 px-2 py-0.5 text-xs font-medium">Ditolak</span>
                                                    @endif
                                                    @if ($isLocked)
                                                        <span class="rounded-full bg-green-100 text-green-700 border border-green-200 px-2 py-0.5 text-xs font-medium">✓ Tervalidasi</span>
                                                    @endif
                                                    @if (!empty($rubric))
                                                        <button type="button"
                                                                data-rubrik-indicator="{{ $indicator->id }}"
                                                                class="btn-rubrik-indicator inline-flex items-center gap-1 rounded-full border border-sky-200 bg-white px-2 py-0.5 text-xs text-sky-600 hover:bg-sky-50 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                                            </svg>
                                                            Rubrik
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($indicator->description)
                                                <p class="mt-1 text-xs text-slate-500">{{ $indicator->description }}</p>
                                            @endif
                                            @if ($validation?->notes)
                                                <div class="mt-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2">
                                                    <p class="text-xs text-amber-600 font-medium">💬 Catatan validator:</p>
                                                    <p class="text-xs text-amber-700 mt-0.5">{{ $validation->notes }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Input jawaban --}}
                                    <div class="ml-9 space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                                            {{-- Deskripsi --}}
                                            <div class="sm:col-span-2">
                                                <label for="desc_{{ $indicator->id }}"
                                                       class="block text-xs font-medium text-gray-600 mb-1">
                                                    Deskripsi
                                                    @if (!$isLocked)<span class="text-red-500" aria-hidden="true">*</span>@endif
                                                </label>
                                                <textarea id="desc_{{ $indicator->id }}"
                                                          name="entries[{{ $indicator->id }}][description]"
                                                          rows="2"
                                                          @if ($isLocked) disabled readonly @endif
                                                          placeholder="Jelaskan kondisi di kecamatan Anda…"
                                                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                                 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100
                                                                 disabled:bg-gray-50 disabled:text-gray-500 resize-none">{{ $entry?->description }}</textarea>
                                            </div>

                                            {{-- Skor --}}
                                            <div>
                                                <label for="score_{{ $indicator->id }}"
                                                       class="block text-xs font-medium text-gray-600 mb-1">
                                                    Skor (1–{{ $indicator->max_score }})
                                                    @if (!$isLocked)<span class="text-red-500" aria-hidden="true">*</span>@endif
                                                </label>
                                                <select id="score_{{ $indicator->id }}"
                                                        name="entries[{{ $indicator->id }}][score]"
                                                        @if ($isLocked) disabled @endif
                                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                               focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100
                                                               disabled:bg-gray-50 disabled:text-gray-500">
                                                    @if (!$entry || $entry->score === null)
                                                        <option value="" disabled selected>-- Pilih Skor --</option>
                                                    @endif
                                                    @for ($s = 1; $s <= $indicator->max_score; $s++)
                                                        <option value="{{ $s }}" {{ ($entry?->score ?? null) == $s ? 'selected' : '' }}>
                                                            {{ $s }}
                                                        </option>
                                                    @endfor
                                                </select>
                                                @if ($validation?->validated_score !== null)
                                                    <p class="mt-1 text-xs text-sky-600">
                                                        Skor validator: <strong>{{ $validation->validated_score }}</strong>
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Status Bukti Pendukung --}}
                                        <div>
                                            <p class="block text-xs font-medium text-gray-700 mb-0.5">
                                                Status Bukti Pendukung
                                                @if (!$isLocked)<span class="text-red-500" aria-hidden="true">*</span>@endif
                                            </p>
                                            @if (!$isLocked)
                                                <p class="text-xs text-gray-400 mb-2">Pilih salah satu berikut. Pilihan ini wajib ditentukan sebelum formulir dikirim.</p>
                                            @endif

                                            @if ($isLocked)
                                                @if ($savedDoc === 'NO_DOC')
                                                    <p class="text-xs text-gray-500 italic">Belum terdapat kegiatan — link dokumen tidak diperlukan.</p>
                                                @elseif ($savedDoc)
                                                    <a href="{{ $savedDoc }}" target="_blank" rel="noopener noreferrer"
                                                       class="inline-flex items-center gap-1 text-xs text-sky-600 hover:text-sky-700">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                        </svg>
                                                        Lihat dokumen tersimpan
                                                    </a>
                                                @else
                                                    <p class="text-xs text-gray-400 italic">Tidak ada dokumen.</p>
                                                @endif
                                            @else
                                                <div class="space-y-2 doc-radio-group" data-ind="{{ $indicator->id }}">

                                                    <label class="doc-radio-card flex items-start gap-3 cursor-pointer rounded-lg border p-3 transition-all
                                                                  {{ $docChoice === 'gdrive' ? 'border-sky-500 bg-sky-50 ring-1 ring-sky-400' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                                        <input type="radio"
                                                               name="doc_choice_{{ $indicator->id }}"
                                                               value="gdrive"
                                                               class="doc-choice-radio mt-0.5 accent-sky-600 shrink-0"
                                                               data-ind="{{ $indicator->id }}"
                                                               {{ $docChoice === 'gdrive' ? 'checked' : '' }}>
                                                        <div class="min-w-0">
                                                            <p class="text-xs font-semibold text-gray-800 leading-snug">Melampirkan link Google Drive</p>
                                                            <p class="text-xs text-gray-500 mt-0.5 leading-snug">Pilih jika sudah ada dan tersedia dokumen/foto bukti pendukung.</p>
                                                        </div>
                                                    </label>

                                                    <div id="doc_field_{{ $indicator->id }}"
                                                         class="{{ $docChoice === 'gdrive' ? '' : 'hidden' }} pl-3">
                                                        <input type="url"
                                                               name="entries[{{ $indicator->id }}][supporting_doc]"
                                                               value="{{ $docChoice === 'gdrive' ? $savedDoc : '' }}"
                                                               placeholder="https://drive.google.com/…"
                                                               maxlength="500"
                                                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                                      focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                                    </div>

                                                    <label class="doc-radio-card flex items-start gap-3 cursor-pointer rounded-lg border p-3 transition-all
                                                                  {{ $docChoice === 'no_doc' ? 'border-sky-500 bg-sky-50 ring-1 ring-sky-400' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                                        <input type="radio"
                                                               name="doc_choice_{{ $indicator->id }}"
                                                               value="no_doc"
                                                               class="doc-choice-radio mt-0.5 accent-sky-600 shrink-0"
                                                               data-ind="{{ $indicator->id }}"
                                                               {{ $docChoice === 'no_doc' ? 'checked' : '' }}>
                                                        <div class="min-w-0">
                                                            <p class="text-xs font-semibold text-gray-800 leading-snug">Belum Terdapat Kegiatan</p>
                                                            <p class="text-xs text-gray-500 mt-0.5 leading-snug">Pilih jika kegiatan yang dimaksud memang belum dilaksanakan.</p>
                                                        </div>
                                                    </label>

                                                    <div id="doc_nodoc_{{ $indicator->id }}"
                                                         class="{{ $docChoice === 'no_doc' ? '' : 'hidden' }}">
                                                        <input type="hidden"
                                                               name="entries[{{ $indicator->id }}][supporting_doc]"
                                                               value="NO_DOC">
                                                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                                                            <p class="text-xs font-semibold text-blue-800 mb-0.5">Link dokumen tidak diperlukan.</p>
                                                            <p class="text-xs text-blue-700 leading-snug">Dengan memilih opsi ini, pengguna menyatakan bahwa belum terdapat kegiatan untuk indikator tersebut.</p>
                                                        </div>
                                                    </div>

                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Tombol Aksi --}}
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                    <button type="button" id="btn-draft"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg
                                   border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700
                                   hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Simpan Draft
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg
                                   bg-sky-600 px-6 py-2.5 text-sm font-semibold text-white
                                   hover:bg-sky-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Submit Assessment
                    </button>
                </div>

            </div>
        </form>

        @endif

        @else
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <p class="text-sm text-gray-500">Assessment tidak ditemukan.</p>
                <a href="{{ route('kecamatan.assessment.index') }}"
                   class="mt-3 inline-block text-sm text-sky-600 hover:text-sky-700 font-medium">
                    ← Kembali ke daftar
                </a>
            </div>
        @endisset

    </div>

    {{-- ══════════════════════════════════════════════════════════════
         PUSH KE @stack('scripts') — di dalam x-layouts.kecamatan
         Drawer rubrik di-render di body level via stack, bukan di
         dalam <main> yang overflow-y-auto.
    ══════════════════════════════════════════════════════════════ --}}
    @push('scripts')

    {{-- ── Drawer backdrop & panel ── --}}
    @if (!empty($rubric))
    <div id="rubrik-backdrop"
         class="fixed inset-0 bg-black/40 z-40 hidden"
         style="opacity:0;transition:opacity .3s"
         aria-hidden="true"></div>

    <aside id="rubrik-drawer"
           role="dialog" aria-modal="true" aria-label="Panduan Rubrik Penilaian"
           style="transition:transform .3s ease-in-out"
           class="fixed top-0 right-0 h-full w-full sm:w-[480px] lg:w-[520px]
                  bg-white shadow-2xl z-50 flex flex-col translate-x-full">

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

        <div class="flex-1 overflow-y-auto">
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

    <script id="rubrik-data" type="application/json">
    {!! json_encode(
        collect($rubricByIndicator)->map(fn ($item) => [
            'indicator_id'   => $item->indicator_id,
            'indicator_text' => $item->indicator->indicator,
            'indicator_num'  => $item->indicator->number,
            'score1'         => $item->score1,
            'score2'         => $item->score2,
            'score3'         => $item->score3,
            'score4'         => $item->score4,
        ])->values()
    ) !!}
    </script>
    @endif

    <script>
    (function () {
        // ── Draft save ──────────────────────────────────────────────
        var btnDraft = document.getElementById('btn-draft');
        if (btnDraft) {
            btnDraft.addEventListener('click', async function () {
                var form = document.getElementById('form-assessment');
                var formData = new FormData(form);
                var assessmentId = '{{ $assessment->id ?? 0 }}';
                try {
                    await fetch('/kecamatan/assessment/' + assessmentId + '/draft', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: formData,
                    });
                    var orig = this.innerHTML;
                    this.textContent = '✓ Draft Tersimpan';
                    this.classList.add('text-green-700', 'border-green-300');
                    var btn = this;
                    setTimeout(function () {
                        btn.innerHTML = orig;
                        btn.classList.remove('text-green-700', 'border-green-300');
                    }, 2000);
                } catch (e) { alert('Gagal menyimpan draft. Coba lagi.'); }
            });
        }

        // ── Validasi submit ─────────────────────────────────────────
        var form = document.getElementById('form-assessment');
        if (form) {
            form.addEventListener('submit', function (e) {
                var empty = [];

                form.querySelectorAll('textarea[name*="[description]"]:not([disabled])').forEach(function (ta) {
                    if (ta.value.trim() === '') {
                        var row = ta.closest('[data-indicator-id]');
                        empty.push({ el: ta, type: 'desc' });
                    }
                });

                form.querySelectorAll('select[name*="[score]"]:not([disabled])').forEach(function (sel) {
                    if (sel.value === '') empty.push({ el: sel, type: 'score' });
                });

                form.querySelectorAll('[data-indicator-id]').forEach(function (row) {
                    var radios = row.querySelectorAll('input[type="radio"][name^="doc_choice_"]');
                    if (!radios.length) return;
                    if (!Array.from(radios).some(function (r) { return r.checked; })) {
                        var group = row.querySelector('.doc-radio-group');
                        empty.push({ el: group || radios[0], type: 'doc' });
                    }
                });

                if (!empty.length) return;
                e.preventDefault();

                empty.forEach(function (f) {
                    if (f.type === 'doc') {
                        (f.el.querySelectorAll('.doc-radio-card') || []).forEach(function (c) {
                            c.classList.add('border-red-400', 'ring-1', 'ring-red-300');
                            c.classList.remove('border-gray-200');
                        });
                    } else {
                        f.el.classList.add('border-red-400', 'ring-2', 'ring-red-100');
                        var clear = function () {
                            var bad = f.type === 'score' ? f.el.value === '' : f.el.value.trim() === '';
                            if (!bad) { f.el.classList.remove('border-red-400', 'ring-2', 'ring-red-100'); }
                        };
                        f.el.addEventListener('input', clear);
                        f.el.addEventListener('change', clear);
                    }
                });

                empty[0].el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (empty[0].el.focus) empty[0].el.focus();

                var existing = document.getElementById('submit-error-msg');
                if (existing) existing.remove();
                var dc = empty.filter(function(f){return f.type==='desc';}).length;
                var sc = empty.filter(function(f){return f.type==='score';}).length;
                var pc = empty.filter(function(f){return f.type==='doc';}).length;
                var parts = [];
                if (dc) parts.push(dc + ' kolom deskripsi');
                if (sc) parts.push(sc + ' skor');
                if (pc) parts.push(pc + ' dokumen pendukung');
                var msg = document.createElement('p');
                msg.id = 'submit-error-msg';
                msg.className = 'text-sm text-red-600 font-medium text-center';
                msg.textContent = 'Masih ada ' + parts.join(', ') + ' yang belum diisi.';
                var btnSubmit = form.querySelector('button[type="submit"]');
                btnSubmit.parentNode.insertBefore(msg, btnSubmit.parentNode.firstChild);
            });
        }

        // ── Radio doc cards ─────────────────────────────────────────
        document.querySelectorAll('.doc-choice-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var indId    = this.dataset.ind;
                var fieldDiv = document.getElementById('doc_field_' + indId);
                var nodocDiv = document.getElementById('doc_nodoc_' + indId);
                var group    = this.closest('.doc-radio-group');

                if (this.value === 'gdrive') {
                    if (fieldDiv) fieldDiv.classList.remove('hidden');
                    if (nodocDiv) {
                        nodocDiv.classList.add('hidden');
                        var hi = nodocDiv.querySelector('input[type="hidden"]');
                        if (hi) hi.disabled = true;
                    }
                } else {
                    if (fieldDiv) {
                        fieldDiv.classList.add('hidden');
                        var url = fieldDiv.querySelector('input[type="url"]');
                        if (url) url.value = '';
                    }
                    if (nodocDiv) {
                        nodocDiv.classList.remove('hidden');
                        var hi = nodocDiv.querySelector('input[type="hidden"]');
                        if (hi) hi.disabled = false;
                    }
                }

                if (group) {
                    group.querySelectorAll('.doc-radio-card').forEach(function (card) {
                        var r = card.querySelector('input[type="radio"]');
                        if (!r) return;
                        if (r.checked) {
                            card.classList.add('border-sky-500', 'bg-sky-50', 'ring-1', 'ring-sky-400');
                            card.classList.remove('border-gray-200', 'bg-white', 'border-red-400', 'ring-red-300');
                        } else {
                            card.classList.remove('border-sky-500', 'bg-sky-50', 'ring-1', 'ring-sky-400');
                            card.classList.add('border-gray-200', 'bg-white');
                        }
                    });
                }
            });
        });

        // ── Rubrik drawer ───────────────────────────────────────────
        @if (!empty($rubric))
        (function () {
            var drawer   = document.getElementById('rubrik-drawer');
            var backdrop = document.getElementById('rubrik-backdrop');
            var btnOpen  = document.getElementById('btn-open-rubrik');
            var btnClose = document.getElementById('btn-close-rubrik');
            if (!drawer) return;

            var rubrikMap = {};
            try {
                JSON.parse(document.getElementById('rubrik-data').textContent)
                    .forEach(function (i) { rubrikMap[i.indicator_id] = i; });
            } catch(e) {}

            function openDrawer() {
                drawer.classList.remove('translate-x-full');
                backdrop.classList.remove('hidden');
                setTimeout(function () { backdrop.style.opacity = '1'; }, 10);
                document.body.style.overflow = 'hidden';
            }
            function closeDrawer() {
                drawer.classList.add('translate-x-full');
                backdrop.style.opacity = '0';
                setTimeout(function () { backdrop.classList.add('hidden'); }, 300);
                document.body.style.overflow = '';
            }

            if (btnOpen)  btnOpen.addEventListener('click', openDrawer);
            if (btnClose) btnClose.addEventListener('click', closeDrawer);
            if (backdrop) backdrop.addEventListener('click', closeDrawer);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !drawer.classList.contains('translate-x-full')) closeDrawer();
            });

            // Tab switching
            document.querySelectorAll('.rubrik-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var t = this.dataset.tab;
                    document.querySelectorAll('.rubrik-tab').forEach(function (x) {
                        var on = x.dataset.tab === t;
                        x.classList.toggle('text-sky-700', on);
                        x.classList.toggle('border-sky-600', on);
                        x.classList.toggle('font-semibold', on);
                        x.classList.toggle('text-slate-500', !on);
                        x.classList.toggle('border-transparent', !on);
                    });
                    document.querySelectorAll('.rubrik-panel').forEach(function (p) {
                        p.classList.toggle('hidden', p.id !== 'panel-' + t);
                    });
                });
            });

            function escHtml(s) {
                return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            }

            function showRubrikForIndicator(id) {
                var item    = rubrikMap[id];
                var emptyEl = document.getElementById('rubrik-aktif-empty');
                var contEl  = document.getElementById('rubrik-aktif-content');
                if (!item) { emptyEl.classList.remove('hidden'); contEl.classList.add('hidden'); return; }

                var scores = [{s:1,d:item.score1},{s:2,d:item.score2},{s:3,d:item.score3},{s:4,d:item.score4}].filter(function(x){return x.d;});
                var html = '<div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 mb-3">'
                    + '<p class="text-xs text-sky-600 font-semibold mb-0.5">Indikator ' + item.indicator_num + '</p>'
                    + '<p class="text-sm text-sky-900 font-medium leading-snug">' + escHtml(item.indicator_text) + '</p>'
                    + '</div><div class="space-y-2">';
                scores.forEach(function (x) {
                    html += '<div class="flex gap-3 rounded-lg border border-slate-200 bg-white p-3">'
                        + '<span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-sky-600 text-white text-xs font-bold shrink-0">' + x.s + '</span>'
                        + '<p class="text-sm text-slate-700 leading-snug">' + escHtml(x.d) + '</p></div>';
                });
                html += '</div>';
                contEl.innerHTML = html;
                emptyEl.classList.add('hidden');
                contEl.classList.remove('hidden');
            }

            function highlightRow(id) {
                document.querySelectorAll('[data-rubrik-row]').forEach(function (el) {
                    el.classList.remove('bg-sky-50','ring-1','ring-sky-200');
                });
                var row = document.querySelector('[data-rubrik-row="' + id + '"]');
                if (row) { row.classList.add('bg-sky-50','ring-1','ring-sky-200'); row.scrollIntoView({behavior:'smooth',block:'nearest'}); }
            }

            document.querySelectorAll('.btn-rubrik-indicator').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = parseInt(this.dataset.rubrikIndicator, 10);
                    document.getElementById('tab-aktif').click();
                    showRubrikForIndicator(id);
                    highlightRow(id);
                    openDrawer();
                });
            });
        })();
        @endif
    })();
    </script>
    @endpush

</x-layouts.kecamatan>
