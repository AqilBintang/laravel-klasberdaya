{{--
    components/layouts/kecamatan.blade.php
    Anonymous Blade component — dipanggil sebagai <x-layouts.kecamatan pageTitle="...">

    Props:
      • $pageTitle — judul halaman (opsional)
    Slot:
      • $slot — konten halaman utama
--}}
@props(['pageTitle' => 'Dashboard'])

@php
    use Illuminate\Support\Facades\Request;

    $user = auth()->user();
    $path = Request::path();
    $is   = fn(string $prefix) => str_starts_with($path, ltrim($prefix, '/'));

    $navItems = [
        ['label' => 'Dashboard',     'route' => 'kecamatan.dashboard',        'prefix' => 'kecamatan/dashboard',   'icon' => 'gauge'],
        ['label' => 'Panduan',       'route' => 'kecamatan.panduan',          'prefix' => 'kecamatan/panduan',     'icon' => 'book'],
        ['label' => 'Isi Assessment','route' => 'kecamatan.assessment.index', 'prefix' => 'kecamatan/assessment',  'icon' => 'clipboard'],
        ['label' => 'Hasil Nilai',   'route' => 'kecamatan.hasil',            'prefix' => 'kecamatan/hasil',       'icon' => 'chart'],
        ['label' => 'Statistik',     'route' => 'kecamatan.statistik',        'prefix' => 'kecamatan/statistik',   'icon' => 'trending'],
        ['label' => 'Laporan',       'route' => 'kecamatan.laporan',          'prefix' => 'kecamatan/laporan',     'icon' => 'spreadsheet'],
    ];
@endphp

<x-dashboard
    sidebarBg="bg-slate-900"
    pageTitle="{{ config('app.name') }}"
>

    <x-slot:sidebar>
        <div class="flex h-full flex-col bg-slate-900 text-slate-100">

            {{-- Brand --}}
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-700/60 shrink-0">
                <img src="/logo/logo-kota.png" alt="Logo Kota" class="h-8 w-auto object-contain">
                <div class="flex flex-col leading-tight">
                    <span class="font-bold text-sm text-white">Klasifikasi Berdaya</span>
                    <span class="text-xs text-slate-400">Portal Kecamatan</span>
                </div>
            </div>

            {{-- Nav --}}
            <nav aria-label="Navigasi Kecamatan" class="flex-1 overflow-y-auto px-3 py-4">
                <ul class="flex flex-col gap-0.5" role="list">
                    @foreach ($navItems as $item)
                        @php $active = $is($item['prefix']); @endphp
                        <li>
                            <a href="{{ route($item['route']) }}"
                               @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                       'bg-sky-500/20 text-sky-300' => $active,
                                       'text-slate-400 hover:bg-slate-800 hover:text-slate-100' => !$active])>

                                @if ($item['icon'] === 'gauge')
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                @elseif ($item['icon'] === 'book')
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                @elseif ($item['icon'] === 'clipboard')
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                @elseif ($item['icon'] === 'chart')
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @elseif ($item['icon'] === 'trending')
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                @elseif ($item['icon'] === 'spreadsheet')
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                @endif

                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Logout --}}
            <div class="px-3 py-4 border-t border-slate-700/60 shrink-0">
                <form method="POST" action="{{ route('kecamatan.logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-slate-100 transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>

        </div>
    </x-slot:sidebar>

    <x-slot:header>
        <h1 class="text-base font-semibold text-gray-800 truncate">{{ $pageTitle }}</h1>
    </x-slot:header>

    {{ $slot }}

</x-dashboard>
