{{--
    kecamatan/assessment/review.blade.php
    Halaman review jawaban self-assessment sebelum di-submit.
    Data dari AssessmentController::review($id):
      - $assessment      : Assessment
      - $categories      : Collection<AssessmentCategory> with indicators
      - $entriesMap      : Collection<SelfAssessment> keyed by indicator_id
      - $totalIndicators : int
      - $filledCount     : int
      - $totalScore      : int
      - $maxTotalScore   : int
      - $userStatus      : UserAssessmentStatus|null
--}}

<x-layouts.kecamatan pageTitle="Review Assessment">

    <div class="space-y-6">

        {{-- ── Breadcrumb ──────────────────────────────────────────────── --}}
        <div>
            <a href="{{ route('kecamatan.assessment.show', $id) }}"
               class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-3">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Form Pengisian
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Review Jawaban</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $assessment->title }} &mdash; Periode {{ $assessment->periode }}</p>
            </div>
        </div>



        {{-- ── Peringatan jika ada yang belum terisi ───────────────────── --}}
        @if ($filledCount < $totalIndicators)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">Ada jawaban yang belum lengkap</p>
                <p class="text-xs text-amber-700 mt-0.5">
                    Sebanyak <strong>{{ $totalIndicators - $filledCount }} indikator</strong> masih kosong.
                    Pastikan semua indikator telah diisi sebelum submit.
                    <a href="{{ route('kecamatan.assessment.show', $id) }}" class="underline font-medium hover:text-amber-900">Kembali dan lengkapi.</a>
                </p>
            </div>
        </div>
        @endif

        {{-- ── Tabel Jawaban Per Kategori ──────────────────────────────── --}}
        <div class="space-y-4">
            @foreach ($categories as $cat)
                @php $catIndicators = $cat->indicators; @endphp
                @if ($catIndicators->isEmpty()) @continue @endif

                <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                    {{-- Header kategori --}}
                    <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-sky-600 text-white text-xs font-bold shrink-0">
                            {{ $cat->code }}
                        </span>
                        <p class="text-sm font-semibold text-gray-800">{{ $cat->name }}</p>
                    </div>

                    <div class="divide-y divide-gray-50">
                        @foreach ($catIndicators as $ind)
                            @php
                                $entry    = $entriesMap->get($ind->id);
                                $desc     = $entry?->description;
                                $score    = $entry && $entry->score > 0 ? (int) $entry->score : null;
                                $doc      = $entry?->supporting_doc;
                                $isEmpty  = !$desc || trim($desc) === '';

                                $docLabel = match(true) {
                                    $doc === 'NO_DOC'         => 'Belum ada kegiatan',
                                    $doc === 'GDRIVE_PENDING' => 'Ada dokumen — URL belum diisi',
                                    $doc !== null && $doc !== ''=> null, // tampilkan link
                                    default                   => '—',
                                };
                            @endphp

                            <div class="px-5 py-4 {{ $isEmpty ? 'bg-red-50' : '' }}">
                                <div class="flex items-start gap-3">
                                    {{-- Nomor + status isi --}}
                                    <div class="flex flex-col items-center gap-1 shrink-0 w-7 mt-0.5">
                                        <span class="text-xs font-bold text-gray-400 leading-none">{{ $ind->number }}</span>
                                        @if ($isEmpty)
                                            <span class="w-2 h-2 rounded-full bg-red-400 mt-0.5" title="Belum diisi"></span>
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-green-400 mt-0.5" title="Sudah diisi"></span>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        {{-- Teks indikator --}}
                                        <p class="text-xs font-medium text-gray-500 mb-2 leading-snug">{{ $ind->indicator }}</p>

                                        @if ($isEmpty)
                                            <p class="text-xs text-red-500 italic">— Belum diisi —</p>
                                        @else
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-2 text-xs">
                                                {{-- Deskripsi --}}
                                                <div class="sm:col-span-2">
                                                    <span class="block text-gray-400 font-medium mb-0.5">Deskripsi</span>
                                                    <p class="text-gray-700 leading-relaxed">{{ $desc }}</p>
                                                </div>

                                                <div class="space-y-2">
                                                    {{-- Skor --}}
                                                    <div>
                                                        <span class="block text-gray-400 font-medium mb-0.5">Skor</span>
                                                        @if ($score !== null)
                                                            <span class="inline-flex items-center gap-1 font-bold text-gray-800">
                                                                {{ $score }}
                                                                <span class="font-normal text-gray-400">/ {{ $ind->max_score }}</span>
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400">—</span>
                                                        @endif
                                                    </div>

                                                    {{-- Bukti pendukung --}}
                                                    <div>
                                                        <span class="block text-gray-400 font-medium mb-0.5">Bukti Pendukung</span>
                                                        @if ($docLabel === null)
                                                            <a href="{{ $doc }}" target="_blank" rel="noopener noreferrer"
                                                               class="inline-flex items-center gap-1 text-sky-600 hover:text-sky-700 hover:underline">
                                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                                </svg>
                                                                Lihat Dokumen
                                                            </a>
                                                        @elseif ($doc === 'GDRIVE_PENDING')
                                                            <span class="text-amber-600 font-medium">{{ $docLabel }}</span>
                                                        @else
                                                            <span class="text-gray-500">{{ $docLabel }}</span>
                                                        @endif
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
        </div>

        {{-- ── Tombol Aksi ─────────────────────────────────────────────── --}}
        <div class="sticky bottom-0 bg-white border-t border-gray-100 px-0 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <a href="{{ route('kecamatan.assessment.show', $id) }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg
                      border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700
                      hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali &amp; Edit
            </a>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <p class="text-xs text-gray-400 hidden sm:block">Setelah dikirim, jawaban tidak dapat diubah kecuali ada permintaan revisi.</p>

                {{-- Form submit — POST ke endpoint yang sama dengan show.blade.php --}}
                <form method="POST"
                      action="{{ $userStatus && in_array($userStatus->status, ['NEEDS_REVISION','RESUBMITTED'])
                                 ? route('kecamatan.assessment.update', $id)
                                 : route('kecamatan.assessment.submit', $id) }}"
                      id="form-review-submit">
                    @csrf
                    @if ($userStatus && in_array($userStatus->status, ['NEEDS_REVISION','RESUBMITTED']))
                        @method('PUT')
                    @endif

                    {{-- Kirim semua jawaban tersimpan sebagai hidden inputs --}}
                    @foreach ($categories as $cat)
                        @foreach ($cat->indicators as $ind)
                            @php $entry = $entriesMap->get($ind->id); @endphp
                            @if ($entry)
                                <input type="hidden" name="entries[{{ $ind->id }}][description]"  value="{{ $entry->description }}">
                                <input type="hidden" name="entries[{{ $ind->id }}][score]"         value="{{ $entry->score }}">
                                <input type="hidden" name="entries[{{ $ind->id }}][supporting_doc]" value="{{ $entry->supporting_doc }}">
                            @endif
                        @endforeach
                    @endforeach

                    <button type="submit" id="btn-submit-final"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg
                                   bg-sky-600 px-6 py-2.5 text-sm font-semibold text-white
                                   hover:bg-sky-700 active:bg-sky-800 transition-colors
                                   disabled:opacity-60 disabled:cursor-not-allowed"
                            @if ($filledCount < $totalIndicators) disabled title="Lengkapi semua indikator dahulu" @endif>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Kirim Sekarang
                    </button>
                </form>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
    (function () {
        var btn = document.getElementById('btn-submit-final');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (btn.disabled) return;
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Mengirim…';
            document.getElementById('form-review-submit').submit();
        });
    })();
    </script>
    @endpush

</x-layouts.kecamatan>
