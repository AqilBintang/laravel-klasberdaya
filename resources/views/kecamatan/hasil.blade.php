{{--
    kecamatan/hasil.blade.php
    Halaman Hasil Nilai Assessment untuk Kecamatan.
    Data dari HasilController::index():
      - $groups : array of AssessmentGroup (assessment, categories[], totalScore, maxScore, statusAkhir)
--}}
<x-layouts.kecamatan pageTitle="Hasil Nilai">

    <div class="space-y-8">

        {{-- ── Heading ──────────────────────────────────────────────── --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Hasil Nilai Assessment</h2>
            <p class="mt-1 text-sm text-gray-500">Rekap penilaian kecamatan Anda per periode</p>
        </div>

        @if (empty($groups))
            {{-- ── Empty state ───────────────────────────────────────── --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed
                        border-slate-200 bg-white py-20 text-center">
                <svg class="w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438
                             0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806
                             1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0
                             01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42
                             3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946
                             3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                <p class="text-slate-500 font-medium text-sm">Belum ada hasil assessment</p>
                <p class="text-xs text-slate-400 mt-1">Submit assessment dan tunggu validasi dari admin</p>
            </div>

        @else
            @foreach ($groups as $g)
                <div class="space-y-4">

                    {{-- ── Header assessment ───────────────────────────── --}}
                    <div class="rounded-xl border border-gray-200 bg-white px-5 py-5">
                        <p class="text-xs text-gray-400 mb-3">
                            {{ $g['assessment']->title }} · Periode {{ $g['assessment']->periode }}
                        </p>
                        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5">Total Skor</p>
                                <p class="text-4xl font-bold text-gray-900 tracking-tight">
                                    {{ $g['totalScore'] }}
                                    <span class="text-xl font-normal text-gray-400 ml-1">/ {{ $g['maxScore'] }}</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1.5">Status Akhir</p>
                                @if ($g['statusAkhir'])
                                    @php $cfg = \App\Helpers\Scoring::getKlasifikasiConfig($g['statusAkhir']); @endphp
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-semibold border {{ $cfg['bg'] }} {{ $cfg['color'] }} {{ $cfg['border'] }}">
                                        {{ $g['statusAkhir'] }}
                                    </span>
                                @else
                                    <span class="text-sm text-slate-400 italic">Tidak berlaku</span>
                                @endif
                            </div>
                        </div>
                        {{-- Progress bar --}}
                        <div class="mt-4">
                            @php $pct = $g['maxScore'] > 0 ? round(($g['totalScore'] / $g['maxScore']) * 100) : 0; @endphp
                            <div class="flex justify-between text-xs text-gray-400 mb-1">
                                <span>Progress keseluruhan</span>
                                <span>{{ $pct }}%</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full bg-sky-500 transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Per Kategori ─────────────────────────────────── --}}
                    @foreach ($g['categories'] as $cat)
                        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">

                            {{-- Category header --}}
                            <div class="flex items-center justify-between px-5 py-4 bg-slate-50 border-b border-slate-200">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white shrink-0">
                                        {{ $cat['code'] }}
                                    </span>
                                    <div>
                                        <p class="font-semibold text-slate-900 text-sm">{{ $cat['name'] }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ count($cat['items']) }} indikator · {{ $cat['totalScore'] }}/{{ $cat['maxScore'] }} poin
                                        </p>
                                    </div>
                                </div>
                                @if ($cat['klasifikasi'])
                                    @php $cfgCat = \App\Helpers\Scoring::getKlasifikasiConfig($cat['klasifikasi']); @endphp
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $cfgCat['bg'] }} {{ $cfgCat['color'] }} {{ $cfgCat['border'] }}">
                                        {{ $cat['klasifikasi'] }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @endif
                            </div>

                            {{-- Score bar --}}
                            <div class="px-5 py-3 border-b border-slate-100">
                                @php
                                    $catPct = $cat['maxScore'] > 0 ? round(($cat['totalScore'] / $cat['maxScore']) * 100) : 0;
                                    $barColor = $catPct >= 80 ? 'bg-green-500' : ($catPct >= 60 ? 'bg-amber-400' : 'bg-red-400');
                                @endphp
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $catPct }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-500 w-14 text-right shrink-0">
                                        {{ $cat['totalScore'] }}/{{ $cat['maxScore'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Indikator detail --}}
                            <div class="divide-y divide-slate-100">
                                @foreach ($cat['items'] as $item)
                                    @php
                                        $validation   = $item->validations->first();
                                        $effScore     = $validation?->validated_score ?? $item->score;
                                        $statusMap = [
                                            'VALIDATED' => ['label' => 'Divalidasi', 'cls' => 'text-green-600'],
                                            'SUBMITTED' => ['label' => 'Menunggu',   'cls' => 'text-amber-500'],
                                            'REJECTED'  => ['label' => 'Ditolak',    'cls' => 'text-red-500'],
                                            'DRAFT'     => ['label' => 'Draft',      'cls' => 'text-slate-400'],
                                        ];
                                        $sCfg = $statusMap[$item->status] ?? $statusMap['DRAFT'];
                                    @endphp
                                    <div class="px-5 py-3 flex items-start gap-4">
                                        <div class="w-5 text-center text-xs font-medium text-slate-400 mt-0.5 shrink-0">
                                            {{ $item->indicator->number }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-slate-700 leading-snug">{{ $item->indicator->indicator }}</p>
                                            @if ($item->description && $item->description !== '-')
                                                <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $item->description }}</p>
                                            @endif
                                            @if ($validation?->notes)
                                                <p class="text-xs text-amber-600 mt-1">💬 {{ $validation->notes }}</p>
                                            @endif
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-base font-bold text-slate-900 leading-none">
                                                {{ $effScore }}<span class="text-xs font-normal text-slate-400">/{{ $item->indicator->max_score }}</span>
                                            </p>
                                            @if ($validation?->validated_score !== null && $validation->validated_score !== $item->score)
                                                <p class="text-xs text-slate-400 line-through">{{ $item->score }}</p>
                                            @endif
                                            <span class="flex items-center gap-1 text-xs mt-1 justify-end {{ $sCfg['cls'] }}">
                                                {{ $sCfg['label'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    @endforeach

                    {{-- ── Rekapitulasi ─────────────────────────────────── --}}
                    <div class="rounded-xl border border-slate-200 bg-white px-5 py-5">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                                         M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <h3 class="font-semibold text-slate-800 text-sm">Rekapitulasi Nilai</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500">
                                        <th class="pb-2 text-left">Kategori</th>
                                        <th class="pb-2 text-center">Skor</th>
                                        <th class="pb-2 text-center">Maks</th>
                                        <th class="pb-2 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($g['categories'] as $cat)
                                        <tr>
                                            <td class="py-2.5 text-slate-700 font-medium">{{ $cat['code'] }}. {{ $cat['name'] }}</td>
                                            <td class="py-2.5 text-center font-bold text-slate-900">{{ $cat['totalScore'] }}</td>
                                            <td class="py-2.5 text-center text-slate-500">{{ $cat['maxScore'] }}</td>
                                            <td class="py-2.5 text-right">
                                                @if ($cat['klasifikasi'])
                                                    @php $cfgR = \App\Helpers\Scoring::getKlasifikasiConfig($cat['klasifikasi']); @endphp
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $cfgR['bg'] }} {{ $cfgR['color'] }} {{ $cfgR['border'] }}">
                                                        {{ $cat['klasifikasi'] }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-slate-400 italic">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-t-2 border-slate-300 font-bold">
                                        <td class="pt-3 text-slate-800">Total Keseluruhan</td>
                                        <td class="pt-3 text-center text-slate-900 text-base">{{ $g['totalScore'] }}</td>
                                        <td class="pt-3 text-center text-slate-600">{{ $g['maxScore'] }}</td>
                                        <td class="pt-3 text-right">
                                            @if ($g['statusAkhir'])
                                                @php $cfgFinal = \App\Helpers\Scoring::getKlasifikasiConfig($g['statusAkhir']); @endphp
                                                <span class="inline-flex items-center rounded-full px-4 py-1.5 text-sm font-semibold border {{ $cfgFinal['bg'] }} {{ $cfgFinal['color'] }} {{ $cfgFinal['border'] }}">
                                                    {{ $g['statusAkhir'] }}
                                                </span>
                                            @else
                                                <span class="text-sm text-slate-400 italic">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            @endforeach
        @endif

    </div>

</x-layouts.kecamatan>
