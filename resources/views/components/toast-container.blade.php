{{--
    components/toast-container.blade.php
    Toast notifikasi auto-dismiss tipe 1 — tidak perlu respons user.

    Cara pakai:
        <x-toast-container />          ← letakkan sekali di layout

    Sumber data: Laravel session flash key:
        session('success')  → toast hijau
        session('error')    → toast merah
        session('warning')  → toast kuning
        session('info')     → toast biru

    Perilaku:
        - Muncul di pojok kanan bawah layar (fixed)
        - Animasi slide-up + fade-in saat tampil
        - Hilang otomatis setelah 3 detik (fade-out + slide-down)
        - Bisa ditutup manual via tombol × sebelum 3 detik
        - Support banyak toast sekaligus (ditampilkan bertumpuk ke atas)
        - Tidak ada interaksi/konfirmasi yang diperlukan dari user

    Dependensi: Alpine.js (sudah tersedia melalui @vite)
--}}

@php
    $toasts = [];

    if (session('success')) {
        $toasts[] = ['type' => 'success', 'message' => session('success')];
    }
    if (session('error')) {
        $toasts[] = ['type' => 'error',   'message' => session('error')];
    }
    if (session('warning')) {
        $toasts[] = ['type' => 'warning', 'message' => session('warning')];
    }
    if (session('info')) {
        $toasts[] = ['type' => 'info',    'message' => session('info')];
    }
@endphp

@if (count($toasts) > 0)
<div
    id="toast-region"
    role="region"
    aria-live="polite"
    aria-label="Notifikasi"
    class="fixed top-5 right-5 z-50 flex flex-col gap-3 pointer-events-none items-end"
>
    @foreach ($toasts as $index => $toast)
        @php
            $cfg = match($toast['type']) {
                'success' => [
                    'border_left' => 'border-l-green-500',
                    'icon_bg'     => 'bg-green-500',
                    'label'       => 'Success',
                    'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>',
                ],
                'error' => [
                    'border_left' => 'border-l-red-500',
                    'icon_bg'     => 'bg-red-500',
                    'label'       => 'Error',
                    'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>',
                ],
                'warning' => [
                    'border_left' => 'border-l-yellow-400',
                    'icon_bg'     => 'bg-yellow-400',
                    'label'       => 'Warning',
                    'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>',
                ],
                default => [
                    'border_left' => 'border-l-blue-500',
                    'icon_bg'     => 'bg-blue-500',
                    'label'       => 'Info',
                    'icon'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                ],
            };
        @endphp

        <div
            x-data="{
                show: false,
                init() {
                    this.$nextTick(() => {
                        this.show = true;
                        setTimeout(() => { this.show = false }, 4500);
                    });
                }
            }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            style="display: none"
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            class="pointer-events-auto w-80 max-w-[calc(100vw-2.5rem)] bg-white rounded-lg shadow-lg border-l-4 border border-gray-100 {{ $cfg['border_left'] }} flex items-start gap-3 px-4 py-3.5 pr-10 relative"
        >
            {{-- Icon bulat --}}
            <div class="shrink-0 mt-0.5 h-7 w-7 rounded-full {{ $cfg['icon_bg'] }} flex items-center justify-center">
                <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    {!! $cfg['icon'] !!}
                </svg>
            </div>

            {{-- Teks --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-800">{{ $cfg['label'] }}</p>
                <p class="text-sm text-gray-500 leading-snug mt-0.5">{{ $toast['message'] }}</p>
            </div>

            {{-- Tombol tutup --}}
            <button
                type="button"
                @click="show = false"
                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                aria-label="Tutup notifikasi"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endforeach
</div>
@endif
