{{--
    kabupaten/review/show.blade.php
    Detail jawaban assessment satu kecamatan — tampilkan semua per indikator.
    Tombol keputusan (Setujui / Perlu Perbaikan) di bagian bawah halaman.

    Data dari ApprovalController::show():
      $approval  : KabupatenApproval
      $entries   : Collection<SelfAssessment> — semua indikator kecamatan
      $summary   : [total, submitted, validated, draft]
--}}
<x-layouts.kabupaten pageTitle="Detail Review">

    <div class="space-y-6">

        {{-- ── Breadcrumb ──────────────────────────────────────────────── --}}
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('kabupaten.review.index') }}" class="hover:text-indigo-600 transition-colors">
                Review Assessment
            </a>
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 font-medium truncate">
                {{ $approval->kecamatanUser?->kecamatanWilayah?->nama
                    ?? $approval->kecamatanUser?->kecamatan
                    ?? 'Detail' }}
            </span>
        </nav>

        @php
            $kecamatanUser = $approval->kecamatanUser;
            $namaKecamatan = $kecamatanUser?->kecamatanWilayah?->nama
                ?? $kecamatanUser?->kecamatan
                ?? $kecamatanUser?->name
                ?? '—';
            $namaKabupaten = $kecamatanUser?->kabupatenWilayah?->nama
                ?? $kecamatanUser?->kabupaten;
        @endphp

        {{-- ── Info Header ─────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">{{ $namaKecamatan }}</h2>
                    @if ($namaKabupaten)
                        <p class="text-sm text-gray-500">{{ $namaKabupaten }}</p>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-1 text-sm text-gray-500">
                    <span>Assessment <strong class="text-gray-800">{{ $approval->assessment?->title ?? '—' }}</strong></span>
                    <span>Periode <strong class="text-gray-800">{{ $approval->periode }}</strong></span>
                </div>
            </div>

            {{-- Ringkasan --}}
            <div class="mt-3 flex flex-wrap gap-3 border-t border-gray-100 pt-3 text-xs text-gray-500">
                <span>Total indikator: <strong class="text-gray-700">{{ $summary['total'] }}</strong></span>
                @if ($summary['submitted'] > 0)
                    <span>Menunggu validasi: <strong class="text-amber-600">{{ $summary['submitted'] }}</strong></span>
                @endif
                @if ($summary['validated'] > 0)
                    <span>Sudah tervalidasi: <strong class="text-green-600">{{ $summary['validated'] }}</strong></span>
                @endif
                @if ($summary['draft'] > 0)
                    <span>Draft: <strong class="text-gray-500">{{ $summary['draft'] }}</strong></span>
                @endif
            </div>
        </div>

        {{-- ── Status approval saat ini (jika sudah diputuskan) ──────── --}}
        @if ($approval->status !== 'PENDING')
            <div @class([
                'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm',
                'bg-green-50 border-green-200 text-green-800' => $approval->status === 'APPROVED',
                'bg-red-50 border-red-200 text-red-800'       => $approval->status === 'NEEDS_REVISION',
            ])>
                @if ($approval->status === 'APPROVED')
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold">Sudah disetujui</p>
                        @if ($approval->notes)
                            <p class="mt-0.5 text-xs">Catatan: {{ $approval->notes }}</p>
                        @endif
                        <p class="mt-0.5 text-xs text-green-600">
                            Diteruskan ke tim teknis · {{ \Carbon\Carbon::parse($approval->reviewed_at)->diffForHumans() }}
                        </p>
                    </div>
                @else
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                    </svg>
                    <div>
                        <p class="font-semibold">Diminta perbaikan</p>
                        @if ($approval->notes)
                            <p class="mt-0.5 text-xs">Catatan: {{ $approval->notes }}</p>
                        @endif
                        <p class="mt-0.5 text-xs text-red-600">
                            Kecamatan sedang memperbaiki · {{ \Carbon\Carbon::parse($approval->reviewed_at)->diffForHumans() }}
                        </p>
                    </div>
                @endif
            </div>
        @endif

        {{-- ── Flash messages ──────────────────────────────────────────── --}}
        @if (session('success'))
            <div class="flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-center gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── Daftar Jawaban per Indikator (read-only) ────────────────── --}}
        @if ($entries->isEmpty())
            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white py-12 text-center">
                <p class="text-sm text-gray-500">Belum ada jawaban yang tersimpan untuk assessment ini.</p>
            </div>
        @else
            @php
                $categories = $entries->groupBy(fn ($e) => $e->indicator?->category_id);
            @endphp

            @foreach ($categories as $catId => $catEntries)
                @php $cat = $catEntries->first()?->indicator?->category; @endphp
                <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">

                    {{-- Category header --}}
                    <div class="flex items-center gap-3 px-5 py-4 bg-slate-50 border-b border-slate-200">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white shrink-0">
                            {{ $cat?->code ?? '—' }}
                        </span>
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">{{ $cat?->name ?? 'Kategori' }}</p>
                            <p class="text-xs text-slate-500">{{ $catEntries->count() }} indikator</p>
                        </div>
                    </div>

                    {{-- Indikator rows --}}
                    <div class="divide-y divide-slate-100">
                        @foreach ($catEntries as $entry)
                            @php
                                $statusCls = match($entry->status) {
                                    'VALIDATED' => 'bg-green-100 text-green-700',
                                    'SUBMITTED' => 'bg-sky-100 text-sky-700',
                                    'REJECTED'  => 'bg-red-100 text-red-700',
                                    'DRAFT'     => 'bg-gray-100 text-gray-500',
                                    default     => 'bg-gray-100 text-gray-500',
                                };
                                $statusLabel = match($entry->status) {
                                    'VALIDATED' => 'Tervalidasi',
                                    'SUBMITTED' => 'Dikirim',
                                    'REJECTED'  => 'Ditolak',
                                    'DRAFT'     => 'Draft',
                                    default     => $entry->status,
                                };
                            @endphp
                            <div class="px-5 py-4">
                                <div class="flex items-start gap-4">

                                    {{-- Nomor indikator --}}
                                    <div class="w-6 text-center text-xs font-bold text-slate-400 mt-0.5 shrink-0">
                                        {{ $entry->indicator?->number }}
                                    </div>

                                    {{-- Konten --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-2 flex-wrap">
                                            <p class="text-sm font-medium text-slate-800 leading-snug flex-1">
                                                {{ $entry->indicator?->indicator }}
                                            </p>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusCls }} shrink-0">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        {{-- Jawaban --}}
                                        @if ($entry->description)
                                            <div class="mt-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                                                <p class="text-xs text-slate-500 font-medium mb-1">Jawaban:</p>
                                                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $entry->description }}</p>
                                            </div>
                                        @endif

                                        {{-- Skor & dokumen --}}
                                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                            <span>
                                                Skor: <strong class="text-slate-800">{{ $entry->score }}</strong>
                                                / {{ $entry->indicator?->max_score ?? '?' }}
                                            </span>
                                            @if ($entry->supporting_doc && $entry->supporting_doc !== 'NO_DOC' && $entry->supporting_doc !== 'GDRIVE_PENDING')
                                                <a href="{{ $entry->supporting_doc }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                    </svg>
                                                    Dokumen pendukung
                                                </a>
                                            @elseif ($entry->supporting_doc === 'NO_DOC')
                                                <span class="text-slate-400 italic">Tanpa dokumen</span>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        {{-- ── Form Keputusan (hanya jika masih PENDING) ──────────────── --}}
        @if ($approval->status === 'PENDING')
            <div class="rounded-xl border-2 border-indigo-200 bg-indigo-50 p-6 shadow-sm" id="form-keputusan">
                <h3 class="text-base font-bold text-indigo-900 mb-1">Berikan Keputusan</h3>
                <p class="text-sm text-indigo-700 mb-5">
                    Setelah disetujui, assessment akan diteruskan ke tim teknis untuk divalidasi.
                    Jika perlu perbaikan, kecamatan akan diminta memperbaiki jawabannya.
                </p>

                {{-- Catatan opsional --}}
                <div class="mb-5">
                    <label for="notes" class="block text-sm font-medium text-indigo-800 mb-1.5">
                        Catatan <span class="font-normal text-indigo-500">(opsional)</span>
                    </label>
                    <textarea id="notes" rows="3"
                              placeholder="Tulis catatan untuk kecamatan…"
                              class="w-full rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm
                                     text-gray-800 placeholder:text-gray-400
                                     focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100
                                     resize-none"></textarea>
                </div>

                @error('status')
                    <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
                @enderror

                {{-- Dua tombol keputusan — buka modal, bukan submit langsung --}}
                <div class="flex flex-col sm:flex-row gap-3">

                    {{-- Setujui --}}
                    <button type="button" id="btn-open-approve"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg
                                   bg-green-600 px-5 py-3 text-sm font-semibold text-white
                                   hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Setujui — Teruskan ke Tim Teknis
                    </button>

                    {{-- Perlu Perbaikan --}}
                    <button type="button" id="btn-open-revision"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg
                                   border-2 border-red-300 bg-white px-5 py-3 text-sm font-semibold
                                   text-red-700 hover:bg-red-50 transition-colors focus:outline-none focus:ring-2 focus:ring-red-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                        </svg>
                        Perlu Perbaikan
                    </button>

                </div>

                {{-- Form hidden — submit via JS setelah konfirmasi --}}
                <form method="POST" action="{{ route('kabupaten.review.store', $approval->id) }}" id="form-keputusan-submit" class="hidden">
                    @csrf
                    <input type="hidden" name="notes"  id="hf-notes">
                    <input type="hidden" name="status" id="hf-status">
                </form>
            </div>

            {{-- ── Modal Konfirmasi Keputusan ──────────────────────────── --}}
            <div id="confirm-modal"
                 class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
                 role="dialog" aria-modal="true" aria-labelledby="modal-title">

                <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">

                    {{-- Header --}}
                    <div class="px-6 pt-6 pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-3 mb-1">
                            <div id="modal-icon" class="rounded-xl p-2"></div>
                            <h3 id="modal-title" class="font-bold text-gray-900 text-base"></h3>
                        </div>
                        <p id="modal-desc" class="text-sm text-gray-500 mt-2"></p>
                    </div>

                    {{-- Pratinjau catatan (jika ada) --}}
                    <div id="modal-notes-preview" class="hidden px-6 pt-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Catatan yang akan dikirim</p>
                        <p id="modal-notes-text" class="text-sm text-gray-700 rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 leading-relaxed"></p>
                    </div>

                    {{-- Warning --}}
                    <div id="modal-warning" class="mx-6 mt-4 rounded-lg px-4 py-3 text-xs leading-relaxed hidden"></div>

                    {{-- Tombol --}}
                    <div class="flex gap-2 px-6 py-5">
                        <button type="button" id="modal-cancel"
                                class="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Kembali
                        </button>
                        <button type="button" id="modal-confirm"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors">
                            Konfirmasi
                        </button>
                    </div>

                </div>
            </div>
        @endif

        {{-- Tombol kembali --}}
        <div>
            <a href="{{ route('kabupaten.review.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2
                      text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke Daftar
            </a>
        </div>

    </div>

    @push('scripts')
    <script>
    (function () {
        var modal      = document.getElementById('confirm-modal');
        var btnApprove = document.getElementById('btn-open-approve');
        var btnRevise  = document.getElementById('btn-open-revision');
        var btnCancel  = document.getElementById('modal-cancel');
        var btnConfirm = document.getElementById('modal-confirm');

        if (!modal) return; // halaman sudah diputuskan, tidak ada modal

        var pendingStatus = null;

        var config = {
            APPROVED: {
                icon: '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                iconBg:   'bg-green-100',
                title:    'Setujui Assessment',
                desc:     'Assessment ini akan diteruskan ke tim teknis untuk divalidasi. Tindakan ini tidak dapat dibatalkan.',
                warning:  'Pastikan Anda sudah memeriksa seluruh jawaban kecamatan sebelum menyetujui.',
                warnCls:  'bg-green-50 border border-green-200 text-green-800',
                btnCls:   'bg-green-600 hover:bg-green-700',
                btnLabel: 'Ya, Setujui',
            },
            NEEDS_REVISION: {
                icon: '<svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>',
                iconBg:   'bg-amber-100',
                title:    'Minta Perbaikan',
                desc:     'Kecamatan akan diminta untuk memperbaiki dan mengirim ulang jawabannya.',
                warning:  'Tuliskan catatan yang jelas agar kecamatan memahami bagian mana yang perlu diperbaiki.',
                warnCls:  'bg-amber-50 border border-amber-200 text-amber-800',
                btnCls:   'bg-amber-600 hover:bg-amber-700',
                btnLabel: 'Ya, Minta Perbaikan',
            },
        };

        function openModal(status) {
            pendingStatus = status;
            var c = config[status];

            // Isi konten modal
            var iconEl = document.getElementById('modal-icon');
            iconEl.innerHTML = c.icon;
            iconEl.className = 'rounded-xl p-2 ' + c.iconBg;

            document.getElementById('modal-title').textContent = c.title;
            document.getElementById('modal-desc').textContent  = c.desc;

            var warn = document.getElementById('modal-warning');
            warn.textContent = c.warning;
            warn.className   = 'mx-6 mt-4 rounded-lg px-4 py-3 text-xs leading-relaxed ' + c.warnCls;
            warn.classList.remove('hidden');

            // Pratinjau catatan
            var notesVal   = (document.getElementById('notes')?.value ?? '').trim();
            var notesBlock = document.getElementById('modal-notes-preview');
            if (notesVal) {
                document.getElementById('modal-notes-text').textContent = notesVal;
                notesBlock.classList.remove('hidden');
            } else {
                notesBlock.classList.add('hidden');
            }

            // Warna tombol konfirmasi
            btnConfirm.className = 'flex-1 inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors ' + c.btnCls;
            btnConfirm.textContent = c.btnLabel;
            btnConfirm.disabled = false;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            pendingStatus = null;
        }

        function doSubmit() {
            if (!pendingStatus) return;
            document.getElementById('hf-notes').value  = (document.getElementById('notes')?.value ?? '').trim();
            document.getElementById('hf-status').value = pendingStatus;
            btnConfirm.disabled    = true;
            btnConfirm.textContent = 'Memproses…';
            document.getElementById('form-keputusan-submit').submit();
        }

        if (btnApprove) btnApprove.addEventListener('click', function () { openModal('APPROVED'); });
        if (btnRevise)  btnRevise.addEventListener('click',  function () { openModal('NEEDS_REVISION'); });
        btnCancel.addEventListener('click', closeModal);
        btnConfirm.addEventListener('click', doSubmit);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
    })();
    </script>
    @endpush

</x-layouts.kabupaten>
