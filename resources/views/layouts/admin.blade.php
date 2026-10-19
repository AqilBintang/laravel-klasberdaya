{{--
    layouts/admin.blade.php
    Layout dashboard untuk role ADMIN dan SUPER_ADMIN.
    Sidebar bg-gray-900 dengan menu lengkap admin.
    Mendukung accordion "Assessment" dan section "Super Admin".
--}}
@php
    use Illuminate\Support\Facades\Request;

    $user   = auth()->user();
    $isSA   = $user?->isSuperAdmin();
    $path   = Request::path();          // misal: admin/assessment/create

    // Helper: apakah path mulai dengan prefix tertentu
    $is = fn(string $prefix) => str_starts_with($path, ltrim($prefix, '/'));

    // Accordion Assessment aktif bila salah satu sub-menu aktif
    $assessmentActive = $is('admin/assessment');
@endphp

<x-dashboard
    sidebarBg="bg-gray-900"
    pageTitle="{{ config('app.name') }}"
>

    {{-- ─ SIDEBAR SLOT ──────────────────────────────────────────── --}}
    <x-slot:sidebar>
        <div class="flex h-full flex-col bg-gray-900 text-gray-100">

            {{-- Brand --}}
            <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-700/60 shrink-0">
                <img src="/logo/logo-kota.png" alt="Logo Kota" class="h-8 w-auto object-contain">
                <div class="flex flex-col leading-tight">
                    <span class="font-bold text-sm text-white">Klasifikasi Berdaya</span>
                    <span class="text-xs text-gray-400">Admin Panel</span>
                </div>
            </div>

            {{-- Nav --}}
            <nav aria-label="Admin Navigasi" class="flex-1 overflow-y-auto px-3 py-4">
                <ul class="flex flex-col gap-0.5" role="list">

                    {{-- Dashboard --}}
                    <li>
                        <a href="{{ route('admin.dashboard') }}"
                           @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                   'bg-sky-500/20 text-sky-300' => $path === 'admin/dashboard',
                                   'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => $path !== 'admin/dashboard'])>
                            {{-- gauge icon --}}
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    {{-- Landing Page --}}
                    <li>
                        <a href="{{ route('admin.landing-page.index') }}"
                           @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                   'bg-sky-500/20 text-sky-300' => $is('admin/landing-page'),
                                   'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('admin/landing-page')])>
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                            </svg>
                            <span>Landing Page</span>
                        </a>
                    </li>

                    {{-- Panduan --}}
                    <li>
                        <a href="{{ route('admin.panduan.index') }}"
                           @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                   'bg-sky-500/20 text-sky-300' => $is('admin/panduan'),
                                   'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('admin/panduan')])>
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <span>Panduan Rubrik</span>
                        </a>
                    </li>

                    {{-- Assessment (accordion) --}}
                    <li x-data="{ open: {{ $assessmentActive ? 'true' : 'false' }} }">
                        <button
                            type="button"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            @class(['flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                    'bg-sky-500/10 text-sky-300' => $assessmentActive,
                                    'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$assessmentActive])
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span class="flex-1 text-left">Assessment</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <ul x-show="open" x-transition class="mt-1 ml-7 flex flex-col gap-0.5 border-l border-gray-700/60 pl-3">
                            <li>
                                <a href="{{ route('admin.assessment.create') }}"
                                   @class(['flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm transition-colors',
                                           'text-sky-300 font-medium' => $is('admin/assessment/create') || $path === 'admin/assessment',
                                           'text-gray-400 hover:text-gray-100' => !$is('admin/assessment/create') && $path !== 'admin/assessment'])>
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Create Assessment
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.assessment.results.index') }}"
                                   @class(['flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm transition-colors',
                                           'text-sky-300 font-medium' => $is('admin/assessment/results'),
                                           'text-gray-400 hover:text-gray-100' => !$is('admin/assessment/results')])>
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                    Rekapitulasi Assessment
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Klasifikasi Berdaya --}}
                    <li>
                        <a href="{{ route('admin.klasifikasi-berdaya') }}"
                           @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                   'bg-sky-500/20 text-sky-300' => $is('admin/klasifikasi-berdaya'),
                                   'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('admin/klasifikasi-berdaya')])>
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Klasifikasi Berdaya</span>
                        </a>
                    </li>

                    {{-- Export Laporan --}}
                    <li>
                        <a href="{{ route('admin.export.assessment') }}"
                           @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                   'bg-sky-500/20 text-sky-300' => $is('admin/export'),
                                   'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('admin/export')])>
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Export Laporan</span>
                        </a>
                    </li>

                    {{-- Manage User --}}
                    <li>
                        <a href="{{ route('admin.users.index') }}"
                           @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                   'bg-sky-500/20 text-sky-300' => $is('admin/users'),
                                   'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('admin/users')])>
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span>Manage User</span>
                        </a>
                    </li>

                    {{-- ── SUPER ADMIN SECTION ────────────────────────── --}}
                    @if ($isSA)
                        <li class="pt-3">
                            <div class="flex items-center gap-2 px-3 pb-1">
                                <div class="h-px flex-1 bg-gray-700/60"></div>
                                <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-500">Super Admin</span>
                                <div class="h-px flex-1 bg-gray-700/60"></div>
                            </div>
                        </li>

                        {{-- Manajemen Admin --}}
                        <li>
                            <a href="{{ route('admin.manage-admin.index') }}"
                               @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                       'bg-red-500/20 text-red-300' => $is('admin/manage-admin'),
                                       'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('admin/manage-admin')])>
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Manajemen Admin</span>
                            </a>
                        </li>

                        {{-- Manajemen Kecamatan --}}
                        <li>
                            <a href="{{ route('admin.manage-kecamatan') }}"
                               @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                       'bg-red-500/20 text-red-300' => $is('admin/manage-kecamatan'),
                                       'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('admin/manage-kecamatan')])>
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Manajemen Kecamatan</span>
                            </a>
                        </li>

                        {{-- Audit Log --}}
                        <li>
                            <a href="{{ route('admin.audit-log') }}"
                               @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                       'bg-red-500/20 text-red-300' => $is('admin/audit-log'),
                                       'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('admin/audit-log')])>
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Audit Log</span>
                            </a>
                        </li>

                        {{-- Backup --}}
                        <li>
                            <a href="{{ route('admin.backup.index') }}"
                               @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                       'bg-red-500/20 text-red-300' => $is('admin/backup'),
                                       'text-gray-400 hover:bg-gray-800 hover:text-gray-100' => !$is('admin/backup')])>
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                                </svg>
                                <span>Backup Data</span>
                            </a>
                        </li>
                    @endif

                </ul>
            </nav>

            {{-- Logout --}}
            <div class="px-3 py-4 border-t border-gray-700/60 shrink-0">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-gray-100 transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>

        </div>
    </x-slot:sidebar>

    {{-- ─ HEADER SLOT ────────────────────────────────────────────── --}}
    <x-slot:header>
        <h1 class="text-base font-semibold text-gray-800 truncate">
            {{ $pageTitle ?? 'Dashboard' }}
        </h1>
    </x-slot:header>

    {{-- ─ MAIN CONTENT (dari child view) ──────────────────────────── --}}
    {{ $slot }}

</x-dashboard>
