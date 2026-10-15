<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductRouting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductRoutingService
{
    public function createDraft(Product $product, string $name): ProductRouting
    {
        return DB::transaction(function () use ($product, $name) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $active = $lockedProduct->activeRouting()->with('operations')->first();
            $version = ((int) $lockedProduct->routings()->max('version')) + 1;
            $routing = $lockedProduct->routings()->create([
                'version' => $version,
                'name' => $name,
                'status' => 'draft',
            ]);

            if ($active) {
                foreach ($active->operations as $operation) {
                    $routing->operations()->create($operation->only([
                        'sequence',
                        'process_id',
                        'machine_id',
                        'name',
                        'setup_time_minutes',
                        'ideal_cycle_seconds',
                        'labor_required',
                        'estimated_energy_kwh',
                        'work_instructions',
                        'requires_quality_inspection',
                        'is_optional',
                    ]));
                }
            }

            return $routing;
        });
    }

    public function activate(ProductRouting $routing): void
    {
        DB::transaction(function () use ($routing) {
            Product::query()->lockForUpdate()->findOrFail($routing->product_id);
            $locked = ProductRouting::query()->lockForUpdate()->withCount('operations')->findOrFail($routing->id);
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages(['routing' => 'Hanya draft routing yang dapat diaktifkan.']);
            }
            if ($locked->operations_count === 0) {
                throw ValidationException::withMessages(['routing' => 'Tambahkan minimal satu operasi sebelum mengaktifkan routing.']);
            }

            ProductRouting::query()
                ->where('product_id', $locked->product_id)
                ->where('status', 'active')
                ->whereKeyNot($locked->id)
                ->update(['status' => 'archived', 'updated_at' => now()]);

            $locked->update(['status' => 'active']);
        });
    }

    public function assertDraft(ProductRouting $routing): void
    {
        if ($routing->status !== 'draft') {
            throw ValidationException::withMessages(['routing' => 'Routing aktif/arsip tidak dapat diubah. Buat versi draft baru terlebih dahulu.']);
        }
    }

    public function addOperation(ProductRouting $routing, array $attributes): void
    {
        DB::transaction(function () use ($routing, $attributes) {
            $locked = ProductRouting::query()->lockForUpdate()->findOrFail($routing->id);
            $this->assertDraft($locked);
            $locked->operations()->create($attributes);
        });
    }

    public function updateOperation(ProductRouting $routing, int $operationId, array $attributes): void
    {
        DB::transaction(function () use ($routing, $operationId, $attributes) {
            $locked = ProductRouting::query()->lockForUpdate()->findOrFail($routing->id);
            $this->assertDraft($locked);
            $operation = $locked->operations()->lockForUpdate()->findOrFail($operationId);
            $operation->update($attributes);
        });
    }

    public function deleteOperation(ProductRouting $routing, int $operationId): void
    {
        DB::transaction(function () use ($routing, $operationId) {
            $locked = ProductRouting::query()->lockForUpdate()->findOrFail($routing->id);
            $this->assertDraft($locked);
            $locked->operations()->lockForUpdate()->findOrFail($operationId)->delete();
        });
    }

    public function reorder(ProductRouting $routing, array $operationIds): void
    {
        DB::transaction(function () use ($routing, $operationIds) {
            $locked = ProductRouting::query()->lockForUpdate()->findOrFail($routing->id);
            $this->assertDraft($locked);

            $operations = $locked->operations()->lockForUpdate()->get();
            $expectedIds = $operations->pluck('id')->sort()->values()->all();
            $requestedIds = collect($operationIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($expectedIds !== $requestedIds || count($operationIds) !== count(array_unique($requestedIds))) {
                throw ValidationException::withMessages(['operations' => 'Urutan harus memuat setiap operasi routing ini tepat satu kali.']);
            }

            $offset = (int) ($operations->max('sequence') ?? 0) + $operations->count() + 1;
            foreach ($operationIds as $index => $id) {
                $locked->operations()->whereKey($id)->update(['sequence' => $offset + $index]);
            }
            foreach ($operationIds as $index => $id) {
                $locked->operations()->whereKey($id)->update(['sequence' => $index + 1]);
            }
        });
    }
}
