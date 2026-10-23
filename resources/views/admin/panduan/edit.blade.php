{{--
    admin/panduan/edit.blade.php
    Edit panduan rubrik — metadata + textarea inline per sel skor.
    Data: $id, $rubric, $categories, $assessments
--}}
<x-layouts.admin pageTitle="Edit Panduan Rubrik">
    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <a href="{{ route('admin.panduan.show', $id) }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Batal & Kembali
            </a>
            <h2 class="text-2xl font-bold text-gray-900">Edit Panduan Rubrik</h2>
            <p class="mt-1 text-sm text-gray-500">Isi deskripsi untuk setiap skor (1–4) per indikator.</p>
        </div>

        <form method="POST" action="{{ route('admin.panduan.update', $id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Metadata --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-gray-800">Informasi Panduan</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            Judul <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" required
                               value="{{ old('title', $rubric->title) }}"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('title') border-red-300 @enderror">
                        @error('title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assessment</label>
                        <p class="px-3 py-2.5 text-sm text-gray-600 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $rubric->assessment?->title }} ({{ $rubric->assessment?->periode }})
                        </p>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea id="description" name="description" rows="2"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 resize-none">{{ old('description', $rubric->description) }}</textarea>
                </div>
            </div>

            {{-- Tabel per kategori --}}
            @forelse ($categories as $cat)
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3 bg-sky-600">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/20 text-xs font-bold text-white shrink-0">
                            {{ $cat['code'] }}
                        </span>
                        <p class="font-semibold text-white text-sm">{{ $cat['name'] }}</p>
                        <span class="ml-auto text-xs text-sky-200">{{ count($cat['items']) }} indikator</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-400 w-8">No</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 min-w-[180px]">Indikator</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-red-500 min-w-[180px]">Skor 1</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-orange-500 min-w-[180px]">Skor 2</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-yellow-600 min-w-[180px]">Skor 3</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-green-600 min-w-[180px]">Skor 4</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($cat['items'] as $item)
                                    <tr class="align-top">
                                        <td class="px-4 py-3 text-xs font-bold text-gray-400">{{ $item->indicator->number }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-700 leading-snug">{{ $item->indicator->indicator }}</td>
                                        @foreach (['score1','score2','score3','score4'] as $col)
                                            <td class="px-4 py-3">
                                                <textarea name="items[{{ $item->id }}][{{ $col }}]"
                                                          rows="3"
                                                          class="w-full rounded-lg border border-gray-200 px-2.5 py-2 text-xs focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 resize-none"
                                                          placeholder="Deskripsi {{ substr($col, -1) }}…">{{ old("items.{$item->id}.{$col}", $item->$col) }}</textarea>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-12 text-center">
                    <p class="text-sm text-gray-500">Belum ada item rubrik. Pastikan assessment sudah memiliki indikator aktif.</p>
                </div>
            @endforelse

            {{-- Action bar --}}
            <div class="sticky bottom-0 flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-6 py-4 shadow-md">
                <p class="text-xs text-gray-400">Perubahan akan langsung terlihat di halaman panduan kecamatan.</p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.panduan.show', $id) }}"
                       class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-sky-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 transition-colors">
                        Simpan Semua Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
