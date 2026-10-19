{{--
    admin/dashboard.blade.php
    Halaman dashboard utama untuk Admin & Super Admin.
    Data: totalKecamatan, totalAssessmentPublished, totalMenunggu,
          totalDivalidasi, assessmentTerbaru, activityLog
--}}
<x-layouts.admin pageTitle="Dashboard">

    <div class="space-y-8">

        {{-- ── Heading ───────────────────────────────────────────────── --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Overview</h2>
            <p class="mt-1 text-sm text-gray-500">Ringkasan data platform klasifikasi kecamatan berdaya</p>
        </div>

        {{-- ── Statistic Cards ────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Total Kecamatan --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-sky-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalKecamatan }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Total Kecamatan</p>
                </div>
            </div>

            {{-- Assessment Published --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-green-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalAssessmentPublished }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Assessment Published</p>
                </div>
            </div>

            {{-- Menunggu Validasi --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-amber-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalMenunggu }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Menunggu Validasi</p>
                </div>
            </div>

            {{-- Sudah Divalidasi --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 flex items-center gap-4 shadow-sm">
                <div class="rounded-lg bg-emerald-50 p-3 shrink-0">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalDivalidasi }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Sudah Divalidasi</p>
                </div>
            </div>

        </div>

        {{-- ── Two-column grid: Assessment Terbaru + Log Aktivitas ────── --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            {{-- Assessment Terbaru --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Assessment Terbaru</h3>
                    <a href="{{ route('admin.assessment.create') }}"
                       class="text-xs text-sky-600 hover:text-sky-700 font-medium flex items-center gap-1">
                        Lihat Semua
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                @if ($assessmentTerbaru->isEmpty())
                    <p class="px-5 py-8 text-sm text-gray-400 text-center">Belum ada assessment.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($assessmentTerbaru as $a)
                            @php
                                $statusMap = [
                                    'DRAFT'     => ['label' => 'Draft',     'cls' => 'bg-gray-100 text-gray-600'],
                                    'PUBLISHED' => ['label' => 'Published', 'cls' => 'bg-green-100 text-green-700'],
                                    'REVISION'  => ['label' => 'Revisi',    'cls' => 'bg-yellow-100 text-yellow-700'],
                                    'ARCHIVED'  => ['label' => 'Arsip',     'cls' => 'bg-slate-100 text-slate-600'],
                                ];
                                $s = $statusMap[$a->status] ?? $statusMap['DRAFT'];
                            @endphp
                            <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ $a->title }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Periode {{ $a->periode }}
                                        · {{ $a->categories->count() }} kategori
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $s['cls'] }}">
                                    {{ $s['label'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Log Aktivitas --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Log Aktivitas</h3>
                    @if (auth()->user()?->isSuperAdmin())
                        <a href="{{ route('admin.audit-log') }}"
                           class="text-xs text-sky-600 hover:text-sky-700 font-medium flex items-center gap-1">
                            Lihat Semua
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @endif
                </div>

                @if ($activityLog->isEmpty())
                    <p class="px-5 py-8 text-sm text-gray-400 text-center">Belum ada aktivitas.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($activityLog as $log)
                            @php
                                // Mapping action ke warna ikon & label display
                                $actionMap = [
                                    'SUBMIT'           => ['dot' => 'bg-sky-400',    'label' => 'Pengisian'],
                                    'VALIDATE_APPROVE' => ['dot' => 'bg-green-400',  'label' => 'Disetujui'],
                                    'VALIDATE_REJECT'  => ['dot' => 'bg-red-400',    'label' => 'Ditolak'],
                                    'VALIDATE_REVISI'  => ['dot' => 'bg-yellow-400', 'label' => 'Revisi'],
                                    'CREATE'           => ['dot' => 'bg-indigo-400', 'label' => 'Dibuat'],
                                    'UPDATE'           => ['dot' => 'bg-amber-400',  'label' => 'Diupdate'],
                                    'DELETE'           => ['dot' => 'bg-rose-400',   'label' => 'Dihapus'],
                                    'LOGIN'            => ['dot' => 'bg-gray-400',   'label' => 'Login'],
                                ];
                                $a = $actionMap[$log->action] ?? ['dot' => 'bg-gray-300', 'label' => $log->action];
                                $who = $log->user?->kecamatan
                                    ? ($log->user->kecamatan . ($log->user->kabupaten ? ', ' . $log->user->kabupaten : ''))
                                    : ($log->user?->name ?? '—');
                            @endphp
                            <li class="flex items-start gap-3 px-5 py-3">
                                <span class="mt-1.5 inline-block h-2 w-2 shrink-0 rounded-full {{ $a['dot'] }}"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-800 leading-snug">
                                        <span class="font-medium">{{ $a['label'] }}</span>
                                        @if ($log->target_type)
                                            <span class="text-gray-400"> · {{ $log->target_type }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $who }}</p>
                                </div>
                                <time class="shrink-0 text-xs text-gray-400 whitespace-nowrap" datetime="{{ $log->created_at->toISOString() }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </time>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>

    </div>

</x-layouts.admin>
