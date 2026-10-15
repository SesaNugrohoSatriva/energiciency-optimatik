@extends('layouts.app')

@section('content')
    @php
        $columns = match ($key) {
            'machines' => ['code', 'name', 'category', 'location', 'rated_kw', 'target_oee', 'status', 'device_id'],
            'processes' => ['code', 'name', 'machine_id', 'setup_time_minutes', 'ideal_cycle_seconds', 'estimated_kwh'],
            'products' => ['sku', 'name', 'category', 'base_unit', 'batch_size', 'target_oee', 'is_active', 'material_cost', 'labor_cost', 'overhead_cost'],
            'production-orders' => ['order_number', 'product_id', 'status', 'planned_quantity', 'actual_quantity', 'good_quantity', 'scheduled_at'],
            'inventory' => ['sku', 'name', 'quantity', 'threshold', 'unit', 'unit_cost', 'location'],
            'maintenance-logs' => ['machine_id', 'issue', 'priority', 'status', 'scheduled_at'],
            'energy-records' => ['machine_id', 'production_order_id', 'production_batch_id', 'device_id', 'external_event_id', 'kwh', 'cost', 'recorded_at', 'source', 'payload'],
            'bom-items' => ['bom_id', 'inventory_item_id', 'component_sku', 'component_name', 'quantity_per_unit', 'scrap_allowance_percent', 'unit_cost'],
            'oee-records' => ['machine_id', 'production_batch_id', 'routing_operation_id', 'recorded_on', 'planned_minutes', 'operating_minutes', 'ideal_cycle_minutes', 'total_output', 'good_output', 'oee'],
        };
        $sortable = [
            'machines' => ['name', 'code', 'status', 'rated_kw'],
            'processes' => ['name', 'code', 'estimated_kwh'],
            'products' => ['name', 'sku', 'batch_size'],
            'production-orders' => ['order_number', 'status', 'scheduled_at'],
            'inventory' => ['name', 'sku', 'quantity'],
            'maintenance-logs' => ['scheduled_at', 'priority', 'status'],
            'energy-records' => ['recorded_at', 'kwh', 'cost', 'source'],
            'bom-items' => ['component_name', 'component_sku', 'unit_cost'],
            'oee-records' => ['recorded_on', 'oee'],
        ][$key];
        $statusContext = match ($key) {
            'machines' => 'machine',
            'production-orders' => 'production-order',
            'maintenance-logs' => 'maintenance',
            'energy-records' => 'source',
            'products' => 'boolean',
            default => null,
        };
    @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <p class="max-w-2xl text-sm text-slate-500">Gunakan pencarian atau filter untuk menemukan data. Perubahan disimpan langsung pada workspace aplikasi.</p>
        <x-ui.button :href="route($key.'.create')">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Tambah {{ $label }}
        </x-ui.button>
    </div>

    <form method="GET" action="{{ route($key.'.index') }}" class="app-card mb-4 flex flex-col gap-3 p-3 sm:flex-row sm:items-center">
        <label class="relative min-w-0 flex-1">
            <span class="sr-only">Cari {{ strtolower($label) }}</span>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
            <input type="search" name="search" value="{{ $search }}" placeholder="Cari {{ strtolower($label) }}..." class="app-input pl-9">
        </label>
        @if (count($filterOptions))
            <label class="sm:w-56">
                <span class="sr-only">Filter {{ $filterLabel }}</span>
                <select name="filter" class="app-input">
                    <option value="">Semua status / sumber</option>
                    @foreach ($filterOptions as $option)
                        <option value="{{ $option }}" @selected($filter === $option)>{{ strtolower($option) === 'iot' ? 'IoT' : str($option)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <label class="sm:w-48">
            <span class="sr-only">Urutkan berdasarkan</span>
            <select name="sort" class="app-input">
                <option value="">Urutan terbaru</option>
                @foreach ($sortable as $column)
                    <option value="{{ $column }}" @selected($sort === $column)>{{ str($column)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </label>
        <label class="sm:w-32">
            <span class="sr-only">Arah pengurutan</span>
            <select name="direction" class="app-input">
                <option value="desc" @selected($direction === 'desc')>Menurun</option>
                <option value="asc" @selected($direction === 'asc')>Menaik</option>
            </select>
        </label>
        <x-ui.button type="submit" variant="secondary">Terapkan</x-ui.button>
        @if ($search || $filter || $sort)
            <a href="{{ route($key.'.index') }}" class="px-2 py-2 text-sm font-semibold text-brand-700 hover:text-brand-900">Reset</a>
        @endif
    </form>

    <x-ui.table>
        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
            <tr>
                @foreach ($columns as $column)
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5">{{ str($column)->replace('_', ' ')->title() }}</th>
                @endforeach
                <th scope="col" class="px-4 py-3.5 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($records as $record)
                <tr class="transition hover:bg-slate-50/80">
                    @foreach ($columns as $column)
                        @php($value = data_get($record, $column))
                        <td class="whitespace-nowrap px-4 py-3.5 text-slate-700">
                            @if (in_array($column, ['status', 'source', 'priority']))
                                <x-ui.status-badge :status="$value ?? '—'" :context="$column === 'source' ? 'source' : ($column === 'priority' ? 'priority' : $statusContext)" />
                            @elseif ($column === 'is_active')
                                <x-ui.status-badge :status="$value ? 'Active' : 'Inactive'" context="boolean" />
                            @elseif ($column === 'bom_id')
                                {{ $record->bom?->bom_number ?? '—' }}
                            @elseif ($column === 'inventory_item_id')
                                {{ $record->inventoryItem?->sku ?? 'Belum ditautkan' }}
                            @elseif ($column === 'production_batch_id')
                                {{ ($key === 'oee-records' ? $record->batch?->batch_number : $record->productionBatch?->batch_number) ?? '—' }}
                            @elseif ($column === 'routing_operation_id')
                                {{ $record->routingOperation?->name ?? '—' }}
                            @elseif ($column === 'payload')
                                @php($payloadText = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $value)
                                <code class="block max-w-56 truncate rounded bg-slate-50 px-2 py-1 text-xs text-slate-600" title="{{ $payloadText }}">{{ $payloadText ?: '—' }}</code>
                            @elseif (in_array($column, ['cost', 'unit_cost', 'material_cost', 'labor_cost', 'overhead_cost']))
                                {{ $value === null ? '—' : 'Rp '.number_format((float) $value, 0, ',', '.') }}
                            @elseif (in_array($column, ['kwh', 'estimated_kwh', 'quantity', 'threshold', 'quantity_per_unit']))
                                {{ $value === null ? '—' : number_format((float) $value, 2, ',', '.') }}
                            @elseif ($value instanceof \Illuminate\Support\Carbon)
                                {{ $value->format('d M Y H:i') }}
                            @else
                                {{ $value ?? '—' }}
                            @endif
                        </td>
                    @endforeach
                    <td class="px-4 py-3.5">
                        <div class="flex items-center justify-end gap-2">
                            <a class="rounded-md px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100" href="{{ route($key.'.show', $record->id) }}">Detail<span class="sr-only"> {{ $label }} {{ $record->id }}</span></a>
                            <a class="rounded-md px-2.5 py-1.5 text-xs font-bold text-brand-700 hover:bg-brand-50" href="{{ route($key.'.edit', $record->id) }}">Edit<span class="sr-only"> {{ $label }} {{ $record->id }}</span></a>
                            <x-ui.confirm-delete :action="route($key.'.destroy', $record->id)" :label="$label" :identifier="$record->id" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) + 1 }}" class="p-0">
                    <x-ui.empty-state :title="$search || $filter ? 'Tidak ada hasil yang cocok' : 'Belum ada '.$label" :description="$search || $filter ? 'Sesuaikan kata pencarian atau hapus filter untuk melihat data lainnya.' : 'Tambahkan data untuk mulai mengelola informasi ini.'">
                        @if (! $search && ! $filter)
                            <x-ui.button :href="route($key.'.create')">Tambah {{ $label }}</x-ui.button>
                        @endif
                    </x-ui.empty-state>
                </td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500">Menampilkan {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} dari {{ $records->total() }} data</p>
        {{ $records->links() }}
    </div>
@endsection
