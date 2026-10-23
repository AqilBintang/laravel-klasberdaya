{{--
    admin/manage-kecamatan.blade.php
    Ringkasan manajemen kecamatan berdasarkan wilayah.
    Data: $stats (array, optional) — ringkasan per kabupaten
--}}
<x-layouts.admin pageTitle="Manajemen Kecamatan">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Manajemen Kecamatan</h2>
                <p class="mt-1 text-sm text-gray-500">Ringkasan kecamatan terdaftar per kabupaten/kota</p>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Kecamatan
            </a>
        </div>

        @if (isset($stats) && count($stats) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($stats as $kab)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <h3 class="font-semibold text-gray-800">{{ $kab['nama'] }}</h3>
                            <span class="shrink-0 rounded-full bg-sky-50 text-sky-700 px-2.5 py-0.5 text-xs font-semibold">
                                {{ $kab['total'] }} kec
                            </span>
                        </div>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Aktif</span>
                                <span class="font-medium text-gray-900">{{ $kab['aktif'] ?? $kab['total'] }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Sudah isi assessment</span>
                                <span class="font-medium text-gray-900">{{ $kab['isiAssessment'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Tervalidasi</span>
                                <span class="font-medium text-green-700">{{ $kab['tervalidasi'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <a href="{{ route('admin.users.index') }}?kabupaten={{ urlencode($kab['nama']) }}"
                               class="text-xs text-sky-600 hover:text-sky-700 font-medium flex items-center gap-1">
                                Lihat User Kecamatan
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-gray-500 font-medium text-sm">Belum ada kecamatan terdaftar</p>
                <a href="{{ route('admin.users.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    Tambah Kecamatan Pertama
                </a>
            </div>
        @endif

    </div>
</x-layouts.admin>
