{{--
    admin/manage-admin.blade.php
    Daftar user internal: SUPER_ADMIN, ADMIN, VALIDATOR.
    Hanya SUPER_ADMIN yang bisa akses.
    Data: $admins (Collection<User>)
--}}
<x-layouts.admin pageTitle="Manajemen Admin">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Manajemen Admin</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola akun admin dan validator platform</p>
            </div>
            <a href="{{ route('admin.manage-admin.create') }}"
               class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Akun
            </a>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->has('delete') || $errors->has('role'))
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('delete') ?: $errors->first('role') }}
            </div>
        @endif

        {{-- Warning --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-amber-700">
                    Halaman ini hanya untuk Super Admin. Perubahan berpengaruh langsung pada akses seluruh platform.
                </p>
            </div>
        </div>

        @if (isset($admins) && $admins->isNotEmpty())
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nama</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Role</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Bergabung</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($admins as $a)
                            @php
                                $roleMap = [
                                    'SUPER_ADMIN' => ['label' => 'Super Admin', 'cls' => 'bg-red-100 text-red-700'],
                                    'ADMIN'       => ['label' => 'Admin',       'cls' => 'bg-blue-100 text-blue-700'],
                                    'VALIDATOR'   => ['label' => 'Tim Teknis',  'cls' => 'bg-purple-100 text-purple-700'],
                                ];
                                $r = $roleMap[$a->role] ?? ['label' => $a->role, 'cls' => 'bg-gray-100 text-gray-600'];
                                $isSelf      = $a->id === auth()->id();
                                $isSuperAdmin = $a->role === 'SUPER_ADMIN';
                                $canEdit     = !$isSuperAdmin || $isSelf;
                                $canDelete   = !$isSuperAdmin && !$isSelf;
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 font-medium text-gray-900">
                                    {{ $a->name }}
                                    @if ($isSelf)
                                        <span class="ml-1.5 text-xs text-gray-400">(Anda)</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $a->email }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $r['cls'] }}">
                                        {{ $r['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($a->is_active)
                                        <span class="rounded-full bg-green-100 text-green-700 px-2.5 py-0.5 text-xs font-medium">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 text-gray-500 px-2.5 py-0.5 text-xs font-medium">Non-aktif</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-500 text-xs">
                                    {{ $a->created_at?->format('d M Y') }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($canEdit)
                                            <a href="{{ route('admin.manage-admin.edit', $a->id) }}"
                                               class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                                                Edit
                                            </a>
                                        @else
                                            <span class="px-3 py-1.5 text-xs text-gray-300">—</span>
                                        @endif

                                        @if ($canDelete)
                                            <form method="POST"
                                                  action="{{ route('admin.manage-admin.destroy', $a->id) }}"
                                                  onsubmit="return confirm('Hapus akun {{ addslashes($a->name) }}? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="px-3 py-1.5 text-xs text-gray-300">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-12 text-center">
                <p class="text-sm text-gray-500">Belum ada data admin/validator.</p>
                <a href="{{ route('admin.manage-admin.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    Tambah Akun Pertama
                </a>
            </div>
        @endif

    </div>
</x-layouts.admin>
