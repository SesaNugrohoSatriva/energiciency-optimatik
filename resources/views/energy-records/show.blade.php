@extends('layouts.app')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Event {{ $record->external_event_id ?: 'tanpa external event ID' }} · ID internal #{{ $record->id }}</p>
        <div class="flex gap-2">
            <x-ui.button :href="route('energy-records.index')" variant="secondary">Kembali ke monitoring</x-ui.button>
            <x-ui.button :href="route('energy-records.edit', $record->id)">Edit data</x-ui.button>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(300px,0.75fr)]">
        <section class="app-card p-5 sm:p-7">
            <h2 class="text-base font-bold text-navy-900">Pembacaan tersimpan</h2>
            <dl class="mt-4 grid gap-x-6 sm:grid-cols-2">
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Waktu pencatatan</dt><dd class="mt-1 text-sm font-medium">{{ $record->recorded_at?->format('d M Y H:i:s') ?: '—' }}</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Sumber data</dt><dd class="mt-1"><x-ui.status-badge :status="$record->source" context="source" /></dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Mesin</dt><dd class="mt-1 text-sm font-medium">@if ($record->machine)<a class="text-brand-700 hover:underline" href="{{ route('machines.show', $record->machine_id) }}">{{ $record->machine->name }} · {{ $record->machine->code }}</a>@else Mesin belum ditautkan @endif</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Device ID</dt><dd class="mt-1 text-sm font-medium">{{ $record->device_id ?: 'Tidak tersedia' }}</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Energi per record</dt><dd class="mt-1 text-sm font-medium">{{ number_format((float) $record->kwh, 3, ',', '.') }} kWh</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Tarif yang tersimpan</dt><dd class="mt-1 text-sm font-medium">Rp {{ number_format((float) $record->tariff_per_kwh, 2, ',', '.') }} / kWh</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Biaya tersimpan</dt><dd class="mt-1 text-sm font-medium">Rp {{ number_format((float) $record->cost, 0, ',', '.') }}</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">External event ID</dt><dd class="mt-1 break-all font-mono text-sm">{{ $record->external_event_id ?: 'Tidak tersedia' }}</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Process</dt><dd class="mt-1 text-sm font-medium">{{ $record->process?->name ?: 'Tidak ditautkan' }}</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Routing operation</dt><dd class="mt-1 text-sm font-medium">{{ $record->routingOperation?->name ?: 'Tidak ditautkan' }}</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Production order</dt><dd class="mt-1 text-sm font-medium">{{ $record->productionOrder?->order_number ?: $record->productionBatch?->productionOrder?->order_number ?: 'Belum dialokasikan ke produksi' }}</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Production batch</dt><dd class="mt-1 text-sm font-medium">{{ $record->productionBatch?->batch_number ?: 'Tidak ditautkan' }}</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-xs font-semibold uppercase text-slate-500">Status validasi</dt><dd class="mt-1 text-sm font-medium">Tidak tersimpan sebagai field terpisah</dd></div>
            </dl>
            <p class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">Biaya merupakan nilai yang disimpan untuk event ini dan menggunakan tarif yang ditampilkan. Perangkat tidak memiliki status online/heartbeat dalam skema saat ini.</p>
        </section>

        <section class="app-card p-5 sm:p-7">
            <h2 class="text-base font-bold text-navy-900">Payload perangkat</h2>
            <p class="mt-1 text-xs text-slate-500">Payload tersimpan setelah penyamaran key yang mengindikasikan token, secret, password, credential, atau API key.</p>
            @if ($payload !== null)
                <pre class="mt-4 max-h-[32rem] overflow-auto rounded-lg bg-slate-950 p-4 text-xs leading-5 text-slate-100">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <x-ui.empty-state class="mt-4" title="Payload tidak tersedia" description="Record ini tidak menyimpan objek payload JSON." />
            @endif
        </section>
    </div>
@endsection
