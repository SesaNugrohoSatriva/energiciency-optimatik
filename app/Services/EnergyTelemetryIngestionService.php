<?php

namespace App\Services;

use App\Models\EnergyRecord;
use App\Models\Machine;
use Carbon\CarbonImmutable;

class EnergyTelemetryIngestionService
{
    public function ingest(array $data, ?Machine $machine, float $tariff): EnergyRecord
    {
        $recordedAt = CarbonImmutable::parse($data['recorded_at'])
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');

        return EnergyRecord::firstOrCreate(
            ['external_event_id' => $data['external_event_id']],
            [
                'machine_id' => $machine?->id,
                'process_id' => $data['process_id'] ?? null,
                'production_order_id' => $data['production_order_id'] ?? null,
                'device_id' => $data['device_id'],
                'kwh' => $data['kwh'],
                'tariff_per_kwh' => $tariff,
                'cost' => round((float) $data['kwh'] * $tariff, 2),
                'recorded_at' => $recordedAt,
                'source' => 'iot',
                'payload' => $data['payload'] ?? [],
            ]
        );
    }
}
