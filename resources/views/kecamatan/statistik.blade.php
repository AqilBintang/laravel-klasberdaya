{{--
    kecamatan/statistik.blade.php
    Halaman Statistik Klasifikasi - identik dengan KecamatanStatistikClient (NextJS).
    Data dari StatistikController::index():
      $riwayat : array of PeriodeStat
        ['periode', 'totalScore', 'maxScore', 'statusAkhir', 'categories' => [
          ['id','code','name','order','totalScore','maxScore','klasifikasi']
        ]]
--}}
<x-layouts.kecamatan pageTitle="Statistik Klasifikasi">

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>

<div class="space-y-6">

    {{-- Heading --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Statistik Klasifikasi</h2>
        <p class="mt-1 text-sm text-gray-500">Riwayat hasil klasifikasi kecamatan Anda per periode</p>
    </div>

    @if (empty($riwayat))
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed
                    border-gray-200 bg-white py-20 text-center">
            <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
            <p class="text-gray-500 font-medium text-sm">Belum ada data statistik</p>
            <p class="text-xs text-gray-400 mt-1">
                Data muncul setelah assessment Anda divalidasi oleh admin
            </p>
        </div>
    @else

        {{-- Encode data PHP ke JSON untuk JS --}}
        @php
            $chartData = array_map(fn ($r) => [
                'periode' => $r['periode'],
                'skor'    => $r['totalScore'],
                'maks'    => $r['maxScore'],
                'pct'     => $r['maxScore'] > 0 ? round(($r['totalScore'] / $r['maxScore']) * 100) : 0,
            ], $riwayat);
        @endphp
        <script>
            window.__STATISTIK__ = @json($riwayat);
            window.__CHART_DATA__ = @json($chartData);
        </script>

        {{-- Line Chart --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Skor Per Tahun</h3>
            <div class="h-52">
                <canvas id="statistikChart"></canvas>
            </div>
            {{-- Legend --}}
            <div class="flex items-center gap-4 mt-3">
                <span class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span class="inline-block w-5 h-0.5 bg-sky-400 rounded"></span>
                    Total Skor
                </span>
                <span class="flex items-center gap-1.5 text-xs text-gray-400">
                    <span class="inline-block w-5 border-t-2 border-dashed border-gray-300"></span>
                    Skor Maks
                </span>
            </div>
        </div>

        {{-- Filter pill periode --}}
        <div class="flex items-center gap-2 flex-wrap" id="filter-pills">
            <button
                data-periode="semua"
                onclick="filterPeriode('semua')"
                class="pill-btn rounded-full border px-3 py-1 text-xs font-medium transition-colors
                       bg-sky-600 border-sky-600 text-white">
                Semua
            </button>
            @foreach ($riwayat as $r)
                <button
                    data-periode="{{ $r['periode'] }}"
                    onclick="filterPeriode('{{ $r['periode'] }}')"
                    class="pill-btn rounded-full border px-3 py-1 text-xs font-medium transition-colors
                           border-gray-200 text-gray-600 hover:border-sky-300 hover:text-sky-600">
                    {{ $r['periode'] }}
                </button>
            @endforeach
        </div>

        {{-- Container tabel per periode --}}
        <div id="periodeList" class="space-y-4"></div>

        {{-- Pagination --}}
        <div id="pagination" class="hidden">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-400" id="paginationInfo"></p>
                <div class="flex items-center gap-1" id="paginationButtons"></div>
            </div>
        </div>

    @endif
</div>

@if (!empty($riwayat))
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Chart.js ──────────────────────────────────────────────────────────
    const chartData = window.__CHART_DATA__ || [];

    const ctx = document.getElementById('statistikChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(d => d.periode),
                datasets: [
                    {
                        label: 'Skor Maks',
                        data: chartData.map(d => d.maks),
                        borderColor: '#e2e8f0',
                        borderWidth: 2,
                        borderDash: [4, 4],
                        pointRadius: 0,
                        tension: 0.3,
                        fill: false,
                    },
                    {
                        label: 'Total Skor',
                        data: chartData.map(d => d.skor),
                        borderColor: '#0ea5e9',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#0ea5e9',
                        pointRadius: 4,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y,
                        },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        backgroundColor: '#fff',
                        titleColor: '#374151',
                        bodyColor: '#6b7280',
                    },
                },
                scales: {
                    x: {
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#94a3b8', font: { size: 12 } },
                        border: { display: false },
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#94a3b8', font: { size: 12 } },
                        border: { display: false },
                    },
                },
            },
        });
    }

    // ── Data & state ──────────────────────────────────────────────────────
    const riwayat    = window.__STATISTIK__ || [];
    const PAGE_SIZE  = 5;
    let activePeriode = 'semua';
    let currentPage   = 1;

    // ── Klasifikasi config (sama dengan PHP helper) ───────────────────────
    const KLAS_CONFIG = {
        'Rintisan':   { bg: 'bg-red-100',   color: 'text-red-700',   border: 'border-red-200'   },
        'Berkembang': { bg: 'bg-amber-100',  color: 'text-amber-700', border: 'border-amber-200' },
        'Maju':       { bg: 'bg-blue-100',   color: 'text-blue-700',  border: 'border-blue-200'  },
        'Unggul':     { bg: 'bg-green-100',  color: 'text-green-700', border: 'border-green-200' },
    };

    function klasBadge(level) {
        if (!level) return '<span class="text-xs text-gray-400">N/A</span>';
        const c = KLAS_CONFIG[level];
        if (!c) return '<span class="text-xs text-gray-400">' + level + '</span>';
        return '<span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold '
             + c.bg + ' ' + c.color + ' ' + c.border + '">' + level + '</span>';
    }

    // ── Build tabel satu periode ──────────────────────────────────────────
    function buildPeriodeCard(r) {
        const cats = (r.categories || []).map(cat => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3 text-gray-700">
                    <span class="font-medium">${cat.code}.</span> ${cat.name}
                </td>
                <td class="px-5 py-3 text-center font-semibold text-gray-900">${cat.totalScore}</td>
                <td class="px-5 py-3 text-center text-gray-400">${cat.maxScore}</td>
                <td class="px-5 py-3 text-right">${klasBadge(cat.klasifikasi)}</td>
            </tr>
        `).join('');

        return `
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
                    <div>
                        <p class="text-xs text-gray-400">Periode</p>
                        <p class="font-bold text-gray-900 mt-0.5">${r.periode}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <p class="text-xs text-gray-400">Total Skor</p>
                            <p class="font-bold text-gray-900 mt-0.5">
                                ${r.totalScore}
                                <span class="text-sm font-normal text-gray-400 ml-1">/ ${r.maxScore}</span>
                            </p>
                        </div>
                        ${klasBadge(r.statusAkhir)}
                    </div>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs font-semibold text-gray-400">
                            <th class="px-5 py-2.5 text-left">Kategori</th>
                            <th class="px-5 py-2.5 text-center">Skor</th>
                            <th class="px-5 py-2.5 text-center">Maks</th>
                            <th class="px-5 py-2.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">${cats}</tbody>
                </table>
            </div>
        `;
    }

    // ── Render list + pagination ──────────────────────────────────────────
    function render() {
        const filtered = activePeriode === 'semua'
            ? riwayat
            : riwayat.filter(r => r.periode === activePeriode);

        const totalPages  = Math.ceil(filtered.length / PAGE_SIZE);
        const safePage    = Math.min(currentPage, totalPages || 1);
        const paginated   = filtered.slice((safePage - 1) * PAGE_SIZE, safePage * PAGE_SIZE);

        // List
        const listEl = document.getElementById('periodeList');
        listEl.innerHTML = paginated.map(buildPeriodeCard).join('');

        // Pagination
        const paginationEl = document.getElementById('pagination');
        const infoEl       = document.getElementById('paginationInfo');
        const buttonsEl    = document.getElementById('paginationButtons');

        if (totalPages > 1) {
            paginationEl.classList.remove('hidden');
            infoEl.textContent = 'Halaman ' + safePage + ' dari ' + totalPages + ' - ' + filtered.length + ' periode';

            let btns = '';

            // Prev
            btns += `<button onclick="goPage(${safePage - 1})" ${safePage === 1 ? 'disabled' : ''}
                class="flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200
                       text-gray-500 hover:border-sky-300 hover:text-sky-600
                       disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>`;

            // Page numbers
            for (let n = 1; n <= totalPages; n++) {
                const active = n === safePage;
                btns += `<button onclick="goPage(${n})"
                    class="flex items-center justify-center w-8 h-8 rounded-lg border text-xs font-medium transition-colors
                           ${active ? 'bg-sky-600 border-sky-600 text-white'
                                    : 'border-gray-200 text-gray-600 hover:border-sky-300 hover:text-sky-600'}"
                >${n}</button>`;
            }

            // Next
            btns += `<button onclick="goPage(${safePage + 1})" ${safePage === totalPages ? 'disabled' : ''}
                class="flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200
                       text-gray-500 hover:border-sky-300 hover:text-sky-600
                       disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>`;

            buttonsEl.innerHTML = btns;
        } else {
            paginationEl.classList.add('hidden');
        }
    }

    // ── Global handlers ───────────────────────────────────────────────────
    window.filterPeriode = function (periode) {
        activePeriode = periode;
        currentPage   = 1;

        // Update pill styles
        document.querySelectorAll('.pill-btn').forEach(btn => {
            const isActive = btn.dataset.periode === periode;
            btn.className = 'pill-btn rounded-full border px-3 py-1 text-xs font-medium transition-colors '
                + (isActive
                    ? 'bg-sky-600 border-sky-600 text-white'
                    : 'border-gray-200 text-gray-600 hover:border-sky-300 hover:text-sky-600');
        });

        render();
    };

    window.goPage = function (n) {
        currentPage = n;
        render();
        document.getElementById('periodeList').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    // ── Init ──────────────────────────────────────────────────────────────
    render();
});
</script>
@endif

</x-layouts.kecamatan>
