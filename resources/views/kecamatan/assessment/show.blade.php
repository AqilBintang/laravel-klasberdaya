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
--}}
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
            {{-- ── Blocked: assessment sedang diperbarui, user belum mulai ── --}}
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

        {{-- ── Banner: Assessment sedang diperbarui ───────────────────── --}}
        @if ($assessment->status === 'REVISION')
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm">
                        <p class="font-medium text-amber-800">Assessment Sedang Diperbarui</p>
                        <p class="text-amber-700 mt-0.5">
                            Admin sedang melakukan pembaruan pada assessment ini. Anda dapat menyelesaikan pengisian versi saat ini.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Banner: Ada update tersedia (HAS_UPDATE) ───────────────────── --}}
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

        {{-- ── Banner: Perlu revisi karena update admin (NEEDS_REVISION) ─── --}}
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

        {{-- ── Form Pengisian per Kategori ─────────────────────────────── --}}
        <form method="POST"
              action="{{ route('kecamatan.assessment.submit', $assessment->id) }}"
              id="form-assessment">
            @csrf

            <div class="space-y-6">

                @foreach ($assessment->categories->sortBy('order') as $category)
                    @php
                        $indicators = $category->indicators->sortBy('number');
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">

                        {{-- Category header --}}
                        <div class="flex items-center gap-3 px-5 py-4 bg-slate-50 border-b border-slate-200">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white shrink-0">
                                {{ $category->code }}
                            </span>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">{{ $category->name }}</p>
                                <p class="text-xs text-slate-500">{{ $indicators->count() }} indikator</p>
                            </div>
                        </div>

                        {{-- Indicators --}}
                        <div class="divide-y divide-slate-100">
                            @foreach ($indicators as $indicator)
                                @php
                                    // Cari jawaban yang sudah ada untuk indikator ini
                                    $entry       = $existingEntries?->firstWhere('indicator_id', $indicator->id);
                                    $validation  = $entry?->validations->sortByDesc('validated_at')->first();
                                    $isNew       = in_array($indicator->id, $newIndicatorIds ?? []);
                                    $isRevision  = in_array($indicator->id, $validatorRevisionIndicatorIds ?? []);
                                    $isRejected  = in_array($indicator->id, $validatorRejectedIndicatorIds ?? []);
                                    $isLocked    = $entry && $entry->status === 'VALIDATED' && !$isRevision && !$isRejected;

                                    $rowBg = match(true) {
                                        $isRejected => 'bg-red-50',
                                        $isRevision => 'bg-yellow-50',
                                        $isNew      => 'bg-blue-50',
                                        default     => '',
                                    };
                                @endphp
                                <div class="px-5 py-4 {{ $rowBg }}">
                                    <div class="flex items-start gap-3 mb-3">
                                        {{-- Nomor --}}
                                        <span class="w-6 text-center text-xs font-bold text-slate-400 mt-0.5 shrink-0">
                                            {{ $indicator->number }}
                                        </span>

                                        {{-- Pertanyaan + label --}}
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
                                                </div>
                                            </div>

                                            @if ($indicator->description)
                                                <p class="mt-1 text-xs text-slate-500">{{ $indicator->description }}</p>
                                            @endif

                                            {{-- Catatan validator --}}
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

                                            {{-- Deskripsi / Bukti --}}
                                            <div class="sm:col-span-2">
                                                <label for="desc_{{ $indicator->id }}"
                                                       class="block text-xs font-medium text-gray-600 mb-1">
                                                    Deskripsi / Bukti Pendukung
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

                                            {{-- Skor mandiri --}}
                                            <div>
                                                <label for="score_{{ $indicator->id }}"
                                                       class="block text-xs font-medium text-gray-600 mb-1">
                                                    Skor Mandiri (1–{{ $indicator->max_score }})
                                                </label>
                                                <select id="score_{{ $indicator->id }}"
                                                        name="entries[{{ $indicator->id }}][score]"
                                                        @if ($isLocked) disabled @endif
                                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                               focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100
                                                               disabled:bg-gray-50 disabled:text-gray-500">
                                                    @for ($s = 1; $s <= $indicator->max_score; $s++)
                                                        <option value="{{ $s }}"
                                                            {{ ($entry?->score ?? 1) == $s ? 'selected' : '' }}>
                                                            {{ $s }}
                                                        </option>
                                                    @endfor
                                                </select>

                                                {{-- Skor tervalidasi --}}
                                                @if ($validation?->validated_score !== null)
                                                    <p class="mt-1 text-xs text-sky-600">
                                                        Skor validator: <strong>{{ $validation->validated_score }}</strong>
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Dokumen pendukung (link Google Drive) --}}
                                        <div>
                                            <label for="doc_{{ $indicator->id }}"
                                                   class="block text-xs font-medium text-gray-600 mb-1">
                                                Link Dokumen Pendukung
                                                <span class="text-gray-400 font-normal">(Google Drive, opsional)</span>
                                            </label>
                                            <input type="url"
                                                   id="doc_{{ $indicator->id }}"
                                                   name="entries[{{ $indicator->id }}][supporting_doc]"
                                                   value="{{ $entry?->supporting_doc }}"
                                                   @if ($isLocked) disabled readonly @endif
                                                   placeholder="https://drive.google.com/…"
                                                   maxlength="500"
                                                   class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                                          focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100
                                                          disabled:bg-gray-50 disabled:text-gray-500">
                                            @if ($entry?->supporting_doc && !$isLocked)
                                                <a href="{{ $entry->supporting_doc }}"
                                                   target="_blank" rel="noopener noreferrer"
                                                   class="mt-1 inline-flex items-center gap-1 text-xs text-sky-600 hover:text-sky-700">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                    </svg>
                                                    Lihat dokumen tersimpan
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                @endforeach

                {{-- ── Tombol Aksi ──────────────────────────────────────── --}}
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">

                    {{-- Simpan Draft --}}
                    <button type="button" id="btn-draft"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg
                                   border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700
                                   hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Simpan Draft
                    </button>

                    {{-- Submit --}}
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

    @push('scripts')
    <script>
        // Auto-save draft via AJAX
        document.getElementById('btn-draft')?.addEventListener('click', async function () {
            const form     = document.getElementById('form-assessment')
            const formData = new FormData(form)
            formData.append('_method', 'POST')

            const assessmentId = '{{ $assessment->id ?? 0 }}'
            try {
                const res = await fetch(`/kecamatan/assessment/${assessmentId}/draft`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: formData,
                })
                const data = await res.json()
                // Flash mini di dalam tombol
                const btn = this
                const orig = btn.innerHTML
                btn.textContent = '✓ Draft Tersimpan'
                btn.classList.add('text-green-700', 'border-green-300')
                setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('text-green-700', 'border-green-300') }, 2000)
            } catch (e) {
                alert('Gagal menyimpan draft. Coba lagi.')
            }
        })
    </script>
    @endpush

</x-layouts.kecamatan>
