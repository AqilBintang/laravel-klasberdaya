{{--
    admin/faq/category.blade.php
    Kelola pertanyaan dalam satu kategori FAQ.
    Admin bisa tambah, edit, dan hapus pertanyaan.
    Data: $category (FaqCategory), $items (Collection<FaqItem>)
--}}
<x-layouts.admin pageTitle="Pertanyaan FAQ — {{ $category->name }}">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                    <a href="{{ route('admin.faq.index') }}" class="hover:text-sky-600 transition-colors">Kelola FAQ</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-gray-900 font-medium">{{ $category->name }}</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $category->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Kelola pertanyaan dan jawaban dalam kategori ini
                    <span class="ml-1 rounded-full {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} px-2 py-0.5 text-xs font-medium">
                        {{ $category->is_active ? 'Aktif' : 'Non-aktif' }}
                    </span>
                </p>
            </div>
            <a href="{{ route('admin.faq.index') }}"
               class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Daftar Kategori
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

        <div x-data="{ modalAdd: false, modalEdit: false, editData: {} }" class="space-y-4">

            {{-- Tombol tambah pertanyaan --}}
            <div class="flex justify-end">
                <button type="button"
                        @click="modalAdd = true"
                        class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Pertanyaan
                </button>
            </div>

            {{-- Info urutan --}}
            <div class="flex items-start gap-2 rounded-md bg-sky-50 border border-sky-200 px-3 py-2 text-xs text-sky-700">
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Pertanyaan ditampilkan urut berdasarkan <strong>Urutan</strong>. Angka lebih kecil tampil lebih dulu. Pertanyaan non-aktif tidak tampil di halaman publik.</span>
            </div>

            {{-- ── DAFTAR PERTANYAAN ──────────────────────────────────────────── --}}
            @if ($items->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($items as $i => $item)
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-5">
                            <div class="flex items-start gap-4">

                                {{-- Nomor & status --}}
                                <div class="flex flex-col items-center gap-1.5 shrink-0 pt-0.5">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 text-xs font-bold text-sky-700">
                                        {{ $i + 1 }}
                                    </span>
                                    @if ($item->is_active)
                                        <span class="text-[10px] text-green-600 font-medium">Aktif</span>
                                    @else
                                        <span class="text-[10px] text-gray-400 font-medium">Non-aktif</span>
                                    @endif
                                </div>

                                {{-- Konten --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 text-sm leading-snug">{{ $item->question }}</p>
                                    <p class="mt-1.5 text-sm text-gray-500 leading-relaxed">{{ $item->answer }}</p>
                                    <p class="mt-2 text-xs text-gray-400">Urutan: {{ $item->sort_order }}</p>
                                </div>

                                {{-- Aksi --}}
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button"
                                            @click="modalEdit = true; editData = {
                                                id:         {{ $item->id }},
                                                question:   {{ json_encode($item->question) }},
                                                answer:     {{ json_encode($item->answer) }},
                                                sort_order: {{ $item->sort_order }},
                                                is_active:  {{ $item->is_active ? 'true' : 'false' }}
                                            }"
                                            class="rounded-lg border border-sky-200 bg-white px-3 py-1.5 text-xs font-medium text-sky-600 hover:bg-sky-50 transition-colors">
                                        Edit
                                    </button>

                                    <form method="POST"
                                          action="{{ route('admin.faq.category.item.destroy', [$category, $item]) }}"
                                          onsubmit="return confirm('Hapus pertanyaan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-16 text-center">
                    <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-500">Belum ada pertanyaan di kategori ini.</p>
                    <button type="button" @click="modalAdd = true"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                        Tambah Pertanyaan Pertama
                    </button>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════════════════════
                 MODAL: TAMBAH PERTANYAAN
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

                <div class="w-full max-w-xl rounded-2xl bg-white shadow-xl max-h-[90vh] overflow-y-auto"
                     @click.outside="modalAdd = false">
                    <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                        <h3 class="text-base font-semibold text-gray-900">Tambah Pertanyaan FAQ</h3>
                        <button type="button" @click="modalAdd = false"
                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                                aria-label="Tutup modal">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST"
                          action="{{ route('admin.faq.category.item.store', $category) }}"
                          class="px-6 py-5 space-y-4">
                        @csrf

                        <div>
                            <label for="add_question" class="block text-sm font-medium text-gray-700 mb-1">
                                Pertanyaan <span class="text-red-500">*</span>
                            </label>
                            <textarea id="add_question" name="question" rows="2" required
                                      placeholder="Contoh: Apa itu Klasifikasi Indeks Kecamatan Berdaya?"
                                      class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 resize-y">{{ old('question') }}</textarea>
                        </div>

                        <div>
                            <label for="add_answer" class="block text-sm font-medium text-gray-700 mb-1">
                                Jawaban <span class="text-red-500">*</span>
                            </label>
                            <textarea id="add_answer" name="answer" rows="4" required
                                      placeholder="Tulis jawaban lengkap di sini..."
                                      class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 resize-y">{{ old('answer') }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="add_item_sort_order" class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                                <input type="number" id="add_item_sort_order" name="sort_order"
                                       value="{{ old('sort_order', $items->count()) }}" min="0"
                                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="flex items-center gap-2 mt-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" id="add_item_is_active" name="is_active" value="1"
                                           checked
                                           class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                    <label for="add_item_is_active" class="text-sm text-gray-700">Aktif</label>
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
                                Simpan Pertanyaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 MODAL: EDIT PERTANYAAN
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

                <div class="w-full max-w-xl rounded-2xl bg-white shadow-xl max-h-[90vh] overflow-y-auto"
                     @click.outside="modalEdit = false">
                    <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                        <h3 class="text-base font-semibold text-gray-900">Edit Pertanyaan FAQ</h3>
                        <button type="button" @click="modalEdit = false"
                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                                aria-label="Tutup modal">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST"
                          :action="'{{ url('admin/faq/categories/' . $category->id . '/items') }}/' + editData.id"
                          class="px-6 py-5 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="edit_question" class="block text-sm font-medium text-gray-700 mb-1">
                                Pertanyaan <span class="text-red-500">*</span>
                            </label>
                            <textarea id="edit_question" name="question" rows="2" required
                                      x-bind:value="editData.question"
                                      @input="editData.question = $event.target.value"
                                      placeholder="Pertanyaan"
                                      class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 resize-y"
                                      x-effect="$el.value = editData.question || ''"></textarea>
                        </div>

                        <div>
                            <label for="edit_answer" class="block text-sm font-medium text-gray-700 mb-1">
                                Jawaban <span class="text-red-500">*</span>
                            </label>
                            <textarea id="edit_answer" name="answer" rows="5" required
                                      placeholder="Jawaban"
                                      class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 resize-y"
                                      x-effect="$el.value = editData.answer || ''"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="edit_item_sort_order" class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                                <input type="number" id="edit_item_sort_order" name="sort_order"
                                       :value="editData.sort_order" min="0"
                                       class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="flex items-center gap-2 mt-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" id="edit_item_is_active" name="is_active" value="1"
                                           :checked="editData.is_active"
                                           class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                    <label for="edit_item_is_active" class="text-sm text-gray-700">Aktif</label>
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
                                Perbarui Pertanyaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>{{-- end x-data --}}

    </div>
</x-layouts.admin>
