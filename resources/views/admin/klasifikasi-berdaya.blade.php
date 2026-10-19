{{--
    admin/klasifikasi-berdaya.blade.php
    Halaman statistik klasifikasi kecamatan berdaya.
    Data: $totalRegistered, $totalValidated, $activeYear, $years, $summary, $kabDistribusi, $chartData
    Klasifikasi: Rintisan (merah), Berkembang (kuning), Maju (biru), Unggul (hijau)
--}}
<x-layouts.admin pageTitle="Klasifikasi Berdaya">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Klasifikasi Berdaya</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Statistik klasifikasi kecamatan berdasarkan hasil assessment yang telah divalidasi
                </p>
            </div>
            @isset($years)
                @if (count($years) > 0)
                    <form method="GET" class="flex items-center gap-2">
                        <label class="text-sm text-gray-500">Tahun:</label>
                        <select name="tahun" onchange="this.form.submit()"
                                class="rounded-lg border border-gray-200 pl-3 pr-8 py-2 text-sm focus:border-sky-400 focus:outline-none">
                            @foreach ($years as $yr)
                                <option value="{{ $yr }}" {{ ($activeYear ?? '') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            @endisset
        </div>

        @if (!isset($totalRegistered) || $totalRegistered === 0)
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <p class="text-gray-500 font-medium">Belum ada kecamatan terdaftar di sistem.</p>
            </div>
        @else

        {{-- Overview metrics --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-blue-50 p-2.5 shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Total Kecamatan</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalRegistered }}</p>
                        <p class="text-xs text-gray-400">Terdaftar di sistem</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-green-50 p-2.5 shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Tervalidasi</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalValidated ?? 0 }}</p>
                        <p class="text-xs text-gray-400">Tahun {{ $activeYear ?? '&mdash;' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-amber-50 p-2.5 shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Coverage</p>
                        <p class="text-2xl font-bold text-gray-900">
                            @php $cov = $totalRegistered > 0 ? round((($totalValidated ?? 0) / $totalRegistered) * 100) : 0; @endphp
                            {{ $cov }}%
                        </p>
                        <p class="text-xs text-gray-400">Data coverage</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-purple-50 p-2.5 shrink-0">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Periode Data</p>
                        <p class="text-2xl font-bold text-gray-900">{{ count($years ?? []) }}</p>
                        <p class="text-xs text-gray-400">
                            @if (!empty($years))
                                {{ $years[0] }} - {{ $years[count($years)-1] }}
                            @else
                                Belum ada
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Distribusi Klasifikasi --}}
        @isset($summary)
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Distribusi Klasifikasi</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Breakdown klasifikasi kecamatan untuk tahun {{ $activeYear ?? '&mdash;' }}
                    (dari {{ $totalValidated ?? 0 }} kecamatan yang tervalidasi)
                </p>
            </div>
            <div class="p-6">
                @php
                    $levels = [
                        'Rintisan'   => ['text' => 'text-red-700',   'bar' => 'bg-red-400'],
                        'Berkembang' => ['text' => 'text-amber-700', 'bar' => 'bg-amber-400'],
                        'Maju'       => ['text' => 'text-blue-700',  'bar' => 'bg-blue-400'],
                        'Unggul'     => ['text' => 'text-green-700', 'bar' => 'bg-green-500'],
                    ];
                    $totalVal = $totalValidated ?? 0;
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    @foreach ($levels as $level => $cls)
                        @php
                            $count = $summary[$level] ?? 0;
                            $pct = $totalVal > 0 ? round(($count / $totalVal) * 100) : 0;
                        @endphp
                        <div class="text-center">
                            <p class="font-bold text-2xl text-gray-900">{{ $count }}</p>
                            <p class="text-sm font-medium {{ $cls['text'] }}">{{ $level }}</p>
                            <p class="text-xs text-gray-500">{{ $pct }}% dari tervalidasi</p>
                        </div>
                    @endforeach
                </div>

                {{-- Progress bar --}}
                <div class="bg-gray-100 rounded-full h-3 overflow-hidden">
                    <div class="h-full flex">
                        @foreach ($levels as $level => $cls)
                            @php
                                $count = $summary[$level] ?? 0;
                                $pct = $totalVal > 0 ? ($count / $totalVal) * 100 : 0;
                            @endphp
                            @if ($pct > 0)
                                <div class="h-full {{ $cls['bar'] }}" style="width: {{ $pct }}%"
                                     title="{{ $level }}: {{ $count }} ({{ round($pct) }}%)"></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                @if (($summary['belumAda'] ?? 0) > 0)
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">{{ $summary['belumAda'] }} kecamatan</span>
                            belum memiliki data assessment untuk tahun {{ $activeYear }}.
                        </p>
                    </div>
                @endif
            </div>
        </div>
        @endisset

        {{-- Tren Historis (Chart.js) --}}
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Tren Historis</h3>
                <p class="text-sm text-gray-500 mt-1">Perkembangan klasifikasi kecamatan dari waktu ke waktu</p>
            </div>
            <div class="p-6">
                @if (empty($chartData) || count($chartData) === 0)
                    <div class="h-64 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 font-medium">Belum ada data assessment yang divalidasi</p>
                        <p class="text-sm text-gray-400">Hasil klasifikasi muncul setelah admin memvalidasi assessment kecamatan</p>
                    </div>
                @else
                    <div class="space-y-3">
                        <div style="height: 360px; position: relative;">
                            <canvas id="klasifikasiBerdayaChart"></canvas>
                        </div>
                        {{-- Legend — dot bulat kecil, sesuai Recharts --}}
                        <div class="flex flex-wrap justify-center gap-3 text-xs text-gray-500">
                            @foreach ([['Rintisan','#ef4444'],['Berkembang','#f59e0b'],['Maju','#3b82f6'],['Unggul','#22c55e']] as [$lbl, $clr])
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block w-2 h-2 rounded-full" style="background:{{ $clr }}"></span>
                                {{ $lbl }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Distribusi Regional --}}
        @isset($kabDistribusi)
        @if (count($kabDistribusi) > 0 && $activeYear)
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Distribusi Regional</h3>
                <p class="text-sm text-gray-500 mt-1">Breakdown klasifikasi per kabupaten/kota untuk tahun {{ $activeYear }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Kabupaten/Kota</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-red-500 uppercase tracking-wide">Rintisan</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-amber-600 uppercase tracking-wide">Berkembang</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-blue-600 uppercase tracking-wide">Maju</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-green-600 uppercase tracking-wide">Unggul</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Distribusi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($kabDistribusi as $kab)
                            @php
                                $validated = ($kab['counts']['Rintisan'] ?? 0)
                                           + ($kab['counts']['Berkembang'] ?? 0)
                                           + ($kab['counts']['Maju'] ?? 0)
                                           + ($kab['counts']['Unggul'] ?? 0);
                                $barColors2 = ['Rintisan'=>'bg-red-400','Berkembang'=>'bg-amber-400','Maju'=>'bg-blue-400','Unggul'=>'bg-green-500'];
                                $badgeColors= ['Rintisan'=>'bg-red-400','Berkembang'=>'bg-amber-400','Maju'=>'bg-blue-400','Unggul'=>'bg-green-500'];
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $kab['nama'] }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="font-semibold text-gray-900">{{ $kab['total'] }}</span>
                                    @if ($validated > 0)
                                        <span class="block text-xs text-gray-400">{{ $validated }} tervalidasi</span>
                                    @endif
                                </td>
                                @foreach (['Rintisan', 'Berkembang', 'Maju', 'Unggul'] as $lvl)
                                    @php $cnt = $kab['counts'][$lvl] ?? 0; @endphp
                                    <td class="px-5 py-4 text-center">
                                        @if ($cnt > 0)
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold text-white {{ $badgeColors[$lvl] }}">
                                                {{ $cnt }}
                                            </span>
                                        @else
                                            <span class="text-gray-300">&mdash;</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-6 py-4">
                                    @if ($validated > 0)
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden min-w-[80px]">
                                                <div class="h-full flex">
                                                    @foreach ($barColors2 as $lvl => $barCls)
                                                        @php
                                                            $cnt = $kab['counts'][$lvl] ?? 0;
                                                            $pct2 = $validated > 0 ? ($cnt / $validated) * 100 : 0;
                                                        @endphp
                                                        @if ($pct2 > 0)
                                                            <div class="h-full {{ $barCls }}" style="width:{{ $pct2 }}%"
                                                                 title="{{ $lvl }}: {{ $cnt }}"></div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                            <span class="text-xs text-gray-500 whitespace-nowrap">
                                                {{ round(($validated / $kab['total']) * 100) }}% coverage
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Belum ada data</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @endisset

        @endif {{-- end if totalRegistered > 0 --}}
    </div>

    {{-- Chart.js via CDN + inisialisasi chart (hanya dimuat jika ada data) --}}
    @if (!empty($chartData) && count($chartData) > 0)
        @php
            $chartLabels     = array_column($chartData, 'year');
            $chartRintisan   = array_column($chartData, 'rintisan');
            $chartBerkembang = array_column($chartData, 'berkembang');
            $chartMaju       = array_column($chartData, 'maju');
            $chartUnggul     = array_column($chartData, 'unggul');
        @endphp
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
        (function () {
            const labels     = @json($chartLabels);
            const rintisan   = @json($chartRintisan);
            const berkembang = @json($chartBerkembang);
            const maju       = @json($chartMaju);
            const unggul     = @json($chartUnggul);

            function initChart() {
                const ctx = document.getElementById('klasifikasiBerdayaChart');
                if (!ctx) return;

                // Sesuaikan tinggi canvas dengan container
                ctx.style.width  = '100%';
                ctx.style.height = '100%';

                // Hancurkan instance lama jika ada (live-reload friendly)
                const existing = Chart.getChart ? Chart.getChart(ctx) : null;
                if (existing) existing.destroy();

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            // Warna & urutan sesuai Recharts: #ef4444 / #f59e0b / #3b82f6 / #22c55e
                            // barPercentage: lebar bar relatif terhadap slot-nya
                            // categoryPercentage: seberapa lebar grup mengisi 1 slot label
                            { label: 'Rintisan',   data: rintisan,   backgroundColor: '#ef4444',
                              borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                              borderSkipped: false, maxBarThickness: 44,
                              barPercentage: 0.9, categoryPercentage: 0.5 },
                            { label: 'Berkembang', data: berkembang, backgroundColor: '#f59e0b',
                              borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                              borderSkipped: false, maxBarThickness: 44,
                              barPercentage: 0.9, categoryPercentage: 0.5 },
                            { label: 'Maju',       data: maju,       backgroundColor: '#3b82f6',
                              borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                              borderSkipped: false, maxBarThickness: 44,
                              barPercentage: 0.9, categoryPercentage: 0.5 },
                            { label: 'Unggul',     data: unggul,     backgroundColor: '#22c55e',
                              borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                              borderSkipped: false, maxBarThickness: 44,
                              barPercentage: 0.9, categoryPercentage: 0.5 },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        // Grouped (tidak stacked) — sama dengan Recharts default
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (item) {
                                        return item.dataset.label + ': ' + item.parsed.y;
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                stacked: false,   // grouped, bukan stacked
                                grid: {
                                    // CartesianGrid strokeDasharray="3 3" → dashed di kedua arah
                                    display: true,
                                    color: 'rgba(0,0,0,0.1)',
                                    lineWidth: 1,
                                },
                                border: { dash: [3, 3] },
                                ticks: {
                                    font: { size: 12 },
                                    maxRotation: 0,
                                },
                            },
                            y: {
                                stacked: false,   // grouped
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: 'rgba(0,0,0,0.1)',
                                    lineWidth: 1,
                                },
                                border: { dash: [3, 3] },
                                ticks: {
                                    font: { size: 11 },
                                    callback: function (v) { return Number.isInteger(v) ? v : null; },
                                },
                            },
                        },
                        // Margin sesuai Recharts: top:10 right:16 left:0 bottom:0
                        layout: {
                            padding: { top: 10, right: 16, left: 0, bottom: 0 },
                        },
                    },
                });
            }

            initChart();
        })();
        </script>
        @endpush
    @endif
</x-layouts.admin>
