{{--
    kecamatan/panduan.blade.php
    Halaman Panduan Penilaian untuk Kecamatan.
    Data dari PanduanController::index():
      - $rubrics : Collection<AssessmentRubric> with assessment + items.indicator.category
--}}
<x-layouts.kecamatan pageTitle="Panduan Penilaian">

    <div class="space-y-8">

        {{-- ── Heading ──────────────────────────────────────────────────── --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Panduan Penilaian</h2>
            <p class="mt-1 text-sm text-gray-500">
                Rubrik penilaian sebagai panduan pengisian self assessment
            </p>
        </div>

        @if ($rubrics->isEmpty())
            {{-- ── Empty state ─────────────────────────────────────────── --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed
                        border-slate-200 bg-white py-20 text-center">
                <svg class="w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168
                             18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5
                             5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746
                             0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-slate-500 font-medium text-sm">Belum ada panduan yang tersedia</p>
                <p class="text-xs text-slate-400 mt-1">Hubungi administrator untuk informasi lebih lanjut</p>
            </div>
        @else
            {{-- ── Rubrik per assessment ─────────────────────────────── --}}
            @foreach ($rubrics as $rubric)
                @php
                    // Kelompokkan item per kategori
                    $catGroups = [];
                    foreach ($rubric->items as $item) {
                        $cat = $item->indicator->category;
                        $key = $cat->id;
                        if (!isset($catGroups[$key])) {
                            $catGroups[$key] = [
                                'code'  => $cat->code,
                                'name'  => $cat->name,
                                'order' => $cat->order,
                                'items' => [],
                            ];
                        }
                        $catGroups[$key]['items'][] = $item;
                    }
                    usort($catGroups, fn ($a, $b) => $a['order'] <=> $b['order']);
                @endphp

                <div class="space-y-4">

                    {{-- ── Header rubrik ──────────────────────────────── --}}
                    <div class="rounded-xl bg-sky-600 px-5 py-4 text-white">
                        <h3 class="text-base font-bold">
                            {{ Str::title($rubric->title) }}
                        </h3>
                        <p class="text-sky-200 text-xs mt-0.5">
                            Periode {{ $rubric->assessment->periode }}
                        </p>
                    </div>

                    {{-- ── Per kategori ────────────────────────────────── --}}
                    @foreach ($catGroups as $cat)
                        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">

                            {{-- Header kategori --}}
                            <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                                <h4 class="font-semibold text-slate-700 text-sm">
                                    {{ $cat['code'] }}. {{ $cat['name'] }}
                                </h4>
                            </div>

                            {{-- Tabel indikator --}}
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-600">
                                            <th class="px-3 py-3 text-center w-10">No</th>
                                            <th class="px-4 py-3 text-left min-w-[200px]">Indikator</th>
                                            <th class="px-4 py-3 text-left min-w-[150px]">Skor 1</th>
                                            <th class="px-4 py-3 text-left min-w-[150px]">Skor 2</th>
                                            <th class="px-4 py-3 text-left min-w-[150px]">Skor 3</th>
                                            <th class="px-4 py-3 text-left min-w-[150px]">Skor 4</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($cat['items'] as $item)
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-3 py-4 text-center text-slate-500 text-xs font-medium align-top">
                                                    {{ $item->indicator->number }}
                                                </td>
                                                <td class="px-4 py-4 text-slate-800 text-sm font-medium align-top leading-relaxed">
                                                    {{ $item->indicator->indicator }}
                                                </td>
                                                <td class="px-4 py-4 text-slate-600 text-xs align-top leading-relaxed">
                                                    {{ $item->score1 ?? '—' }}
                                                </td>
                                                <td class="px-4 py-4 text-slate-600 text-xs align-top leading-relaxed">
                                                    {{ $item->score2 ?? '—' }}
                                                </td>
                                                <td class="px-4 py-4 text-slate-600 text-xs align-top leading-relaxed">
                                                    {{ $item->score3 ?? '—' }}
                                                </td>
                                                <td class="px-4 py-4 text-slate-600 text-xs align-top leading-relaxed">
                                                    {{ $item->score4 ?? '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    @endforeach

                </div>
            @endforeach
        @endif

    </div>

</x-layouts.kecamatan>
