<x-layouts.landing>
    <x-slot name="title">Beranda — {{ config('app.name', 'Klas Berdaya') }}</x-slot>

    {{-- =====================================================================
         Migrasi dari: src/app/(landing)/home/page.tsx
         Data dikirim dari HomeController::index() via compact():
           $banner         = ['slides' => [...]]
           $tentang        = ['heading','description','points','imageUrl']
           $stats          = ['totalRegistered','totalWithData','years','chartData']
           $kabKota        = [['kode','nama'], ...]
           $kecamatanMap   = ['kode_kab' => [['kode','nama'], ...], ...]
    ====================================================================== --}}

    {{-- ① HERO ------------------------------------------------------------ --}}
    <section
        class="sticky top-0 flex items-center justify-center"
        style="min-height:100svh; background-image:url('/hero/hero.jpg'); background-size:cover; background-position:center; background-repeat:no-repeat;"
        aria-label="Hero section"
    >
        {{-- Overlay hitam --}}
        <div class="absolute inset-0 bg-black/55" aria-hidden="true"></div>

        <div class="relative z-10 w-full max-w-6xl px-6 md:px-8 flex flex-col lg:flex-row items-center gap-8 lg:gap-16">

            {{-- Caption kiri --}}
            <div class="flex-1 flex flex-col items-start gap-5 text-white text-center lg:text-left">
                <h1 class="w-full text-3xl sm:text-4xl lg:text-5xl font-normal tracking-tight leading-tight drop-shadow-lg">
                    Klasifikasi<br>Kecamatan Berdaya
                </h1>
                <p class="text-base text-justify lg:text-lg font-normal text-white/80 leading-relaxed">
                    Klas Berdaya (Klasifikasi Kecamatan Berdaya) merupakan sistem penilaian yang digunakan untuk mengukur tingkat perkembangan implementasi Program Kecamatan Berdaya di Provinsi Jawa Tengah.  Penilaian dilakukan melalui self assessment berdasarkan sejumlah indikator ukur.
                </p>
                <a
                    href="#data"
                    class="inline-flex self-center lg:self-start items-center gap-2 rounded-full bg-white/20
                           border border-white/30 px-6 py-2.5 text-sm font-medium text-white
                           backdrop-blur-sm transition-colors hover:bg-white/30"
                    aria-label="Lihat data klasifikasi"
                >
                    Lihat Data Klasifikasi
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>

            {{-- Banner — hanya lg ke atas --}}
            @php $bannerSlides = $banner['slides'] ?? []; @endphp
            <div class="hidden lg:block w-full lg:w-[480px] shrink-0">
                <div
                    class="relative w-full overflow-hidden rounded-2xl shadow-md"
                    id="info-banner"
                    aria-label="Banner informatif"
                    data-autoplay="4000"
                >
                    {{-- Slide area --}}
                    <div class="relative w-full bg-gray-200" style="height:220px;">
                        @forelse ($bannerSlides as $i => $slide)
                            <div
                                class="banner-slide absolute inset-0 transition-opacity duration-500 {{ $i === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' }}"
                                data-index="{{ $i }}"
                            >
                                @if(!empty($slide['imageUrl']))
                                    <img
                                        src="{{ $slide['imageUrl'] }}"
                                        alt="{{ $slide['alt'] ?? '' }}"
                                        class="w-full h-full object-cover"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100 border-2 border-dashed border-gray-300">
                                        <span class="text-sm text-gray-400 select-none">
                                            Banner {{ $i + 1 }} — banner info
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @empty
                            {{-- Tidak ada slide sama sekali: tampilkan placeholder --}}
                            <div class="absolute inset-0 flex items-center justify-center bg-gray-100 border-2 border-dashed border-gray-300">
                                <span class="text-sm text-gray-400 select-none">Banner info</span>
                            </div>
                        @endforelse
                    </div>

                    @if(count($bannerSlides) > 1)
                    {{-- Tombol Prev --}}
                    <button
                        type="button"
                        id="banner-prev"
                        class="absolute left-3 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-black/30 text-white backdrop-blur-sm transition-colors hover:bg-black/50 z-20"
                        aria-label="Slide sebelumnya"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    {{-- Tombol Next --}}
                    <button
                        type="button"
                        id="banner-next"
                        class="absolute right-3 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-black/30 text-white backdrop-blur-sm transition-colors hover:bg-black/50 z-20"
                        aria-label="Slide berikutnya"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    {{-- Dot indicators --}}
                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-20" id="banner-dots">
                        @foreach ($bannerSlides as $i => $slide)
                            <button
                                type="button"
                                class="banner-dot h-1.5 rounded-full transition-all duration-300 {{ $i === 0 ? 'w-5 bg-white' : 'w-1.5 bg-white/60' }}"
                                data-index="{{ $i }}"
                                aria-label="Pergi ke slide {{ $i + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </section>

    {{-- Sections below overlay the sticky hero --}}
    <div class="relative z-10">

    {{-- ② STAT CARDS -------------------------------------------------------- --}}
    <section aria-label="Statistik ringkas" style="background-color:oklch(88.87% 0.0568 219.092)">
        <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                {{-- Kecamatan Terdaftar --}}
                <div class="flex flex-col items-center gap-1.5 rounded-2xl bg-white/70 px-3 py-4 sm:px-6 sm:py-5 text-center shadow-sm backdrop-blur-sm">
                    <div class="flex h-9 w-9 sm:h-11 sm:w-11 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 tabular-nums stat-count" data-target="{{ $stats['totalRegistered'] ?? 0 }}">
                        {{ number_format($stats['totalRegistered'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="text-xs sm:text-sm font-medium text-gray-700 leading-tight">Kecamatan Terdaftar</p>
                    <p class="text-[11px] sm:text-xs text-gray-500 leading-tight">Seluruh Jawa Tengah</p>
                </div>

                {{-- Sudah Dinilai --}}
                <div class="flex flex-col items-center gap-1.5 rounded-2xl bg-white/70 px-3 py-4 sm:px-6 sm:py-5 text-center shadow-sm backdrop-blur-sm">
                    <div class="flex h-9 w-9 sm:h-11 sm:w-11 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 tabular-nums stat-count" data-target="{{ $stats['totalWithData'] ?? 0 }}">
                        {{ number_format($stats['totalWithData'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="text-xs sm:text-sm font-medium text-gray-700 leading-tight">Sudah Dinilai</p>
                    <p class="text-[11px] sm:text-xs text-gray-500 leading-tight">Memiliki data tervalidasi</p>
                </div>

                {{-- Tahun Data Terbaru --}}
                @php
                    $latestYear   = !empty($stats['years']) ? end($stats['years']) : '-';
                    $firstYear    = !empty($stats['years']) ? $stats['years'][0] : null;
                    $yearCount    = !empty($stats['years']) ? count($stats['years']) : 0;
                    $latestChartRow = !empty($stats['chartData']) ? end($stats['chartData']) : null;
                    $majuCount    = $latestChartRow['maju'] ?? 0;
                @endphp
                <div class="flex flex-col items-center gap-1.5 rounded-2xl bg-white/70 px-3 py-4 sm:px-6 sm:py-5 text-center shadow-sm backdrop-blur-sm">
                    <div class="flex h-9 w-9 sm:h-11 sm:w-11 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 tabular-nums">{{ $latestYear }}</p>
                    <p class="text-xs sm:text-sm font-medium text-gray-700 leading-tight">Tahun Data Terbaru</p>
                    @if($yearCount > 1 && $firstYear)
                        <p class="text-[11px] sm:text-xs text-gray-500 leading-tight">Dari {{ $firstYear }} s.d. {{ $latestYear }}</p>
                    @endif
                </div>

                {{-- Kecamatan Maju --}}
                <div class="flex flex-col items-center gap-1.5 rounded-2xl bg-white/70 px-3 py-4 sm:px-6 sm:py-5 text-center shadow-sm backdrop-blur-sm">
                    <div class="flex h-9 w-9 sm:h-11 sm:w-11 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 tabular-nums stat-count" data-target="{{ $majuCount }}">
                        {{ number_format($majuCount, 0, ',', '.') }}
                    </p>
                    <p class="text-xs sm:text-sm font-medium text-gray-700 leading-tight">Kecamatan Maju</p>
                    @if($latestYear !== '-')
                        <p class="text-[11px] sm:text-xs text-gray-500 leading-tight">Per tahun {{ $latestYear }}</p>
                    @endif
                </div>

            </div>
        </div>
    </section>

    {{-- ③ TENTANG KLASIFIKASI BERDAYA --------------------------------------- --}}
    <section aria-labelledby="tentang-heading" style="background-color:oklch(88.87% 0.0568 219.092)">
        <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 pb-20">
            <div class="w-full h-px bg-sky-200/60 mb-16"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div class="flex flex-col gap-5">
                    <div class="inline-flex w-fit items-center rounded-full bg-white/60 px-3 py-1 text-xs font-semibold text-sky-800 ring-1 ring-sky-200">
                        Tentang Platform
                    </div>
                    <h2 id="tentang-heading" class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900">
                        {{ $tentang['heading'] ?? 'Apa itu Klasifikasi Berdaya?' }}
                    </h2>
                    <p class="text-sm md:text-base text-justify leading-relaxed text-gray-700">
                        {{ $tentang['description'] ?? '' }}
                    </p>
                    @if(!empty($tentang['points']))
                    <ul class="flex flex-col gap-3 text-sm text-gray-700">
                        @foreach ($tentang['points'] as $point)
                        <li class="flex items-start gap-2.5">
                            <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-sky-500 text-white text-[10px] font-bold">✓</span>
                            <span>{{ $point }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden border-2 border-dashed border-sky-300/60 bg-white/40">
                    @if(!empty($tentang['imageUrl']))
                        <img
                            src="{{ $tentang['imageUrl'] }}"
                            alt="Ilustrasi Tentang Platform"
                            class="w-full h-full object-cover rounded-2xl"
                        >
                    @else
                        <div class="flex flex-col items-center justify-center gap-3 w-full h-full">
                            <svg class="w-16 h-16 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/>
                                <circle cx="8.5" cy="8.5" r="1.5" stroke-width="1.5"/>
                                <path d="M21 15l-5-5L5 21" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="text-sm text-sky-400 font-medium">Gambar</span>
                            <span class="text-xs text-sky-400">Rasio 4 : 3</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- wave divider --}}
    <div style="background-color:oklch(88.87% 0.0568 219.092)" class="overflow-hidden leading-none -mb-1">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" class="w-full h-12 block" aria-hidden="true">
            <path d="M0,0 C720,80 720,80 1440,0 L1440,60 L0,60 Z" fill="#e2e8f0"/>
        </svg>
    </div>

    {{-- ④ 4 LEVEL KLASIFIKASI ----------------------------------------------- --}}
    <section aria-labelledby="level-heading" class="bg-slate-200">
        <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-20">
            <div class="text-center mb-10">
                <div class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700 mb-3">
                    Sistem Penilaian
                </div>
                <h2 id="level-heading" class="text-3xl md:text-4xl font-bold text-gray-900">4 Level Klasifikasi</h2>
                <p class="mt-3 text-sm md:text-base text-gray-600 max-w-xl mx-auto">
                    Setiap kecamatan diklasifikasikan ke dalam salah satu dari empat tingkat
                    berdasarkan total skor penilaian mandiri.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                $levels = [
                    ['level'=>'Rintisan',  'range'=>'Skor ≤ 14,41', 'accent'=>'bg-red-400',   'desc'=>'Mulai menunjukkan inisiatif pemberdayaan namun masih memerlukan pendampingan intensif.'],
                    ['level'=>'Berkembang','range'=>'Skor ≤ 29,13', 'accent'=>'bg-amber-400', 'desc'=>'Telah memiliki sistem pemberdayaan dasar dan mulai menjalankan program secara konsisten.'],
                    ['level'=>'Maju',      'range'=>'Skor ≤ 43,23', 'accent'=>'bg-blue-400',  'desc'=>'Telah memiliki sistem dan program pemberdayaan yang berjalan dengan baik dan terstruktur.'],
                    ['level'=>'Unggul',    'range'=>'Skor ≥ 43,24', 'accent'=>'bg-green-500', 'desc'=>'Kecamatan dengan kapasitas pemberdayaan optimal dan menjadi rujukan daerah sekitarnya.'],
                ];
                @endphp
                @foreach ($levels as $item)
                <div class="flex gap-4 rounded-2xl bg-white p-5 shadow-sm">
                    <div class="w-1 shrink-0 rounded-full {{ $item['accent'] }}" aria-hidden="true"></div>
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $item['level'] }}</p>
                        <p class="text-xs text-gray-400">{{ $item['range'] }}</p>
                        <p class="mt-1 text-xs text-gray-600 leading-relaxed">{{ $item['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- wave divider --}}
    <div class="bg-slate-200 overflow-hidden leading-none -mb-1">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" class="w-full h-12 block" aria-hidden="true">
            <path d="M0,60 C720,-20 720,-20 1440,60 L1440,60 L0,60 Z" fill="#ffffff"/>
        </svg>
    </div>

    {{-- ⑤ DATA KLASIFIKASI -------------------------------------------------- --}}
    <section id="data" aria-labelledby="data-heading" class="bg-white">
        <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-12">
            <div class="text-center mb-6">
                <div class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700 mb-3">
                    Data Publik
                </div>
                <h2 id="data-heading" class="text-3xl md:text-4xl font-bold text-gray-900">
                    Data Klasifikasi Kecamatan
                </h2>
                <p class="mt-3 text-sm md:text-base text-gray-600 max-w-xl mx-auto">
                    Cek hasil klasifikasi kotamu!
                </p>
            </div>

            {{-- Filter wilayah --}}
            <div class="rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm mb-3">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label for="filter-kabkota" class="block text-xs font-medium text-gray-500 mb-1">Kabupaten/Kota</label>
                        <select
                            id="filter-kabkota"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700
                                   focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20"
                            onchange="filterKecamatan(this.value)"
                        >
                            <option value="">Seluruh Jawa Tengah</option>
                            @foreach ($kabKota as $kab)
                                <option value="{{ $kab['kode'] }}">{{ $kab['nama'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="filter-kecamatan" class="block text-xs font-medium text-gray-500 mb-1">Kecamatan</label>
                        <select
                            id="filter-kecamatan"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700
                                   focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20"
                            disabled
                            onchange="loadKecamatanChart(document.getElementById('filter-kabkota').value, this.value)"
                        >
                            <option value="">Pilih kecamatan...</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Peta + Chart: satu card, 2 kolom --}}
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-100 items-stretch">

                    {{-- Peta SVG Jawa Tengah --}}
                    <div class="p-3 min-h-[280px] sm:min-h-[340px] lg:min-h-0 relative">
                        <svg id="jateng-map" viewBox="{{ $mapViewBox ?? '0 0 1000 600' }}" class="w-full h-[300px] md:h-[360px]" role="img" aria-label="Peta kabupaten/kota Jawa Tengah">
                            @foreach ($kabKotaShapes as $shape)
                                <path
                                    d="{{ $shape['d'] }}"
                                    data-kode="{{ $shape['kode'] }}"
                                    data-nama="{{ $shape['nama'] }}"
                                    fill="rgb(148 163 184 / 0.35)"
                                    stroke="rgb(255 255 255)"
                                    stroke-width="1.2"
                                    class="cursor-pointer hover:fill-sky-200/70 transition-colors map-region"
                                    onclick="selectKabKota('{{ $shape['kode'] }}')"
                                >
                                    <title>{{ $shape['nama'] }}</title>
                                </path>
                            @endforeach
                        </svg>
                    </div>

                    {{-- Chart / distribusi data --}}
                    <div class="p-4 min-h-[260px] lg:min-h-0 flex flex-col justify-center">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1" id="chart-mode-label">Distribusi Klasifikasi</p>
                        <p class="text-sm font-semibold text-gray-800 mb-4" id="chart-area-label">Jawa Tengah</p>

                    {{-- Chart container --}}
                    <div id="chart-container" class="relative">
                        @if(!empty($stats['chartData']))
                            @php
                                $chartDataJson = json_encode(array_values($stats['chartData']));
                            @endphp
                            {{-- Canvas untuk Chart.js — diisi JS setelah DOMContentLoaded --}}
                            <canvas id="distribusi-canvas" height="160" style="max-height:160px"></canvas>
                            {{-- Legend --}}
                            <div class="flex flex-wrap justify-center gap-x-3 gap-y-1 mt-2" id="chart-legend">
                                <span class="flex items-center gap-1.5 text-[11px] text-gray-400"><span class="h-2 w-2 rounded-full shrink-0 bg-red-400"></span>Rintisan</span>
                                <span class="flex items-center gap-1.5 text-[11px] text-gray-400"><span class="h-2 w-2 rounded-full shrink-0 bg-amber-400"></span>Berkembang</span>
                                <span class="flex items-center gap-1.5 text-[11px] text-gray-400"><span class="h-2 w-2 rounded-full shrink-0 bg-blue-400"></span>Maju</span>
                                <span class="flex items-center gap-1.5 text-[11px] text-gray-400"><span class="h-2 w-2 rounded-full shrink-0 bg-green-500"></span>Unggul</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-2" id="chart-meta">
                                Data tahun {{ $latestYear }}
                            </p>
                            <script>
                                window.__INITIAL_CHART_DATA__ = {!! $chartDataJson !!};
                            </script>
                        @else
                            <div class="flex flex-col items-center justify-center gap-1.5 py-10 rounded-xl bg-gray-50">
                                <span class="text-sm font-medium text-gray-500">Belum ada data</span>
                                <span class="text-xs text-gray-400">Belum ada data tervalidasi.</span>
                            </div>
                        @endif
                    </div>{{-- /chart-container --}}
                </div>{{-- /chart column --}}

                </div>{{-- /grid --}}
            </div>

        </div>
    </section>

    </div>{{-- /sticky overlay wrapper --}}

    @push('scripts')
    {{-- Chart.js via CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    {{-- Data wilayah untuk filter JS --}}
    <script>
    var KECAMATAN_MAP = @json($kecamatanMap ?? []);
    var STATS_CHART   = @json($stats['chartData'] ?? []);

    // Warna konsisten dengan NextJS LandingDistribusiChart
    var LEVEL_COLORS = {
        rintisan:   '#f87171',
        berkembang: '#fbbf24',
        maju:       '#60a5fa',
        unggul:     '#4ade80',
    };
    var STATUS_COLOR = {
        'Rintisan':   '#f87171',
        'Berkembang': '#fbbf24',
        'Maju':       '#60a5fa',
        'Unggul':     '#4ade80',
    };

    // Referensi instance Chart.js aktif
    var __chartInstance = null;

    // ── Inisialisasi chart awal (dari data server) ─────────────────────────
    // Script berada di akhir body sehingga DOM sudah siap — tidak perlu
    // menunggu DOMContentLoaded. Langsung inisialisasi setelah fungsi
    // renderDistribusiChart terdefinisi (lihat bagian bawah blok ini).
    

    // ── Highlight peta ─────────────────────────────────────────────────────
    function highlightMap(kabKode) {
        document.querySelectorAll('#jateng-map .map-region').forEach(function(path) {
            if (kabKode && path.dataset.kode === kabKode) {
                path.setAttribute('fill', 'rgb(14 165 233 / 0.35)');
                path.setAttribute('stroke', 'rgb(2 132 199)');
            } else {
                path.setAttribute('fill', 'rgb(148 163 184 / 0.35)');
                path.setAttribute('stroke', 'rgb(255 255 255)');
            }
        });
    }

    // ── Dipanggil saat klik peta ───────────────────────────────────────────
    function selectKabKota(kode) {
        var sel = document.getElementById('filter-kabkota');
        sel.value = kode;
        filterKecamatan(kode);
    }

    // ── Filter kecamatan dropdown ──────────────────────────────────────────
    function filterKecamatan(kabKode) {
        var sel = document.getElementById('filter-kecamatan');
        sel.innerHTML = '<option value="">Semua kecamatan</option>';
        sel.disabled = !kabKode;

        highlightMap(kabKode);

        if (!kabKode) {
            renderDistribusiChart(STATS_CHART, 'Jawa Tengah');
            document.getElementById('chart-mode-label').textContent = 'Distribusi Klasifikasi';
            return;
        }

        var list = KECAMATAN_MAP[kabKode] || [];
        list.forEach(function(k) {
            var opt = document.createElement('option');
            opt.value = k.kode;
            opt.textContent = k.nama;
            sel.appendChild(opt);
        });

        fetchChart({ kabKotaKode: kabKode }, function(data) {
            var kabNama = document.querySelector('#filter-kabkota option[value="' + kabKode + '"]')?.textContent || kabKode;
            renderDistribusiChart(data.chartData || [], data.label || kabNama);
        });
    }

    // ── Load chart untuk kecamatan spesifik ───────────────────────────────
    function loadKecamatanChart(kabKode, kecKode) {
        if (!kecKode) {
            filterKecamatan(kabKode);
            return;
        }
        document.getElementById('chart-mode-label').textContent = 'Hasil Klasifikasi';
        fetchChart({ kabKotaKode: kabKode, kecamatanKode: kecKode }, function(data) {
            renderKecamatanChart(data.skorPerTahun || [], data.label || kecKode);
        });
    }

    // ── Fetch data dari API publik ─────────────────────────────────────────
    function fetchChart(params, cb) {
        var qs = new URLSearchParams(params).toString();
        showLoading();
        fetch('/public/klasifikasi?' + qs)
            .then(function(r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function(json) { cb(json.data || {}); })
            .catch(function() { showError(); });
    }

    // ── Destroy chart lama sebelum render baru ─────────────────────────────
    function destroyChart() {
        if (__chartInstance) {
            __chartInstance.destroy();
            __chartInstance = null;
        }
    }

    // ── Render distribusi (grouped bar per tahun) ─────────────────────────
    // Identik dengan LandingDistribusiChart.tsx di NextJS
    function renderDistribusiChart(rows, label, updateLabel) {
        if (updateLabel !== false) {
            document.getElementById('chart-area-label').textContent = label || 'Jawa Tengah';
            document.getElementById('chart-mode-label').textContent = 'Distribusi Klasifikasi';
        }

        if (!rows || rows.length === 0) {
            showEmpty();
            return;
        }

        // Hitung total kecamatan dari baris terakhir
        var last = rows[rows.length - 1];
        var total = (last.rintisan||0) + (last.berkembang||0) + (last.maju||0) + (last.unggul||0);
        var year  = last.year || '';

        // Update meta info
        var metaEl = document.getElementById('chart-meta');
        if (metaEl) metaEl.textContent = total + ' kecamatan · ' + rows.length + ' tahun data';

        // Siapkan container chart
        showCanvas('distribusi-canvas');

        destroyChart();
        var ctx = document.getElementById('distribusi-canvas').getContext('2d');

        __chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: rows.map(function(r) { return r.year; }),
                datasets: [
                    {
                        label: 'Rintisan',
                        data: rows.map(function(r) { return r.rintisan || 0; }),
                        backgroundColor: LEVEL_COLORS.rintisan,
                        borderRadius: 3,
                        borderSkipped: false,
                    },
                    {
                        label: 'Berkembang',
                        data: rows.map(function(r) { return r.berkembang || 0; }),
                        backgroundColor: LEVEL_COLORS.berkembang,
                        borderRadius: 3,
                        borderSkipped: false,
                    },
                    {
                        label: 'Maju',
                        data: rows.map(function(r) { return r.maju || 0; }),
                        backgroundColor: LEVEL_COLORS.maju,
                        borderRadius: 3,
                        borderSkipped: false,
                    },
                    {
                        label: 'Unggul',
                        data: rows.map(function(r) { return r.unggul || 0; }),
                        backgroundColor: LEVEL_COLORS.unggul,
                        borderRadius: 3,
                        borderSkipped: false,
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
                            title: function(items) { return 'Tahun ' + items[0].label; },
                            label: function(item) {
                                return ' ' + item.dataset.label + ': ' + item.parsed.y + ' kecamatan';
                            },
                        },
                        backgroundColor: '#fff',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        titleColor: '#374151',
                        bodyColor: '#6b7280',
                        padding: 8,
                        cornerRadius: 8,
                        boxPadding: 4,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#9ca3af', font: { size: 11 } },
                    },
                    y: {
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        border: { display: false },
                        ticks: { color: '#9ca3af', font: { size: 11 }, precision: 0 },
                    },
                },
            },
        });

        showLegend('distribusi');
    }

    // ── Render kecamatan spesifik (bar % skor per tahun) ─────────────────
    // Identik dengan LandingKecamatanChart.tsx di NextJS
    function renderKecamatanChart(rows, label) {
        document.getElementById('chart-area-label').textContent = label || '';

        if (!rows || rows.length === 0) {
            showEmpty('Kecamatan ini belum memiliki data tervalidasi.');
            return;
        }

        var latest  = rows[rows.length - 1];
        var latestColor = STATUS_COLOR[latest.statusAkhir] || '#94a3b8';

        // Update meta: badge status terbaru
        var metaEl = document.getElementById('chart-meta');
        if (metaEl) {
            metaEl.innerHTML = '<span style="display:inline-block;background:' + latestColor + ';color:#fff;'
                + 'border-radius:9999px;padding:0 8px;font-size:11px;font-weight:600;margin-right:6px;">'
                + (latest.statusAkhir || 'Belum terklasifikasi')
                + '</span>'
                + '<span style="color:#9ca3af;font-size:11px;">' + latest.year + '</span>';
        }

        // Siapkan data bar: persentase per tahun
        var barData = rows.map(function(r) {
            return {
                year:   r.year,
                pct:    r.maxWeightedScore > 0 ? Math.round(r.weightedScore / r.maxWeightedScore * 100) : 0,
                status: r.statusAkhir,
                color:  STATUS_COLOR[r.statusAkhir] || '#94a3b8',
            };
        });

        showCanvas('distribusi-canvas');
        destroyChart();
        var ctx = document.getElementById('distribusi-canvas').getContext('2d');

        __chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: barData.map(function(r) { return r.year; }),
                datasets: [{
                    label: 'Skor',
                    data:  barData.map(function(r) { return r.pct; }),
                    backgroundColor: barData.map(function(r) { return r.color; }),
                    borderRadius: 3,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(items) { return 'Tahun ' + items[0].label; },
                            label: function(item) {
                                var row = barData[item.dataIndex];
                                return [
                                    ' Status: ' + (row.status || 'Belum terklasifikasi'),
                                    ' Skor: ' + item.parsed.y + '%',
                                ];
                            },
                        },
                        backgroundColor: '#fff',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        titleColor: '#374151',
                        bodyColor: '#6b7280',
                        padding: 8,
                        cornerRadius: 8,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#9ca3af', font: { size: 11 } },
                    },
                    y: {
                        min: 0, max: 100,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        border: { display: false },
                        ticks: {
                            color: '#9ca3af',
                            font: { size: 11 },
                            callback: function(v) { return v + '%'; },
                        },
                    },
                },
            },
        });

        showLegend('kecamatan');
    }

    // ── Helpers tampilan ──────────────────────────────────────────────────
    function showCanvas(id) {
        var container = document.getElementById('chart-container');
        if (!container) return;
        // Pastikan canvas ada
        var canvas = document.getElementById(id);
        if (!canvas) {
            container.innerHTML =
                '<canvas id="' + id + '" height="160" style="max-height:160px"></canvas>'
                + '<div class="flex flex-wrap justify-center gap-x-3 gap-y-1 mt-2" id="chart-legend"></div>'
                + '<p class="text-xs text-gray-400 mt-2" id="chart-meta"></p>';
        }
    }

    function showLoading() {
        destroyChart();
        var container = document.getElementById('chart-container');
        if (container) container.innerHTML =
            '<div class="flex flex-col gap-3 animate-pulse py-4">'
            + '<div class="h-40 rounded-lg bg-gray-100 w-full"></div>'
            + '<div class="h-3 w-40 rounded bg-gray-100 mt-1"></div>'
            + '</div>';
    }

    function showError() {
        destroyChart();
        var container = document.getElementById('chart-container');
        if (container) container.innerHTML =
            '<div class="flex flex-col items-center justify-center gap-1.5 py-10 rounded-xl bg-red-50">'
            + '<span class="text-sm font-medium text-red-500">Gagal memuat data</span>'
            + '<span class="text-xs text-red-400">Coba pilih wilayah lain</span>'
            + '</div>';
    }

    function showEmpty(msg) {
        destroyChart();
        var container = document.getElementById('chart-container');
        if (container) container.innerHTML =
            '<div class="flex flex-col items-center justify-center gap-1.5 py-10 rounded-xl bg-gray-50">'
            + '<span class="text-sm font-medium text-gray-500">Belum ada data</span>'
            + '<span class="text-xs text-gray-400">' + (msg || 'Wilayah ini belum memiliki data tervalidasi.') + '</span>'
            + '</div>';
    }

    function showLegend(mode) {
        var legendEl = document.getElementById('chart-legend');
        if (!legendEl) return;
        if (mode === 'distribusi') {
            legendEl.innerHTML = [
                ['#f87171','Rintisan'],
                ['#fbbf24','Berkembang'],
                ['#60a5fa','Maju'],
                ['#4ade80','Unggul'],
            ].map(function(l) {
                return '<span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#9ca3af;">'
                    + '<span style="width:8px;height:8px;border-radius:50%;background:' + l[0] + ';flex-shrink:0;"></span>'
                    + l[1] + '</span>';
            }).join('');
        } else {
            legendEl.innerHTML = Object.entries(STATUS_COLOR).map(function(e) {
                return '<span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#9ca3af;">'
                    + '<span style="width:8px;height:8px;border-radius:50%;background:' + e[1] + ';flex-shrink:0;"></span>'
                    + e[0] + '</span>';
            }).join('');
        }
    }

    // ── InfoBanner slider ─────────────────────────────────────────────────
    // Migrasi dari: src/components/shared/ui/InfoBanner.tsx
    (function () {
        var banner   = document.getElementById('info-banner');
        if (!banner) return;

        var slides   = banner.querySelectorAll('.banner-slide');
        var dots     = banner.querySelectorAll('.banner-dot');
        var prevBtn  = document.getElementById('banner-prev');
        var nextBtn  = document.getElementById('banner-next');
        var total    = slides.length;
        if (total <= 1) return;

        var current  = 0;
        var paused   = false;
        var interval = parseInt(banner.dataset.autoplay || '4000', 10);
        var timer    = null;

        function goTo(idx) {
            slides[current].classList.remove('opacity-100', 'z-10');
            slides[current].classList.add('opacity-0', 'z-0');
            if (dots[current]) {
                dots[current].classList.remove('w-5', 'bg-white');
                dots[current].classList.add('w-1.5', 'bg-white/60');
            }
            current = (idx + total) % total;
            slides[current].classList.remove('opacity-0', 'z-0');
            slides[current].classList.add('opacity-100', 'z-10');
            if (dots[current]) {
                dots[current].classList.remove('w-1.5', 'bg-white/60');
                dots[current].classList.add('w-5', 'bg-white');
            }
        }

        function startTimer() {
            timer = setInterval(function () {
                if (!paused) goTo(current + 1);
            }, interval);
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () { goTo(parseInt(dot.dataset.index, 10)); });
        });

        banner.addEventListener('mouseenter', function () { paused = true; });
        banner.addEventListener('mouseleave', function () { paused = false; });

        startTimer();
    })();

    // ── Inisialisasi chart awal ────────────────────────────────────────────
    // Script ini berada di akhir <body> (via @stack('scripts')), DOM sudah
    // siap dan Chart.js sudah dimuat — langsung render tanpa menunggu event.
    (function () {
        var initial = STATS_CHART;
        if (initial && initial.length > 0) {
            renderDistribusiChart(initial, 'Jawa Tengah', false);
        }
    })();
    </script>
    @endpush

</x-layouts.landing>
