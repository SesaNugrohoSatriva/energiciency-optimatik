<?php

namespace App\Services;

use App\Models\ProductRouting;
use App\Models\ProductionOrder;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductionOrderStructureService
{
    public function captureLegacyOrders(): void
    {
        ProductionOrder::query()->whereNull('routing_snapshot')->each(function (ProductionOrder $order): void {
            $snapshots = $this->capture($order);
            $order->update(array_filter($snapshots, fn ($value) => $value !== null));
        });
    }

    public function capture(ProductionOrder $order, ?int $routingId = null): array
    {
        $product = Product::findOrFail($order->product_id);
        $routing = $routingId ? ProductRouting::findOrFail($routingId) : $product->activeRouting()->with('operations')->first();

        if ($routing && ((int) $routing->product_id !== (int) $product->id || $routing->status !== 'active')) {
            throw ValidationException::withMessages(['product_routing_id' => 'Order harus menggunakan routing aktif milik produk yang dipilih.']);
        }

        return [
            'product_routing_id' => $routing?->id,
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
