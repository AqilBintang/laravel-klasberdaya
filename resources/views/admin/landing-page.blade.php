{{--
    admin/landing-page.blade.php
    Form untuk mengedit konten landing page.
    Sections: Banner (slides) dan Tentang Platform.
    Data: $banner (array slides), $tentang (array heading/description/points/imageUrl)
--}}
<x-layouts.admin pageTitle="Landing Page">
    <div class="space-y-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-900">Kelola Landing Page</h2>
            <p class="mt-1 text-sm text-gray-500">Ubah konten yang tampil di halaman publik</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── TABS ───────────────────────────────────────────────────────── --}}
        <div x-data="{ tab: '{{ session('active_tab', $activeTab ?? 'banner') }}' }">

            <div class="border-b border-gray-200">
                <nav class="flex gap-0" aria-label="Tab navigasi landing page">
                    <button type="button"
                            @click="tab = 'banner'"
                            :class="tab === 'banner'
                                ? 'border-sky-500 text-sky-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                        </svg>
                        Banner
                    </button>
                    <button type="button"
                            @click="tab = 'tentang'"
                            :class="tab === 'tentang'
                                ? 'border-sky-500 text-sky-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tentang Platform
                    </button>
                    <button type="button"
                            @click="tab = 'faq'"
                            :class="tab === 'faq'
                                ? 'border-sky-500 text-sky-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        FAQ
                    </button>
                </nav>
            </div>

            {{-- ── TAB: BANNER ─────────────────────────────────────────────── --}}
            <div x-show="tab === 'banner'" x-cloak class="mt-5">
                <form method="POST"
                      action="{{ route('admin.landing-page.update') }}"
                      enctype="multipart/form-data"
                      class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="banner">

                    {{-- Info ukuran --}}
                    <div class="flex items-start gap-2 rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-700">
                        <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Ukuran gambar yang disarankan: <strong>1200 × 220 px</strong> (rasio ±16:3), format JPG atau PNG, maks 5 MB.</span>
                    </div>

                    {{-- Slides --}}
                    <div x-data="bannerSlides({{ json_encode($banner['slides'] ?? []) }})" class="space-y-3">

                        <template x-if="slides.length === 0">
                            <div class="rounded-xl border-2 border-dashed border-gray-200 py-8 text-center text-sm text-gray-400">
                                Belum ada slide. Tambahkan slide baru.
                            </div>
                        </template>

                        <div class="space-y-2">
                            <template x-for="(slide, idx) in slides" :key="slide.id">
                                <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-3">
                                    <div class="flex gap-3 items-start">

                                        {{-- Preview thumbnail --}}
                                        <div class="shrink-0 w-24 h-16 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                                            <template x-if="slide.imageUrl">
                                                <img :src="slide.imageUrl" :alt="slide.alt || ''" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!slide.imageUrl">
                                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </template>
                                        </div>

                                        {{-- Input area --}}
                                        <div class="flex-1 min-w-0 space-y-2">

                                            {{-- Upload file --}}
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 mb-1">
                                                    Upload gambar <span class="font-normal text-gray-400">(JPG/PNG/WebP · maks 5 MB)</span>
                                                </label>
                                                <input type="file"
                                                       :name="'slide_files[' + idx + ']'"
                                                       accept="image/jpeg,image/png,image/webp"
                                                       @change="handleFilePreview($event, idx)"
                                                       class="text-sm text-gray-600 file:mr-3 file:rounded-lg file:border file:border-gray-200 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-gray-600 hover:file:bg-gray-50">
                                            </div>

                                            {{-- Hidden imageUrl (pertahankan URL existing jika tidak upload baru) --}}
                                            <input type="hidden" :name="'slides[' + idx + '][imageUrl]'" :value="slide.imageUrl">

                                            {{-- Alt text --}}
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 mb-1">
                                                    Alt text <span class="font-normal text-gray-400">(untuk aksesibilitas)</span>
                                                </label>
                                                <input type="text"
                                                       :name="'slides[' + idx + '][alt]'"
                                                       x-model="slide.alt"
                                                       placeholder="Contoh: Banner program unggulan 2025"
                                                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                            </div>

                                            {{-- Hidden ID --}}
                                            <input type="hidden" :name="'slides[' + idx + '][id]'" :value="slide.id">
                                        </div>

                                        {{-- Hapus slide --}}
                                        <button type="button"
                                                @click="removeSlide(idx)"
                                                :aria-label="'Hapus slide ' + (idx + 1)"
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button"
                                @click="addSlide()"
                                class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Slide
                        </button>
                    </div>

                    <div class="flex items-center justify-end pt-2 border-t border-gray-100">
                        <button type="submit"
                                class="rounded-lg bg-sky-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                            Simpan Banner
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── TAB: TENTANG PLATFORM ────────────────────────────────────── --}}
            <div x-show="tab === 'tentang'" x-cloak class="mt-5">
                <form method="POST"
                      action="{{ route('admin.landing-page.update') }}"
                      enctype="multipart/form-data"
                      class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="tentang">

                    <p class="text-sm text-gray-500">
                        Ubah teks di bagian "Tentang Platform" pada landing page.
                    </p>

                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">
                        <h3 class="font-semibold text-gray-800 border-b border-gray-100 pb-3">Tentang Platform</h3>

                        {{-- Judul --}}
                        <div>
                            <label for="tentang_heading" class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                            <input type="text" id="tentang_heading" name="tentang_heading"
                                   value="{{ old('tentang_heading', $tentang['heading'] ?? 'Apa itu Klasifikasi Berdaya?') }}"
                                   placeholder="Apa itu Klasifikasi Berdaya?"
                                   class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        </div>

                        {{-- Deskripsi --}}
                        <div>
                            <label for="tentang_description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea id="tentang_description" name="tentang_description" rows="3"
                                      class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 resize-y">{{ old('tentang_description', $tentang['description'] ?? '') }}</textarea>
                        </div>

                        {{-- Poin-poin --}}
                        <div x-data="tentangPoints({{ json_encode($tentang['points'] ?? []) }})">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Poin-poin Utama</p>
                                    <p class="text-xs text-gray-400">Tampil sebagai daftar bullet di landing page.</p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(point, idx) in points" :key="idx">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sky-500 text-white text-[10px] font-bold" aria-hidden="true">✓</span>
                                        <input type="text"
                                               :name="'tentang_points[]'"
                                               :aria-label="'Poin ' + (idx + 1)"
                                               x-model="points[idx]"
                                               class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        <button type="button"
                                                @click="removePoint(idx)"
                                                :aria-label="'Hapus poin ' + (idx + 1)"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <button type="button"
                                    @click="addPoint()"
                                    class="mt-2 flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Tambah Poin
                            </button>
                        </div>

                        {{-- Gambar Ilustrasi --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gambar Ilustrasi</label>
                            <p class="text-xs text-gray-400 mb-3">Tampil di sisi kanan section Tentang Platform. Rasio 4:3, maks 5 MB.</p>

                            @if (!empty($tentang['imageUrl']))
                                <div class="mb-3">
                                    <img src="{{ $tentang['imageUrl'] }}" alt="Ilustrasi Tentang Platform"
                                         class="w-40 h-[120px] rounded-lg object-cover border border-gray-200">
                                    <p class="text-xs text-gray-400 mt-1">Gambar saat ini</p>
                                </div>
                            @endif
                            <input type="file" name="tentang_image" accept="image/jpeg,image/png,image/webp"
                                   class="text-sm text-gray-600 file:mr-3 file:rounded-lg file:border file:border-gray-200 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-gray-600 hover:file:bg-gray-50">
                            <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, WebP. Maks 5 MB.</p>
                            {{-- Pertahankan URL lama jika tidak upload baru --}}
                            <input type="hidden" name="tentang_image_existing" value="{{ $tentang['imageUrl'] ?? '' }}">
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-2 border-t border-gray-100">
                        <button type="submit"
                                class="rounded-lg bg-sky-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                            Simpan Tentang Platform
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── TAB: FAQ ────────────────────────────────────────────────── --}}
            <div x-show="tab === 'faq'" x-cloak class="mt-5">

                @php
                    $faqCategories = \App\Models\FaqCategory::withCount('items')
                        ->orderBy('sort_order')->orderBy('id')->get();
                @endphp

                {{-- Info --}}
                <div class="flex items-start gap-2 rounded-md bg-sky-50 border border-sky-200 px-3 py-2 text-xs text-sky-700 mb-4">
                    <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Gunakan <strong>Urutan</strong> untuk mengatur posisi tampil kategori. Angka lebih kecil tampil lebih dulu. Klik <strong>Kelola Pertanyaan</strong> untuk menambah/edit pertanyaan dalam kategori.</span>
                </div>

                <div x-data="{ modalFaqAdd: false, modalFaqEdit: false, faqEditData: {} }" class="space-y-4">

                    {{-- Header: tombol tambah + lihat publik --}}
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('landing.faq') }}" target="_blank"
                           class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Lihat FAQ Publik
                        </a>
                        <button type="button"
                                @click="modalFaqAdd = true"
                                class="flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Kategori
                        </button>
                    </div>

                    {{-- Tabel kategori --}}
                    @if ($faqCategories->isNotEmpty())
                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-10">No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nama Kategori</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">Pertanyaan</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">Urutan</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($faqCategories as $i => $cat)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $i + 1 }}</td>
                                            <td class="px-4 py-3 font-medium text-gray-800">{{ $cat->name }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="rounded-full bg-gray-100 text-gray-600 px-2.5 py-0.5 text-xs font-medium">
                                                    {{ $cat->items_count }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-gray-500 text-xs">{{ $cat->sort_order }}</td>
                                            <td class="px-4 py-3 text-center">
                                                @if ($cat->is_active)
                                                    <span class="rounded-full bg-green-100 text-green-700 px-2.5 py-0.5 text-xs font-medium">Aktif</span>
                                                @else
                                                    <span class="rounded-full bg-gray-100 text-gray-500 px-2.5 py-0.5 text-xs font-medium">Non-aktif</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('admin.faq.category.show', $cat) }}"
                                                       class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                                                        Kelola Pertanyaan
                                                    </a>
                                                    <button type="button"
                                                            @click="modalFaqEdit = true; faqEditData = {
                                                                id:         {{ $cat->id }},
                                                                name:       '{{ addslashes($cat->name) }}',
                                                                sort_order: {{ $cat->sort_order }},
                                                                is_active:  {{ $cat->is_active ? 'true' : 'false' }}
                                                            }"
                                                            class="rounded-lg border border-sky-200 bg-white px-3 py-1.5 text-xs font-medium text-sky-600 hover:bg-sky-50 transition-colors">
                                                        Edit
                                                    </button>
                                                    <form method="POST"
                                                          action="{{ route('admin.faq.category.destroy', $cat) }}"
                                                          onsubmit="return confirm('Hapus kategori \'{{ addslashes($cat->name) }}\' beserta {{ $cat->items_count }} pertanyaan di dalamnya?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="_from_landing" value="1">
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
                        <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-14 text-center">
                            <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-gray-500">Belum ada kategori FAQ.</p>
                            <button type="button" @click="modalFaqAdd = true"
                                    class="mt-3 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                                Tambah Kategori Pertama
                            </button>
                        </div>
                    @endif

                    {{-- MODAL: TAMBAH KATEGORI --}}
                    <div x-show="modalFaqAdd"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                         @keydown.escape.window="modalFaqAdd = false">
                        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl" @click.outside="modalFaqAdd = false">
                            <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100">
                                <h3 class="text-base font-semibold text-gray-900">Tambah Kategori FAQ</h3>
                                <button type="button" @click="modalFaqAdd = false"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('admin.faq.category.store') }}" class="px-6 py-5 space-y-4">
                                @csrf
                                <input type="hidden" name="_from_landing" value="1">
                                <div>
                                    <label for="lp_add_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Nama Kategori <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="lp_add_name" name="name"
                                           value="{{ old('name') }}"
                                           placeholder="Contoh: Umum, Penilaian & Klasifikasi"
                                           required
                                           class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="lp_add_sort_order" class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                                        <input type="number" id="lp_add_sort_order" name="sort_order"
                                               value="{{ old('sort_order', 0) }}" min="0"
                                               class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        <p class="text-xs text-gray-400 mt-1">Angka lebih kecil = tampil lebih dulu</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <div class="flex items-center gap-2 mt-3">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" id="lp_add_is_active" name="is_active" value="1" checked
                                                   class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                            <label for="lp_add_is_active" class="text-sm text-gray-700">Aktif</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                                    <button type="button" @click="modalFaqAdd = false"
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

                    {{-- MODAL: EDIT KATEGORI --}}
                    <div x-show="modalFaqEdit"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                         @keydown.escape.window="modalFaqEdit = false">
                        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl" @click.outside="modalFaqEdit = false">
                            <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100">
                                <h3 class="text-base font-semibold text-gray-900">Edit Kategori FAQ</h3>
                                <button type="button" @click="modalFaqEdit = false"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <form method="POST"
                                  :action="'{{ url('admin/faq/categories') }}/' + faqEditData.id"
                                  class="px-6 py-5 space-y-4">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_from_landing" value="1">
                                <div>
                                    <label for="lp_edit_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Nama Kategori <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="lp_edit_name" name="name"
                                           :value="faqEditData.name"
                                           required
                                           class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="lp_edit_sort_order" class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                                        <input type="number" id="lp_edit_sort_order" name="sort_order"
                                               :value="faqEditData.sort_order" min="0"
                                               class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <div class="flex items-center gap-2 mt-3">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" id="lp_edit_is_active" name="is_active" value="1"
                                                   :checked="faqEditData.is_active"
                                                   class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                            <label for="lp_edit_is_active" class="text-sm text-gray-700">Aktif</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                                    <button type="button" @click="modalFaqEdit = false"
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

                </div>{{-- end x-data faq --}}
            </div>

        </div>{{-- end x-data tabs --}}

    </div>

    {{-- Alpine.js components untuk slides dan points --}}
    <script>
        function bannerSlides(initial) {
            return {
                slides: initial.length ? initial : [],
                addSlide() {
                    this.slides.push({ id: 'banner-' + Date.now(), imageUrl: '', alt: '' })
                },
                removeSlide(idx) {
                    this.slides.splice(idx, 1)
                },
                handleFilePreview(event, idx) {
                    const file = event.target.files[0]
                    if (!file) return
                    const reader = new FileReader()
                    reader.onload = (e) => {
                        this.slides[idx].imageUrl = e.target.result
                    }
                    reader.readAsDataURL(file)
                }
            }
        }

        function tentangPoints(initial) {
            return {
                points: initial.length ? [...initial] : [''],
                addPoint() {
                    this.points.push('')
                },
                removePoint(idx) {
                    this.points.splice(idx, 1)
                }
            }
        }
    </script>
</x-layouts.admin>
