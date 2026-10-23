<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnergyRecord;
use App\Models\IotDevice;

class LiveMonitorController extends Controller
{
    /**
     * GET /api/iot/latest
     * Kembalikan pembacaan terbaru dari semua perangkat IoT aktif.
     */
    public function index()
    {
        $devices = IotDevice::where('is_active', true)->get();

        $readings = $devices->map(function ($device) {
            $record = EnergyRecord::where('device_id', $device->device_id)
                ->where('source', 'iot')
                ->latest('recorded_at')
                ->first();

            if (!$record) return null;

            $payload = $record->payload ?? [];

            return [
                'device_id'    => $device->device_id,
                'machine_name' => $device->machine?->name ?? '—',
                'recorded_at'  => $record->recorded_at?->diffForHumans(),
                'voltage_v'    => $payload['voltage_v']    ?? null,
                'current_a'    => $payload['current_a']    ?? null,
                'power_w'      => $payload['power_w']      ?? null,
                'frequency_hz' => $payload['frequency_hz'] ?? null,
                'power_factor' => $payload['power_factor'] ?? null,
                'kwh'          => $record->kwh,
                'cost'         => $record->cost,
            ];
        })->filter()->values();

        return response()->json(['ok' => true, 'data' => $readings]);
    }
}
