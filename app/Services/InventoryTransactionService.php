<?php

namespace App\Services;

use App\Models\BatchMaterial;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryTransactionService
{
    public function captureOpeningBalances(): void
    {
        InventoryItem::query()->each(function (InventoryItem $item): void {
            $this->recordOpeningBalance($item);
        });
    }

    public function recordOpeningBalance(InventoryItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            if ($locked->transactions()->exists()) {
                return;
            }

            InventoryTransaction::create([
                'inventory_item_id' => $locked->id,
                'transaction_type' => 'opening_balance',
                'quantity' => $locked->quantity,
                'unit_cost_snapshot' => $locked->unit_cost,
                'reference' => 'Opening balance',
            ]);
        });
    }

    public function setOnHand(InventoryItem $item, float $newQuantity, ?int $userId): void
    {
        DB::transaction(function () use ($item, $newQuantity, $userId): void {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            $delta = $newQuantity - (float) $locked->quantity;
            if (abs($delta) < 0.000005) {
                return;
            }

            $reserved = (float) BatchMaterial::query()
                ->where('inventory_item_id', $locked->id)
                ->sum(DB::raw('reserved_quantity - consumed_quantity'));
            if ($newQuantity + 0.00001 < $reserved) {
                throw ValidationException::withMessages(['quantity' => 'Saldo on-hand tidak boleh di bawah jumlah reservasi aktif.']);
            }

            $locked->update(['quantity' => $newQuantity]);
            InventoryTransaction::create([
                'inventory_item_id' => $locked->id,
                'user_id' => $userId,
                'transaction_type' => 'adjustment',
                'quantity' => $delta,
                'unit_cost_snapshot' => $locked->unit_cost,
                'reference' => 'Manual stock adjustment',
            ]);
        });
    }
}
