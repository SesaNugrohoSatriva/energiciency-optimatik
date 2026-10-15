<?php

namespace App\Services;

use App\Models\ProductionBatch;
use App\Models\ProductionBatchCostEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionCostEntryService
{
    public function record(ProductionBatch $batch, string $category, float $amount, string $description, ?int $userId): ProductionBatchCostEntry
    {
        return DB::transaction(function () use ($batch, $category, $amount, $description, $userId) {
            $locked = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if (!in_array($locked->status, ['in_progress', 'completed'], true)) {
                throw ValidationException::withMessages(['batch' => 'Biaya aktual hanya dapat dicatat pada batch yang sedang berjalan atau selesai.']);
            }

            return ProductionBatchCostEntry::create([
                'production_batch_id' => $locked->id,
                'user_id' => $userId,
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
            ]);
        });
    }
}
