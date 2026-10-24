@extends('layouts.app')

@section('content')
    <section class="app-card mb-5 p-5 sm:p-7">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-start">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-2xl font-extrabold tracking-tight text-navy-900">{{ $machine->name }}</h2>
                    <x-ui.status-badge :status="$machine->status" context="machine" />
                    @if ($machine->simulated)
                        <x-ui.status-badge status="simulation" context="source" />
                    @endif
                </div>
                <p class="mt-1 font-mono text-sm text-slate-500">{{ $machine->code }} · {{ $machine->category ?: 'Kategori tidak ditentukan' }}</p>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                    <div><dt class="text-xs text-slate-500">Lokasi / workstation</dt><dd class="mt-1 font-semibold text-navy-900">{{ $machine->location ?: 'Data belum tersedia' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Daya nominal</dt><dd class="mt-1 font-semibold text-navy-900">{{ number_format((float) $machine->rated_kw, 2, ',', '.') }} kW <span class="font-normal text-slate-500">(spesifikasi, bukan daya aktual)</span></dd></div>
                    <div><dt class="text-xs text-slate-500">Kapasitas</dt><dd class="mt-1 font-semibold text-navy-900">{{ $machine->capacity ? number_format((float) $machine->capacity, 2, ',', '.').' '.($machine->capacity_unit ?: '') : 'Data belum tersedia' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Target OEE</dt><dd class="mt-1 font-semibold text-navy-900">{{ number_format((float) $machine->target_oee, 1, ',', '.') }}%</dd></div>
                    <div><dt class="text-xs text-slate-500">Perangkat terdaftar</dt><dd class="mt-1 font-semibold text-navy-900">{{ $machine->device_id ?: 'Tidak ditautkan' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Status koneksi IoT</dt><dd class="mt-1 font-semibold text-slate-600">Tidak tersedia — backend tidak menyimpan heartbeat</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Pembacaan energi terakhir pada periode</dt><dd class="mt-1 font-semibold text-navy-900">{{ $summary['last_recorded_at'] ? \Illuminate\Support\Carbon::parse($summary['last_recorded_at'])->format('d M Y H:i') : 'Tidak ada pembacaan pada periode terpilih' }}</dd></div>
                </dl>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.button :href="route('machines.edit', $machine->id)" variant="secondary">Edit mesin</x-ui.button>
                <x-ui.button :href="route('maintenance-logs.create', ['machine_id' => $machine->id])" variant="secondary">Catat maintenance</x-ui.button>
                <x-ui.button :href="route('energy-records.create', ['machine_id' => $machine->id])">Catat energi</x-ui.button>
            </div>
        </div>
    </section>

    <nav aria-label="Bagian detail mesin" class="app-card mb-5 flex gap-2 overflow-x-auto p-2 text-sm">
        <a class="whitespace-nowrap rounded-lg px-3 py-2 font-semibold text-brand-800 hover:bg-brand-50 focus-visible:outline-brand-600" href="#energy-analytics">Energy Analytics</a>
        <a class="whitespace-nowrap rounded-lg px-3 py-2 font-semibold text-brand-800 hover:bg-brand-50 focus-visible:outline-brand-600" href="#production-history">Production History</a>
        <a class="whitespace-nowrap rounded-lg px-3 py-2 font-semibold text-brand-800 hover:bg-brand-50 focus-visible:outline-brand-600" href="#performance-history">Performance</a>
        <a class="whitespace-nowrap rounded-lg px-3 py-2 font-semibold text-brand-800 hover:bg-brand-50 focus-visible:outline-brand-600" href="#maintenance-history">Maintenance</a>
        <a class="whitespace-nowrap rounded-lg px-3 py-2 font-semibold text-brand-800 hover:bg-brand-50 focus-visible:outline-brand-600" href="#energy-record-history">Energy Records</a>
    </nav>

    <form method="GET" action="{{ route('machines.show', $machine->id) }}" class="app-card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <label class="sm:col-span-2"><span class="app-label">Cari event, device, atau order</span><span class="relative block"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg><input class="app-input pl-10" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Event ID, device, order..."></span></label>
        <label><span class="app-label">Dari tanggal</span><input class="app-input" type="date" name="from" value="{{ $filters['from'] }}"></label>
        <label><span class="app-label">Sampai tanggal</span><input class="app-input" type="date" name="to" value="{{ $filters['to'] }}"></label>
        <label><span class="app-label">Sumber</span><select class="app-input" name="source"><option value="">Semua sumber</option>@foreach (['simulation' => 'Simulasi', 'manual' => 'Manual', 'iot' => 'IoT'] as $value => $label)<option value="{{ $value }}" @selected(($filters['source'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label><span class="app-label">Production order</span><select class="app-input" name="production_order_id"><option value="">Semua order</option>@foreach ($orders as $order)<option value="{{ $order->id }}" @selected(($filters['production_order_id'] ?? '') == $order->id)>{{ $order->order_number }}</option>@endforeach</select></label>
        <label><span class="app-label">Production batch</span><select class="app-input" name="production_batch_id"><option value="">Semua batch</option>@foreach ($batches as $batch)<option value="{{ $batch->id }}" @selected(($filters['production_batch_id'] ?? '') == $batch->id)>{{ $batch->batch_number }}</option>@endforeach</select></label>
        <label><span class="app-label">Device ID</span><select class="app-input" name="device_id"><option value="">Semua device</option>@foreach ($devices as $device)<option value="{{ $device }}" @selected(($filters['device_id'] ?? '') === $device)>{{ $device }}</option>@endforeach</select></label>
        <label><span class="app-label">Bucket grafik</span><select class="app-input" name="interval">@foreach (['hour' => 'Jam', 'day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan'] as $value => $label)<option value="{{ $value }}" @selected($filters['interval'] === $value)>{{ $label }}</option>@endforeach</select></label>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4"><x-ui.button type="submit">Terapkan filter</x-ui.button><a href="{{ route('machines.show', $machine->id) }}" class="app-button-secondary">Reset</a></div>
    </form>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card label="Energi tercatat" :value="number_format($summary['total_kwh'], 2, ',', '.').' kWh'" detail="{{ $summary['record_count'] }} record · {{ $filters['from'] }} sampai {{ $filters['to'] }}" icon="⚡" />
        <x-ui.kpi-card label="Rata-rata nilai per record" :value="$summary['average_record_kwh'] === null ? 'Data belum tersedia' : number_format($summary['average_record_kwh'], 2, ',', '.').' kWh'" detail="Bukan rata-rata daya atau energi per jam" icon="Ø" tone="blue" />
        <x-ui.kpi-card label="Biaya energi tersimpan" :value="'Rp '.number_format($summary['total_cost'], 0, ',', '.')" detail="Jumlah biaya per event dengan tarif historisnya" icon="Rp" tone="navy" />
        <x-ui.kpi-card label="OEE periode terukur" :value="$oee === null ? 'Data belum tersedia' : number_format($oee * 100, 1, ',', '.').' %'" :detail="((int) ($validOee->valid_count ?? 0)).' catatan lengkap · Target '.number_format((float) $machine->target_oee, 1, ',', '.').'% · '.($oeeTargetDifference === null ? 'selisih belum dapat dihitung' : number_format($oeeTargetDifference, 1, ',', '.').' pp dari target')" icon="O" tone="green" />
        <x-ui.kpi-card label="Availability" :value="$availability === null ? 'Data belum tersedia' : number_format($availability * 100, 1, ',', '.').' %'" detail="Total operating minutes / planned minutes" icon="A" tone="green" />
        <x-ui.kpi-card label="Performance" :value="$performance === null ? 'Data belum tersedia' : number_format($performance * 100, 1, ',', '.').' %'" detail="Σ(ideal cycle × total output) / operating time" icon="P" tone="blue" />
        <x-ui.kpi-card label="Quality" :value="$quality === null ? 'Data belum tersedia' : number_format($quality * 100, 1, ',', '.').' %'" detail="Good output / total output" icon="Q" tone="green" />
        <x-ui.kpi-card label="Kontribusi biaya mesin" :value="$costContribution === null ? 'Tidak dapat dihitung' : number_format($costContribution * 100, 1, ',', '.').' %'" detail="Biaya mesin / biaya semua mesin pada filter sama" icon="%" tone="amber" />
    </div>

    <div class="mb-5 grid gap-5 xl:grid-cols-[minmax(0,1.25fr)_minmax(300px,0.75fr)]">
        <x-ui.energy-trend id="energy-analytics" :data="$trend" title="Energy analytics · {{ $machine->name }}" />
        <section class="app-card p-5 sm:p-6">
            <h2 class="text-base font-bold text-navy-900">Intensitas energi & biaya</h2>
            @if ($operatingHours > 0)
                <dl class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Jam operasi tercatat</dt><dd class="mt-1 font-bold text-navy-900">{{ number_format($operatingHours, 1, ',', '.') }} jam</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Energi / jam operasi tercatat</dt><dd class="mt-1 font-bold text-navy-900">{{ number_format($energyPerOperatingHour, 2, ',', '.') }} kWh/jam</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Biaya / jam operasi tercatat</dt><dd class="mt-1 font-bold text-navy-900">Rp {{ number_format($costPerOperatingHour, 0, ',', '.') }}/jam</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Record OEE lengkap</dt><dd class="mt-1 font-bold text-navy-900">{{ (int) ($validOee->valid_count ?? 0) }} / {{ $oeeRecordCount }}</dd></div>
                </dl>
                <p class="mt-3 text-xs leading-5 text-slate-500">Energi/biaya dibagi total jam operasi dari catatan OEE harian pada rentang tanggal yang sama. Ini rasio periode, bukan daya aktual pada setiap interval.</p>
            @else
                <x-ui.empty-state class="mt-3" title="Jam operasi belum tersedia" description="Catatan OEE yang memuat planned time, operating time, ideal cycle, dan output diperlukan untuk menghitung intensitas per jam." />
            @endif
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
                <strong>Belum dapat dihitung dari skema saat ini:</strong> average/peak power, gap data, reset cumulative meter, idle/downtime, dan freshness terhadap interval kirim yang diharapkan. Jenis meter serta frekuensi pembacaan belum disimpan; anomaly detection karena itu belum diaktifkan agar tidak menghasilkan peringatan tanpa baseline yang valid.
            </div>
            @if ($previousSummary)
                @php
                    $energyChange = $previousSummary['total_kwh'] > 0 ? (($summary['total_kwh'] - $previousSummary['total_kwh']) / $previousSummary['total_kwh']) * 100 : null;
                    $costChange = $previousSummary['total_cost'] > 0 ? (($summary['total_cost'] - $previousSummary['total_cost']) / $previousSummary['total_cost']) * 100 : null;
                @endphp
                <p class="mt-4 text-sm text-slate-700">Perbandingan periode sebelumnya: energi {{ $energyChange === null ? 'tidak dapat dibandingkan' : number_format($energyChange, 1, ',', '.').'%' }}, biaya {{ $costChange === null ? 'tidak dapat dibandingkan' : number_format($costChange, 1, ',', '.').'%' }}.</p>
            @else
                <p class="mt-4 text-sm text-slate-500">Belum ada record dalam periode pembanding dengan panjang yang sama.</p>
            @endif
        </section>
    </div>

    <section id="production-history" class="app-card mb-5 p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-base font-bold text-navy-900">Production history tertaut</h2><p class="mt-1 text-xs text-slate-500">Hanya batch yang secara eksplisit ditautkan ke record energi ini.</p></div>
        </div>
        @if ($orderEnergy->isNotEmpty())
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500"><tr><th class="py-2">Order / produk</th><th class="py-2 text-right">Energi</th><th class="py-2 text-right">Biaya tersimpan</th><th class="py-2 text-right">Biaya / unit baik</th><th class="py-2 text-right">Biaya / total output</th><th class="py-2 text-right">Record</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($orderEnergy as $row)
                            <tr>
                                <td class="py-3"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('production-orders.show', $row['order']->id) }}">{{ $row['order']->order_number }}</a><div class="text-xs text-slate-500">{{ $row['order']->product?->name ?: 'Produk tidak tersedia' }}</div></td>
                                <td class="py-3 text-right">{{ number_format($row['total_kwh'], 2, ',', '.') }} kWh</td>
                                <td class="py-3 text-right">Rp {{ number_format($row['total_cost'], 0, ',', '.') }}</td>
                                <td class="py-3 text-right">{{ $row['cost_per_good_unit'] === null ? 'Tidak dapat dihitung' : 'Rp '.number_format($row['cost_per_good_unit'], 2, ',', '.') }}</td>
                                <td class="py-3 text-right">{{ $row['cost_per_output_unit'] === null ? 'Tidak dapat dihitung' : 'Rp '.number_format($row['cost_per_output_unit'], 2, ',', '.') }}</td>
                                <td class="py-3 text-right">{{ $row['record_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-500">Belum ada record energi yang tertaut langsung ke production order pada filter ini.</p>
        @endif
        @if ($linkedBatches->isNotEmpty())
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="text-xs uppercase text-slate-500"><tr><th class="py-2">Batch / Order</th><th class="py-2">Produk</th><th class="py-2">Waktu</th><th class="py-2 text-right">Energi mesin</th><th class="py-2 text-right">Biaya energi</th><th class="py-2 text-right">Output aktual / baik</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($linkedBatches as $batch)
                            <tr>
                                <td class="py-3"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('production-orders.batches.show', [$batch->production_order_id, $batch->id]) }}">{{ $batch->batch_number }}</a><div class="text-xs text-slate-500">{{ $batch->productionOrder?->order_number }}</div></td>
                                <td class="py-3">{{ $batch->productionOrder?->product?->name ?: 'Data belum tersedia' }}</td>
                                <td class="py-3">{{ $batch->started_at?->format('d M Y H:i') ?: 'Waktu mulai tidak tercatat' }}<div class="text-xs text-slate-500">{{ $batch->completed_at?->format('d M Y H:i') ?: 'Belum selesai / tidak tercatat' }}</div></td>
                                <td class="py-3 text-right">{{ number_format((float) $batch->energyRecords->sum('kwh'), 2, ',', '.') }} kWh</td>
                                <td class="py-3 text-right">Rp {{ number_format((float) $batch->energyRecords->sum('cost'), 0, ',', '.') }}</td>
                                <td class="py-3 text-right">{{ number_format((int) $batch->actual_quantity) }} / {{ number_format((int) $batch->good_quantity) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-slate-500">Energi per unit baik: {{ $energyPerGoodUnit === null ? 'Tidak dapat dihitung—output baik atau tautan batch tidak tersedia' : number_format($energyPerGoodUnit, 3, ',', '.').' kWh/unit baik' }}. Energi per total output: {{ $energyPerOutputUnit === null ? 'Tidak dapat dihitung—output aktual belum tersedia' : number_format($energyPerOutputUnit, 3, ',', '.').' kWh/unit' }}.</p>
        @else
            <x-ui.empty-state class="mt-3" title="Belum ada energi yang dialokasikan ke batch" description="Record energi tanpa batch tetap dihitung pada konsumsi mesin, tetapi tidak dipaksakan menjadi konsumsi produksi tertentu." />
        @endif
    </section>

    <section id="performance-history" class="app-card mb-5 p-5 sm:p-6">
        <div><h2 class="text-base font-bold text-navy-900">Performance history</h2><p class="mt-1 text-xs text-slate-500">Perhitungan periode menggabungkan input mentah catatan OEE yang lengkap.</p></div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[620px] text-left text-sm">
                <thead class="text-xs uppercase text-slate-500"><tr><th class="py-2">Tanggal</th><th class="py-2 text-right">Planned / operating</th><th class="py-2 text-right">Total / good output</th><th class="py-2 text-right">OEE tersimpan</th><th class="py-2">Catatan</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($oeeRecords as $record)
                        <tr><td class="py-3">{{ $record->recorded_on->format('d M Y') }}</td><td class="py-3 text-right">{{ $record->planned_minutes }} / {{ $record->operating_minutes }} menit</td><td class="py-3 text-right">{{ number_format($record->total_output) }} / {{ number_format($record->good_output) }}</td><td class="py-3 text-right">{{ number_format((float) $record->oee * 100, 1, ',', '.') }}%</td><td class="py-3 text-slate-600">{{ $record->notes ?: '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-500">Belum ada catatan OEE pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $oeeRecords->links() }}</div>
    </section>

    <section id="maintenance-history" class="app-card mb-5 p-5 sm:p-6">
        <div><h2 class="text-base font-bold text-navy-900">Maintenance history</h2><p class="mt-1 text-xs text-slate-500">Log maintenance tersimpan untuk mesin ini.</p></div>
        <p class="mt-2 text-sm text-slate-600">Pemeliharaan berikutnya: {{ $nextMaintenance ? $nextMaintenance->issue.' · '.$nextMaintenance->scheduled_at->format('d M Y') : 'Tidak ada jadwal mendatang yang tercatat' }}. Skema belum mencatat durasi downtime atau hasil perbandingan energi sebelum/sesudah maintenance.</p>
        @if ($maintenanceLogs->isNotEmpty())
            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($maintenanceLogs as $log)
                    <li class="flex flex-wrap items-start justify-between gap-3 py-3">
                        <div><p class="font-semibold text-navy-900">{{ $log->issue }}</p><p class="mt-1 text-xs text-slate-500">{{ $log->scheduled_at?->format('d M Y') ?: 'Tanggal belum dijadwalkan' }} · {{ $log->notes ?: 'Tidak ada catatan tambahan' }}</p></div>
                        <div class="flex items-center gap-2"><x-ui.status-badge :status="$log->priority" context="priority" /><x-ui.status-badge :status="$log->status" context="maintenance" /></div>
                    </li>
                @endforeach
            </ul>
        @else
            <x-ui.empty-state class="mt-3" title="Belum ada log maintenance" description="Riwayat pemeliharaan mesin akan muncul jika log terkait telah dicatat." />
        @endif
    </section>

    <section id="energy-record-history" class="app-card p-5 sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><h2 class="text-base font-bold text-navy-900">Energy record history</h2><p class="mt-1 text-xs text-slate-500">Biaya dan event individual yang dapat ditelusuri kembali.</p></div>
            <p class="text-xs text-slate-500">Daya nominal {{ number_format((float) $machine->rated_kw, 2, ',', '.') }} kW bukan pengukuran aktual.</p>
        </div>
        <x-ui.table class="mt-4">
            <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Order / batch / operasi</th><th class="px-4 py-3 text-right">kWh</th><th class="px-4 py-3 text-right">Biaya</th><th class="px-4 py-3">Sumber / event</th><th class="px-4 py-3 text-right">Detail</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($energyRecords as $record)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">{{ $record->recorded_at?->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm">{{ $record->productionOrder?->order_number ?: $record->productionBatch?->productionOrder?->order_number ?: 'Belum dialokasikan ke produksi' }}<div class="text-xs text-slate-500">{{ $record->productionBatch?->batch_number ?: $record->routingOperation?->name ?: $record->process?->name ?: '' }}</div></td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $record->kwh, 2, ',', '.') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">Rp {{ number_format((float) $record->cost, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><x-ui.status-badge :status="$record->source" context="source" /><div class="mt-1 max-w-48 truncate text-xs text-slate-500" title="{{ $record->external_event_id }}">{{ $record->external_event_id ?: 'Event ID tidak tersedia' }}</div></td>
                        <td class="px-4 py-3 text-right"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('energy-records.show', $record->id) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-0"><x-ui.empty-state title="Tidak ada pembacaan sesuai filter" description="Ubah periode atau sumber untuk melihat histori yang tersedia." /></td></tr>
                @endforelse
            </tbody>
        </x-ui.table>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><p class="text-xs text-slate-500">{{ $energyRecords->total() }} record cocok dengan filter.</p>{{ $energyRecords->links() }}</div>
    </section>
@endsection
