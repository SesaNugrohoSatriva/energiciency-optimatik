<?php

namespace App\Services;

use App\Models\Product;

class ProductCostingService
{
    public function estimate(Product $product, float $energyKwh, float $laborCost, float $overheadCost): array
    {
        $material = (float) $product->material_cost;
        $energy = $energyKwh * (float) env('ENERGY_TARIFF_PER_KWH', 1640);

        return [
            'material' => $material,
            'labor' => $laborCost,
            'energy' => $energy,
            'overhead' => $overheadCost,
            'total' => $material + $laborCost + $energy + $overheadCost,
        ];
    }
}
