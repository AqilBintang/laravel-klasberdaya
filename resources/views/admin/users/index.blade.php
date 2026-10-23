{{--
    admin/users/index.blade.php
    Daftar user kecamatan yang terdaftar.
    Data: $users (Collection<User>)
--}}
<x-layouts.admin pageTitle="Manage User">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Manage User Kecamatan</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola akun kecamatan yang dapat mengisi self assessment</p>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah User
            </a>
        </div>

        @if (isset($users) && $users->isNotEmpty())
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nama</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Kecamatan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Kabupaten/Kota</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $u)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-gray-900">{{ $u->name }}</p>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $u->email }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $u->kecamatan ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $u->kabupaten ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($u->is_active)
                                        <span class="rounded-full bg-green-100 text-green-700 px-2.5 py-0.5 text-xs font-medium">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 text-gray-600 px-2.5 py-0.5 text-xs font-medium">Non-aktif</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.users.show', $u->id) }}"
                                           class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">Detail</a>
                                        <a href="{{ route('admin.users.edit', $u->id) }}"
                                           class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">Edit</a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}"
                                              onsubmit="return confirm('Hapus user ini?')">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p class="text-gray-500 font-medium text-sm">Belum ada user terdaftar</p>
                <a href="{{ route('admin.users.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    Tambah User Pertama
                </a>
            </div>
        @endif
    </div>
</x-layouts.admin>
