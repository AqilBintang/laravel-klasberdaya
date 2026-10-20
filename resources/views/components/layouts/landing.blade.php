{{--
    components/layouts/landing.blade.php
    Anonymous Blade component — dipanggil sebagai <x-layouts.landing title="...">
    Berisi navbar + footer + slot untuk halaman publik (landing).

    Props:
      • $title — judul halaman (opsional)
    Slot:
      • $slot — konten halaman utama
      • named slot "title" — override <title> tag
--}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Klasifikasi Berdaya') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased bg-white text-gray-900 flex flex-col min-h-screen">

    {{-- =====================================================================
         NAVBAR — migrasi dari Navbar.tsx
         Scroll effect dihandle JS di bawah
    ====================================================================== --}}
    <header class="fixed top-0 z-50 w-full px-0 transition-all duration-300 ease-in-out" id="site-header">
        <nav
            id="main-nav"
            aria-label="Navigasi Utama"
            class="mx-auto flex items-center justify-between px-4 sm:px-6 py-3
                   w-full bg-white border-b border-gray-100 shadow-sm
                   transition-all duration-300 ease-in-out"
        >
            {{-- Logo --}}
            <a href="{{ route('landing.home') }}" class="flex items-center justify-center gap-1 shrink-0">
                <img src="/logo/logo-kota.png"        alt="Logo Kota"   class="h-8 sm:h-10 w-auto object-contain">
                <img src="/logo/kecamatan-berdaya.png" alt="Klas Berdaya" class="h-9 sm:h-11 w-auto object-contain translate-y-px">
            </a>

            {{-- Desktop nav links --}}
            <ul class="hidden md:flex items-center gap-6" role="list">
                @php
                    $navItems = [
                        ['label' => 'Home',    'href' => route('landing.home')],
                        ['label' => 'FAQ',     'href' => route('landing.faq')],
                    ];
                @endphp
                @foreach ($navItems as $item)
                    @php $active = request()->url() === $item['href']; @endphp
                    <li>
                        <a
                            href="{{ $item['href'] }}"
                            @if($active) aria-current="page" @endif
                            class="relative text-sm font-medium transition-colors duration-200 pb-1
                                   after:absolute after:bottom-0 after:left-0 after:h-0.5 after:rounded-full after:transition-all after:duration-200
                                   {{ $active
                                       ? 'text-sky-600 after:w-full after:bg-sky-500'
                                       : 'text-gray-600 hover:text-gray-900 after:w-0 hover:after:w-full hover:after:bg-gray-400' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Mobile hamburger --}}
            <button
                type="button"
                id="mobile-menu-btn"
                class="md:hidden flex items-center justify-center rounded-md p-2 text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                aria-label="Buka menu"
                aria-expanded="false"
                aria-controls="mobile-menu-panel"
            >
                {{-- icon burger --}}
                <svg id="icon-menu" class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                {{-- icon X --}}
                <svg id="icon-close" class="size-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </nav>

        {{-- Mobile overlay --}}
        <div
            id="mobile-overlay"
            class="fixed inset-0 z-40 bg-black/40 md:hidden hidden"
            aria-hidden="true"
        ></div>

        {{-- Mobile panel --}}
        <div
            id="mobile-menu-panel"
            role="dialog"
            aria-modal="true"
            aria-label="Menu navigasi"
            class="fixed top-0 right-0 z-50 h-full w-72 bg-white shadow-xl md:hidden
                   transform translate-x-full transition-transform duration-300 ease-in-out"
        >
            <div class="flex items-center justify-between px-5 py-4 border-b">
                <span class="font-bold text-gray-900">Menu</span>
                <button
                    type="button"
                    id="mobile-menu-close"
                    class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 transition-colors"
                    aria-label="Tutup menu"
                >
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <nav aria-label="Menu mobile">
                <ul class="flex flex-col px-3 py-4 gap-1" role="list">
                    @foreach ($navItems as $item)
                        @php $active = request()->url() === $item['href']; @endphp
                        <li>
                            <a
                                href="{{ $item['href'] }}"
                                @if($active) aria-current="page" @endif
                                class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition-colors
                                       {{ $active
                                           ? 'bg-sky-50 text-sky-700 font-semibold border-l-2 border-sky-500'
                                           : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </header>

    {{-- ===================================================================
         MAIN CONTENT
    =================================================================== --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- =====================================================================
         FOOTER — migrasi dari Footer.tsx
    ====================================================================== --}}
    <footer class="bg-gray-900 text-gray-300">
        {{-- Main footer --}}
        <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">

                {{-- Kolom 1 — Brand --}}
                <div class="flex flex-col gap-4">
                    <a
                        href="{{ route('landing.home') }}"
                        class="inline-flex w-fit items-center gap-1 rounded-xl border border-white/20 bg-white p-2 shadow-lg shadow-black/10"
                    >
                        <img src="/logo/logo-kota.png"         alt="Logo Kota"   class="h-10 w-auto object-contain">
                        <img src="/logo/kecamatan-berdaya.png" alt="Klas Berdaya" class="h-10 w-auto object-contain translate-y-px">
                    </a>
                    <p class="max-w-xs text-sm leading-relaxed text-gray-400">
                        Platform Klasifikasi Kecamatan Berdaya dalam mendukung evaluasi perkembangan implementasi program Kecamatan Berdaya Provinsi Jawa Tengah.
                    </p>
                </div>

                {{-- Kolom 2 — Navigasi --}}
                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Navigasi</h3>
                    <ul class="flex flex-col gap-2">
                        <li><a href="{{ route('landing.home') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Home</a></li>
                        <li><a href="{{ route('landing.faq') }}"  class="text-sm text-gray-400 hover:text-white transition-colors">FAQ</a></li>
                    </ul>
                </div>

                {{-- Kolom 3 — Kontak --}}
                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Kontak</h3>
                    <ul class="flex flex-col gap-3 text-sm text-gray-400">
                        <li class="flex items-start gap-2">
                            <svg class="mt-1 w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Jl. Pemuda No. 148, Semarang, Jawa Tengah 50132</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>(024) 1234-5678</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <a href="mailto:kecamatanberdaya@jatengprov.go.id" class="hover:text-white transition-colors">kecamatanberdaya@jatengprov.go.id</a>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="border-t border-gray-800">
            <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
                <span>© {{ date('Y') }} Klas Berdaya. Seluruh hak cipta dilindungi.</span>
                <span>Klasifikasi Kecamatan Berdaya Provinsi Jawa Tengah</span>
            </div>
        </div>
    </footer>

    @stack('scripts')

    {{-- =====================================================================
         JS — scroll effect navbar + mobile menu
         Menggantikan useEffect/useState dari Navbar.tsx
    ====================================================================== --}}
    <script>
    (function () {
        // ── Scroll effect ──────────────────────────────────────────────────
        // Animasi: header padding melebar → nav terlihat "mengambang" + rounded
        // Tidak mengubah width elemen → tidak ada layout reflow di konten bawah
        const header = document.getElementById('site-header');
        const nav    = document.getElementById('main-nav');
        if (header && nav) {
            window.addEventListener('scroll', function () {
                if (window.scrollY > 50) {
                    // Floating state
                    header.classList.add('px-3', 'sm:px-5', 'pt-4');
                    nav.classList.remove('w-full', 'border-b', 'border-gray-100', 'shadow-sm');
                    nav.classList.add('rounded-2xl', 'shadow-[0_5px_20px_rgba(0,0,0,0.15)]');
                } else {
                    // Default state
                    header.classList.remove('px-3', 'sm:px-5', 'pt-4');
                    nav.classList.add('w-full', 'border-b', 'border-gray-100', 'shadow-sm');
                    nav.classList.remove('rounded-2xl', 'shadow-[0_5px_20px_rgba(0,0,0,0.15)]');
                }
            }, { passive: true });
        }

        // ── Mobile menu ────────────────────────────────────────────────────
        const btn      = document.getElementById('mobile-menu-btn');
        const panel    = document.getElementById('mobile-menu-panel');
        const overlay  = document.getElementById('mobile-overlay');
        const closeBtn = document.getElementById('mobile-menu-close');
        const iconMenu  = document.getElementById('icon-menu');
        const iconClose = document.getElementById('icon-close');

        function openMenu() {
            panel.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            btn.setAttribute('aria-expanded', 'true');
            btn.setAttribute('aria-label', 'Tutup menu');
            iconMenu.classList.add('hidden');
            iconClose.classList.remove('hidden');
        }

        function closeMenu() {
            panel.classList.add('translate-x-full');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            btn.setAttribute('aria-expanded', 'false');
            btn.setAttribute('aria-label', 'Buka menu');
            iconMenu.classList.remove('hidden');
            iconClose.classList.add('hidden');
        }

        if (btn)      btn.addEventListener('click', openMenu);
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        if (overlay)  overlay.addEventListener('click', closeMenu);
    })();
    </script>
</body>
</html>
