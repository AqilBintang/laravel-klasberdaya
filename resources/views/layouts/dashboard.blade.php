{{--
    layouts/dashboard.blade.php
    Layout utama dashboard — dipakai oleh semua role (admin, kecamatan, validator).
    Slot yang tersedia:
      • $sidebar   — konten sidebar (dikirim dari layout role masing-masing)
      • $header    — judul halaman di header atas
      • $slot      — konten halaman utama
    Props yang dikirim dari layout role:
      • $sidebarBg — kelas tailwind bg sidebar (misal: "bg-gray-900" / "bg-slate-900")
      • $logoutRoute — route name untuk logout
      • $logoutMethod — "admin" / "kecamatan" / dll
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Klasifikasi Berdaya') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-100 overflow-hidden" id="app-body">

{{-- =========================================================
     LAYOUT — Sidebar kiri + Content kanan (flex h-screen)
     Identik dengan pola AdminLayoutClient.tsx di Next.js
========================================================== --}}
<div class="flex h-screen overflow-hidden bg-gray-100">

    {{-- ── SIDEBAR (desktop: fixed, mobile: drawer) ─────────────── --}}
    @isset($sidebar)
        {{-- Mobile overlay --}}
        <div
            id="sidebar-overlay"
            class="fixed inset-0 z-20 bg-black/40 md:hidden hidden"
            aria-hidden="true"
        ></div>

        {{-- Sidebar panel --}}
        <aside
            id="sidebar-panel"
            aria-label="Sidebar Navigasi"
            class="fixed inset-y-0 left-0 z-30 w-64 flex flex-col
                   transform -translate-x-full md:translate-x-0
                   transition-transform duration-300 ease-in-out
                   {{ $sidebarBg ?? 'bg-gray-900' }}"
        >
            {{ $sidebar }}
        </aside>
    @endisset

    {{-- ── MAIN AREA (header + content) ────────────────────────── --}}
    <div class="flex flex-1 flex-col md:pl-64 min-w-0 overflow-hidden">

        {{-- ─ Header ──────────────────────────────────────────────── --}}
        <header class="sticky top-0 z-10 flex h-14 items-center gap-3 bg-white border-b border-gray-200 px-4 shadow-sm">

            {{-- Hamburger — mobile only --}}
            <button
                type="button"
                id="sidebar-toggle"
                class="md:hidden flex items-center justify-center rounded-md p-1.5 text-gray-600 hover:bg-gray-100 transition-colors"
                aria-label="Buka menu"
                aria-expanded="false"
                aria-controls="sidebar-panel"
            >
                {{-- icon menu --}}
                <svg id="icon-menu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg id="icon-close" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Page title / breadcrumb dari child view --}}
            <div class="flex-1 min-w-0">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>

            {{-- User info --}}
            <div class="flex items-center gap-2 shrink-0">
                <div class="hidden sm:flex flex-col items-end leading-tight">
                    <span class="text-sm font-medium text-gray-800">{{ auth()->user()?->name }}</span>
                    <span class="text-xs text-gray-400 capitalize">
                        @php
                            $roleLabel = [
                                'SUPER_ADMIN' => 'Super Admin',
                                'ADMIN'       => 'Admin',
                                'VALIDATOR'   => 'Validator',
                                'USER'        => 'Kecamatan',
                            ];
                        @endphp
                        {{ $roleLabel[auth()->user()?->role] ?? auth()->user()?->role }}
                    </span>
                </div>
                {{-- Avatar --}}
                <div class="h-8 w-8 rounded-full bg-sky-600 flex items-center justify-center text-white text-sm font-bold shrink-0">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                </div>
            </div>
        </header>

        {{-- ─ Page Content ────────────────────────────────────────── --}}
        <main class="flex-1 overflow-y-auto overscroll-none bg-gray-100 p-4 md:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')

{{-- Toast notifikasi auto-dismiss (tipe 1: tidak perlu respons user) --}}
<x-toast-container />

{{-- =========================================================
     JS — Mobile sidebar toggle
     Menggantikan useState(sidebarOpen) dari React
========================================================== --}}
<script>
(function () {
    const toggle  = document.getElementById('sidebar-toggle');
    const overlay = document.getElementById('sidebar-overlay');
    const panel   = document.getElementById('sidebar-panel');
    const iconMenu  = document.getElementById('icon-menu');
    const iconClose = document.getElementById('icon-close');

    if (!toggle || !panel) return;

    function openSidebar() {
        panel.classList.remove('-translate-x-full');
        overlay && overlay.classList.remove('hidden');
        iconMenu  && iconMenu.classList.add('hidden');
        iconClose && iconClose.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        panel.classList.add('-translate-x-full');
        overlay && overlay.classList.add('hidden');
        iconMenu  && iconMenu.classList.remove('hidden');
        iconClose && iconClose.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
    }

    let isOpen = false;

    toggle.addEventListener('click', function () {
        isOpen = !isOpen;
        isOpen ? openSidebar() : closeSidebar();
    });

    overlay && overlay.addEventListener('click', function () {
        isOpen = false;
        closeSidebar();
    });

    // Tutup sidebar jika resize ke desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 768) {
            overlay && overlay.classList.add('hidden');
            iconMenu  && iconMenu.classList.remove('hidden');
            iconClose && iconClose.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
            isOpen = false;
        }
    }, { passive: true });
})();
</script>

</body>
</html>
