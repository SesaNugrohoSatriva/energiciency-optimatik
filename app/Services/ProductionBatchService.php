<?php

namespace App\Services;

use App\Models\ProductionBatch;
use App\Models\ProductionBatchEvent;
use App\Models\ProductionOrder;
use App\Models\BatchMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionBatchService
{
    public function create(ProductionOrder $order, int $plannedQuantity, ?string $notes, ?int $userId): ProductionBatch
    {
        return DB::transaction(function () use ($order, $plannedQuantity, $notes, $userId) {
            $lockedOrder = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status === 'Completed') {
                throw ValidationException::withMessages(['production_order' => 'Order yang sudah selesai tidak dapat menerima batch baru.']);
            }
            $planned = (int) $lockedOrder->batches()->where('status', '!=', 'cancelled')->sum('planned_quantity');
            $remaining = (int) $lockedOrder->planned_quantity - (int) $lockedOrder->baseline_actual_quantity - $planned;
            if ($plannedQuantity > $remaining) {
                throw ValidationException::withMessages(['planned_quantity' => 'Total kuantitas batch melebihi sisa kuantitas order.']);
            }

            $next = ((int) $lockedOrder->batches()->count()) + 1;
            $batch = $lockedOrder->batches()->create([
                'batch_number' => $lockedOrder->order_number.'-B'.str_pad((string) $next, 3, '0', STR_PAD_LEFT),
                'planned_quantity' => $plannedQuantity,
                'status' => 'planned',
                'bom_snapshot' => $lockedOrder->bom_snapshot,
                'routing_snapshot' => $lockedOrder->routing_snapshot,
                'notes' => $notes,
            ]);
            $outputQuantity = max(1, (int) ($lockedOrder->bom_snapshot['output_quantity'] ?? 1));
            foreach ($lockedOrder->bom_snapshot['items'] ?? [] as $item) {
                $required = (float) ($item['quantity_per_unit'] ?? 0)
                    * $plannedQuantity / $outputQuantity
                    * (1 + (float) ($item['scrap_allowance_percent'] ?? 0) / 100);
                $batch->materials()->create([
                    'inventory_item_id' => $item['inventory_item_id'] ?? null,
                    'component_sku' => $item['component_sku'] ?? null,
                    'component_name' => $item['component_name'] ?? 'Komponen BOM',
                    'unit' => $item['unit'] ?? 'pcs',
                    'required_quantity' => $required,
                    'unit_cost_snapshot' => $item['unit_cost'] ?? 0,
                    'status' => empty($item['inventory_item_id']) ? 'unmapped' : 'not_reserved',
                ]);
            }
            $this->recordEvent($batch, 'created', null, 'planned', ['planned_quantity' => $plannedQuantity], $userId);

            return $batch;
        });
    }

    public function start(ProductionBatch $batch, ?int $userId): void
    {
        DB::transaction(function () use ($batch, $userId) {
            $locked = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($locked->status !== 'planned') {
                throw ValidationException::withMessages(['batch' => 'Hanya batch berstatus planned yang dapat dimulai.']);
            }
            if (!$locked->bom_snapshot) {
                throw ValidationException::withMessages(['batch' => 'Order belum memiliki snapshot BOM; tetapkan BOM aktif sebelum memulai batch.']);
            }
            if (empty($locked->routing_snapshot['operations'])) {
                throw ValidationException::withMessages(['batch' => 'Order belum memiliki snapshot routing aktif dengan operasi. Tetapkan routing sebelum memulai batch.']);
            }
            $materials = $locked->materials()->lockForUpdate()->get();
            if ($materials->isEmpty()) {
                throw ValidationException::withMessages(['batch' => 'Snapshot BOM batch tidak memiliki material yang dapat ditelusuri.']);
            }
            foreach ($materials as $material) {
                if (!$material->inventory_item_id || (float) $material->reserved_quantity + 0.00001 < (float) $material->required_quantity) {
                    throw ValidationException::withMessages(['batch' => 'Semua material batch harus tertaut ke inventory dan direservasi sebelum produksi dimulai.']);
                }
            }

            $from = $locked->status;
            $locked->update(['status' => 'in_progress', 'started_at' => now(), 'started_by' => $userId]);
            $locked->productionOrder()->update(['status' => 'In Progress']);
            $this->recordEvent($locked, 'started', $from, 'in_progress', [], $userId);
        });
    }

    public function complete(ProductionBatch $batch, int $actualQuantity, int $goodQuantity, ?string $notes, ?int $userId): void
    {
        DB::transaction(function () use ($batch, $actualQuantity, $goodQuantity, $notes, $userId) {
            $locked = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($locked->status !== 'in_progress') {
                throw ValidationException::withMessages(['batch' => 'Batch harus berstatus in_progress sebelum diselesaikan.']);
            }
            if ($actualQuantity > (int) $locked->planned_quantity) {
                throw ValidationException::withMessages(['actual_quantity' => 'Output aktual tidak boleh melebihi kuantitas rencana batch.']);
            }
            if ($goodQuantity > $actualQuantity) {
                throw ValidationException::withMessages(['good_quantity' => 'Output baik tidak boleh melebihi output aktual.']);
            }
            if ($locked->materials()->whereRaw('reserved_quantity > consumed_quantity')->exists()) {
                throw ValidationException::withMessages(['materials' => 'Konsumsi seluruh reservasi atau lepaskan sisa material sebelum menyelesaikan batch.']);
            }

            $from = $locked->status;
            $locked->update([
                'status' => 'completed',
                'actual_quantity' => $actualQuantity,
                'good_quantity' => $goodQuantity,
                'rejected_quantity' => $actualQuantity - $goodQuantity,
                'completed_at' => now(),
                'completed_by' => $userId,
                'notes' => $notes ?? $locked->notes,
            ]);
            $this->syncOrderTotals($locked->productionOrder()->lockForUpdate()->firstOrFail());
            $this->recordEvent($locked, 'completed', $from, 'completed', [
                'actual_quantity' => $actualQuantity,
                'good_quantity' => $goodQuantity,
                'rejected_quantity' => $actualQuantity - $goodQuantity,
            ], $userId);
        });
    }

    public function cancel(ProductionBatch $batch, ?int $userId): void
    {
        DB::transaction(function () use ($batch, $userId) {
            $locked = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($locked->status !== 'planned') {
                throw ValidationException::withMessages(['batch' => 'Hanya batch planned yang belum dimulai dapat dibatalkan.']);
            }
            if ($locked->materials()->whereRaw('reserved_quantity > consumed_quantity')->exists()) {
                throw ValidationException::withMessages(['materials' => 'Lepaskan reservasi material sebelum membatalkan batch.']);
            }

            $from = $locked->status;
            $locked->update(['status' => 'cancelled']);
            $this->recordEvent($locked, 'cancelled', $from, 'cancelled', [], $userId);
        });
    }

    private function syncOrderTotals(ProductionOrder $order): void
    {
        $batches = $order->batches()->where('status', 'completed');
        $actual = (int) $order->baseline_actual_quantity + (int) (clone $batches)->sum('actual_quantity');
        $good = (int) $order->baseline_good_quantity + (int) (clone $batches)->sum('good_quantity');
        $remaining = $order->batches()->whereIn('status', ['planned', 'in_progress'])->exists();
        $hasCompletedBatch = $order->batches()->where('status', 'completed')->exists();
        $status = !$remaining && $actual >= (int) $order->planned_quantity
            ? 'Completed'
            : ($actual > 0 || $remaining || $hasCompletedBatch ? 'In Progress' : 'Planned');

        $order->update(['actual_quantity' => $actual, 'good_quantity' => $good, 'status' => $status]);
    }

    private function recordEvent(ProductionBatch $batch, string $type, ?string $from, string $to, array $details, ?int $userId): void
    {
        ProductionBatchEvent::create([
            'production_batch_id' => $batch->id,
            'user_id' => $userId,
            'event_type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'details' => $details,
        ]);
    }
}
