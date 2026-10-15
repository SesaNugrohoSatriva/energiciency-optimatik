<?php

namespace App\Services;

use App\Models\EnergyRecord;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnergyAnalyticsService
{
    public function filters(Request $request, ?int $machineId = null): array
    {
        $latest = EnergyRecord::query()
            ->when($machineId, fn ($query) => $query->where('machine_id', $machineId))
            ->max('recorded_at');
        $anchor = $latest
            ? CarbonImmutable::parse($latest)->toDateString()
            : CarbonImmutable::now()->toDateString();

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'interval' => ['nullable', 'in:hour,day,week,month'],
            'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
            'source' => ['nullable', 'in:simulation,manual,iot'],
            'production_order_id' => ['nullable', 'integer', 'exists:production_orders,id'],
            'production_batch_id' => ['nullable', 'integer', 'exists:production_batches,id'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        if (! $from && ! $to) {
            $to = $anchor;
            $from = CarbonImmutable::parse($anchor)->subDays(13)->toDateString();
        } elseif (! $from) {
            $from = CarbonImmutable::parse($to)->subDays(13)->toDateString();
        } elseif (! $to) {
            $to = CarbonImmutable::now()->toDateString();
        }

        if (CarbonImmutable::parse($from)->gt(CarbonImmutable::parse($to))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'to' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
            ]);
        }

        $filters['from'] = $from;
        $filters['to'] = $to;
        $filters['interval'] = $filters['interval'] ?? 'day';

        return $filters;
    }

    public function query(array $filters, ?int $machineId = null): Builder
    {
        return EnergyRecord::query()
            ->when($machineId, fn (Builder $query) => $query->where('machine_id', $machineId))
            ->when(! $machineId && ($filters['machine_id'] ?? null), fn (Builder $query, int $id) => $query->where('machine_id', $id))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('external_event_id', 'like', '%'.$search.'%')
                        ->orWhere('device_id', 'like', '%'.$search.'%')
                        ->orWhere('source', 'like', '%'.$search.'%')
                        ->orWhereHas('machine', function (Builder $machine) use ($search): void {
                            $machine->where('name', 'like', '%'.$search.'%')
                                ->orWhere('code', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('productionOrder', fn (Builder $order) => $order->where('order_number', 'like', '%'.$search.'%'))
                        ->orWhereHas('productionBatch', fn (Builder $batch) => $batch->where('batch_number', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->where('recorded_at', '>=', CarbonImmutable::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->where('recorded_at', '<=', CarbonImmutable::parse($to)->endOfDay()))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['production_order_id'] ?? null, fn (Builder $query, int $orderId) => $query->where('production_order_id', $orderId))
            ->when($filters['production_batch_id'] ?? null, fn (Builder $query, int $batchId) => $query->where('production_batch_id', $batchId))
            ->when($filters['device_id'] ?? null, fn (Builder $query, string $deviceId) => $query->where('device_id', $deviceId));
    }

    public function summary(Builder $query): array
    {
        $summary = (clone $query)
            ->selectRaw('
                COUNT(*) as record_count,
                COUNT(DISTINCT machine_id) as machine_count,
                COALESCE(SUM(kwh), 0) as total_kwh,
                COALESCE(SUM(cost), 0) as total_cost,
                AVG(kwh) as average_record_kwh,
                MAX(recorded_at) as last_recorded_at
            ')
            ->first();

        return [
            'record_count' => (int) $summary->record_count,
            'machine_count' => (int) $summary->machine_count,
            'total_kwh' => (float) $summary->total_kwh,
            'total_cost' => (float) $summary->total_cost,
            'average_record_kwh' => $summary->average_record_kwh === null ? null : (float) $summary->average_record_kwh,
            'last_recorded_at' => $summary->last_recorded_at,
        ];
    }

    public function trend(Builder $query, string $interval): array
    {
        $bucket = $this->bucketExpression($interval);

        return (clone $query)
            ->selectRaw("$bucket as bucket, SUM(kwh) as total_kwh, SUM(cost) as total_cost, COUNT(*) as record_count")
            ->groupBy('bucket')
            ->orderByDesc('bucket')
            ->limit(180)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($row) => [
                'bucket' => (string) $row->bucket,
                'total_kwh' => (float) $row->total_kwh,
                'total_cost' => (float) $row->total_cost,
                'record_count' => (int) $row->record_count,
            ])
            ->all();
    }

    public function previousPeriodSummary(array $filters, ?int $machineId = null): ?array
    {
        $start = CarbonImmutable::parse($filters['from'])->startOfDay();
        $end = CarbonImmutable::parse($filters['to'])->endOfDay();
        $days = $start->diffInDays($end) + 1;
        $previousFrom = $start->subDays($days)->toDateString();
        $previousTo = $start->subDay()->toDateString();

        $previousFilters = [...$filters, 'from' => $previousFrom, 'to' => $previousTo];
        $previous = $this->query($previousFilters, $machineId);

        $summary = $this->summary($previous);

        return $summary['record_count'] === 0 ? null : $summary;
    }

    private function bucketExpression(string $interval): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return match ($interval) {
                'hour' => "strftime('%Y-%m-%d %H:00', recorded_at)",
                'week' => "date(recorded_at, '-' || ((CAST(strftime('%w', recorded_at) AS INTEGER) + 6) % 7) || ' days')",
                'month' => "strftime('%Y-%m', recorded_at)",
                default => "date(recorded_at)",
            };
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return match ($interval) {
                'hour' => "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00')",
                'week' => "DATE_FORMAT(DATE_SUB(DATE(recorded_at), INTERVAL WEEKDAY(recorded_at) DAY), '%Y-%m-%d')",
                'month' => "DATE_FORMAT(recorded_at, '%Y-%m')",
                default => 'DATE(recorded_at)',
            };
        }

        return 'DATE(recorded_at)';
    }
}
