{{--
    admin/rubric/create.blade.php
    Form tambah rubric baru.
    Data: $assessments
--}}
<x-layouts.admin pageTitle="Tambah Rubric">
    <div class="space-y-6">
        <div>
            <a href="{{ route('admin.rubric.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
            <h2 class="text-2xl font-bold text-gray-900">Tambah Rubric</h2>
            <p class="mt-1 text-sm text-gray-500">
                Buat rubric baru untuk panduan penilaian kecamatan.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.rubric.store') }}" class="space-y-6">
            @csrf

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-5">

                <div>
                    <label for="assessment_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Assessment <span class="text-red-500">*</span>
                    </label>
                    <select id="assessment_id" name="assessment_id"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('assessment_id') border-red-300 @enderror">
                        <option value="">-- Pilih Assessment --</option>
                        @foreach ($assessments as $a)
                            <option value="{{ $a->id }}" {{ old('assessment_id') == $a->id ? 'selected' : '' }}>
                                {{ $a->title }} ({{ $a->periode }})
                            </option>
                        @endforeach
                    </select>
                    @error('assessment_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                        Judul Rubric <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" required
                           value="{{ old('title') }}"
                           placeholder="contoh: Rubrik Penilaian Kecamatan Berdaya 2026"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 @error('title') border-red-300 @enderror">
                    @error('title')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea id="description" name="description" rows="3"
                              placeholder="Deskripsi singkat rubrik ini…"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 resize-none">{{ old('description') }}</textarea>
                </div>

            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.rubric.index') }}"
                   class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-700 transition-colors">
                    Simpan Rubric
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
