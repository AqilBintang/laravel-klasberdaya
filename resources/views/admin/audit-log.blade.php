{{--
    admin/audit-log.blade.php
    Halaman log aktivitas platform (audit trail).
    Data: $logs (Collection<AuditLog> paginated, optional)
--}}
<x-layouts.admin pageTitle="Audit Log">
    <div class="space-y-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-900">Audit Log</h2>
            <p class="mt-1 text-sm text-gray-500">Riwayat aktivitas seluruh pengguna platform</p>
        </div>

        @if (isset($logs) && $logs->isNotEmpty())
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Waktu</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Pengguna</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Target</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($logs as $log)
                            @php
                                $actionMap = [
                                    'SUBMIT'           => ['dot' => 'bg-sky-400',    'label' => 'Submit'],
                                    'VALIDATE_APPROVE' => ['dot' => 'bg-green-400',  'label' => 'Approve'],
                                    'VALIDATE_REJECT'  => ['dot' => 'bg-red-400',    'label' => 'Tolak'],
                                    'VALIDATE_REVISI'  => ['dot' => 'bg-yellow-400', 'label' => 'Revisi'],
                                    'CREATE'           => ['dot' => 'bg-indigo-400', 'label' => 'Buat'],
                                    'UPDATE'           => ['dot' => 'bg-amber-400',  'label' => 'Update'],
                                    'DELETE'           => ['dot' => 'bg-rose-400',   'label' => 'Hapus'],
                                    'LOGIN'            => ['dot' => 'bg-gray-400',   'label' => 'Login'],
                                ];
                                $a = $actionMap[$log->action] ?? ['dot' => 'bg-gray-300', 'label' => $log->action];
                                $who = $log->user?->kecamatan
                                    ? ($log->user->kecamatan . ($log->user->kabupaten ? ', ' . $log->user->kabupaten : ''))
                                    : ($log->user?->name ?? '—');
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-xs text-gray-700">{{ $log->created_at->format('d M Y') }}</p>
                                    <p class="text-xs text-gray-400">{{ $log->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-gray-800 text-sm">{{ $who }}</p>
                                    @isset($log->user)
                                        <p class="text-xs text-gray-400">{{ $log->user->email }}</p>
                                    @endisset
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block h-2 w-2 rounded-full shrink-0 {{ $a['dot'] }}"></span>
                                        <span class="font-medium text-gray-700 text-sm">{{ $a['label'] }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    @if ($log->target_type)
                                        <span class="text-xs bg-gray-100 rounded px-1.5 py-0.5">{{ $log->target_type }}</span>
                                        @if ($log->target_id)
                                            <span class="text-xs text-gray-400 ml-1">#{{ $log->target_id }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-500 text-xs max-w-xs truncate">
                                    {{ $log->details ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (method_exists($logs, 'links'))
                <div>{{ $logs->links() }}</div>
            @endif
        @else
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500 font-medium text-sm">Belum ada aktivitas tercatat</p>
            </div>
        @endif

    </div>
</x-layouts.admin>
