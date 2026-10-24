<?php

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use App\Models\ProductionOrder;
use App\Services\ProductionBatchService;
use App\Services\ProductionCostEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionBatchController extends Controller
{
    public function store(Request $request, ProductionOrder $productionOrder, ProductionBatchService $service): RedirectResponse
    {
        $data = $request->validate([
            'planned_quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $batch = $service->create($productionOrder, (int) $data['planned_quantity'], $data['notes'] ?? null, $request->user()?->id);

        return redirect()->route('production-orders.batches.show', [$productionOrder, $batch])
            ->with('success', 'Batch ' . $batch->batch_number . ' berhasil dibuat dengan snapshot routing order.');
    }

    public function show(ProductionOrder $productionOrder, ProductionBatch $batch): View
    {
        abort_unless($batch->production_order_id === $productionOrder->id, 404);
        $batch->load(['events.user', 'energyRecords.machine', 'energyRecords.routingOperation', 'costEntries', 'oeeRecords.machine', 'oeeRecords.routingOperation']);
        $actualEnergyKwh = (float) $batch->energyRecords->sum('kwh');
        $actualEnergyCost = (float) $batch->energyRecords->sum('cost');
        $actualLaborCost = (float) $batch->costEntries->where('category', 'labor')->sum('amount');
        $actualOverheadCost = (float) $batch->costEntries->where('category', 'overhead')->sum('amount');
        $plannedEnergyKwh = collect($batch->routing_snapshot['operations'] ?? [])->sum(fn ($operation) => (float) ($operation['estimated_energy_kwh'] ?? 0));
        $actualCostTotal = $actualEnergyCost + $actualLaborCost + $actualOverheadCost;
        $pageTitle = 'Batch ' . $batch->batch_number;
        $pageDescription = 'Progres, output, dan struktur produk yang dibekukan untuk batch ini.';
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Production Orders', 'url' => route('production-orders.index')],
            ['label' => $productionOrder->order_number, 'url' => route('production-orders.show', $productionOrder)],
            ['label' => $batch->batch_number],
        ];

        return view('production-batches.show', compact('productionOrder', 'batch', 'actualEnergyKwh', 'actualEnergyCost', 'actualLaborCost', 'actualOverheadCost', 'plannedEnergyKwh', 'actualCostTotal', 'pageTitle', 'pageDescription', 'breadcrumbs'));
    }

    public function start(Request $request, ProductionOrder $productionOrder, ProductionBatch $batch, ProductionBatchService $service): RedirectResponse
    {
        abort_unless($batch->production_order_id === $productionOrder->id, 404);
        $service->start($batch, $request->user()?->id);

        return back()->with('success', 'Batch mulai diproses.');
    }

    public function complete(Request $request, ProductionOrder $productionOrder, ProductionBatch $batch, ProductionBatchService $service): RedirectResponse
    {
        abort_unless($batch->production_order_id === $productionOrder->id, 404);
        $data = $request->validate([
            'actual_quantity' => ['required', 'integer', 'min:0'],
            'good_quantity' => ['required', 'integer', 'min:0', 'lte:actual_quantity'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $service->complete($batch, (int) $data['actual_quantity'], (int) $data['good_quantity'], $data['notes'] ?? null, $request->user()?->id);

        return redirect()->route('production-orders.batches.show', [$productionOrder, $batch])->with('success', 'Output batch tercatat dan progres order diperbarui.');
    }

    public function cancel(Request $request, ProductionOrder $productionOrder, ProductionBatch $batch, ProductionBatchService $service): RedirectResponse
    {
        abort_unless($batch->production_order_id === $productionOrder->id, 404);
        $service->cancel($batch, $request->user()?->id);

        return redirect()->route('production-orders.show', $productionOrder)->with('success', 'Batch planned dibatalkan.');
    }

    public function recordCost(Request $request, ProductionOrder $productionOrder, ProductionBatch $batch, ProductionCostEntryService $service): RedirectResponse
    {
        abort_unless($batch->production_order_id === $productionOrder->id, 404);
        $data = $request->validate([
            'category' => ['required', 'in:labor,overhead'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:180'],
        ]);
        $service->record($batch, $data['category'], (float) $data['amount'], $data['description'], $request->user()?->id);

        return back()->with('success', 'Biaya aktual dicatat dalam ledger batch.');
    }
}
