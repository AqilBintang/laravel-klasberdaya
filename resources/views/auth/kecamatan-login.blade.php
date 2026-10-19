<x-layouts.auth-guest>
    <x-slot name="title">Login Kecamatan — {{ config('app.name') }}</x-slot>

    {{-- Migrasi dari: src/app/kecamatan/login/page.tsx
         Background: bg-gradient-to-br from-sky-50 to-blue-100
         Warna aksen: sky/blue
    --}}
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-sky-50 to-blue-100 p-4">
        <div class="w-full max-w-md">

            {{-- Logo + judul --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3 mb-4">
                    <img src="/logo/logo-kota.png"         alt="Logo"       class="h-12 w-auto object-contain">
                    <img src="/logo/kecamatan-berdaya.png" alt="Klas Berdaya" class="h-12 w-auto object-contain">
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Portal Kecamatan Klas Berdaya</h1>
                <p class="mt-1 text-sm text-gray-500">Halaman kecamatan</p>
            </div>

            {{-- Card --}}
            <div class="bg-white rounded-2xl shadow-lg p-8">

                {{-- Session error --}}
                @if (session('error'))
                    <div class="mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('kecamatan.login.store') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Email
                        </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Masukkan email"
                            required
                            autofocus
                            autocomplete="email"
                            class="w-full rounded-xl border px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400
                                   focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400/20
                                   {{ $errors->has('email') ? 'border-red-400' : 'border-gray-300' }}"
                        >
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Masukkan password"
                                required
                                autocomplete="current-password"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 pr-12 text-sm text-gray-900
                                       placeholder:text-gray-400 focus:border-sky-400 focus:outline-none
                                       focus:ring-2 focus:ring-sky-400/20
                                       {{ $errors->has('password') ? 'border-red-400' : 'border-gray-300' }}"
                            >
                            {{-- Toggle show/hide password --}}
                            <button
                                type="button"
                                onclick="togglePassword('password', this)"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                aria-label="Tampilkan password"
                            >
                                <svg class="w-4 h-4 icon-eye" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg class="w-4 h-4 icon-eye-off hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Error: akun admin coba login di portal kecamatan
                         Setara dengan isAdminError di LoginForm.tsx --}}
                    @if (session('admin_error'))
                        <div class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                            <p>Akun admin tidak dapat login di sini.</p>
                            <a
                                href="{{ route('admin.login') }}"
                                class="mt-1 inline-block font-medium underline hover:text-red-800"
                            >
                                Pergi ke halaman login admin →
                            </a>
                        </div>
                    @endif

                    {{-- Validation errors umum --}}
                    @if ($errors->any() && !$errors->has('email') && !$errors->has('password'))
                        <div class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-sky-600 py-3
                               text-sm font-semibold text-white hover:bg-sky-700 transition-colors"
                    >
                        Masuk
                    </button>
                </form>

            </div>{{-- /card --}}

            <p class="text-center mt-6 text-xs text-gray-400">
                Hubungi administrator jika mengalami kendala login
            </p>
        </div>
    </div>

    @push('scripts')
    <script>
    function togglePassword(fieldId, btn) {
        var field  = document.getElementById(fieldId);
        var eyeOn  = btn.querySelector('.icon-eye');
        var eyeOff = btn.querySelector('.icon-eye-off');
        if (field.type === 'password') {
            field.type = 'text';
            eyeOn.classList.add('hidden');
            eyeOff.classList.remove('hidden');
            btn.setAttribute('aria-label', 'Sembunyikan password');
        } else {
            field.type = 'password';
            eyeOn.classList.remove('hidden');
            eyeOff.classList.add('hidden');
            btn.setAttribute('aria-label', 'Tampilkan password');
        }
    }
    </script>
    @endpush
</x-layouts.auth-guest>
