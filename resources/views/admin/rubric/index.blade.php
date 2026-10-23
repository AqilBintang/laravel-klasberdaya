{{--
    admin/rubric/index.blade.php
    Daftar rubric penilaian assessment.
    Data: $rubrics (Collection<AssessmentRubric>)
--}}
<x-layouts.admin pageTitle="Daftar Rubric">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Rubric Penilaian</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola kriteria penilaian untuk setiap indikator</p>
            </div>
            <a href="{{ route('admin.rubric.create') }}"
               class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Rubric
            </a>
        </div>

        @if (isset($rubrics) && $rubrics->isNotEmpty())
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Judul</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Assessment</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Indikator</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rubrics as $r)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-gray-900">{{ $r->title ?? $r->name ?? 'Rubric #' . $r->id }}</p>
                                    @if ($r->description ?? false)
                                        <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $r->description }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    @if ($r->assessment)
                                        <p>{{ $r->assessment->title }}</p>
                                        <p class="text-xs text-gray-400">Periode {{ $r->assessment->periode }}</p>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center text-gray-600">{{ $r->items_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.rubric.show', $r->id) }}"
                                           class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">Detail</a>
                                        <a href="{{ route('admin.rubric.edit', $r->id) }}"
                                           class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">Edit</a>
                                        <form method="POST" action="{{ route('admin.rubric.destroy', $r->id) }}"
                                              onsubmit="return confirm('Hapus rubric ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-gray-500 font-medium text-sm">Belum ada rubric</p>
                <a href="{{ route('admin.rubric.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    Tambah Rubric
                </a>
            </div>
        @endif
    </div>
</x-layouts.admin>
