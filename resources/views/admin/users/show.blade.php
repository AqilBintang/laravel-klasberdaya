{{--
    admin/users/show.blade.php
    Detail user kecamatan.
    Data: $id, $user (optional)
--}}
<x-layouts.admin pageTitle="Detail User">
    <div class="space-y-6">
        <div>
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-2xl font-bold text-gray-900">
                    {{ isset($user) ? $user->name : 'Detail User' }}
                </h2>
                @isset($user)
                    <a href="{{ route('admin.users.edit', $user->id) }}"
                       class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                @endisset
            </div>
        </div>

        @isset($user)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Info card --}}
                <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Informasi Akun</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="grid grid-cols-3 gap-3">
                            <dt class="text-gray-400">Nama</dt>
                            <dd class="col-span-2 font-medium text-gray-800">{{ $user->name }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <dt class="text-gray-400">Email</dt>
                            <dd class="col-span-2 text-gray-800">{{ $user->email }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <dt class="text-gray-400">Kecamatan</dt>
                            <dd class="col-span-2 text-gray-800">{{ $user->kecamatan ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <dt class="text-gray-400">Kabupaten/Kota</dt>
                            <dd class="col-span-2 text-gray-800">{{ $user->kabupaten ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <dt class="text-gray-400">Status</dt>
                            <dd class="col-span-2">
                                @if ($user->is_active)
                                    <span class="rounded-full bg-green-100 text-green-700 px-2.5 py-0.5 text-xs font-medium">Aktif</span>
                                @else
                                    <span class="rounded-full bg-gray-100 text-gray-600 px-2.5 py-0.5 text-xs font-medium">Non-aktif</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <dt class="text-gray-400">Terdaftar</dt>
                            <dd class="col-span-2 text-gray-800">{{ $user->created_at->format('d M Y') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Stats --}}
                <div class="space-y-3">
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Assessment</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $user->selfAssessments?->count() ?? 0 }}</p>
                        <p class="text-xs text-gray-400 mt-1">pengisian dilakukan</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Tervalidasi</p>
                        <p class="text-3xl font-bold text-green-700">
                            {{ $user->selfAssessments?->where('status', 'VALIDATED')->count() ?? 0 }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">sudah divalidasi</p>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                <p class="text-sm text-gray-500">User tidak ditemukan.</p>
            </div>
        @endisset
    </div>
</x-layouts.admin>
