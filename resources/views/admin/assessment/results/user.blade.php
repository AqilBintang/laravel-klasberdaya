{{--
    admin/assessment/results/user.blade.php
    Daftar assessment yang diisi oleh satu kecamatan.
    Data: $userId, $user (optional), $entriesResult (optional)
--}}
<x-layouts.admin pageTitle="Detail Kecamatan">
    <div class="space-y-6">
        <div>
            <a href="{{ route('admin.assessment.results.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Rekapitulasi
            </a>
            <h2 class="text-2xl font-bold text-gray-900">
                {{ isset($user) ? ($user->kecamatan ?? $user->name) : 'Detail Kecamatan' }}
            </h2>
            @isset($user)
                <p class="mt-1 text-sm text-gray-500">{{ $user->kabupaten ?? '' }}</p>
            @endisset
        </div>

        @if (isset($entriesResult) && count($entriesResult) > 0)
            <div class="grid grid-cols-1 gap-4">
                @foreach ($entriesResult as $entry)
                    <a href="{{ route('admin.assessment.results.detail', [$userId, $entry['assessmentId']]) }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 hover:border-sky-300 hover:shadow-sm transition-all group block">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 group-hover:text-sky-600 transition-colors">{{ $entry['assessmentTitle'] }}</p>
                                <p class="text-xs text-gray-400 mt-1">Periode: {{ $entry['periode'] }}</p>
                            </div>
                            @php
                                $kls = $entry['statusAkhir'] ?? null;
                                $klsMap = [
                                    'Rintisan'   => 'bg-red-100 text-red-700',
                                    'Berkembang' => 'bg-amber-100 text-amber-700',
                                    'Maju'       => 'bg-blue-100 text-blue-700',
                                    'Unggul'     => 'bg-green-100 text-green-700',
                                ];
                                $klsCls = $klsMap[$kls] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $klsCls }}">
                                {{ $kls ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-sm">
                            <span class="text-gray-500">
                                Skor: {{ $entry['totalScore'] ?? 'N/A' }} / {{ $entry['maxScore'] ?? 'N/A' }}
                            </span>
                            <span class="text-sky-600 font-medium flex items-center gap-1">
                                Lihat Detail
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <p class="text-sm text-gray-500">Belum ada data assessment untuk kecamatan ini.</p>
            </div>
        @endif
    </div>
</x-layouts.admin>
