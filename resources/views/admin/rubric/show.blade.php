{{--
    admin/rubric/show.blade.php
    Detail rubric dengan daftar item per kategori.
    Data: $id, $rubric, $categories (array per kategori dengan items)
--}}
<x-layouts.admin pageTitle="Detail Rubric">
    <div class="space-y-6">
        {{-- Breadcrumb --}}
        <div>
            <a href="{{ route('admin.rubric.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Daftar
            </a>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $rubric->title }}</h2>
                    @if ($rubric->assessment)
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $rubric->assessment->title }} · Periode {{ $rubric->assessment->periode }}
                        </p>
                    @endif
                </div>
                <a href="{{ route('admin.rubric.edit', $id) }}"
                   class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            </div>
        </div>

        @if ($rubric->description)
            <div class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-600">
                {{ $rubric->description }}
            </div>
        @endif

        {{-- Tabel rubrik per kategori --}}
        @forelse ($categories as $cat)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                {{-- Header kategori --}}
                <div class="flex items-center gap-3 px-5 py-3 bg-sky-600">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/20 text-xs font-bold text-white shrink-0">
                        {{ $cat['code'] }}
                    </span>
                    <p class="font-semibold text-white text-sm">{{ $cat['name'] }}</p>
                    <span class="ml-auto text-xs text-sky-200">{{ count($cat['items']) }} indikator</span>
                </div>

                {{-- Tabel indikator + skor --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 w-6">No</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 min-w-[220px]">Indikator</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-red-500 min-w-[160px]">Skor 1</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-orange-500 min-w-[160px]">Skor 2</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-yellow-600 min-w-[160px]">Skor 3</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-green-600 min-w-[160px]">Skor 4</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($cat['items'] as $item)
                                <tr class="hover:bg-gray-50 align-top">
                                    <td class="px-4 py-3 text-xs font-bold text-gray-400">
                                        {{ $item->indicator->number }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-800 leading-snug">
                                        {{ $item->indicator->indicator }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 leading-snug text-xs">{{ $item->score1 }}</td>
                                    <td class="px-4 py-3 text-gray-600 leading-snug text-xs">{{ $item->score2 }}</td>
                                    <td class="px-4 py-3 text-gray-600 leading-snug text-xs">{{ $item->score3 }}</td>
                                    <td class="px-4 py-3 text-gray-600 leading-snug text-xs">{{ $item->score4 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-12 text-center">
                <p class="text-sm text-gray-500">Belum ada item rubrik untuk panduan ini.</p>
            </div>
        @endforelse
    </div>
</x-layouts.admin>
