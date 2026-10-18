<?php

namespace App\Http\Controllers;

use App\Models\EnergyRecord;
use App\Models\InventoryItem;
use App\Models\Machine;
use App\Models\MaintenanceLog;
use App\Models\OeeRecord;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionBatch;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $energy = (float) EnergyRecord::sum('kwh');
        $energyCost = (float) EnergyRecord::sum('cost');
        $totalProduction = (int) ProductionOrder::sum('actual_quantity');
        $goodProduction = (int) ProductionOrder::sum('good_quantity');
        $orderCount = ProductionOrder::count();
        $machineCount = Machine::count();
        $machinesNeedingAttention = Machine::where('status', '!=', 'Normal')->count();

        $oeeMetrics = OeeRecord::query()
            ->selectRaw('AVG(availability) as availability, AVG(performance) as performance, AVG(quality) as quality, AVG(oee) as oee')
            ->first();

        $energyTrend = EnergyRecord::query()
            ->selectRaw('DATE(recorded_at) as period, SUM(kwh) as kwh, SUM(cost) as cost')
            ->groupBy('period')
            ->orderByDesc('period')
            ->limit(14)
            ->get()
            ->reverse()
            ->values();

        $productionTrend = ProductionOrder::query()
            ->whereNotNull('scheduled_at')
            ->selectRaw('DATE(scheduled_at) as period, SUM(actual_quantity) as actual_quantity, SUM(planned_quantity) as planned_quantity')
            ->groupBy('period')
            ->orderByDesc('period')
            ->limit(14)
            ->get()
            ->reverse()
            ->values();

        $energyByMachine = EnergyRecord::query()
            ->leftJoin('machines', 'energy_records.machine_id', '=', 'machines.id')
            ->selectRaw("COALESCE(machines.name, 'Mesin tidak ditautkan') as machine, SUM(energy_records.kwh) as kwh")
            ->groupBy('machines.name')
            ->orderByDesc('kwh')
            ->limit(8)
            ->get();

        $energyByProcess = EnergyRecord::query()
            ->leftJoin('processes', 'energy_records.process_id', '=', 'processes.id')
            ->selectRaw("COALESCE(processes.name, 'Proses tidak ditautkan') as process, SUM(energy_records.kwh) as kwh")
            ->groupBy('processes.name')
            ->orderByDesc('kwh')
            ->limit(8)
            ->get();

        $oeeByMachine = OeeRecord::query()
            ->join('machines', 'oee_records.machine_id', '=', 'machines.id')
            ->selectRaw('machines.name as machine, AVG(oee_records.oee) as oee')
            ->groupBy('machines.id', 'machines.name')
            ->orderByDesc('oee')
            ->limit(8)
            ->get();

        $orderStatuses = ProductionOrder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $lowStockItems = InventoryItem::whereColumn('quantity', '<=', 'threshold')
            ->orderBy('quantity')
            ->limit(5)
            ->get(['id', 'name', 'sku', 'quantity', 'threshold', 'unit']);

        $maintenanceAlerts = MaintenanceLog::whereIn('status', ['Open', 'Monitor', 'Planned', 'In Progress'])
            ->orderByRaw("CASE priority WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4 END")
            ->limit(5)
            ->get(['id', 'machine_id', 'issue', 'priority', 'status']);

        $costComponents = [
            ['label' => 'Material (BOM aktif)', 'value' => (float) DB::table('bom_items')->join('boms', 'bom_items.bom_id', '=', 'boms.id')->where('boms.status', 'active')->selectRaw('COALESCE(SUM(bom_items.quantity_per_unit * (1 + bom_items.scrap_allowance_percent / 100) * bom_items.unit_cost), 0) as total')->value('total'), 'color' => 'bg-brand-500'],
            ['label' => 'Tenaga kerja', 'value' => (float) Product::sum('labor_cost'), 'color' => 'bg-sky-300'],
            ['label' => 'Energi tertaut order', 'value' => (float) EnergyRecord::join('production_orders', 'energy_records.production_order_id', '=', 'production_orders.id')->sum('energy_records.cost'), 'color' => 'bg-emerald-500'],
            ['label' => 'Overhead produk', 'value' => (float) Product::sum('overhead_cost'), 'color' => 'bg-amber-400'],
        ];
        $costScale = max(array_column($costComponents, 'value')) ?: 1;

        $pageTitle = 'Dashboard';
        $pageDescription = 'Ringkasan efisiensi produksi, aset, dan konsumsi energi.';
        $breadcrumbs = [['label' => 'Dashboard']];

        return view('dashboard.index', compact(
            'energy',
            'energyCost',
            'totalProduction',
            'goodProduction',
            'orderCount',
            'machineCount',
            'machinesNeedingAttention',
            'oeeMetrics',
            'energyTrend',
            'productionTrend',
            'energyByMachine',
            'energyByProcess',
            'oeeByMachine',
            'orderStatuses',
            'lowStockItems',
            'maintenanceAlerts',
            'costComponents',
            'costScale',
            'pageTitle',
            'pageDescription',
            'breadcrumbs',
        ));
    }

    public function reports()
    {
        $periodExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-01', recorded_at)"
            : "DATE_FORMAT(recorded_at, '%Y-%m-01')";
        $monthly = EnergyRecord::query()
            ->selectRaw($periodExpression . ' as period, SUM(kwh) as kwh, SUM(cost) as cost')
            ->groupBy('period')
            ->orderByDesc('period')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $pageTitle = 'Analytics & reports';
        $pageDescription = 'Agregasi konsumsi dan biaya energi dari catatan yang tersimpan.';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Reports']];

        return view('dashboard.reports', compact('monthly', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    public function costing()
    {
        $products = Product::with('activeBom.items')->orderBy('name')->get();
        $actualMaterials = DB::table('inventory_transactions')
            ->join('production_batches', 'inventory_transactions.production_batch_id', '=', 'production_batches.id')
            ->join('production_orders', 'production_batches.production_order_id', '=', 'production_orders.id')
            ->where('inventory_transactions.transaction_type', 'consumption')
            ->selectRaw('production_orders.product_id, SUM(inventory_transactions.quantity * inventory_transactions.unit_cost_snapshot) as amount')
            ->groupBy('production_orders.product_id')
            ->get()
            ->keyBy('product_id');
        $actualEnergy = DB::table('energy_records')
            ->join('production_batches', 'energy_records.production_batch_id', '=', 'production_batches.id')
            ->join('production_orders', 'production_batches.production_order_id', '=', 'production_orders.id')
            ->selectRaw('production_orders.product_id, SUM(energy_records.cost) as amount')
            ->groupBy('production_orders.product_id')
            ->get()
            ->keyBy('product_id');
        $actualLaborOverhead = DB::table('production_batch_cost_entries')
            ->join('production_batches', 'production_batch_cost_entries.production_batch_id', '=', 'production_batches.id')
            ->join('production_orders', 'production_batches.production_order_id', '=', 'production_orders.id')
            ->selectRaw('production_orders.product_id, production_batch_cost_entries.category, SUM(production_batch_cost_entries.amount) as amount')
            ->groupBy('production_orders.product_id', 'production_batch_cost_entries.category')
            ->get()
            ->groupBy('product_id');
        $actualBatchCounts = DB::table('production_batches')
            ->join('production_orders', 'production_batches.production_order_id', '=', 'production_orders.id')
            ->whereIn('production_batches.status', ['in_progress', 'completed'])
            ->selectRaw('production_orders.product_id, COUNT(*) as total')
            ->groupBy('production_orders.product_id')
            ->pluck('total', 'product_id');

        foreach ($products as $product) {
            $energy = EnergyRecord::join('production_orders', 'energy_records.production_order_id', '=', 'production_orders.id')
                ->where('production_orders.product_id', $product->id)
                ->sum('energy_records.cost');
            $goodUnits = (int) ProductionOrder::where('product_id', $product->id)->sum('good_quantity');
            $product->costing_material = (float) ($product->activeBom?->items->sum(fn ($item) => $item->quantity_per_unit * (1 + $item->scrap_allowance_percent / 100) * $item->unit_cost) ?? 0);
            $product->costing_energy = $goodUnits > 0 ? ((float) $energy / $goodUnits) : 0;
            $product->costing_labor = (float) $product->labor_cost;
            $product->costing_overhead = (float) $product->overhead_cost;
            $product->costing_total = $product->costing_material + $product->costing_energy + $product->costing_labor + $product->costing_overhead;
            $product->costing_unit = $product->costing_total;
            $product->actual_material_cost = (float) ($actualMaterials[$product->id]->amount ?? 0);
            $product->actual_energy_cost = (float) ($actualEnergy[$product->id]->amount ?? 0);
            $manualCosts = $actualLaborOverhead->get($product->id, collect())->keyBy('category');
            $product->actual_labor_cost = (float) ($manualCosts['labor']->amount ?? 0);
            $product->actual_overhead_cost = (float) ($manualCosts['overhead']->amount ?? 0);
            $product->actual_cost_total = $product->actual_material_cost + $product->actual_energy_cost + $product->actual_labor_cost + $product->actual_overhead_cost;
            $product->actual_batch_count = (int) ($actualBatchCounts[$product->id] ?? 0);
        }

        $pageTitle = 'Energy costing';
        $pageDescription = 'Estimasi master dan biaya aktual dari transaksi batch yang ditautkan.';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Costing']];

        return view('dashboard.costing', compact('products', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }
}
