<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\OeeRecord;
use App\Models\ProductionBatch;
use App\Models\ProductionOrder;
use App\Services\EnergyAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class MachineAnalyticsController extends Controller
{
    public function show(Request $request, int $machine, EnergyAnalyticsService $analytics)
    {
        $machine = Machine::findOrFail($machine);
        $filters = $analytics->filters($request, $machine->id);
        unset($filters['machine_id']);
        $energyQuery = $analytics->query($filters, $machine->id);
        $summary = $analytics->summary($energyQuery);
        $previousSummary = $analytics->previousPeriodSummary($filters, $machine->id);
        $trend = $analytics->trend($energyQuery, $filters['interval']);
        $energyRecords = (clone $energyQuery)->with([
            'process',
            'productionOrder.product',
            'productionBatch.productionOrder.product',
            'routingOperation',
        ])->latest('recorded_at')->paginate(15)->withQueryString();
        $orderIds = (clone $energyQuery)
            ->whereNotNull('production_order_id')
            ->distinct()
            ->orderBy('production_order_id')
            ->pluck('production_order_id');
        $orders = $orderIds->isEmpty()
            ? collect()
            : ProductionOrder::with('product')->whereIn('id', $orderIds)->orderBy('order_number')->get();
        $orderEnergy = (clone $energyQuery)
            ->whereNotNull('production_order_id')
            ->selectRaw('production_order_id, SUM(kwh) as total_kwh, SUM(cost) as total_cost, COUNT(*) as record_count')
            ->groupBy('production_order_id')
            ->orderByDesc('total_kwh')
            ->limit(100)
            ->get()
            ->map(function ($totals) use ($orders): array {
                $order = $orders->firstWhere('id', $totals->production_order_id);
                $goodQuantity = (int) ($order?->good_quantity ?? 0);
                $actualQuantity = (int) ($order?->actual_quantity ?? 0);

                return [
                    'order' => $order,
                    'total_kwh' => (float) $totals->total_kwh,
                    'total_cost' => (float) $totals->total_cost,
                    'record_count' => (int) $totals->record_count,
                    'cost_per_good_unit' => $goodQuantity > 0 ? (float) $totals->total_cost / $goodQuantity : null,
                    'cost_per_output_unit' => $actualQuantity > 0 ? (float) $totals->total_cost / $actualQuantity : null,
                ];
            });
        $batchOptionIds = (clone $energyQuery)
            ->whereNotNull('production_batch_id')
            ->distinct()
            ->orderBy('production_batch_id')
            ->pluck('production_batch_id');
        $batches = $batchOptionIds->isEmpty()
            ? collect()
            : ProductionBatch::whereIn('id', $batchOptionIds)->orderBy('batch_number')->get();
        $devices = (clone $energyQuery)->whereNotNull('device_id')->distinct()->orderBy('device_id')->pluck('device_id');
        $shareFilters = $filters;
        unset($shareFilters['search']);
        $totalCost = $analytics->summary($analytics->query($shareFilters))['total_cost'];
        $costContribution = $totalCost > 0 ? $summary['total_cost'] / $totalCost : null;

        $oeeQuery = OeeRecord::query()
            ->where('machine_id', $machine->id)
            ->whereBetween('recorded_on', [$filters['from'], $filters['to']]);
        $oeeRecordCount = (clone $oeeQuery)->count();
        $validOee = (clone $oeeQuery)
            ->where('planned_minutes', '>', 0)
            ->where('operating_minutes', '>', 0)
            ->where('ideal_cycle_minutes', '>', 0)
            ->where('total_output', '>', 0)
            ->whereColumn('good_output', '<=', 'total_output')
            ->selectRaw('
                COUNT(*) as valid_count,
                SUM(planned_minutes) as planned_minutes,
                SUM(operating_minutes) as operating_minutes,
                SUM(ideal_cycle_minutes * total_output) as ideal_minutes_for_output,
                SUM(total_output) as total_output,
                SUM(good_output) as good_output
            ')
            ->first();
        $performance = (int) $validOee->operating_minutes > 0
            ? (float) $validOee->ideal_minutes_for_output / (float) $validOee->operating_minutes
            : null;
        $availability = (int) $validOee->planned_minutes > 0
            ? (float) $validOee->operating_minutes / (float) $validOee->planned_minutes
            : null;
        $quality = (int) $validOee->total_output > 0
            ? (float) $validOee->good_output / (float) $validOee->total_output
            : null;
        $oee = $availability !== null && $performance !== null && $quality !== null
            ? $availability * $performance * $quality
            : null;
        $oeeTargetDifference = $oee === null ? null : ($oee * 100) - (float) $machine->target_oee;
        $operatingHours = (float) ($validOee->operating_minutes ?? 0) / 60;
        $energyPerOperatingHour = $operatingHours > 0 ? $summary['total_kwh'] / $operatingHours : null;
        $costPerOperatingHour = $operatingHours > 0 ? $summary['total_cost'] / $operatingHours : null;
        $oeeRecords = (clone $oeeQuery)->orderByDesc('recorded_on')->paginate(10, ['*'], 'oee_page')->withQueryString();

        $batchIds = (clone $energyQuery)->whereNotNull('production_batch_id')->distinct()->pluck('production_batch_id');
        $allocatedKwh = (float) (clone $energyQuery)->whereNotNull('production_batch_id')->sum('kwh');
        $linkedBatches = $batchIds->isEmpty()
            ? collect()
            : ProductionBatch::with([
                'productionOrder.product',
                'energyRecords' => fn ($query) => $query
                    ->where('machine_id', $machine->id)
                    ->whereBetween('recorded_at', [
                        CarbonImmutable::parse($filters['from'])->startOfDay(),
                        CarbonImmutable::parse($filters['to'])->endOfDay(),
                    ])
                    ->when($filters['source'] ?? null, fn ($query, string $source) => $query->where('source', $source))
                    ->when($filters['production_order_id'] ?? null, fn ($query, int $orderId) => $query->where('production_order_id', $orderId))
                    ->when($filters['production_batch_id'] ?? null, fn ($query, int $batchId) => $query->where('production_batch_id', $batchId))
                    ->when($filters['device_id'] ?? null, fn ($query, string $deviceId) => $query->where('device_id', $deviceId))
                    ->when($filters['search'] ?? null, function ($query, string $search): void {
                        $query->where(function ($query) use ($search): void {
                            $query->where('external_event_id', 'like', '%' . $search . '%')
                                ->orWhere('device_id', 'like', '%' . $search . '%')
                                ->orWhereHas('machine', fn ($machineQuery) => $machineQuery->where('name', 'like', '%' . $search . '%')->orWhere('code', 'like', '%' . $search . '%'))
                                ->orWhereHas('productionOrder', fn ($orderQuery) => $orderQuery->where('order_number', 'like', '%' . $search . '%'))
                                ->orWhereHas('productionBatch', fn ($batchQuery) => $batchQuery->where('batch_number', 'like', '%' . $search . '%'));
                        });
                    })
                    ->orderBy('recorded_at'),
            ])->whereIn('id', $batchIds)->orderByDesc('started_at')->get();
        $goodOutput = (int) $linkedBatches->sum('good_quantity');
        $totalOutput = (int) $linkedBatches->sum('actual_quantity');
        $energyPerGoodUnit = $goodOutput > 0 ? $allocatedKwh / $goodOutput : null;
        $energyPerOutputUnit = $totalOutput > 0 ? $allocatedKwh / $totalOutput : null;

        $maintenanceLogs = $machine->maintenanceLogs()->orderByRaw('scheduled_at IS NULL')->orderByDesc('scheduled_at')->limit(10)->get();
        $nextMaintenance = $machine->maintenanceLogs()
            ->whereNotNull('scheduled_at')
            ->whereDate('scheduled_at', '>=', CarbonImmutable::today())
            ->where('status', '!=', 'Completed')
            ->orderBy('scheduled_at')
            ->first();
        $pageTitle = $machine->name;
        $pageDescription = $machine->code . ' · Profil mesin, histori energi, produksi tertaut, OEE, dan maintenance.';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Machines', 'url' => route('machines.index')],
            ['label' => $machine->name],
        ];

        return view('machines.show', compact(
            'machine',
            'filters',
            'summary',
            'previousSummary',
            'trend',
            'energyRecords',
            'orders',
            'orderEnergy',
            'batches',
            'devices',
            'costContribution',
            'oeeRecordCount',
            'validOee',
            'availability',
            'performance',
            'quality',
            'oee',
            'oeeTargetDifference',
            'operatingHours',
            'energyPerOperatingHour',
            'costPerOperatingHour',
            'oeeRecords',
            'linkedBatches',
            'goodOutput',
            'totalOutput',
            'energyPerGoodUnit',
            'energyPerOutputUnit',
            'maintenanceLogs',
            'nextMaintenance',
            'pageTitle',
            'pageDescription',
            'breadcrumbs'
        ));
    }
}
