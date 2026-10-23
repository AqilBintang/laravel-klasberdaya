{{--
    admin/faq/index.blade.php
    Daftar kategori FAQ. Admin bisa tambah/edit/hapus kategori,
    dan masuk ke halaman kelola pertanyaan per kategori.
    Data: $categories (Collection<FaqCategory> with items_count)
--}}
<x-layouts.admin pageTitle="Kelola FAQ">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Kelola FAQ</h2>
                <p class="mt-1 text-sm text-gray-500">Atur kategori dan pertanyaan yang tampil di halaman FAQ publik</p>
            </div>
            <a href="{{ route('landing.faq') }}" target="_blank"
               class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Lihat FAQ Publik
            </a>
        </div>

        {{-- Flash --}}
        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Info --}}
        <div class="flex items-start gap-2 rounded-md bg-sky-50 border border-sky-200 px-3 py-2 text-xs text-sky-700">
            <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Gunakan <strong>Urutan</strong> untuk mengatur posisi tampil kategori. Angka lebih kecil tampil lebih dulu. Klik nama kategori untuk mengelola pertanyaan di dalamnya.</span>
        </div>

        <div x-data="{ modalAdd: false, modalEdit: false, editData: {} }" class="space-y-4">

            {{-- ── TOMBOL TAMBAH KATEGORI ─────────────────────────────────────── --}}
            <div class="flex justify-end">
                <button type="button"
                        @click="modalAdd = true"
                        class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Kategori
                </button>
            </div>

            {{-- ── TABEL KATEGORI ──────────────────────────────────────────────── --}}
            @if ($categories->isNotEmpty())
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-12">No</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nama Kategori</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">Jumlah Pertanyaan</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-20">Urutan</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($categories as $i => $cat)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-4 text-gray-400 text-xs">{{ $i + 1 }}</td>
                                    <td class="px-5 py-4">
                                        <a href="{{ route('admin.faq.category.show', $cat) }}"
                                           class="font-medium text-sky-600 hover:text-sky-700 hover:underline">
                                            {{ $cat->name }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="rounded-full bg-gray-100 text-gray-600 px-2.5 py-0.5 text-xs font-medium">
                                            {{ $cat->items_count }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center text-gray-600 text-xs">{{ $cat->sort_order }}</td>
                                    <td class="px-5 py-4 text-center">
                                        @if ($cat->is_active)
                                            <span class="rounded-full bg-green-100 text-green-700 px-2.5 py-0.5 text-xs font-medium">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-gray-100 text-gray-500 px-2.5 py-0.5 text-xs font-medium">Non-aktif</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Kelola Pertanyaan --}}
                                            <a href="{{ route('admin.faq.category.show', $cat) }}"
                                               class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                                                Kelola Pertanyaan
                                            </a>

                                            {{-- Edit Kategori --}}
                                            <button type="button"
                                                    @click="modalEdit = true; editData = {
                                                        id:         {{ $cat->id }},
                                                        name:       '{{ addslashes($cat->name) }}',
                                                        sort_order: {{ $cat->sort_order }},
                                                        is_active:  {{ $cat->is_active ? 'true' : 'false' }}
                                                    }"
                                                    class="rounded-lg border border-sky-200 bg-white px-3 py-1.5 text-xs font-medium text-sky-600 hover:bg-sky-50 transition-colors">
                                                Edit
                                            </button>

                                            {{-- Hapus Kategori --}}
                                            <form method="POST"
                                                  action="{{ route('admin.faq.category.destroy', $cat) }}"
                                                  onsubmit="return confirm('Hapus kategori \'{{ addslashes($cat->name) }}\' beserta {{ $cat->items_count }} pertanyaan di dalamnya?')">
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
                <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                    <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-500">Belum ada kategori FAQ.</p>
                    <p class="text-xs text-gray-400 mt-1">Tambahkan kategori pertama untuk mulai mengisi pertanyaan FAQ.</p>
                    <button type="button" @click="modalAdd = true"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                        Tambah Kategori Pertama
                    </button>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════════════
                 MODAL: TAMBAH KATEGORI
            ═══════════════════════════════════════════════════════════════ --}}
            <div x-show="modalAdd"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                 @keydown.escape.window="modalAdd = false">

                <div class="w-full max-w-md rounded-2xl bg-white shadow-xl"
                     @click.outside="modalAdd = false">
                    <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Tambah Kategori FAQ</h3>
                        <button type="button" @click="modalAdd = false"
                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                                aria-label="Tutup modal">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('admin.faq.category.store') }}" class="px-6 py-5 space-y-4">
                        @csrf

                        <div>
                            <label for="add_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Kategori <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="add_name" name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Contoh: Umum, Penilaian & Klasifikasi"
                                   required
                                   class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="add_sort_order" class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                                <input type="number" id="add_sort_order" name="sort_order"
                                       value="{{ old('sort_order', 0) }}" min="0"
                                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                <p class="text-xs text-gray-400 mt-1">Angka lebih kecil = tampil lebih dulu</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="flex items-center gap-2 mt-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" id="add_is_active" name="is_active" value="1"
                                           checked
                                           class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                    <label for="add_is_active" class="text-sm text-gray-700">Aktif</label>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                            <button type="button" @click="modalAdd = false"
                                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    class="rounded-lg bg-sky-600 px-5 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                                Simpan Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 MODAL: EDIT KATEGORI
            ═══════════════════════════════════════════════════════════════ --}}
            <div x-show="modalEdit"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                 @keydown.escape.window="modalEdit = false">

                <div class="w-full max-w-md rounded-2xl bg-white shadow-xl"
                     @click.outside="modalEdit = false">
                    <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Edit Kategori FAQ</h3>
                        <button type="button" @click="modalEdit = false"
                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                                aria-label="Tutup modal">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST"
                          :action="'{{ url('admin/faq/categories') }}/' + editData.id"
                          class="px-6 py-5 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Kategori <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="edit_name" name="name"
                                   :value="editData.name"
                                   placeholder="Nama kategori"
                                   required
                                   class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="edit_sort_order" class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                                <input type="number" id="edit_sort_order" name="sort_order"
                                       :value="editData.sort_order" min="0"
                                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="flex items-center gap-2 mt-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" id="edit_is_active" name="is_active" value="1"
                                           :checked="editData.is_active"
                                           class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                    <label for="edit_is_active" class="text-sm text-gray-700">Aktif</label>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                            <button type="button" @click="modalEdit = false"
                                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    class="rounded-lg bg-sky-600 px-5 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                                Perbarui Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>{{-- end x-data --}}

    </div>
</x-layouts.admin>
