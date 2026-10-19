{{--
    admin/wilayah.blade.php
    Daftar wilayah (kabupaten/kota dan kecamatan) di Jawa Tengah.
    Data: $kabupatens (Collection<Wilayah> dengan relasi kecamatans)
          $totalAkun (int, optional)
--}}
<x-layouts.admin pageTitle="Data Wilayah">
    <div class="space-y-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-900">Data Wilayah</h2>
            <p class="mt-1 text-sm text-gray-500">Daftar kabupaten/kota dan kecamatan di Jawa Tengah</p>
        </div>

        <div class="rounded-xl border border-sky-100 bg-sky-50 px-4 py-3">
            <p class="text-sm text-sky-700">
                Data wilayah bersumber dari referensi BPS. Jumlah kecamatan menentukan berapa banyak akun yang dapat didaftarkan.
            </p>
        </div>

        @if (isset($kabupatens) && $kabupatens->isNotEmpty())

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-2xl font-bold text-gray-900">{{ $kabupatens->count() }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Kabupaten/Kota</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $kabupatens->sum(fn ($k) => $k->children?->count() ?? 0) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">Total Kecamatan</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-2xl font-bold text-gray-900">{{ $totalAkun ?? '—' }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Akun Terdaftar</p>
                </div>
            </div>

            {{-- Tabel Kabupaten --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Daftar Kabupaten/Kota</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nama</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Kode</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Kecamatan</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Akun Terdaftar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($kabupatens as $kab)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $kab->nama }}</td>
                                <td class="px-5 py-4 text-gray-500 font-mono text-xs">{{ $kab->kode }}</td>
                                <td class="px-5 py-4 text-center text-gray-700">{{ $kab->children?->count() ?? 0 }}</td>
                                <td class="px-5 py-4 text-center text-gray-700">{{ $kab->registeredCount ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <p class="text-sm text-gray-500">Data wilayah belum tersedia.</p>
                <p class="text-xs text-gray-400 mt-1">Jalankan seeder wilayah untuk mengisi data.</p>
            </div>
        @endif

    </div>
</x-layouts.admin>
