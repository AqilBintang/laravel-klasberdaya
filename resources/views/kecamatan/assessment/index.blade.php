{{--
    kecamatan/assessment/index.blade.php
    Daftar assessment yang tersedia untuk diisi oleh kecamatan.
    Data yang diharapkan dari AssessmentController::index():
      - $assessments          : Collection<Assessment> dengan categories.indicators
      - $entriesByAssessment  : array<int, array> — entri per assessmentId
      - $needsRevisionSet     : array<int> — assessmentId yang perlu revisi (ada indikator baru dari update admin)
      - $hasUpdateSet         : array<int> — assessmentId yang ada update indikator baru
      - $blockedSet           : array<int> — assessmentId REVISION yang belum boleh diisi (user belum mulai)
--}}
<x-layouts.kecamatan pageTitle="Isi Assessment">

    <div class="space-y-6">

        {{-- ── Heading ─────────────────────────────────────────────────── --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Isi Assessment</h2>
            <p class="mt-1 text-sm text-gray-500">Pilih assessment yang ingin Anda isi</p>
        </div>

        @if (isset($assessments) && $assessments->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($assessments as $assessment)
                    @php
                        $totalInd   = $assessment->categories->sum(fn ($c) => $c->indicators->count());
                        $entries    = $entriesByAssessment[$assessment->id] ?? [];
                        $entryCount = count($entries);
                        $isUpdating = in_array($assessment->id, $blockedSet ?? []);

                        // Derive status
                        $needsRevision  = in_array($assessment->id, $needsRevisionSet ?? []);
                        $hasUpdate      = in_array($assessment->id, $hasUpdateSet ?? []);

                        if ($entryCount === 0) {
                            $derivedStatus = 'BELUM_DIISI';
                        } elseif (collect($entries)->every(fn($e) => $e['status'] === 'VALIDATED')) {
                            $derivedStatus = 'VALIDATED';
                        } elseif (collect($entries)->every(fn($e) => in_array($e['status'], ['SUBMITTED', 'VALIDATED']))) {
                            $derivedStatus = 'SUBMITTED';
                        } elseif ($entryCount < $totalInd) {
                            $derivedStatus = 'PARTIAL';
                        } else {
                            $derivedStatus = 'DRAFT';
                        }

                        if ($needsRevision) $derivedStatus = 'NEEDS_REVISION';
                        if ($hasUpdate && !$needsRevision) $derivedStatus = 'HAS_UPDATE';

                        $statusConfig = [
                            'BELUM_DIISI'    => ['label' => 'Belum Diisi',      'cls' => 'bg-slate-100 text-slate-600 border-slate-200'],
                            'DRAFT'          => ['label' => 'Draft',             'cls' => 'bg-yellow-50 text-yellow-700 border-yellow-200'],
                            'PARTIAL'        => ['label' => 'Diisi Sebagian',    'cls' => 'bg-orange-50 text-orange-700 border-orange-200'],
                            'SUBMITTED'      => ['label' => 'Menunggu Validasi', 'cls' => 'bg-amber-50 text-amber-700 border-amber-200'],
                            'VALIDATED'      => ['label' => 'Disetujui',         'cls' => 'bg-green-50 text-green-700 border-green-200'],
                            'NEEDS_REVISION' => ['label' => 'Perlu Revisi',      'cls' => 'bg-red-50 text-red-700 border-red-200'],
                            'HAS_UPDATE'     => ['label' => 'Ada Update',        'cls' => 'bg-blue-50 text-blue-700 border-blue-200'],
                        ];
                        $statusCfg = $statusConfig[$derivedStatus] ?? $statusConfig['BELUM_DIISI'];

                        $pct = $totalInd > 0 ? round($entryCount / $totalInd * 100) : 0;
                        $barColor = match ($derivedStatus) {
                            'VALIDATED'      => 'bg-green-500',
                            'SUBMITTED'      => 'bg-amber-400',
                            'NEEDS_REVISION' => 'bg-red-400',
                            'HAS_UPDATE'     => 'bg-blue-400',
                            default          => 'bg-sky-500',
                        };
                        $borderColor = match (true) {
                            $needsRevision => 'border-red-300 hover:border-red-400',
                            $hasUpdate     => 'border-blue-300 hover:border-blue-400',
                            default        => 'border-slate-200 hover:border-sky-300',
                        };
                        $ctaLabel = match ($derivedStatus) {
                            'VALIDATED'      => 'Lihat Detail',
                            'NEEDS_REVISION' => 'Isi Revisi',
                            'HAS_UPDATE'     => 'Lihat Update',
                            'BELUM_DIISI'    => 'Mulai Isi',
                            default          => 'Lanjutkan',
                        };
                    @endphp

                    @if ($isUpdating)
                        {{-- Sedang diperbarui admin — non-interaktif --}}
                        <div class="rounded-xl border border-slate-200 bg-white/60 p-5 opacity-75 cursor-not-allowed">
                            <div class="flex items-start justify-between mb-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Sedang Diperbarui
                                </span>
                                <span class="text-xs text-slate-400">{{ $assessment->periode }}</span>
                            </div>
                            <h3 class="font-semibold text-slate-500 leading-snug">{{ $assessment->title }}</h3>
                            @if ($assessment->description)
                                <p class="mt-1 text-sm text-gray-400 line-clamp-2">{{ $assessment->description }}</p>
                            @endif
                            <p class="mt-3 text-xs text-amber-600">
                                Assessment ini sedang dalam proses pembaruan oleh admin. Silakan coba beberapa saat lagi.
                            </p>
                        </div>

                    @else
                        {{-- Assessment aktif — bisa diklik --}}
                        <a href="{{ route('kecamatan.assessment.show', $assessment->id) }}"
                           class="rounded-xl border bg-white p-5 transition-all group block {{ $borderColor }} hover:shadow-sm">

                            <div class="flex items-start justify-between mb-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusCfg['cls'] }}">
                                    {{ $statusCfg['label'] }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $assessment->periode }}</span>
                            </div>

                            <h3 class="font-semibold text-slate-900 leading-snug transition-colors
                                       {{ $needsRevision ? 'group-hover:text-red-600' : ($hasUpdate ? 'group-hover:text-blue-600' : 'group-hover:text-sky-600') }}">
                                {{ $assessment->title }}
                            </h3>

                            @if ($assessment->description)
                                <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ $assessment->description }}</p>
                            @endif

                            @if ($needsRevision)
                                @php
                                    $hasValidatorFeedback = in_array($assessment->id, $validatorFeedbackSet ?? []);
                                @endphp
                                <p class="mt-2 text-xs text-red-600 font-medium">
                                    ⚠️ @if ($hasValidatorFeedback)
                                        Tim teknis meminta perbaikan. Buka assessment untuk mengisi ulang indikator yang ditandai.
                                    @else
                                        Ada perubahan dari admin. Silakan lengkapi indikator yang baru/diperbarui.
                                    @endif
                                </p>
                            @elseif ($hasUpdate)
                                <p class="mt-2 text-xs text-blue-600 font-medium">
                                    📋 Assessment diperbarui admin. Silakan lengkapi indikator baru yang tersedia.
                                </p>
                            @endif

                            @if ($entryCount > 0)
                                <div class="mt-3">
                                    <div class="flex justify-between text-xs text-slate-400 mb-1">
                                        <span>Progres pengisian</span>
                                        <span>{{ $entryCount }}/{{ $totalInd }} indikator</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full transition-all {{ $barColor }}"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-xs text-slate-400">
                                    {{ $assessment->categories->count() }} kategori · {{ $totalInd }} indikator
                                </span>
                                <span class="flex items-center gap-1 text-sm font-medium transition-colors
                                             {{ $derivedStatus === 'VALIDATED' ? 'text-green-600' : ($needsRevision ? 'text-red-600' : ($hasUpdate ? 'text-blue-600' : 'text-sky-600')) }}">
                                    {{ $ctaLabel }}
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </div>

                        </a>
                    @endif

                @endforeach
            </div>

        @else
            {{-- ── Empty state ─────────────────────────────────────────── --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed
                        border-slate-200 bg-white py-20 text-center">
                <svg class="w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-slate-500 font-medium text-sm">Belum ada assessment yang tersedia</p>
                <p class="text-xs text-slate-400 mt-1">Hubungi administrator untuk informasi lebih lanjut</p>
            </div>
        @endif

    </div>

</x-layouts.kecamatan>
