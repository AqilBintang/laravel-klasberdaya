{{--
    admin/export.blade.php
    Halaman UI Export Laporan — identik dengan Next.js BackupExportClient.

    Route  : GET /admin/export/assessment  (admin.export.assessment)
    Data   : $periodes (array), $kecamatans (array), $years (array), $defaultPeriode (?string)

    Dua card download:
      1. Backup Data Lengkap  → /admin/export/snapshot?periode=...&kecamatan=...
      2. Rekap Status Akhir   → /admin/export/self-assessment?periode=...
--}}
<x-layouts.admin pageTitle="Export Laporan">
    <div class="space-y-6" x-data="exportPage()" x-init="init()">

        {{-- ─ Header ─────────────────────────────────────────────── --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Backup & Export</h2>
            <p class="mt-1 text-sm text-gray-600">
                Export Excel dibuat multi-sheet dan kolom diatur agar mudah dibaca.
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- ─ Card: Filter Export ────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="px-6 pt-5 pb-1">
                <h3 class="text-base font-semibold text-gray-900">Filter Export</h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    Pilih periode dan kecamatan agar export tetap lengkap tapi tidak terlalu besar.
                </p>
            </div>

            <div class="px-6 py-5 grid grid-cols-1 gap-4 md:grid-cols-2">

                {{-- Periode --}}
                <label class="space-y-1">
                    <div class="text-sm font-medium text-gray-700">Periode</div>
                    <select
                        x-model="periode"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-200">
                        <option value="" disabled>Pilih Periode</option>
                        @foreach ($periodes as $p)
                            <option value="{{ $p }}" {{ $p === ($defaultPeriode ?? '') ? 'selected' : '' }}>
                                {{ $p }}
                            </option>
                        @endforeach
                        @if (empty($periodes))
                            <option disabled>— Belum ada data —</option>
                        @endif
                    </select>
                </label>

                {{-- Kecamatan --}}
                <label class="space-y-1">
                    <div class="text-sm font-medium text-gray-700">Kecamatan</div>
                    <select
                        x-model="kecamatan"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-200">
                        <option value="" disabled>Pilih Kecamatan</option>
                        @foreach ($kecamatans as $kec)
                            <option value="{{ $kec }}">{{ $kec }}</option>
                        @endforeach
                        @if (empty($kecamatans))
                            <option disabled>— Belum ada data —</option>
                        @endif
                    </select>
                </label>

            </div>

            <div class="px-6 pb-5 flex justify-end">
                <button
                    type="button"
                    @click="reset()"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-colors">
                    Reset
                </button>
            </div>
        </div>

        {{-- ─ Backfill snapshot (jika dropdown masih kosong) ────── --}}
        @if (empty($kecamatans))
        <div class="rounded-xl border border-amber-200 bg-amber-50 shadow-sm">
            <div class="px-6 py-4 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-sm font-medium text-amber-800">Dropdown kecamatan kosong?</p>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Klik tombol ini untuk men-generate snapshot dari semua data yang sudah tervalidasi.
                        Cukup dilakukan sekali.
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.export.backfill-snapshots') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 transition-colors whitespace-nowrap">
                        Generate Snapshot Data Lama
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- ─ Dua card download ──────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- Card 1: Backup Data Lengkap --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm flex flex-col">
                <div class="px-6 pt-5 pb-4 flex-1">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="rounded-lg bg-sky-50 p-2.5 shrink-0 mt-0.5">
                            <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Backup Data Lengkap</h4>
                            <p class="mt-0.5 text-sm text-gray-500">
                                Judul assessment, kategori, indikator, deskripsi, skor, dokumen, total &amp; klasifikasi.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="px-6 pb-5 flex justify-end">
                    <a
                        :href="snapshotUrl"
                        :aria-disabled="!canDownloadSnapshot"
                        :class="canDownloadSnapshot
                            ? 'bg-sky-600 hover:bg-sky-700 text-white cursor-pointer'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none'"
                        class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                </div>
            </div>

            {{-- Card 2: Rekap Status Akhir --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm flex flex-col">
                <div class="px-6 pt-5 pb-4 flex-1">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="rounded-lg bg-green-50 p-2.5 shrink-0 mt-0.5">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">Rekap Status Akhir</h4>
                            <p class="mt-0.5 text-sm text-gray-500">
                                Semua kecamatan dalam periode terpilih, dikelompokkan per kabupaten/kota dalam sheet terpisah.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="px-6 pb-5 flex justify-end">
                    <a
                        :href="rekapUrl"
                        :aria-disabled="!canDownloadRekap"
                        :class="canDownloadRekap
                            ? 'bg-green-600 hover:bg-green-700 text-white cursor-pointer'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none'"
                        class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                </div>
            </div>

        </div>

        {{-- ─ Card tambahan: Template Assessment ────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="px-6 pt-5 pb-4">
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-violet-50 p-2.5 shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900">Template Assessment</h4>
                        <p class="mt-0.5 text-sm text-gray-500">
                            Daftar seluruh assessment beserta kategori dan indikator penilaian. Satu sheet per assessment.
                        </p>
                    </div>
                    <a href="{{ route('admin.export.template') }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-violet-700 transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download Template
                    </a>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function exportPage() {
            return {
                periode:   '{{ $defaultPeriode ?? '' }}',
                kecamatan: '',

                init() {
                    // tidak ada async fetch; data sudah dari server
                },

                reset() {
                    this.periode   = '{{ $defaultPeriode ?? '' }}';
                    this.kecamatan = '';
                },

                get baseSnapshot() {
                    return '{{ route('admin.export.snapshot') }}';
                },

                get baseRekap() {
                    return '{{ route('admin.export.self-assessment') }}';
                },

                get canDownloadSnapshot() {
                    return this.periode.trim() !== '' && this.kecamatan.trim() !== '';
                },

                get canDownloadRekap() {
                    return this.periode.trim() !== '';
                },

                get snapshotUrl() {
                    if (!this.canDownloadSnapshot) return '#';
                    const params = new URLSearchParams();
                    params.set('periode',   this.periode.trim());
                    params.set('kecamatan', this.kecamatan.trim());
                    return this.baseSnapshot + '?' + params.toString();
                },

                get rekapUrl() {
                    if (!this.canDownloadRekap) return '#';
                    return this.baseRekap + '?periode=' + encodeURIComponent(this.periode.trim());
                },
            }
        }
    </script>
    @endpush
</x-layouts.admin>
