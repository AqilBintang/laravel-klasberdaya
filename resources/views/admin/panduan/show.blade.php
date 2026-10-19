{{--
    admin/panduan/show.blade.php
    Detail panduan rubrik — tabel skor 1–4 per indikator.
    Data: $id, $rubric, $categories, $totalIndicators
--}}
<x-layouts.admin pageTitle="Panduan Rubrik">
    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $rubric->title }}</h2>
                    @if ($rubric->assessment)
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $rubric->assessment->title }} · Periode {{ $rubric->assessment->periode }}
                        </p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.panduan.edit', $id) }}"
                       class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Panduan
                    </a>
                    <form method="POST" action="{{ route('admin.panduan.destroy', $id) }}"
                          onsubmit="return confirm('Hapus panduan ini? Semua data skor akan hilang.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                            </svg>
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Info kelengkapan --}}
        @php $filledCount = collect($categories)->sum(fn($c) => count($c['items'])); @endphp
        @if ($totalIndicators > 0)
            <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 flex items-center gap-3 text-sm text-gray-600">
                <svg class="w-4 h-4 text-sky-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                    Terisi <strong>{{ $filledCount }}</strong> dari <strong>{{ $totalIndicators }}</strong> indikator.
                    @if ($filledCount < $totalIndicators)
                        <a href="{{ route('admin.panduan.edit', $id) }}" class="text-sky-600 hover:text-sky-700 ml-1">Lengkapi sekarang →</a>
                    @endif
                </span>
            </div>
        @endif

        @if ($rubric->description)
            <p class="text-sm text-gray-500">{{ $rubric->description }}</p>
        @endif

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
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 min-w-[200px]">Indikator</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-red-500 min-w-[150px]">Skor 1</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-orange-500 min-w-[150px]">Skor 2</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-yellow-600 min-w-[150px]">Skor 3</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-green-600 min-w-[150px]">Skor 4</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($cat['items'] as $item)
                                <tr class="hover:bg-gray-50 align-top">
                                    <td class="px-4 py-3 text-xs font-bold text-gray-400">{{ $item->indicator->number }}</td>
                                    <td class="px-4 py-3 text-gray-800 leading-snug text-xs">{{ $item->indicator->indicator }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600 leading-snug">{{ $item->score1 ?: '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600 leading-snug">{{ $item->score2 ?: '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600 leading-snug">{{ $item->score3 ?: '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600 leading-snug">{{ $item->score4 ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-12 text-center">
                <p class="text-sm text-gray-500">Belum ada item rubrik.</p>
                <a href="{{ route('admin.panduan.edit', $id) }}"
                   class="mt-3 inline-flex items-center gap-1.5 text-sm text-sky-600 hover:text-sky-700 font-medium">
                    Isi sekarang →
                </a>
            </div>
        @endforelse
    </div>
</x-layouts.admin>
