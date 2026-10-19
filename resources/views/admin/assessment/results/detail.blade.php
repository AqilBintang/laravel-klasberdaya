{{--
    admin/assessment/results/detail.blade.php
    Detail pengisian assessment oleh satu kecamatan + ringkasan akhir.
    Data: $userId, $assessmentId, $periode, $user, $assessment,
          $entries (Collection, key: indicator_id),
          $catSummary, $totalScore, $maxScore, $statusAkhir, $pct
--}}
<x-layouts.admin pageTitle="Detail Assessment Kecamatan">
@php
    if (!function_exists('klasBadge')) {
        function klasBadge(?string $level, string $size = 'sm'): string {
            if (!$level) {
                $textCls = $size === 'lg' ? 'text-sm' : 'text-xs';
                return "<span class=\"{$textCls} text-gray-400 italic\">Tidak berlaku</span>";
            }
            $cfg = \App\Helpers\Scoring::getKlasifikasiConfig($level);
            $pad = $size === 'lg' ? 'px-4 py-1.5 text-sm' : 'px-2.5 py-0.5 text-xs';
            return '<span class="inline-flex items-center rounded-full font-semibold border '
                . e($cfg['bg']) . ' ' . e($cfg['color']) . ' ' . e($cfg['border']) . ' ' . $pad . '">'
                . e($level) . '</span>';
        }
    }
    if (!function_exists('customLabelBadge')) {
        function customLabelBadge(string $label, string $size = 'sm'): string {
            $pad = $size === 'lg' ? 'px-4 py-1.5 text-sm' : 'px-2.5 py-0.5 text-xs';
            return "<span class=\"inline-flex items-center rounded-full font-semibold border border-amber-200 bg-amber-50 text-amber-800 {$pad}\">"
                . e($label) . '</span>';
        }
    }
@endphp

