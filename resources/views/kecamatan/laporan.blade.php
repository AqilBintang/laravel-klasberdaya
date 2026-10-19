{{--
    kecamatan/laporan.blade.php
    Halaman Laporan Tahunan untuk Kecamatan.
    Data dari LaporanController::index():
      - $availableYears : int[]   — tahun yang tersedia (descending)
      - $user           : User
--}}
<x-layouts.kecamatan pageTitle="Laporan Tahunan">

    <div class="space-y-6">

        {{-- ── Heading ──────────────────────────────────────────────────── --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Laporan Tahunan</h2>
            <p class="text-gray-600 mt-1">
                Download rekap seluruh assessment dalam satu tahun dalam format Excel.
            </p>
        </div>

        {{-- ── Flash messages ───────────────────────────────────────────── --}}
        @if (session('error'))
            <div class="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-600">
                {{ session('error') }}
            </div>
        @endif

        @if (empty($availableYears))
            {{-- ── Empty state ─────────────────────────────────────────── --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed
                        border-slate-200 bg-white py-16 text-center">
                {{-- FileSpreadsheet icon --}}
                <svg class="w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-slate-500 font-medium text-sm">Belum ada data assessment</p>
                <p class="text-xs text-slate-400 mt-1">Laporan akan tersedia setelah Anda mengisi assessment</p>
            </div>
        @else
            {{-- ── Card: Rekap Assessment per Tahun ─────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6 max-w-md space-y-5">

                {{-- Header card --}}
                <div class="flex items-center gap-3">
                    {{-- FileSpreadsheet icon (sky-600) --}}
                    <svg class="w-8 h-8 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-gray-900">Rekap Assessment per Tahun</p>
                        <p class="text-sm text-gray-500">
                            Satu file Excel berisi semua periode dalam tahun yang dipilih
                        </p>
                    </div>
                </div>

                {{-- Form pilih tahun + download --}}
                <form method="GET" action="{{ route('kecamatan.laporan') }}" id="form-laporan">
                    <div class="space-y-4">

                        {{-- Select tahun --}}
                        <div>
                            <label for="tahun" class="block text-sm font-medium text-gray-700 mb-1">
                                Pilih Tahun
                            </label>
                            <select id="tahun" name="tahun"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md
                                           focus:ring-2 focus:ring-sky-500 focus:border-sky-500 text-sm">
                                @foreach ($availableYears as $year)
                                    <option value="{{ $year }}"
                                            {{ (request('tahun') == $year || (!request('tahun') && $loop->first)) ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tombol Download --}}
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5
                                       bg-sky-600 text-white text-sm font-medium rounded-md
                                       hover:bg-sky-700 transition-colors">
                            {{-- FileDown icon --}}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download Excel
                            <span id="tahun-label">{{ $availableYears[0] ?? '' }}</span>
                        </button>

                    </div>
                </form>

            </div>
        @endif

    </div>

    {{-- Update label tahun di tombol saat select berubah --}}
    @if (!empty($availableYears))
        <script>
            (function () {
                const select = document.getElementById('tahun');
                const label  = document.getElementById('tahun-label');
                if (!select || !label) return;
                select.addEventListener('change', function () {
                    label.textContent = this.value;
                });
            })();
        </script>
    @endif

</x-layouts.kecamatan>
