{{--
    admin/assessment/results/detail.blade.php
    Detail pengisian assessment oleh satu kecamatan + ringkasan akhir.
    Data: $userId, $assessmentId, $periode, $user, $assessment,
          $entries (Collection, key: indicator_id),
          $catSummary, $totalScore, $maxScore, $statusAkhir, $pct
--}}
<x-layouts.admin pageTitle="Detail Assessment Kecamatan">
@php
    if (!function_exists('klasBadge')) {
        function klasBadge(?string $level, string $size = 'sm'): string {
            if (!$level) {
                $textCls = $size === 'lg' ? 'text-sm' : 'text-xs';
                return "<span class=\"{$textCls} text-gray-400 italic\">Tidak berlaku</span>";
            }
            $cfg = \App\Helpers\Scoring::getKlasifikasiConfig($level);
            $pad = $size === 'lg' ? 'px-4 py-1.5 text-sm' : 'px-2.5 py-0.5 text-xs';
            return '<span class="inline-flex items-center rounded-full font-semibold border '
                . e($cfg['bg']) . ' ' . e($cfg['color']) . ' ' . e($cfg['border']) . ' ' . $pad . '">'
                . e($level) . '</span>';
        }
    }
    if (!function_exists('customLabelBadge')) {
        function customLabelBadge(string $label, string $size = 'sm'): string {
            $pad = $size === 'lg' ? 'px-4 py-1.5 text-sm' : 'px-2.5 py-0.5 text-xs';
            return "<span class=\"inline-flex items-center rounded-full font-semibold border border-amber-200 bg-amber-50 text-amber-800 {$pad}\">"
                . e($label) . '</span>';
        }
    }
@endphp

