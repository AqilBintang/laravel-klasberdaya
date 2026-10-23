{{--
    admin/manage-admin-edit.blade.php
    Form edit akun admin / validator.
    Hanya SUPER_ADMIN yang bisa akses.
    Data: $id, $user
--}}
<x-layouts.admin pageTitle="Edit Akun Admin">
    <div class="space-y-6">

        {{-- Breadcrumb --}}
        <div>
            <a href="{{ route('admin.manage-admin.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Daftar
            </a>
            <h2 class="text-2xl font-bold text-gray-900">Edit Akun</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $user->email }}</p>
        </div>

        <form method="POST" action="{{ route('admin.manage-admin.update', $id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
                <h3 class="text-sm font-semibold text-gray-800">Informasi Akun</h3>

                {{-- Nama --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           value="{{ old('name', $user->name) }}"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('name') border-red-300 @enderror">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" required
                           value="{{ old('email', $user->email) }}"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('email') border-red-300 @enderror">
                    @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Role — super admin tidak bisa ubah rolenya sendiri --}}
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                        Role <span class="text-red-500">*</span>
                    </label>
                    @if ($user->role === 'SUPER_ADMIN')
                        <input type="hidden" name="role" value="SUPER_ADMIN">
                        <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500">
                            Super Admin
                            <span class="ml-2 text-xs text-gray-400">(tidak dapat diubah)</span>
                        </div>
                    @else
                        <select id="role" name="role" required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('role') border-red-300 @enderror">
                            <option value="ADMIN"     {{ old('role', $user->role) === 'ADMIN'     ? 'selected' : '' }}>Admin</option>
                            <option value="VALIDATOR" {{ old('role', $user->role) === 'VALIDATOR' ? 'selected' : '' }}>Tim Teknis (Validator)</option>
                        </select>
                        @error('role')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    @endif
                </div>

                {{-- Status aktif --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Akun</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="is_active" value="1"
                                   {{ old('is_active', $user->is_active ? '1' : '0') === '1' ? 'checked' : '' }}
                                   class="accent-sky-600">
                            <span class="text-sm text-gray-700">Aktif</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="is_active" value="0"
                                   {{ old('is_active', $user->is_active ? '1' : '0') === '0' ? 'checked' : '' }}
                                   class="accent-sky-600">
                            <span class="text-sm text-gray-700">Non-aktif</span>
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Akun non-aktif tidak dapat login.</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Ganti Password</h3>
                    <span class="text-xs text-gray-400">Kosongkan jika tidak ingin mengganti password</span>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password Baru
                    </label>
                    <input type="password" id="password" name="password"
                           autocomplete="new-password"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('password') border-red-300 @enderror">
                    <p class="mt-1 text-xs text-gray-400">Minimal 8 karakter.</p>
                    @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        Konfirmasi Password Baru
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           autocomplete="new-password"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.manage-admin.index') }}"
                   class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
