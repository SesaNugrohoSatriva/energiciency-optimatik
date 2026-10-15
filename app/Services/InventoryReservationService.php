<?php

namespace App\Services;

use App\Models\BatchMaterial;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductionBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryReservationService
{
    public function reserve(ProductionBatch $batch, ?int $userId): void
    {
        DB::transaction(function () use ($batch, $userId) {
            $lockedBatch = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if (!in_array($lockedBatch->status, ['planned', 'in_progress'], true)) {
                throw ValidationException::withMessages(['batch' => 'Material hanya dapat direservasi untuk batch planned atau in_progress.']);
            }

            $materials = $lockedBatch->materials()->lockForUpdate()->get();
            if ($materials->isEmpty()) {
                throw ValidationException::withMessages(['materials' => 'Snapshot BOM batch tidak memiliki kebutuhan material.']);
            }
            foreach ($materials as $material) {
                if (!$material->inventory_item_id) {
                    throw ValidationException::withMessages(['materials' => 'Tautkan semua SKU BOM ke item inventory sebelum melakukan reservasi. Komponen belum tertaut: '.$material->component_name.'.']);
                }
                $remaining = (float) $material->required_quantity - (float) $material->reserved_quantity;
                if ($remaining <= 0) {
                    continue;
                }
                $inventory = InventoryItem::query()->lockForUpdate()->findOrFail($material->inventory_item_id);
                $otherReservations = (float) BatchMaterial::query()
                    ->where('inventory_item_id', $inventory->id)
                    ->sum(DB::raw('reserved_quantity - consumed_quantity'));
                $available = (float) $inventory->quantity - $otherReservations;
                if ($available + 0.00001 < $remaining) {
                    throw ValidationException::withMessages(['materials' => 'Stok tersedia untuk '.$inventory->sku.' tidak mencukupi. Tersedia '.number_format(max(0, $available), 3, ',', '.').' '.$inventory->unit.'.']);
                }

                $material->update([
                    'reserved_quantity' => (float) $material->reserved_quantity + $remaining,
                    'status' => 'reserved',
                ]);
                $this->record($inventory, $lockedBatch, $material, 'reservation', $remaining, $userId);
            }
        });
    }

    public function consume(ProductionBatch $batch, array $quantities, ?int $userId): void
    {
        DB::transaction(function () use ($batch, $quantities, $userId) {
            $lockedBatch = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($lockedBatch->status !== 'in_progress') {
                throw ValidationException::withMessages(['batch' => 'Konsumsi material hanya dapat dicatat saat batch in_progress.']);
            }

            foreach ($quantities as $materialId => $quantity) {
                $material = $lockedBatch->materials()->lockForUpdate()->findOrFail($materialId);
                $quantity = (float) $quantity;
                $remaining = (float) $material->reserved_quantity - (float) $material->consumed_quantity;
                if ($quantity <= 0 || $quantity > $remaining + 0.00001) {
                    throw ValidationException::withMessages(['consumptions' => 'Kuantitas konsumsi harus positif dan tidak melebihi material yang direservasi untuk '.$material->component_name.'.']);
                }

                $inventory = InventoryItem::query()->lockForUpdate()->findOrFail($material->inventory_item_id);
                if ((float) $inventory->quantity + 0.00001 < $quantity) {
                    throw ValidationException::withMessages(['consumptions' => 'Stok aktual '.$inventory->sku.' sudah tidak mencukupi untuk konsumsi.']);
                }
                $inventory->decrement('quantity', $quantity);
                $material->update([
                    'consumed_quantity' => (float) $material->consumed_quantity + $quantity,
                    'status' => 'consumed',
                ]);
                $this->record($inventory, $lockedBatch, $material, 'consumption', $quantity, $userId);
            }
        });
    }

    public function release(ProductionBatch $batch, int $materialId, float $quantity, ?int $userId): void
    {
        DB::transaction(function () use ($batch, $materialId, $quantity, $userId) {
            $lockedBatch = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if (!in_array($lockedBatch->status, ['planned', 'in_progress'], true)) {
                throw ValidationException::withMessages(['batch' => 'Reservasi hanya dapat dilepas sebelum batch selesai.']);
            }
            $material = $lockedBatch->materials()->lockForUpdate()->findOrFail($materialId);
            $remaining = (float) $material->reserved_quantity - (float) $material->consumed_quantity;
            if ($quantity <= 0 || $quantity > $remaining + 0.00001) {
                throw ValidationException::withMessages(['release_quantity' => 'Kuantitas pelepasan harus positif dan tidak melebihi sisa reservasi.']);
            }

            $inventory = InventoryItem::query()->lockForUpdate()->findOrFail($material->inventory_item_id);
            $newReserved = (float) $material->reserved_quantity - $quantity;
            $material->update([
                'reserved_quantity' => $newReserved,
                'status' => $newReserved <= (float) $material->consumed_quantity
                    ? ((float) $material->consumed_quantity > 0 ? 'consumed' : 'released')
                    : 'reserved',
            ]);
            $this->record($inventory, $lockedBatch, $material, 'release', $quantity, $userId);
        });
    }

    private function record(InventoryItem $inventory, ProductionBatch $batch, BatchMaterial $material, string $type, float $quantity, ?int $userId): void
    {
        InventoryTransaction::create([
            'inventory_item_id' => $inventory->id,
            'production_batch_id' => $batch->id,
            'batch_material_id' => $material->id,
            'user_id' => $userId,
            'transaction_type' => $type,
            'quantity' => $quantity,
            'unit_cost_snapshot' => $material->unit_cost_snapshot,
            'reference' => $batch->batch_number,
        ]);
    }
}
