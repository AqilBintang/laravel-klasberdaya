{{--
    admin/backup/index.blade.php
    Halaman backup dan restore data (Super Admin only).
--}}
<x-layouts.admin pageTitle="Backup Data">
    <div class="space-y-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-900">Backup Data</h2>
            <p class="mt-1 text-sm text-gray-500">Export dan import data platform (hanya Super Admin)</p>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Warning --}}
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-medium text-red-800">Perhatian</p>
                    <p class="text-sm text-red-700 mt-0.5">
                        Operasi backup dan restore dapat mempengaruhi data platform secara keseluruhan.
                        Pastikan Anda memahami konsekuensinya sebelum melanjutkan.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Export --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="rounded-lg bg-sky-50 p-2.5 shrink-0">
                        <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Export Backup</h3>
                        <p class="text-xs text-gray-500">Unduh snapshot data platform</p>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mb-5">
                    Export data mencakup semua assessment, self-assessment, user, dan konfigurasi sistem
                    dalam format JSON terkompresi.
                </p>

                <form method="POST" action="{{ route('admin.backup.export') }}">
                    @csrf
                    <div class="space-y-3 mb-5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="format" value="json" checked class="text-sky-600">
                            <span class="text-sm text-gray-700">JSON (lengkap)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="format" value="csv" class="text-sky-600">
                            <span class="text-sm text-gray-700">CSV (data tabular saja)</span>
                        </label>
                    </div>
                    <button type="submit"
                            class="w-full rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                        Export Sekarang
                    </button>
                </form>
            </div>

            {{-- Import --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="rounded-lg bg-amber-50 p-2.5 shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Import / Restore</h3>
                        <p class="text-xs text-gray-500">Pulihkan data dari file backup</p>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mb-5">
                    <strong class="text-red-700">Perhatian:</strong> Proses import akan menimpa data yang sudah ada.
                    Pastikan Anda sudah membuat backup sebelum melanjutkan.
                </p>

                <form method="POST" action="{{ route('admin.backup.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Backup (.json)</label>
                        <input type="file" name="backup_file" accept=".json" required
                               class="text-sm text-gray-600
                                      file:mr-3 file:rounded-lg file:border file:border-gray-200
                                      file:bg-white file:px-3 file:py-1.5 file:text-xs
                                      file:font-medium file:text-gray-600 hover:file:bg-gray-50">
                    </div>
                    <button type="submit"
                            onclick="return confirm('Yakin ingin merestore data? Ini akan menimpa data yang ada.')"
                            class="w-full rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-amber-700 transition-colors">
                        Import & Restore
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-layouts.admin>