<div class="space-y-6">

    {{-- Back + Title --}}
    <div>
        <a href="{{ route('admin.assessment.results.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-3">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Rekapitulasi
        </a>
        <h2 class="text-2xl font-bold text-gray-900">Detail Assessment Kecamatan</h2>
    </div>

    {{-- Header info card --}}
    @isset($user, $assessment)
    <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
                    {{ $assessment->title }}
                    @if ($periode)
                        - Periode {{ $periode }}
                    @endif
                </p>
                <h3 class="text-xl font-bold text-gray-900">
                    {{ $user->kecamatan ?? $user->name }}
                </h3>
                @if ($user->kabupaten)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $user->kabupaten }}</p>
                @endif
            </div>
            <div class="text-right shrink-0">
                <p class="text-xs text-gray-400 mb-1">Total Skor</p>
                <p class="text-3xl font-black text-gray-900">
                    {{ $totalScore }}<span class="text-base font-normal text-gray-400">/{{ $maxScore }}</span>
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $pct }}%</p>
            </div>
        </div>
        {{-- Progress bar --}}
        <div class="mt-4">
            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full rounded-full bg-sky-500 transition-all" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </div>
    @endisset

    {{-- Per-kategori --}}
    @isset($assessment)
        @foreach ($assessment->categories->sortBy('order') as $category)
            @php
                $catSum = collect($catSummary ?? [])->firstWhere('code', $category->code);
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">

                {{-- Category header --}}
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-sm font-bold text-white shrink-0">
                            {{ $category->code }}
                        </span>
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">{{ $category->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $category->indicators->count() }} indikator
                                @if ($catSum)
                                    - {{ $catSum['totalScore'] }}/{{ $catSum['maxScore'] }} poin
                                @endif
                            </p>
                        </div>
                    </div>
                    @if ($catSum)
                        @if ($catSum['customLabel'])
                            {!! customLabelBadge($catSum['customLabel']) !!}
                        @else
                            {!! klasBadge($catSum['klasifikasi']) !!}
                        @endif
                    @endif
                </div>

                {{-- Indicators table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500">
                                <th class="px-5 py-2.5 text-left w-6">#</th>
                                <th class="px-3 py-2.5 text-left">Indikator</th>
                                <th class="px-3 py-2.5 text-left">Deskripsi Jawaban</th>
                                <th class="px-3 py-2.5 text-center whitespace-nowrap">Skor Kecamatan</th>
                                <th class="px-3 py-2.5 text-center whitespace-nowrap">Skor Validator</th>
                                <th class="px-3 py-2.5 text-left">Komentar Tim Teknis</th>
                                <th class="px-3 py-2.5 text-center">Dokumen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($category->indicators->sortBy('number') as $ind)
                                @php
                                    $entry      = isset($entries) ? $entries->get($ind->id) : null;
                                    $validation = $entry?->validations->sortByDesc('validated_at')->first();
                                    $valScore   = $validation?->validated_score;
                                    $changed    = $valScore !== null && $valScore !== $entry->score;
                                @endphp
                                <tr class="align-top hover:bg-gray-50/60">
                                    <td class="px-5 py-3.5 text-xs text-gray-400 font-medium">{{ $ind->number }}</td>

                                    <td class="px-3 py-3.5 text-gray-700 max-w-[200px]">{{ $ind->indicator }}</td>

                                    <td class="px-3 py-3.5 text-gray-600 max-w-[220px]">
                                        @if ($entry && $entry->description && $entry->description !== '-')
                                            <span class="leading-relaxed">{{ $entry->description }}</span>
                                        @else
                                            <span class="text-gray-300 italic text-xs">Tidak diisi</span>
                                        @endif
                                    </td>

                                    {{-- Skor kecamatan --}}
                                    <td class="px-3 py-3.5 text-center">
                                        @if ($entry)
                                            <span class="{{ $changed ? 'text-gray-400 line-through text-xs' : 'font-bold text-gray-900' }} tabular-nums">{{ $entry->score }}</span><span class="text-gray-300 text-xs">/{{ $ind->max_score }}</span>
                                        @else
                                            <span class="text-gray-300 text-xs">N/A</span>
                                        @endif
                                    </td>

                                    {{-- Skor validator --}}
                                    <td class="px-3 py-3.5 text-center">
                                        @if ($valScore !== null)
                                            <span class="{{ $changed ? 'text-sky-700 font-bold' : 'text-gray-400' }} tabular-nums">{{ $valScore }}<span class="text-gray-300 text-xs font-normal">/{{ $ind->max_score }}</span></span>
                                        @else
                                            <span class="text-gray-300 text-xs">N/A</span>
                                        @endif
                                    </td>

                                    {{-- Komentar --}}
                                    <td class="px-3 py-3.5 text-gray-600 max-w-[220px]">
                                        @if ($validation?->notes)
                                            <span class="text-xs leading-relaxed">{{ $validation->notes }}</span>
                                        @else
                                            <span class="text-gray-300 text-xs">N/A</span>
                                        @endif
                                    </td>

                                    {{-- Dokumen --}}
                                    <td class="px-3 py-3.5 text-center">
                                        @if ($entry?->supporting_doc)
                                            <a href="{{ $entry->supporting_doc }}" target="_blank" rel="noopener noreferrer"
                                               class="inline-flex items-center gap-1 text-xs text-sky-600 hover:underline whitespace-nowrap">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                </svg>
                                                Buka
                                            </a>
                                        @else
                                            <span class="text-gray-300 text-xs">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @else
        <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
            <p class="text-sm text-gray-500">Data tidak ditemukan.</p>
        </div>
    @endisset

    {{-- Ringkasan Akhir --}}
    @if (!empty($catSummary))
    <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
        <h3 class="font-semibold text-gray-900 mb-4">Rekapitulasi Nilai</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs font-semibold text-gray-500">
                    <th class="pb-2 text-left">Kategori</th>
                    <th class="pb-2 text-center">Skor</th>
                    <th class="pb-2 text-center">Maks</th>
                    <th class="pb-2 text-right">Klasifikasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($catSummary as $cat)
                    <tr>
                        <td class="py-2.5 text-gray-700">{{ $cat['code'] }}. {{ $cat['name'] }}</td>
                        <td class="py-2.5 text-center font-bold text-gray-900">{{ $cat['totalScore'] }}</td>
                        <td class="py-2.5 text-center text-gray-400">{{ $cat['maxScore'] }}</td>
                        <td class="py-2.5 text-right">
                            @if ($cat['customLabel'])
                                {!! customLabelBadge($cat['customLabel']) !!}
                            @else
                                {!! klasBadge($cat['klasifikasi']) !!}
                            @endif
                        </td>
                    </tr>
                @endforeach

                {{-- Total row --}}
                <tr class="border-t-2 border-gray-200 font-bold">
                    <td class="pt-3 text-gray-900">Total Keseluruhan</td>
                    <td class="pt-3 text-center text-gray-900 text-base">{{ $totalScore }}</td>
                    <td class="pt-3 text-center text-gray-500">{{ $maxScore }}</td>
                    <td class="pt-3 text-right">{!! klasBadge($statusAkhir ?? null, 'lg') !!}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

</div>
</x-layouts.admin>
