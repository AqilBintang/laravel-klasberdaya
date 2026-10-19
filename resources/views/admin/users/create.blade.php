{{--
    admin/users/create.blade.php
    Form untuk menambah user kecamatan baru.
--}}
<x-layouts.admin pageTitle="Tambah User">
    <div class="space-y-6">
        <div>
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
            <h2 class="text-2xl font-bold text-gray-900">Tambah User Kecamatan</h2>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
                <h3 class="font-semibold text-gray-800 text-sm">Informasi Akun</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" required
                               value="{{ old('name') }}"
                               placeholder="Nama perwakilan kecamatan"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('name') border-red-300 @enderror">
                        @error('name')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" required
                               value="{{ old('email') }}"
                               placeholder="contoh@email.com"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('email') border-red-300 @enderror">
                        @error('email')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <input type="password" id="password" name="password" required
                               class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('password') border-red-300 @enderror">
                        @error('password')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5"
                 x-data="wilayahDropdown('{{ old('kabupaten_id') }}', '{{ old('kecamatan_id') }}')">
                <h3 class="font-semibold text-gray-800 text-sm">Data Wilayah</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Kabupaten/Kota --}}
                    <div>
                        <label for="kabupaten_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Kabupaten/Kota <span class="text-red-500">*</span>
                        </label>
                        <select id="kabupaten_id" name="kabupaten_id" required
                                x-model="kabupatenId"
                                @change="onKabupatenChange()"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('kabupaten_id') border-red-300 @enderror">
                            <option value="">-- Pilih Kabupaten/Kota --</option>
                            @foreach ($kabupatens as $kab)
                                <option value="{{ $kab->id }}">{{ $kab->nama }}</option>
                            @endforeach
                        </select>
                        @error('kabupaten_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Kecamatan (dinamis via AJAX) --}}
                    <div>
                        <label for="kecamatan_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Kecamatan <span class="text-red-500">*</span>
                        </label>
                        <select id="kecamatan_id" name="kecamatan_id" required
                                x-model="kecamatanId"
                                :disabled="!kabupatenId || loading"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 disabled:bg-gray-50 disabled:text-gray-400 @error('kecamatan_id') border-red-300 @enderror">
                            <option value="" x-text="loading ? 'Memuat...' : (kabupatenId ? '-- Pilih Kecamatan --' : '-- Pilih kabupaten dulu --')"></option>
                            <template x-for="kec in kecamatans" :key="kec.id">
                                <option :value="kec.id" x-text="kec.nama"></option>
                            </template>
                        </select>
                        @error('kecamatan_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.users.index') }}"
                   class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    Tambah User
                </button>
            </div>
        </form>
    </div>

    <script>
        function wilayahDropdown(oldKabupatenId, oldKecamatanId) {
            return {
                kabupatenId:  oldKabupatenId  || '',
                kecamatanId:  oldKecamatanId  || '',
                kecamatans:   [],
                loading:      false,

                init() {
                    // Bila ada nilai old() dari validasi gagal, muat kecamatan otomatis
                    if (this.kabupatenId) {
                        this.fetchKecamatans(this.kabupatenId);
                    }
                },

                onKabupatenChange() {
                    this.kecamatanId = '';
                    this.kecamatans  = [];
                    if (this.kabupatenId) {
                        this.fetchKecamatans(this.kabupatenId);
                    }
                },

                async fetchKecamatans(kabupatenId) {
                    this.loading = true;
                    try {
                        const res  = await fetch(`{{ route('admin.users.kecamatan-by-kabupaten') }}?kabupaten_id=${kabupatenId}`);
                        const data = await res.json();
                        this.kecamatans = data;
                        // Pulihkan pilihan kecamatan bila ada old() value
                        if (oldKecamatanId) {
                            this.kecamatanId = oldKecamatanId;
                            oldKecamatanId   = '';   // hanya sekali
                        }
                    } catch (e) {
                        console.error('Gagal memuat kecamatan:', e);
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-layouts.admin>
