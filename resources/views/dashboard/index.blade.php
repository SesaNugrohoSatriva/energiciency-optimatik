@extends('layouts.app')

@section('content')
    @php
        $percent = fn ($value) => $value === null ? '—' : number_format((float) $value * 100, 1, ',', '.').'%';
        $maxEnergy = max((float) $energyTrend->max('kwh'), 1);
        $maxProduction = max((float) $productionTrend->max('planned_quantity'), 1);
        $maxMachineEnergy = max((float) $energyByMachine->max('kwh'), 1);
        $maxProcessEnergy = max((float) $energyByProcess->max('kwh'), 1);
        $maxOee = max((float) $oeeByMachine->max('oee'), 1);
        $maxOrderStatus = max((int) $orderStatuses->max('total'), 1);
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">Pantau kondisi dan efisiensi operasional dari catatan yang tersedia.</p>
            <div class="mt-2 inline-flex items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800">
                <span class="h-2 w-2 rounded-full bg-brand-500" aria-hidden="true"></span>
                Ringkasan data aplikasi · {{ now()->format('d M Y') }}
            </div>
        </div>
        <x-ui.button href="{{ route('energy-records.index') }}" variant="secondary">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v18M3 12h18"/></svg>
            Data energi
        </x-ui.button>
    </div>

    <section aria-label="Indikator kinerja utama" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card label="Produksi aktual" :value="number_format($totalProduction)" :detail="number_format($goodProduction).' unit baik · '.$orderCount.' order'" icon="P" tone="blue" />
        <x-ui.kpi-card label="Konsumsi energi" :value="number_format($energy, 2, ',', '.').' kWh'" detail="Akumulasi pembacaan energi tersimpan" icon="⚡" tone="blue" />
        <x-ui.kpi-card label="Biaya energi" :value="'Rp '.number_format($energyCost, 0, ',', '.')" detail="Mengikuti tarif pada tiap catatan" icon="Rp" tone="navy" />
        <x-ui.kpi-card label="OEE keseluruhan" :value="$percent($oeeMetrics->oee)" detail="Rata-rata dari catatan OEE" icon="O" tone="green" />
        <x-ui.kpi-card label="Availability" :value="$percent($oeeMetrics->availability)" detail="Rata-rata availability tercatat" icon="A" tone="green" />
        <x-ui.kpi-card label="Performance" :value="$percent($oeeMetrics->performance)" detail="Rata-rata performance tercatat" icon="P" tone="blue" />
        <x-ui.kpi-card label="Quality" :value="$percent($oeeMetrics->quality)" detail="Rata-rata quality tercatat" icon="Q" tone="green" />
        <x-ui.kpi-card label="Mesin perlu perhatian" :value="number_format($machinesNeedingAttention)" :detail="number_format($machineCount).' mesin terdaftar · status bukan Normal'" icon="!" :tone="$machinesNeedingAttention ? 'amber' : 'green'" />
    </section>

    <section class="mt-6 grid gap-5 xl:grid-cols-5">
        <article class="app-card p-5 sm:p-6 xl:col-span-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-navy-900">Tren konsumsi energi</h2>
                    <p class="mt-1 text-xs text-slate-500">Agregasi kWh per tanggal pembacaan · 14 tanggal terbaru yang memiliki data</p>
                </div>
                <span class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800">kWh</span>
            </div>
            @if ($energyTrend->isNotEmpty())
                <div class="mt-6 flex h-48 items-end gap-2 border-b border-l border-slate-200 px-2 pb-0 pt-4 sm:gap-3">
                    @foreach ($energyTrend as $point)
                        @php($height = max(4, (float) $point->kwh / $maxEnergy * 100))
                        <div class="group flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ \Illuminate\Support\Carbon::parse($point->period)->format('d M Y') }}: {{ number_format($point->kwh, 2, ',', '.') }} kWh">
                            <span class="mb-1 hidden truncate text-center text-[10px] font-semibold text-slate-500 group-hover:block">{{ number_format($point->kwh, 0, ',', '.') }}</span>
                            <div class="w-full rounded-t-md bg-brand-500 transition-colors group-hover:bg-brand-700" style="height: {{ $height }}%" role="img" aria-label="{{ \Illuminate\Support\Carbon::parse($point->period)->format('d M Y') }}: {{ number_format($point->kwh, 2, ',', '.') }} kWh"></div>
                            <span class="mt-2 truncate text-center text-[9px] text-slate-500 sm:text-[10px]">{{ \Illuminate\Support\Carbon::parse($point->period)->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex justify-between text-[10px] text-slate-400"><span>{{ \Illuminate\Support\Carbon::parse($energyTrend->first()->period)->format('d M Y') }}</span><span>{{ \Illuminate\Support\Carbon::parse($energyTrend->last()->period)->format('d M Y') }}</span></div>
            @else
                <x-ui.empty-state title="Belum ada tren energi" description="Grafik akan muncul setelah pembacaan energi tersimpan." class="py-10" />
            @endif
        </article>

        <article class="app-card p-5 sm:p-6 xl:col-span-2">
            <div>
                <h2 class="text-base font-bold text-navy-900">Target energi vs aktual</h2>
                <p class="mt-1 text-xs text-slate-500">Perbandingan hanya ditampilkan jika target energi tersedia.</p>
            </div>
            <x-ui.empty-state title="Target energi belum tersedia" description="Belum ada target kWh tersimpan pada data mesin atau proses. Grafik perbandingan tidak ditampilkan agar tidak menggunakan target perkiraan." class="py-8" />
        </article>
    </section>

    <section class="mt-5">
        <article class="app-card p-5 sm:p-6">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div><h2 class="text-base font-bold text-navy-900">Produksi: rencana dan aktual</h2><p class="mt-1 text-xs text-slate-500">Kuantitas berdasarkan tanggal jadwal order · unit</p></div>
                <div class="flex gap-4 text-[11px] text-slate-600"><span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-slate-300"></span>Rencana</span><span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-brand-500"></span>Aktual</span></div>
            </div>
            @if ($productionTrend->isNotEmpty())
                <div class="flex h-44 items-end gap-2 overflow-x-auto border-b border-l border-slate-200 px-2 pt-3 sm:gap-4">
                    @foreach ($productionTrend as $point)
                        @php($planHeight = max(3, (float) $point->planned_quantity / $maxProduction * 100))
                        @php($actualHeight = max(3, (float) $point->actual_quantity / $maxProduction * 100))
                        <div class="flex h-full min-w-10 flex-1 flex-col justify-end" title="{{ \Illuminate\Support\Carbon::parse($point->period)->format('d M Y') }} · rencana {{ $point->planned_quantity }}, aktual {{ $point->actual_quantity }}">
                            <div class="flex h-full items-end justify-center gap-1">
                                <div class="w-3 rounded-t bg-slate-300 sm:w-5" style="height: {{ $planHeight }}%" role="img" aria-label="Rencana {{ $point->planned_quantity }} unit"></div>
                                <div class="w-3 rounded-t bg-brand-500 sm:w-5" style="height: {{ $actualHeight }}%" role="img" aria-label="Aktual {{ $point->actual_quantity }} unit"></div>
                            </div>
                            <span class="mt-2 truncate text-center text-[9px] text-slate-500 sm:text-[10px]">{{ \Illuminate\Support\Carbon::parse($point->period)->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state title="Belum ada data produksi" description="Grafik akan muncul saat order memiliki tanggal dan kuantitas produksi." class="py-8" />
            @endif
        </article>
    </section>

    <section class="mt-5 grid gap-5 lg:grid-cols-2">
        <article class="app-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-base font-bold text-navy-900">Energi berdasarkan mesin</h2>
                <p class="mt-1 text-xs text-slate-500">Akumulasi kWh sesuai relasi mesin pada catatan energi.</p>
            </div>
            @forelse ($energyByMachine as $row)
                <div class="mb-4 last:mb-0">
                    <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                        <span class="truncate font-semibold text-slate-700">{{ $row->machine }}</span>
                        <span class="shrink-0 tabular-nums text-slate-600">{{ number_format($row->kwh, 2, ',', '.') }} kWh</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="{{ $row->machine }}: {{ number_format($row->kwh, 2, ',', '.') }} kWh">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, (float) $row->kwh / $maxMachineEnergy * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state title="Belum ada pemetaan energi ke mesin" description="Data tanpa relasi mesin tidak dapat dikelompokkan." class="py-8" />
            @endforelse
        </article>

        <article class="app-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-base font-bold text-navy-900">Energi berdasarkan proses</h2>
                <p class="mt-1 text-xs text-slate-500">kWh dari catatan energi yang ditautkan ke proses.</p>
            </div>
            @forelse ($energyByProcess as $row)
                <div class="mb-4 last:mb-0">
                    <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                        <span class="truncate font-semibold text-slate-700">{{ $row->process }}</span>
                        <span class="shrink-0 tabular-nums text-slate-600">{{ number_format($row->kwh, 2, ',', '.') }} kWh</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="{{ $row->process }}: {{ number_format($row->kwh, 2, ',', '.') }} kWh">
                        <div class="h-full rounded-full bg-sky-400" style="width: {{ min(100, (float) $row->kwh / $maxProcessEnergy * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state title="Belum ada pemetaan energi ke proses" description="Data tanpa relasi proses tidak dapat dikelompokkan." class="py-8" />
            @endforelse
        </article>

        <article class="app-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-base font-bold text-navy-900">OEE per mesin</h2>
                <p class="mt-1 text-xs text-slate-500">Rata-rata OEE dari pengukuran yang tersimpan per mesin.</p>
            </div>
            @forelse ($oeeByMachine as $row)
                <div class="mb-4 last:mb-0">
                    <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                        <span class="truncate font-semibold text-slate-700">{{ $row->machine }}</span>
                        <span class="shrink-0 tabular-nums text-slate-600">{{ $percent($row->oee) }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, (float) $row->oee * 100) }}%" role="img" aria-label="OEE {{ $percent($row->oee) }}"></div>
                    </div>
                </div>
            @empty
                <x-ui.empty-state title="Belum ada catatan OEE" description="Tambahkan catatan OEE untuk menampilkan perbandingan mesin." class="py-8">
                    <x-ui.button href="{{ route('oee-records.create') }}" variant="secondary">Tambah catatan</x-ui.button>
                </x-ui.empty-state>
            @endforelse
        </article>
    </section>

    <section class="mt-5 grid gap-5 lg:grid-cols-2">
        <article class="app-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-base font-bold text-navy-900">Komponen estimasi costing</h2>
                <p class="mt-1 text-xs text-slate-500">Agregat nilai master produk dan energi yang ditautkan ke order; bukan realisasi biaya per periode.</p>
            </div>
            @foreach ($costComponents as $component)
                <div class="mb-4 last:mb-0">
                    <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                        <span class="font-semibold text-slate-700">{{ $component['label'] }}</span>
                        <span class="shrink-0 tabular-nums text-slate-600">Rp {{ number_format($component['value'], 0, ',', '.') }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full {{ $component['color'] }}" style="width: {{ min(100, $component['value'] / $costScale * 100) }}%"></div>
                    </div>
                </div>
            @endforeach
            <a href="{{ route('costing') }}" class="mt-5 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-900">Lihat costing produk <span aria-hidden="true" class="ml-1">→</span></a>
        </article>

        <article class="app-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-base font-bold text-navy-900">Status order produksi</h2>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($orderCount) }} order yang tersimpan.</p>
            </div>
            @forelse ($orderStatuses as $row)
                <div class="mb-3 flex items-center gap-3 last:mb-0">
                    <x-ui.status-badge :status="$row->status" context="production-order" />
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, (int) $row->total / $maxOrderStatus * 100) }}%"></div>
                    </div>
                    <span class="w-8 text-right text-xs font-semibold tabular-nums text-slate-600">{{ $row->total }}</span>
                </div>
            @empty
                <x-ui.empty-state title="Belum ada order produksi" description="Order yang dibuat akan dirangkum berdasarkan status." class="py-8" />
            @endforelse
            <a href="{{ route('production-orders.index') }}" class="mt-5 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-900">Kelola order <span aria-hidden="true" class="ml-1">→</span></a>
        </article>
    </section>

    <section class="mt-5 grid gap-5 lg:grid-cols-2">
        <article class="app-card p-5 sm:p-6">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div><h2 class="text-base font-bold text-navy-900">Peringatan persediaan</h2><p class="mt-1 text-xs text-slate-500">Item dengan jumlah pada atau di bawah ambang minimum.</p></div>
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Lihat semua</a>
            </div>
            @forelse ($lowStockItems as $item)
                <div class="flex items-center justify-between gap-3 border-t border-slate-100 py-3 first:border-0">
                    <div class="min-w-0"><p class="truncate text-sm font-semibold text-navy-900">{{ $item->name }}</p><p class="text-xs text-slate-500">{{ $item->sku }}</p></div>
                    <span class="shrink-0 text-xs font-semibold text-amber-800">{{ number_format($item->quantity, 2, ',', '.') }} / {{ number_format($item->threshold, 2, ',', '.') }} {{ $item->unit }}</span>
                </div>
            @empty
                <x-ui.empty-state title="Tidak ada persediaan di bawah ambang" description="Item persediaan dengan jumlah rendah akan muncul di sini." class="py-7" />
            @endforelse
        </article>

        <article class="app-card p-5 sm:p-6">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div><h2 class="text-base font-bold text-navy-900">Pemeliharaan terbuka</h2><p class="mt-1 text-xs text-slate-500">Catatan yang belum berstatus Completed.</p></div>
                <a href="{{ route('maintenance-logs.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Lihat semua</a>
            </div>
            @forelse ($maintenanceAlerts as $alert)
                <div class="flex items-center justify-between gap-3 border-t border-slate-100 py-3 first:border-0">
                    <div class="min-w-0"><p class="truncate text-sm font-semibold text-navy-900">{{ $alert->issue }}</p><p class="text-xs text-slate-500">Mesin #{{ $alert->machine_id }}</p></div>
                    <x-ui.status-badge :status="$alert->priority" context="priority" />
                </div>
            @empty
                <x-ui.empty-state title="Tidak ada pemeliharaan terbuka" description="Catatan pemeliharaan yang aktif akan muncul di sini." class="py-7" />
            @endforelse
        </article>
    </section>

    {{-- ─── Time Series IoT: Arus & Daya (tombol buka modal) ─────────────── --}}
    <section class="mt-5">
        <div class="app-card flex flex-wrap items-center justify-between gap-3 p-4">
            <div>
                <h2 class="text-base font-bold text-navy-900">Time Series IoT · Arus &amp; Daya</h2>
                <p class="mt-1 text-xs text-slate-500">Lihat grafik telemetri historis dari perangkat IoT.</p>
            </div>
            <button id="ts-open-modal" type="button" class="app-button inline-flex items-center gap-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Lihat Grafik
            </button>
        </div>
    </section>

    @include('partials.live-monitor')

