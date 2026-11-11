{{--
    components/layouts/kabupaten.blade.php
    Anonymous Blade component — dipanggil sebagai <x-layouts.kabupaten pageTitle="...">

    Props:
      • $pageTitle — judul halaman (opsional)
    Slot:
      • $slot — konten halaman utama
--}}
@props(['pageTitle' => 'Dashboard'])

@php
    use Illuminate\Support\Facades\Request;

    $path = Request::path();
    $is   = fn(string $prefix) => str_starts_with($path, ltrim($prefix, '/'));
@endphp

<x-dashboard
    sidebarBg="bg-gray-900"
    pageTitle="{{ config('app.name') }}"
>

    <x-slot:sidebar>
        <div class="flex h-full flex-col bg-gray-900 text-gray-100">

            {{-- Brand --}}
            <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-700/60 shrink-0">
                <img src="/logo/logo-kota.png" alt="Logo" class="h-8 w-auto object-contain">
                <div class="flex flex-col leading-tight">
                    <span class="font-bold text-sm text-white">Klasifikasi Berdaya</span>
                    <span class="text-xs text-gray-400">Kabupaten / Kota</span>
                </div>
            </div>

            {{-- Nav --}}
            <nav aria-label="Navigasi Kabupaten" class="flex-1 overflow-y-auto px-3 py-4">
                <ul class="flex flex-col gap-0.5" role="list">

                    {{-- Dashboard --}}
                    <li>
                        <a href="{{ route('kabupaten.dashboard') }}"
                           @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                   'bg-sky-500/20 text-sky-300' => $is('kabupaten/dashboard') || $path === 'kabupaten',
                                   'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !($is('kabupaten/dashboard') || $path === 'kabupaten')])>
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    {{-- Review Assessment --}}
                    <li>
                        <a href="{{ route('kabupaten.review.index') }}"
                           @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                   'bg-sky-500/20 text-sky-300' => $is('kabupaten/review'),
                                   'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('kabupaten/review')])>
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span>Review Assessment</span>
                        </a>
                    </li>

                </ul>
            </nav>

            {{-- Logout --}}
            <div class="px-3 py-4 border-t border-gray-700/60 shrink-0">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-gray-100 transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Logout</span>
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
