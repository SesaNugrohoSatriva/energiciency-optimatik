@extends('layouts.app')

@section('content')
    @php
        $columns = match ($key) {
            'machines' => ['code','name','category','location','capacity','capacity_unit','rated_kw','target_oee','status','device_id','simulated','operating_cost_per_hour'],
            'processes' => ['code','name','machine_id','setup_time_minutes','ideal_cycle_seconds','labor_required','capacity','estimated_kwh','work_instructions','quality_criteria','description'],
            'products' => ['sku','name','category','base_unit','is_active','batch_size','target_oee','simulation_status','material_cost','labor_cost','overhead_cost'],
            'production-orders' => ['order_number','product_id','status','planned_quantity','actual_quantity','good_quantity','scheduled_at','energy_cost','material_cost','labor_cost','overhead_cost'],
            'inventory' => ['sku','name','category','quantity','threshold','unit','unit_cost','location','supplier'],
            'maintenance-logs' => ['machine_id','issue','priority','status','scheduled_at','notes'],
            'energy-records' => ['machine_id','process_id','production_order_id','production_batch_id','routing_operation_id','device_id','external_event_id','kwh','tariff_per_kwh','cost','recorded_at','source','payload'],
            'oee-records' => ['machine_id','production_batch_id','routing_operation_id','recorded_on','planned_minutes','operating_minutes','ideal_cycle_minutes','total_output','good_output','availability','performance','quality','oee','notes'],
        };
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">ID #{{ $record->id }} · Dibuat {{ $record->created_at?->format('d M Y H:i') ?? '—' }}</p>
        <div class="flex gap-2">
            <x-ui.button :href="route($key.'.index')" variant="secondary">Kembali</x-ui.button>
            <x-ui.button :href="route($key.'.edit', $record->id)">Edit data</x-ui.button>
        </div>
    </div>

    <dl class="app-card grid gap-x-6 divide-y divide-slate-100 p-5 sm:grid-cols-2 sm:divide-y-0 sm:p-7">
        @foreach ($columns as $column)
            @php($value = data_get($record, $column))
            <div class="border-b border-slate-100 py-3 last:border-0 sm:py-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ str($column)->replace('_', ' ')->title() }}</dt>
                <dd class="mt-1.5 break-words text-sm font-medium text-navy-900">
                    @if (in_array($column, ['status','source','priority']))
                        <x-ui.status-badge :status="$value ?? '—'" :context="$column === 'source' ? 'source' : ($column === 'priority' ? 'priority' : match($key) {'machines' => 'machine', 'maintenance-logs' => 'maintenance', default => null})" />
                    @elseif (is_array($value))
                        <pre class="overflow-x-auto rounded-lg bg-slate-50 p-3 text-xs">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    @elseif ($value instanceof \Illuminate\Support\Carbon)
                        {{ $value->format('d M Y H:i') }}
                    @else
                        {{ $value ?? '—' }}
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
@endsection
