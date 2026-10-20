<x-layouts.landing>
    <x-slot name="title">FAQ — {{ config('app.name', 'Klas Berdaya') }}</x-slot>

    {{-- =====================================================================
         Migrasi dari: src/app/(landing)/faq/page.tsx
         Data FAQ dikirim dari FaqController::index() via compact('faqData')
         Format: [['category'=>'...', 'items'=>[['question','answer'],...]],...] 
         Accordion dihandle dengan JS vanilla (menggantikan useState di NextJS).
    ====================================================================== --}}

    <div class="min-h-screen bg-white">

        {{-- Hero --}}
        <section class="pt-20 sm:pt-28 pb-10 sm:pb-12 px-4">
            <div class="max-w-2xl mx-auto text-center">
                <div class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700 mb-4">
                    Pusat Bantuan
                </div>
                <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900">
                    Pertanyaan yang Sering Ditanyakan
                </h1>
                <p class="mt-3 text-sm md:text-base text-gray-500 leading-relaxed">
                    Temukan jawaban atas pertanyaan umum seputar platform Klasifikasi Indeks Kecamatan Berdaya.
                </p>
            </div>
        </section>

        {{-- Konten FAQ --}}
        <section class="max-w-3xl mx-auto px-4 md:px-6 pb-20">
            <div class="space-y-6" id="faq-root">

                @foreach ($faqData as $ci => $category)
                <div>
                    {{-- Label kategori --}}
                    <div class="flex items-center gap-3 mb-1">
                        <span class="text-xs font-semibold text-sky-600 uppercase tracking-wider">
                            {{ $category['category'] }}
                        </span>
                        <div class="flex-1 h-px bg-gray-100"></div>
                    </div>

                    {{-- Card accordion --}}
                    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm px-3 sm:px-5">
                        @foreach ($category['items'] as $ii => $item)
                        @php
                            $key       = $ci . '-' . $ii;
                            $isFirst   = ($ci === 0 && $ii === 0);   // item pertama terbuka secara default
                            $isLast    = $loop->last;
                        @endphp
                        <div class="{{ $isLast ? '' : 'border-b border-gray-100' }}">
                            {{-- Toggle button --}}
                            <button
                                type="button"
                                class="flex w-full items-start justify-between gap-4 py-4 text-left faq-toggle"
                                data-key="{{ $key }}"
                                aria-expanded="{{ $isFirst ? 'true' : 'false' }}"
                                aria-controls="faq-answer-{{ $key }}"
                            >
                                <span class="text-sm font-medium leading-snug {{ $isFirst ? 'text-sky-700' : 'text-gray-800' }} faq-question">
                                    {{ $item['question'] }}
                                </span>
                                <svg
                                    class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 faq-chevron {{ $isFirst ? 'rotate-180' : '' }}"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Answer panel --}}
                            <div
                                id="faq-answer-{{ $key }}"
                                class="overflow-hidden text-sm text-gray-600 leading-relaxed transition-all duration-200 faq-answer {{ $isFirst ? 'max-h-96 pb-4' : 'max-h-0' }}"
                            >
                                {{ $item['answer'] }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach

            </div>

            {{-- CTA kontak --}}
            <div class="mt-12 rounded-2xl bg-sky-50 border border-sky-100 px-6 py-8 text-center">
                <p class="text-sm font-semibold text-gray-800">Tidak menemukan jawaban yang kamu cari?</p>
                <p class="mt-1 text-sm text-gray-500">Hubungi kami langsung dan tim kami akan segera membantu.</p>
                <a
                    href="mailto:kecamatanberdaya@jatengprov.go.id"
                    class="mt-4 inline-flex items-center gap-2 rounded-full bg-sky-600 px-5 py-2 text-sm font-medium text-white hover:bg-sky-700 transition-colors"
                >
                    Hubungi Kami
                </a>
            </div>
        </section>

    </div>

    @push('scripts')
    {{-- Accordion JS — menggantikan useState openKey di FaqPage.tsx --}}
    <script>
    (function () {
        var openKey = '0-0'; // default: item pertama terbuka

        document.querySelectorAll('.faq-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key      = btn.getAttribute('data-key');
                var answer   = document.getElementById('faq-answer-' + key);
                var chevron  = btn.querySelector('.faq-chevron');
                var question = btn.querySelector('.faq-question');

                var isOpen = (openKey === key);

                // Tutup yang sedang terbuka
                if (openKey !== null) {
                    var prevAnswer   = document.getElementById('faq-answer-' + openKey);
                    var prevBtn      = document.querySelector('[data-key="' + openKey + '"]');
                    if (prevAnswer)  { prevAnswer.classList.remove('max-h-96', 'pb-4'); prevAnswer.classList.add('max-h-0'); }
                    if (prevBtn)     {
                        prevBtn.setAttribute('aria-expanded', 'false');
                        var pc = prevBtn.querySelector('.faq-chevron');
                        var pq = prevBtn.querySelector('.faq-question');
                        if (pc) pc.classList.remove('rotate-180');
                        if (pq) { pq.classList.remove('text-sky-700'); pq.classList.add('text-gray-800'); }
                    }
                    openKey = null;
                }

                // Buka yang diklik (jika belum terbuka)
                if (!isOpen) {
                    answer.classList.remove('max-h-0');
                    answer.classList.add('max-h-96', 'pb-4');
                    btn.setAttribute('aria-expanded', 'true');
                    chevron.classList.add('rotate-180');
                    question.classList.remove('text-gray-800');
                    question.classList.add('text-sky-700');
                    openKey = key;
                }
            });
        });
    })();
    </script>
    @endpush

</x-layouts.landing>
