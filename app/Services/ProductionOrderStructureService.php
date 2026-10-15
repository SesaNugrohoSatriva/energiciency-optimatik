<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\ProductRouting;
use App\Models\ProductionOrder;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductionOrderStructureService
{
    public function captureLegacyOrders(): void
    {
        ProductionOrder::query()->whereNull('bom_snapshot')->each(function (ProductionOrder $order): void {
            $snapshots = $this->capture($order);
            $order->update(array_filter($snapshots, fn ($value) => $value !== null));
        });
    }

    public function capture(ProductionOrder $order, ?int $bomId = null, ?int $routingId = null): array
    {
        $product = Product::findOrFail($order->product_id);
        $bom = $bomId ? Bom::findOrFail($bomId) : $product->activeBom()->with('items')->first();
        $routing = $routingId ? ProductRouting::findOrFail($routingId) : $product->activeRouting()->with('operations')->first();

        if ($bom && ((int) $bom->product_id !== (int) $product->id || $bom->status !== 'active')) {
            throw ValidationException::withMessages(['bom_id' => 'Order harus menggunakan BOM aktif milik produk yang dipilih.']);
        }
        if ($routing && ((int) $routing->product_id !== (int) $product->id || $routing->status !== 'active')) {
            throw ValidationException::withMessages(['product_routing_id' => 'Order harus menggunakan routing aktif milik produk yang dipilih.']);
        }

        return [
            'bom_id' => $bom?->id,
            'product_routing_id' => $routing?->id,
            'bom_snapshot' => $bom ? [
                'id' => $bom->id,
                'bom_number' => $bom->bom_number,
                'version' => $bom->version,
                'output_quantity' => $bom->output_quantity,
                'items' => $bom->items->map(fn ($item) => $item->only([
                    'id', 'inventory_item_id', 'component_sku', 'component_name', 'quantity_per_unit',
                    'scrap_allowance_percent', 'unit', 'unit_cost', 'supplier',
                ]))->values()->all(),
            ] : null,
            'routing_snapshot' => $routing ? [
                'id' => $routing->id,
                'name' => $routing->name,
                'version' => $routing->version,
                'operations' => $routing->operations->map(fn ($operation) => $operation->only([
                    'id', 'sequence', 'process_id', 'machine_id', 'name', 'setup_time_minutes',
                    'ideal_cycle_seconds', 'labor_required', 'estimated_energy_kwh',
                    'work_instructions', 'requires_quality_inspection', 'is_optional',
                ]))->values()->all(),
            ] : null,
        ];
    }
}
