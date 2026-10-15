@extends('layouts.app')

@section('content')
    <form method="GET" action="{{ route('energy-records.index') }}" class="app-card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <label>
            <span class="app-label">Cari mesin, kode, event, device, atau order</span>
            <input class="app-input" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama mesin, kode, event ID...">
        </label>
        <label>
            <span class="app-label">Dari tanggal</span>
            <input class="app-input" type="date" name="from" value="{{ $filters['from'] }}">
        </label>
        <label>
            <span class="app-label">Sampai tanggal</span>
            <input class="app-input" type="date" name="to" value="{{ $filters['to'] }}">
        </label>
        <label>
            <span class="app-label">Mesin</span>
            <select class="app-input" name="machine_id">
                <option value="">Semua mesin</option>
                @foreach ($machines as $machine)
                    <option value="{{ $machine->id }}" @selected(($filters['machine_id'] ?? '') == $machine->id)>{{ $machine->name }} · {{ $machine->code }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="app-label">Sumber data</span>
            <select class="app-input" name="source">
                <option value="">Semua sumber</option>
                @foreach ($sources as $source)
                    <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ ['simulation' => 'Simulasi', 'manual' => 'Manual', 'iot' => 'IoT'][$source] ?? str($source)->title() }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="app-label">Production order</span>
            <select class="app-input" name="production_order_id">
                <option value="">Semua order</option>
                @foreach ($orders as $order)
                    <option value="{{ $order->id }}" @selected(($filters['production_order_id'] ?? '') == $order->id)>{{ $order->order_number }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="app-label">Production batch</span>
            <select class="app-input" name="production_batch_id">
                <option value="">Semua batch</option>
                @foreach ($batches as $batch)
                    <option value="{{ $batch->id }}" @selected(($filters['production_batch_id'] ?? '') == $batch->id)>{{ $batch->batch_number }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="app-label">Device</span>
            <select class="app-input" name="device_id">
                <option value="">Semua device</option>
                @foreach ($devices as $device)
                    <option value="{{ $device }}" @selected(($filters['device_id'] ?? '') === $device)>{{ $device }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="app-label">Bucket grafik</span>
            <select class="app-input" name="interval">
                @foreach (['hour' => 'Jam', 'day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['interval'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
            <x-ui.button type="submit">Terapkan filter</x-ui.button>
            <a href="{{ route('energy-records.index') }}" class="app-button-secondary">Reset</a>
        </div>
    </form>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.kpi-card label="Total energi tercatat" :value="number_format($summary['total_kwh'], 2, ',', '.').' kWh'" detail="{{ $summary['record_count'] }} record pada periode pilihan" icon="⚡" />
        <x-ui.kpi-card label="Biaya energi tersimpan" :value="'Rp '.number_format($summary['total_cost'], 0, ',', '.')" detail="Menjumlahkan biaya yang tersimpan pada setiap record" icon="Rp" tone="navy" />
        <x-ui.kpi-card label="Mesin dengan record" :value="number_format($summary['machine_count'], 0, ',', '.')" detail="Mesin berbeda pada hasil filter" icon="M" tone="green" />
        <x-ui.kpi-card label="Rata-rata nilai per record" :value="$summary['average_record_kwh'] === null ? 'Data belum tersedia' : number_format($summary['average_record_kwh'], 2, ',', '.').' kWh'" detail="Rata-rata nilai kWh tersimpan, bukan rata-rata daya" icon="Ø" tone="amber" />
    </div>

    <div class="mb-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(260px,0.8fr)]">
        <x-ui.energy-trend :data="$trend" />
        <section class="app-card p-5 sm:p-6">
            <h2 class="text-base font-bold text-navy-900">Konteks & perbandingan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Periode {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }}–{{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}.
                Nilai biaya adalah biaya aktual yang tersimpan per record, bukan dihitung ulang memakai tarif terbaru.
            </p>
            @if ($previousSummary)
                @php
                    $energyChange = $previousSummary['total_kwh'] > 0 ? (($summary['total_kwh'] - $previousSummary['total_kwh']) / $previousSummary['total_kwh']) * 100 : null;
                    $costChange = $previousSummary['total_cost'] > 0 ? (($summary['total_cost'] - $previousSummary['total_cost']) / $previousSummary['total_cost']) * 100 : null;
                @endphp
                <dl class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Perubahan energi vs periode sebelumnya</dt>
                        <dd class="mt-1 text-lg font-bold text-navy-900">{{ $energyChange === null ? 'Tidak dapat dibandingkan' : number_format($energyChange, 1, ',', '.').'%' }}</dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Perubahan biaya vs periode sebelumnya</dt>
                        <dd class="mt-1 text-lg font-bold text-navy-900">{{ $costChange === null ? 'Tidak dapat dibandingkan' : number_format($costChange, 1, ',', '.').'%' }}</dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs text-slate-500">Periode pembanding: {{ $previousSummary['record_count'] }} record. Persentase tidak dihitung jika baseline nol.</p>
            @else
                <p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Belum ada record pada periode pembanding yang setara.</p>
            @endif
            <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
                Skema saat ini tidak menandai pembacaan sebagai interval atau cumulative meter. Total adalah jumlah nilai kWh yang tersimpan; delta meter, gap, dan anomali sensor tidak dapat disimpulkan tanpa metadata interval dan konfigurasi perangkat.
            </p>
            @if ($summary['last_recorded_at'])
                <p class="mt-3 text-xs text-slate-500">Pembacaan terbaru pada filter: {{ \Illuminate\Support\Carbon::parse($summary['last_recorded_at'])->format('d M Y H:i') }}.</p>
            @endif
        </section>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-base font-bold text-navy-900">Riwayat pembacaan</h2>
            <p class="mt-1 text-xs text-slate-500">Data sumber ditampilkan sesuai label tersimpan; badge IoT bukan indikator perangkat online.</p>
        </div>
        <x-ui.button :href="route('energy-records.create')" variant="secondary">Tambah catatan energi</x-ui.button>
    </div>

    <x-ui.table>
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3 text-left">Waktu</th>
                <th class="px-4 py-3 text-left">Mesin</th>
                <th class="px-4 py-3 text-left">Alokasi produksi</th>
                <th class="px-4 py-3 text-right">Energi</th>
                <th class="px-4 py-3 text-right">Biaya tersimpan</th>
                <th class="px-4 py-3 text-left">Sumber / device</th>
                <th class="px-4 py-3 text-right">Detail</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($records as $record)
                <tr>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $record->recorded_at?->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">
                        @if ($record->machine)
                            <a class="font-semibold text-brand-700 hover:underline" href="{{ route('machines.show', $record->machine_id) }}">{{ $record->machine->name }}</a>
                            <div class="text-xs text-slate-500">{{ $record->machine->code }}</div>
                        @else
                            <span class="text-slate-500">Mesin belum ditautkan</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">
                        @if ($record->productionBatch)
                            <div>{{ $record->productionBatch->batch_number }}</div>
                            <div class="text-xs text-slate-500">{{ $record->productionBatch->productionOrder?->order_number }} · {{ $record->productionBatch->productionOrder?->product?->name }}</div>
                        @elseif ($record->productionOrder)
                            <div>{{ $record->productionOrder->order_number }}</div>
                            <div class="text-xs text-slate-500">{{ $record->productionOrder->product?->name }}</div>
                        @else
                            <span class="text-slate-500">Belum dialokasikan ke produksi</span>
                        @endif
                        @if ($record->routingOperation)
                            <div class="text-xs text-slate-500">{{ $record->routingOperation->name }}</div>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-navy-900">{{ number_format((float) $record->kwh, 2, ',', '.') }} kWh</td>
                    <td class="whitespace-nowrap px-4 py-3 text-right text-slate-700">Rp {{ number_format((float) $record->cost, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <x-ui.status-badge :status="$record->source" context="source" />
                        <div class="mt-1 text-xs text-slate-500">{{ $record->device_id ?: 'Tanpa device ID' }}</div>
                        <div class="mt-1 max-w-48 truncate font-mono text-[11px] text-slate-400" title="{{ $record->external_event_id }}">{{ $record->external_event_id ?: 'Tanpa event ID' }}</div>
                    </td>
                    <td class="px-4 py-3 text-right"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('energy-records.show', $record->id) }}">Buka detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-0"><x-ui.empty-state title="Belum ada pembacaan energi" description="Periksa rentang waktu, sumber, mesin, atau device. Catatan baru dapat dimasukkan manual atau dikirim melalui endpoint IoT yang tersedia." /></td></tr>
            @endforelse
        </tbody>
    </x-ui.table>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500">Menampilkan {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} dari {{ $records->total() }} record</p>
        {{ $records->links() }}
    </div>
@endsection
