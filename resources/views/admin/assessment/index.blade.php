{{--
    admin/assessment/index.blade.php
    Daftar assessment dengan status badges.
    Data: $assessments (Collection<Assessment>) with categories
--}}
<x-layouts.admin pageTitle="Daftar Assessment">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Daftar Assessment</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola template assessment yang tersedia</p>
            </div>
            <a href="{{ route('admin.assessment.create') }}"
               class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Assessment
            </a>
        </div>

        {{-- Table --}}
        @if (isset($assessments) && $assessments->isNotEmpty())
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Judul</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Periode</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Kategori</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($assessments as $a)
                            @php
                                $statusMap = [
                                    'DRAFT'     => ['label' => 'Draft',     'cls' => 'bg-gray-100 text-gray-600'],
                                    'PUBLISHED' => ['label' => 'Published', 'cls' => 'bg-green-100 text-green-700'],
                                    'REVISION'  => ['label' => 'Revisi',    'cls' => 'bg-yellow-100 text-yellow-700'],
                                    'ARCHIVED'  => ['label' => 'Arsip',     'cls' => 'bg-slate-100 text-slate-600'],
                                ];
                                $s = $statusMap[$a->status] ?? $statusMap['DRAFT'];
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-gray-900">{{ $a->title }}</p>
                                    @if ($a->description)
                                        <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $a->description }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $a->periode }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $s['cls'] }}">{{ $s['label'] }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $a->categories->count() }} kategori</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.assessment.show', $a->id) }}"
                                           class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">Detail</a>
                                        <a href="{{ route('admin.assessment.edit', $a->id) }}"
                                           class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">Edit</a>
                                        <form method="POST" action="{{ route('admin.assessment.destroy', $a->id) }}"
                                              onsubmit="return confirm('Hapus assessment ini?')">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-gray-500 font-medium text-sm">Belum ada assessment</p>
                <p class="text-xs text-gray-400 mt-1">Buat assessment pertama untuk memulai</p>
                <a href="{{ route('admin.assessment.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    Buat Assessment
                </a>
            </div>
        @endif
    </div>
</x-layouts.admin>