@endsection

@push('modals')
    {{-- ─── Modal Time Series ───────────────────────────────────────────────── --}}
    <div id="ts-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/50 p-4 pt-10 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="ts-modal-title">
        <div class="w-full max-w-5xl rounded-2xl bg-white shadow-2xl">

            {{-- Header Modal --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <div>
                    <h2 id="ts-modal-title" class="text-base font-bold text-navy-900">Time Series IoT · Arus &amp; Daya</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Data telemetri dari payload IoT.</p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Toggle auto-refresh --}}
                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600">
                        <span>Auto-refresh</span>
                        <button id="ts-autorefresh-toggle"
                            role="switch" aria-checked="false"
                            class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-200 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
                            title="Aktifkan untuk memperbarui otomatis tiap 30 detik">
                            <span class="inline-block h-4 w-4 translate-x-0.5 rounded-full bg-white shadow transition-transform"></span>
                        </button>
                        <span id="ts-autorefresh-label" class="w-6 text-slate-400">Off</span>
                    </label>
                    <span id="ts-last-update" class="text-xs text-slate-400"></span>
                    <button id="ts-close-modal" type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup modal">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Filter --}}
            <div class="flex flex-wrap items-end gap-3 border-b border-slate-100 px-6 py-4">
                <label class="flex flex-col gap-1">
                    <span class="app-label">Resolusi</span>
                    <select id="ts-interval" class="app-input text-sm">
                        <option value="minute">Per Menit</option>
                        <option value="day" selected>Per Hari</option>
                        <option value="month">Per Bulan</option>
                    </select>
                </label>
                <label id="ts-wrap-date" class="flex flex-col gap-1">
                    <span class="app-label">Tanggal</span>
                    <input id="ts-date" class="app-input text-sm" type="date" value="{{ now()->toDateString() }}">
                </label>
                <label id="ts-wrap-month" class="hidden flex-col gap-1">
                    <span class="app-label">Bulan</span>
                    <input id="ts-month" class="app-input text-sm" type="month" value="{{ now()->format('Y-m') }}">
                </label>
                <label class="flex flex-col gap-1">
                    <span class="app-label">Device ID <span class="font-normal text-slate-400">(opsional)</span></span>
                    <input id="ts-device" class="app-input text-sm" type="text" placeholder="Semua perangkat" list="ts-device-list">
                    <datalist id="ts-device-list">
                        @foreach(\App\Models\IotDevice::where('is_active', true)->orderBy('device_id')->get(['device_id','machine_code']) as $dev)
                            <option value="{{ $dev->device_id }}">{{ $dev->device_id }}{{ $dev->machine_code ? ' · '.$dev->machine_code : '' }}</option>
                        @endforeach
                    </datalist>
                </label>
                <button id="ts-load" type="button" class="app-button">Tampilkan</button>
                <button id="ts-export" type="button" class="app-button-secondary text-sm" disabled>Ekspor CSV</button>
            </div>

            {{-- Isi Modal --}}
            <div class="px-6 py-5 space-y-5">

                {{-- KPI --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="app-card flex items-center gap-4 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">A</div>
                        <div>
                            <p class="text-xs text-slate-500">Arus rata-rata</p>
                            <p id="ts-kpi-current" class="text-xl font-extrabold tracking-tight text-navy-900">—</p>
                        </div>
                    </div>
                    <div class="app-card flex items-center gap-4 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">W</div>
                        <div>
                            <p class="text-xs text-slate-500">Daya rata-rata</p>
                            <p id="ts-kpi-power" class="text-xl font-extrabold tracking-tight text-navy-900">—</p>
                        </div>
                    </div>
                </div>

                {{-- Charts --}}
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="app-card p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-navy-900">Arus (A)</h3>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700"><span class="h-2 w-2 rounded-full bg-brand-500"></span>current_a</span>
                        </div>
                        <div class="relative h-48 overflow-x-auto">
                            <div id="ts-empty-current" class="absolute inset-0 flex items-center justify-center">
                                <p class="text-sm text-slate-400">Tekan <strong>Tampilkan</strong> untuk memuat data.</p>
                            </div>
                            <svg id="ts-svg-current" class="hidden h-full min-w-[400px] w-full" viewBox="0 0 720 192" aria-label="Grafik arus"></svg>
                        </div>
                        <div id="ts-lbl-current" class="mt-1 flex justify-between text-[10px] text-slate-400"></div>
                    </div>
                    <div class="app-card p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-navy-900">Daya (W)</h3>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700"><span class="h-2 w-2 rounded-full bg-amber-500"></span>power_w</span>
                        </div>
                        <div class="relative h-48 overflow-x-auto">
                            <div id="ts-empty-power" class="absolute inset-0 flex items-center justify-center">
                                <p class="text-sm text-slate-400">Tekan <strong>Tampilkan</strong> untuk memuat data.</p>
                            </div>
                            <svg id="ts-svg-power" class="hidden h-full min-w-[400px] w-full" viewBox="0 0 720 192" aria-label="Grafik daya"></svg>
                        </div>
                        <div id="ts-lbl-power" class="mt-1 flex justify-between text-[10px] text-slate-400"></div>
                    </div>
                </div>

                {{-- Tabel --}}
                <div class="app-card overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Periode</th>
                                <th class="px-4 py-3 text-right">Arus (A)</th>
                                <th class="px-4 py-3 text-right">Daya (W)</th>
                            </tr>
                        </thead>
                        <tbody id="ts-tbody" class="divide-y divide-slate-100 text-slate-700">
                            <tr><td colspan="3" class="py-8 text-center text-sm text-slate-400">Tekan Tampilkan untuk memuat data.</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>{{-- /isi modal --}}
        </div>{{-- /modal panel --}}
    </div>{{-- /modal backdrop --}}

    <script>
    (function () {
        'use strict';

        /* ── DOM refs ─────────────────────────────────────────────── */
        const modal       = document.getElementById('ts-modal');
        const btnOpen     = document.getElementById('ts-open-modal');
        const btnClose    = document.getElementById('ts-close-modal');
        const toggleBtn   = document.getElementById('ts-autorefresh-toggle');
        const toggleLbl   = document.getElementById('ts-autorefresh-label');
        const SVG_NS      = 'http://www.w3.org/2000/svg';
        const elInt       = document.getElementById('ts-interval');
        const elDate      = document.getElementById('ts-date');
        const elMonth     = document.getElementById('ts-month');
        const wrapDate    = document.getElementById('ts-wrap-date');
        const wrapMonth   = document.getElementById('ts-wrap-month');
        const elDev       = document.getElementById('ts-device');
        const elLoad      = document.getElementById('ts-load');
        const elExport    = document.getElementById('ts-export');
        const elTbody     = document.getElementById('ts-tbody');
        const elUpdated   = document.getElementById('ts-last-update');
        const kpiA        = document.getElementById('ts-kpi-current');
        const kpiW        = document.getElementById('ts-kpi-power');

        /* ── Modal buka / tutup ───────────────────────────────────── */
        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
            stopAutoRefresh();
        }

        btnOpen.addEventListener('click', openModal);
        btnClose.addEventListener('click', closeModal);
        // Klik backdrop (luar panel) tutup modal
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        // Esc tutup modal
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });

        /* ── Toggle auto-refresh ──────────────────────────────────── */
        let autoOn    = false;
        let autoTimer = null;
        const INTERVAL_MS = 30_000;

        function startAutoRefresh() {
            if (autoTimer) return;
            autoTimer = setInterval(load, INTERVAL_MS);
        }
        function stopAutoRefresh() {
            clearInterval(autoTimer);
            autoTimer = null;
        }

        toggleBtn.addEventListener('click', () => {
            autoOn = !autoOn;
            toggleBtn.setAttribute('aria-checked', autoOn);
            toggleBtn.querySelector('span').style.transform = autoOn ? 'translateX(1.25rem)' : 'translateX(0.125rem)';
            toggleBtn.style.backgroundColor = autoOn ? '#2563eb' : '';
            toggleLbl.textContent = autoOn ? 'On' : 'Off';
            toggleLbl.style.color = autoOn ? '#2563eb' : '';
            if (autoOn) { load(); startAutoRefresh(); }
            else        { stopAutoRefresh(); }
        });

        /* ── Interval picker ──────────────────────────────────────── */
        elInt.addEventListener('change', () => {
            const isMonth = elInt.value === 'month';
            wrapDate.classList.toggle('hidden', isMonth);
            wrapDate.classList.toggle('flex',   !isMonth);
            wrapMonth.classList.toggle('hidden', !isMonth);
            wrapMonth.classList.toggle('flex',    isMonth);
        });

        /* ── SVG helpers ──────────────────────────────────────────── */
        function svgEl(tag, attrs) {
            const e = document.createElementNS(SVG_NS, tag);
            Object.entries(attrs).forEach(([k, v]) => e.setAttribute(k, v));
            return e;
        }

        function renderChart(svgId, emptyId, lblId, points, stroke, fill) {
            const svg   = document.getElementById(svgId);
            const empty = document.getElementById(emptyId);
            const lbl   = document.getElementById(lblId);
            svg.innerHTML = '';

            if (!points.length) {
                svg.classList.add('hidden');
                empty.classList.remove('hidden');
                empty.querySelector('p').textContent = 'Tidak ada data pada periode ini.';
                lbl.innerHTML = '';
                return;
            }
            svg.classList.remove('hidden');
            empty.classList.add('hidden');

            const W = 720, H = 192;
            const P = { t: 18, r: 16, b: 8, l: 54 };
            const iW = W - P.l - P.r, iH = H - P.t - P.b;
            const vals = points.map(p => p.v);
            const hi   = Math.max(...vals, 0.001);
            const lo   = Math.min(...vals, 0);
            const rng  = hi - lo || 1;
            const cx   = i => P.l + (points.length < 2 ? iW / 2 : (i / (points.length - 1)) * iW);
            const cy   = v => P.t + iH - ((v - lo) / rng) * iH;

            [0, 0.25, 0.5, 0.75, 1].forEach(f => {
                const y = P.t + iH * (1 - f), val = lo + rng * f;
                svg.appendChild(svgEl('line', { x1: P.l, x2: W - P.r, y1: y, y2: y,
                    stroke: f === 0 ? '#cbd5e1' : '#e2e8f0', 'stroke-width': 1 }));
                const t = svgEl('text', { x: P.l - 4, y: y + 4,
                    'text-anchor': 'end', 'font-size': 10, fill: '#64748b' });
                t.textContent = val >= 1000 ? (val/1000).toFixed(1)+'k'
                              : val >= 100  ? val.toFixed(0)
                              : val >= 10   ? val.toFixed(1) : val.toFixed(2);
                svg.appendChild(t);
            });

            if (points.length > 1) {
                const pts = points.map((p, i) => `${cx(i)},${cy(p.v)}`).join(' ');
                svg.appendChild(svgEl('polygon', { fill,
                    points: `${cx(0)},${P.t + iH} ${pts} ${cx(points.length-1)},${P.t + iH}` }));
                svg.appendChild(svgEl('polyline', { points: pts, fill: 'none',
                    stroke, 'stroke-width': 2.5, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }));
            }

            const showDots = points.length <= 120;
            points.forEach((p, i) => {
                const c = svgEl('circle', { cx: cx(i), cy: cy(p.v),
                    r: showDots ? 4 : 0, fill: '#fff', stroke, 'stroke-width': 2 });
                const tt = document.createElementNS(SVG_NS, 'title');
                tt.textContent = `${p.l}  ·  ${p.v}`;
                c.appendChild(tt);
                svg.appendChild(c);
            });

            const picks = [0, ...(points.length > 2 ? [Math.floor((points.length - 1) / 2)] : []),
                           ...(points.length > 1 ? [points.length - 1] : [])];
            lbl.innerHTML = picks.map(i => `<span>${points[i].l}</span>`).join('');
        }

        /* ── Fetch & render ───────────────────────────────────────── */
        let lastData = [];

        async function load() {
            elLoad.disabled    = true;
            elLoad.textContent = 'Memuat…';

            const interval = elInt.value;
            const date     = interval === 'month'
                ? (elMonth.value ? elMonth.value + '-01' : '')
                : (elDate.value || '');
            const device   = elDev.value.trim();

            const params = new URLSearchParams({ interval });
            if (date)   params.set('date',      date);
            if (device) params.set('device_id', device);

            try {
                const res = await fetch(`/api/time-series?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                lastData = (await res.json()).data ?? [];
            } catch (e) {
                elTbody.innerHTML = `<tr><td colspan="3" class="py-8 text-center text-sm text-red-500">Gagal memuat: ${e.message}</td></tr>`;
                elLoad.disabled    = false;
                elLoad.textContent = 'Tampilkan';
                return;
            }

            if (lastData.length) {
                const avgA = (lastData.reduce((s, r) => s + r.current_a, 0) / lastData.length).toFixed(3);
                const avgW = (lastData.reduce((s, r) => s + r.power_w,   0) / lastData.length).toFixed(1);
                kpiA.textContent = `${avgA} A`;
                kpiW.textContent = `${avgW} W`;
            } else {
                kpiA.textContent = '—';
                kpiW.textContent = '—';
            }

            renderChart('ts-svg-current', 'ts-empty-current', 'ts-lbl-current',
                lastData.map(r => ({ l: r.period, v: r.current_a })), '#2563eb', '#2563eb22');
            renderChart('ts-svg-power',   'ts-empty-power',   'ts-lbl-power',
                lastData.map(r => ({ l: r.period, v: r.power_w   })), '#d97706', '#d9770622');

            elTbody.innerHTML = lastData.length
                ? lastData.map(r => `<tr class="hover:bg-slate-50">
                    <td class="px-4 py-2 font-mono text-xs text-slate-600">${r.period}</td>
                    <td class="px-4 py-2 text-right tabular-nums">${r.current_a}</td>
                    <td class="px-4 py-2 text-right tabular-nums">${r.power_w}</td>
                  </tr>`).join('')
                : `<tr><td colspan="3" class="py-8 text-center text-sm text-slate-400">Tidak ada data pada periode ini.</td></tr>`;

            elExport.disabled     = !lastData.length;
            elUpdated.textContent = 'Update: ' + new Date().toLocaleTimeString('id-ID');
            elLoad.disabled       = false;
            elLoad.textContent    = 'Tampilkan';
        }

        elLoad.addEventListener('click', load);

        elExport.addEventListener('click', () => {
            if (!lastData.length) return;
            const csv = [['Periode','Arus (A)','Daya (W)'],
                         ...lastData.map(r => [r.period, r.current_a, r.power_w])]
                        .map(r => r.join(',')).join('\n');
            const a = Object.assign(document.createElement('a'), {
                href: URL.createObjectURL(new Blob([csv], { type: 'text/csv' })),
                download: `ts-${elInt.value}-${Date.now()}.csv`,
            });
            a.click(); URL.revokeObjectURL(a.href);
        });

    })();
    </script>
@endpush