<div class="space-y-6">

    {{-- Back + Title --}}
    <div>
        <a href="{{ route('admin.assessment.results.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-3">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Rekapitulasi
        </a>
        <h2 class="text-2xl font-bold text-gray-900">Detail Assessment Kecamatan</h2>
    </div>

    {{-- Header info card --}}
    @isset($user, $assessment)
    <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">
                    {{ $assessment->title }}
                    @if ($periode)
                        - Periode {{ $periode }}
                    @endif
                </p>
                <h3 class="text-xl font-bold text-gray-900">
                    {{ $user->kecamatan ?? $user->name }}
                </h3>
                @if ($user->kabupaten)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $user->kabupaten }}</p>
                @endif
            </div>
            <div class="text-right shrink-0">
                <p class="text-xs text-gray-400 mb-1">Total Skor</p>
                <p class="text-3xl font-black text-gray-900">
                    {{ $totalScore }}<span class="text-base font-normal text-gray-400">/{{ $maxScore }}</span>
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $pct }}%</p>
            </div>
        </div>
        {{-- Progress bar --}}
        <div class="mt-4">
            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full rounded-full bg-sky-500 transition-all" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </div>
    @endisset

    {{-- Per-kategori --}}
    @isset($assessment)
        @foreach ($assessment->categories->sortBy('order') as $category)
            @php
                $catSum = collect($catSummary ?? [])->firstWhere('code', $category->code);
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">

                {{-- Category header --}}
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-sm font-bold text-white shrink-0">
                            {{ $category->code }}
                        </span>
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">{{ $category->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $category->indicators->count() }} indikator
                                @if ($catSum)
                                    - {{ $catSum['totalScore'] }}/{{ $catSum['maxScore'] }} poin
                                @endif
                            </p>
                        </div>
                    </div>
                    @if ($catSum)
                        @if ($catSum['customLabel'])
                            {!! customLabelBadge($catSum['customLabel']) !!}
                        @else
                            {!! klasBadge($catSum['klasifikasi']) !!}
                        @endif
                    @endif
                </div>

                {{-- Indicators table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500">
                                <th class="px-5 py-2.5 text-left w-6">#</th>
                                <th class="px-3 py-2.5 text-left">Indikator</th>
                                <th class="px-3 py-2.5 text-left">Deskripsi Jawaban</th>
                                <th class="px-3 py-2.5 text-center whitespace-nowrap">Skor Kecamatan</th>
                                <th class="px-3 py-2.5 text-center whitespace-nowrap">Skor Validator</th>
                                <th class="px-3 py-2.5 text-left">Komentar Tim Teknis</th>
                                <th class="px-3 py-2.5 text-center">Dokumen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($category->indicators->sortBy('number') as $ind)
                                @php
                                    $entry      = isset($entries) ? $entries->get($ind->id) : null;
                                    $validation = $entry?->validations->sortByDesc('validated_at')->first();
                                    $valScore   = $validation?->validated_score;
                                    $changed    = $valScore !== null && $valScore !== $entry->score;
                                @endphp
                                <tr class="align-top hover:bg-gray-50/60">
                                    <td class="px-5 py-3.5 text-xs text-gray-400 font-medium">{{ $ind->number }}</td>

                                    <td class="px-3 py-3.5 text-gray-700 max-w-[200px]">{{ $ind->indicator }}</td>

                                    <td class="px-3 py-3.5 text-gray-600 max-w-[220px]">
                                        @if ($entry && $entry->description && $entry->description !== '-')
                                            <span class="leading-relaxed">{{ $entry->description }}</span>
                                        @else
                                            <span class="text-gray-300 italic text-xs">Tidak diisi</span>
                                        @endif
                                    </td>

                                    {{-- Skor kecamatan --}}
                                    <td class="px-3 py-3.5 text-center">
                                        @if ($entry)
                                            <span class="{{ $changed ? 'text-gray-400 line-through text-xs' : 'font-bold text-gray-900' }} tabular-nums">{{ $entry->score }}</span><span class="text-gray-300 text-xs">/{{ $ind->max_score }}</span>
                                        @else
                                            <span class="text-gray-300 text-xs">N/A</span>
                                        @endif
                                    </td>

                                    {{-- Skor validator --}}
                                    <td class="px-3 py-3.5 text-center">
                                        @if ($valScore !== null)
                                            <span class="{{ $changed ? 'text-sky-700 font-bold' : 'text-gray-400' }} tabular-nums">{{ $valScore }}<span class="text-gray-300 text-xs font-normal">/{{ $ind->max_score }}</span></span>
                                        @else
                                            <span class="text-gray-300 text-xs">N/A</span>
                                        @endif
                                    </td>

                                    {{-- Komentar --}}
                                    <td class="px-3 py-3.5 text-gray-600 max-w-[220px]">
                                        @if ($validation?->notes)
                                            <span class="text-xs leading-relaxed">{{ $validation->notes }}</span>
                                        @else
                                            <span class="text-gray-300 text-xs">N/A</span>
                                        @endif
                                    </td>

                                    {{-- Dokumen --}}
                                    <td class="px-3 py-3.5 text-center">
                                        @php $doc = $entry?->supporting_doc; @endphp
                                        @if ($doc && $doc !== 'NO_DOC' && $doc !== 'GDRIVE_PENDING')
                                            @php
                                                $adminDocLinks = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $doc))));
                                            @endphp
                                            <div class="flex flex-col items-center gap-1">
                                                @foreach ($adminDocLinks as $i => $adminLink)
                                                    <a href="{{ $adminLink }}" target="_blank" rel="noopener noreferrer"
                                                       class="inline-flex items-center gap-1 text-xs text-sky-600 hover:underline whitespace-nowrap">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                        </svg>
                                                        Buka{{ count($adminDocLinks) > 1 ? ' ' . ($i + 1) : '' }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @elseif ($doc === 'NO_DOC')
                                            <span class="text-gray-400 text-xs italic">Tanpa dok.</span>
                                        @else
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @else
        <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
            <p class="text-sm text-gray-500">Data tidak ditemukan.</p>
        </div>
    @endisset

    {{-- Ringkasan Akhir --}}
    @if (!empty($catSummary))
    <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
        <h3 class="font-semibold text-gray-900 mb-4">Rekapitulasi Nilai</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs font-semibold text-gray-500">
                    <th class="pb-2 text-left">Kategori</th>
                    <th class="pb-2 text-center">Skor</th>
                    <th class="pb-2 text-center">Maks</th>
                    <th class="pb-2 text-right">Klasifikasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($catSummary as $cat)
                    <tr>
                        <td class="py-2.5 text-gray-700">{{ $cat['code'] }}. {{ $cat['name'] }}</td>
                        <td class="py-2.5 text-center font-bold text-gray-900">{{ $cat['totalScore'] }}</td>
                        <td class="py-2.5 text-center text-gray-400">{{ $cat['maxScore'] }}</td>
                        <td class="py-2.5 text-right">
                            @if ($cat['customLabel'])
                                {!! customLabelBadge($cat['customLabel']) !!}
                            @else
                                {!! klasBadge($cat['klasifikasi']) !!}
                            @endif
                        </td>
                    </tr>
                @endforeach

                {{-- Total row --}}
                <tr class="border-t-2 border-gray-200 font-bold">
                    <td class="pt-3 text-gray-900">Total Keseluruhan</td>
                    <td class="pt-3 text-center text-gray-900 text-base">{{ $totalScore }}</td>
                    <td class="pt-3 text-center text-gray-500">{{ $maxScore }}</td>
                    <td class="pt-3 text-right">{!! klasBadge($statusAkhir ?? null, 'lg') !!}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    {{-- Aksi admin: kembalikan hasil verifikasi tim teknis --}}
    @if (!empty($canReturn) && !empty($periode))
        <div class="rounded-xl border border-amber-200 bg-amber-50/40 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-amber-100 bg-amber-50/70">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Aksi Admin: Kembalikan Hasil Verifikasi
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    Hasil verifikasi tim teknis sudah selesai. Jika hasil ini perlu dicek ulang atau diperbaiki kecamatan,
                    kembalikan submission berikut. Alasan wajib diisi dan akan tercatat di Audit Log.
                </p>
            </div>

            <div class="px-6 py-5">
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button"
                            x-data=""
                            x-on:click="$dispatch('open-modal', 'confirm-reverify')"
                            class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Verifikasi Ulang
                    </button>
                    <button type="button"
                            x-data=""
                            x-on:click="$dispatch('open-modal', 'confirm-revision')"
                            class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Kembalikan ke Kecamatan
                    </button>
                    <span class="text-xs text-gray-400">Periode {{ $periode }}</span>
                </div>
            </div>
        </div>

        {{-- Modal: Konfirmasi Verifikasi Ulang --}}
        <x-modal name="confirm-reverify" :show="old('action') === 'reverify' && $errors->any()" focusable>
            <form method="POST" action="{{ route('admin.assessment.results.return', [$userId, $assessmentId]) }}" class="p-6">
                @csrf
                <input type="hidden" name="periode" value="{{ $periode }}">
                <input type="hidden" name="action" value="reverify">

                <h2 class="text-lg font-semibold text-gray-900">Konfirmasi Verifikasi Ulang</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Hasil verifikasi akan dikembalikan ke antrian validator untuk diverifikasi ulang.
                    Snapshot hasil dihapus sementara dan muncul lagi setelah re-validasi selesai.
                </p>

                <div class="mt-4">
                    <label for="reason-reverify" class="block text-sm font-medium text-gray-700 mb-1">Alasan <span class="text-red-500">*</span></label>
                    <textarea id="reason-reverify" name="reason" rows="3" required minlength="5" maxlength="2000"
                              placeholder="Contoh: skor indikator A3 perlu dicek ulang karena dokumen pendukung baru diterima."
                              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-sky-400 focus:ring-1 focus:ring-sky-400 outline-none">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Ya, Verifikasi Ulang
                    </button>
                </div>
            </form>
        </x-modal>

        {{-- Modal: Konfirmasi Kembalikan ke Kecamatan --}}
        <x-modal name="confirm-revision" :show="old('action') === 'revision' && $errors->any()" focusable>
            <form method="POST" action="{{ route('admin.assessment.results.return', [$userId, $assessmentId]) }}" class="p-6">
                @csrf
                <input type="hidden" name="periode" value="{{ $periode }}">
                <input type="hidden" name="action" value="revision">

                <h2 class="text-lg font-semibold text-gray-900">Konfirmasi Kembalikan ke Kecamatan</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Hasil verifikasi akan dikembalikan ke kecamatan untuk diperbaiki. Kecamatan harus mengisi ulang,
                    lalu kabupaten menyetujui dan validator memvalidasi ulang.
                </p>

                <div class="mt-4">
                    <label for="reason-revision" class="block text-sm font-medium text-gray-700 mb-1">Alasan <span class="text-red-500">*</span></label>
                    <textarea id="reason-revision" name="reason" rows="3" required minlength="5" maxlength="2000"
                              placeholder="Contoh: dokumen pendukung indikator B2 belum lengkap, mohon dilengkapi."
                              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-amber-400 focus:ring-1 focus:ring-amber-400 outline-none">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">Alasan ini ditampilkan ke kecamatan sebagai catatan perbaikan per indikator.</p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Ya, Kembalikan ke Kecamatan
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

</div>
</x-layouts.admin>
