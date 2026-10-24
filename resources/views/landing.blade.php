<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Kenali Energiciency, workspace operasi manufaktur untuk memantau produksi, mesin, energi, dan biaya dalam satu platform.">
        <title>Energiciency · Workspace operasi manufaktur</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-w-0 bg-white text-navy-900 antialiased">
        <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/90 backdrop-blur">
            <nav aria-label="Navigasi utama" class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3.5 sm:px-8 lg:px-10">
                <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="Energiciency, beranda">
                    <img src="{{ asset('images/energiciency-logo.png') }}" alt="Energiciency" class="h-10 w-40 object-contain object-left">
                </a>
                <div class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
                    <a class="transition hover:text-brand-700" href="#platform">Platform</a>
                    <a class="transition hover:text-brand-700" href="#alur-kerja">Alur kerja</a>
                    <a class="transition hover:text-brand-700" href="#modul">Modul</a>
                    <a class="transition hover:text-brand-700" href="#tentang">Tentang</a>
                </div>
                <div class="flex items-center gap-2">
                    <details class="relative md:hidden">
                        <summary class="flex min-h-10 cursor-pointer list-none items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-navy-900">Menu</summary>
                        <div class="absolute right-0 top-12 z-30 grid min-w-44 gap-1 rounded-xl border border-slate-200 bg-white p-2 text-sm font-medium text-slate-700 shadow-lg">
                            <a class="rounded-lg px-3 py-2 hover:bg-slate-50" href="#platform">Platform</a>
                            <a class="rounded-lg px-3 py-2 hover:bg-slate-50" href="#alur-kerja">Alur kerja</a>
                            <a class="rounded-lg px-3 py-2 hover:bg-slate-50" href="#modul">Modul</a>
                            <a class="rounded-lg px-3 py-2 hover:bg-slate-50" href="#tentang">Tentang</a>
                        </div>
                    </details>
                    <a href="{{ route('login') }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-navy-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        Masuk
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                </div>
            </nav>
        </header>

        <main>
            <section class="relative isolate overflow-hidden bg-[#f4f8fc]">
                <div class="absolute inset-0 -z-10 opacity-40" style="background-image: radial-gradient(#9dbbd1 0.8px, transparent 0.8px); background-size: 22px 22px;"></div>
                <div class="absolute -right-32 -top-24 -z-10 h-[34rem] w-[34rem] rounded-full bg-brand-100/70 blur-3xl"></div>
                <div class="mx-auto grid max-w-7xl items-center gap-10 px-5 py-14 sm:px-8 sm:py-16 lg:grid-cols-[0.86fr_1.14fr] lg:gap-12 lg:px-10 lg:py-20">
                    <div class="max-w-xl">
                        <div class="inline-flex items-center gap-2 rounded-full border border-brand-100 bg-white/80 px-3 py-1.5 text-xs font-semibold tracking-wide text-brand-800 shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            PROFIL PLATFORM · MANUFAKTUR
                        </div>
                        <h1 class="mt-6 text-4xl font-bold leading-[1.12] tracking-tight text-navy-950 sm:text-5xl lg:text-[3.65rem]">
                            Dari lantai produksi,<br>
                            <span class="text-brand-600">untuk keputusan lebih baik.</span>
                        </h1>
                        <p class="mt-6 max-w-lg text-base leading-7 text-slate-600 sm:text-lg sm:leading-8">
                            Energiciency menyatukan informasi produksi, performa mesin, konsumsi energi, dan biaya ke dalam satu workspace operasional yang mudah dipahami.
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">
                                Masuk ke workspace
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </a>
                            <a href="#platform" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-slate-300 bg-white/80 px-5 text-sm font-semibold text-navy-900 transition hover:border-brand-300 hover:bg-white">
                                Lihat platform
                            </a>
                        </div>
                        <div class="mt-9 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-medium text-slate-500">
                            <span class="inline-flex items-center gap-2"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Produksi &amp; OEE</span>
                            <span class="inline-flex items-center gap-2"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Energi &amp; biaya</span>
                            <span class="inline-flex items-center gap-2"><svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Aset &amp; persediaan</span>
                        </div>
                    </div>

                    <div id="platform" class="relative mx-auto w-full max-w-[720px] scroll-mt-20 lg:ml-auto">
                        <div class="absolute -inset-5 rounded-[2rem] bg-gradient-to-br from-brand-200/70 via-white/20 to-emerald-100/70 blur-xl"></div>
                        <figure class="relative overflow-hidden rounded-2xl border border-slate-700 bg-navy-950 shadow-[0_28px_80px_-32px_rgba(16,31,51,0.55)]">
                            <img src="{{ asset('images/production-station.svg') }}" alt="Ilustrasi stasiun produksi manufaktur dengan mesin, robot industri, dan jalur konveyor" class="aspect-[1.5] w-full object-cover">
                            <figcaption class="absolute inset-x-0 bottom-0 flex flex-wrap items-center justify-between gap-3 bg-gradient-to-t from-navy-950/95 via-navy-950/70 to-transparent px-4 pb-4 pt-12 sm:px-6 sm:pb-5">
                                <span class="text-xs font-semibold text-white sm:text-sm">Satu pandangan untuk lantai produksi</span>
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-navy-950/60 px-3 py-1.5 text-[10px] font-medium text-slate-200 backdrop-blur"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Mesin · proses · energi</span>
                            </figcaption>
                        </figure>
                        <div class="relative mt-4 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_20px_50px_-32px_rgba(16,31,51,0.38)]">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 3v18h18M7 14l4-4 3 3 6-7"/></svg>
                                    </div>
                                    <div><p class="text-sm font-bold text-navy-900">Ringkasan operasional</p><p class="mt-0.5 text-[11px] text-slate-500">Gambaran aktivitas produksi</p></div>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">DEMO</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 p-4 sm:gap-4 sm:p-6">
                                <article class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5 sm:p-4">
                                    <p class="text-[11px] font-medium text-slate-500">Produksi aktual</p>
                                    <p class="mt-2 text-2xl font-bold tracking-tight text-navy-900">12.480 <span class="text-xs font-medium text-slate-500">unit</span></p>
                                    <div class="mt-3 flex items-center gap-1.5 text-[10px] font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Output tercatat</div>
                                </article>
                                <article class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5 sm:p-4">
                                    <p class="text-[11px] font-medium text-slate-500">Konsumsi energi</p>
                                    <p class="mt-2 text-2xl font-bold tracking-tight text-navy-900">8.642 <span class="text-xs font-medium text-slate-500">kWh</span></p>
                                    <div class="mt-3 flex items-center gap-1.5 text-[10px] font-semibold text-brand-700"><span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>Pembacaan tersimpan</div>
                                </article>
                                <article class="col-span-2 rounded-xl border border-slate-100 p-4 sm:p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div><p class="text-xs font-bold text-navy-900">Tren penggunaan energi</p><p class="mt-1 text-[10px] text-slate-500">Pemantauan berdasarkan catatan yang tersedia</p></div>
                                        <span class="rounded-md bg-brand-50 px-2 py-1 text-[10px] font-semibold text-brand-700">kWh</span>
                                    </div>
                                    <div class="mt-5 flex h-28 items-end gap-2 border-b border-l border-slate-100 px-2 sm:h-32 sm:gap-3">
                                        <div class="h-[36%] flex-1 rounded-t bg-brand-200"></div>
                                        <div class="h-[52%] flex-1 rounded-t bg-brand-300"></div>
                                        <div class="h-[43%] flex-1 rounded-t bg-brand-300"></div>
                                        <div class="h-[68%] flex-1 rounded-t bg-brand-400"></div>
                                        <div class="h-[58%] flex-1 rounded-t bg-brand-400"></div>
                                        <div class="h-[82%] flex-1 rounded-t bg-brand-500"></div>
                                        <div class="h-[64%] flex-1 rounded-t bg-brand-500"></div>
                                        <div class="h-[92%] flex-1 rounded-t bg-brand-600"></div>
                                    </div>
                                    <div class="mt-2 flex justify-between text-[9px] text-slate-400"><span>01 Okt</span><span>08 Okt</span><span>15 Okt</span></div>
                                </article>
                                <article class="col-span-2 flex items-center justify-between rounded-xl bg-navy-950 px-4 py-3.5 text-white sm:px-5">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-brand-200">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v3m0 12v3M5.64 5.64l2.12 2.12m8.48 8.48 2.12 2.12M3 12h3m12 0h3M5.64 18.36l2.12-2.12m8.48-8.48 2.12-2.12"/><circle cx="12" cy="12" r="4"/></svg>
                                        </span>
                                        <div><p class="text-xs font-semibold">Visibilitas untuk keputusan lebih baik</p><p class="mt-1 text-[10px] text-slate-300">Produksi · mesin · energi · biaya</p></div>
                                    </div>
                                    <svg class="h-4 w-4 shrink-0 text-brand-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                </article>
                            </div>
                            <p class="px-5 pb-4 text-[9px] leading-4 text-slate-400 sm:px-6">Pratinjau ilustratif. Angka merupakan contoh visual, bukan data operasional aktual.</p>
                        </div>
                        <div class="absolute -bottom-5 -left-3 hidden items-center gap-3 rounded-xl border border-slate-100 bg-white px-4 py-3 shadow-lg sm:flex">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                            </span>
                            <div><p class="text-xs font-bold text-navy-900">Satu workspace</p><p class="mt-0.5 text-[10px] text-slate-500">Data operasi terhubung</p></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="alur-kerja" class="scroll-mt-20 border-y border-slate-100 bg-slate-50/70">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20 lg:px-10">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Alur informasi</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-navy-950 sm:text-4xl">Dari aktivitas menjadi gambaran operasional.</h2>
                        <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-base">Catatan yang relevan mengalir ke konteks yang lebih jelas—membantu tim membaca proses tanpa kehilangan kaitannya.</p>
                    </div>
                    <div class="mt-10 grid gap-4 md:grid-cols-3">
                        <article class="relative rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                            <span class="text-xs font-bold tracking-[0.14em] text-brand-600">01 · CATAT</span>
                            <h3 class="mt-3 text-base font-bold text-navy-900">Aktivitas di lantai produksi</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Kelola order, batch, output, material, mesin, serta pembacaan energi dalam alur kerja operasional.</p>
                        </article>
                        <article class="relative rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                            <span class="text-xs font-bold tracking-[0.14em] text-brand-600">02 · HUBUNGKAN</span>
                            <h3 class="mt-3 text-base font-bold text-navy-900">Konteks antarproses</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Telusuri informasi berdasarkan mesin, proses, produk, order, dan rentang waktu yang tersedia.</p>
                        </article>
                        <article class="relative rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                            <span class="text-xs font-bold tracking-[0.14em] text-brand-600">03 · PAHAMI</span>
                            <h3 class="mt-3 text-base font-bold text-navy-900">Ringkasan untuk tim</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Baca tren, indikator, dan riwayat operasional untuk membantu menentukan tindak lanjut.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="modul" class="mx-auto max-w-7xl scroll-mt-20 px-5 py-16 sm:px-8 sm:py-20 lg:px-10">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Cakupan platform</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-navy-950 sm:text-4xl">Gambaran menyeluruh, dari mesin hingga biaya.</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-base">Jelajahi informasi lintas proses untuk membantu tim memahami aktivitas di lantai produksi dan di mana perhatian dibutuhkan.</p>
                </div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <article class="rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/60">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 19V5m0 14h16M8 15V9m4 6V5m4 10v-4m4 4V7"/></svg></span>
                        <h3 class="mt-5 text-sm font-bold text-navy-900">Produksi &amp; OEE</h3>
                        <p class="mt-2 text-xs leading-6 text-slate-500">Ikuti order, output, kualitas, availability, dan performa berdasarkan catatan yang tersedia.</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/60">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 10-13h-7l1-7Z"/></svg></span>
                        <h3 class="mt-5 text-sm font-bold text-navy-900">Energi &amp; biaya</h3>
                        <p class="mt-2 text-xs leading-6 text-slate-500">Telusuri konsumsi dan biaya energi berdasarkan periode, mesin, sumber, atau relasi operasional.</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/60">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5M9 9h.01M15 9h.01M9 12h.01M15 12h.01"/></svg></span>
                        <h3 class="mt-5 text-sm font-bold text-navy-900">Mesin &amp; pemeliharaan</h3>
                        <p class="mt-2 text-xs leading-6 text-slate-500">Lihat kondisi aset, histori terkait, catatan performa, serta log pemeliharaan.</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/60">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16M8 4v16m8-16v16"/></svg></span>
                        <h3 class="mt-5 text-sm font-bold text-navy-900">Persediaan &amp; costing</h3>
                        <p class="mt-2 text-xs leading-6 text-slate-500">Kelola komponen, BOM, transaksi material, dan gambaran biaya produksi.</p>
                    </article>
                </div>
            </section>

            <section id="tentang" class="scroll-mt-20 bg-navy-950">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 px-5 py-12 sm:px-8 md:flex-row md:items-center md:justify-between lg:px-10">
                    <div class="max-w-2xl">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-200">Energiciency</p>
                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white sm:text-3xl">Informasi operasional, dalam satu pandangan.</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Workspace untuk manajemen produksi, OEE, BOM, persediaan, energi, costing, pemeliharaan, dan fondasi integrasi IoT.</p>
                    </div>
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-white px-5 text-sm font-semibold text-navy-900 transition hover:bg-brand-50">
                        Masuk ke Energiciency
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-100 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
                <span>&copy; {{ date('Y') }} Energiciency</span>
                <span>Workspace operasi produksi · Data demonstrasi bukan data resmi</span>
            </div>
        </footer>
    </body>
</html>
