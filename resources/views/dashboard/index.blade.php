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
                <p class="mt-1 text-xs text-slate-500">Agregat nilai master BOM/produk dan energi yang ditautkan ke order; bukan realisasi biaya per periode.</p>
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
@endsection
