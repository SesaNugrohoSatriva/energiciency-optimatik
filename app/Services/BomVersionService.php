<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BomVersionService
{
    public function createDraft(Product $product): Bom
    {
        return DB::transaction(function () use ($product) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $active = $lockedProduct->activeBom()->with('items')->first();
            $version = ((int) $lockedProduct->boms()->max('version')) + 1;
            $bom = $lockedProduct->boms()->create([
                'bom_number' => $lockedProduct->sku . '-BOM-' . str_pad((string) $version, 3, '0', STR_PAD_LEFT),
                'version' => $version,
                'status' => 'draft',
                'effective_from' => now()->toDateString(),
                'output_quantity' => $active?->output_quantity ?? 1,
            ]);

            if ($active) {
                foreach ($active->items as $item) {
                    $bom->items()->create($item->only([
                        'product_id',
                        'inventory_item_id',
                        'component_sku',
                        'component_name',
                        'quantity_per_unit',
                        'unit',
                        'unit_cost',
                        'supplier',
                        'scrap_allowance_percent',
                    ]));
                }
            }

            return $bom;
        });
    }

    public function activate(Bom $bom): void
    {
        DB::transaction(function () use ($bom) {
            Product::query()->lockForUpdate()->findOrFail($bom->product_id);
            $locked = Bom::query()->lockForUpdate()->withCount('items')->findOrFail($bom->id);
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages(['bom' => 'Hanya draft BOM yang dapat diaktifkan.']);
            }
            if ($locked->items_count === 0) {
                throw ValidationException::withMessages(['bom' => 'Tambahkan minimal satu komponen sebelum mengaktifkan BOM.']);
            }

            Bom::query()
                ->where('product_id', $locked->product_id)
                ->where('status', 'active')
                ->whereKeyNot($locked->id)
                ->update(['status' => 'archived', 'updated_at' => now()]);

            $locked->update(['status' => 'active', 'effective_from' => now()->toDateString()]);
        });
    }
}
