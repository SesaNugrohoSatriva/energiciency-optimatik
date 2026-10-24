<?php

namespace App\Services;

use App\Models\ProductionBatch;
use App\Models\ProductionBatchEvent;
use App\Models\ProductionOrder;
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
                'batch_number' => $lockedOrder->order_number . '-B' . str_pad((string) $next, 3, '0', STR_PAD_LEFT),
                'planned_quantity' => $plannedQuantity,
                'status' => 'planned',
                'routing_snapshot' => $lockedOrder->routing_snapshot,
                'notes' => $notes,
            ]);
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
            if (empty($locked->routing_snapshot['operations'])) {
                throw ValidationException::withMessages(['batch' => 'Order belum memiliki snapshot routing aktif dengan operasi. Tetapkan routing sebelum memulai batch.']);
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
