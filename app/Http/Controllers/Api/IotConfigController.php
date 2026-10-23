<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IotDevice;
use Illuminate\Http\Request;

class IotConfigController extends Controller
{
    /**
     * GET /api/iot/config
     *
     * ESP32 kirim header:
     *   X-IOT-TOKEN : token rahasia (sama dengan IOT_INGEST_TOKEN di .env)
     *   X-DEVICE-MAC: MAC address ESP32 (format AA:BB:CC:DD:EE:FF)
     *
     * Response:
     *   { device_id, machine_code, send_interval_ms }
     */
    public function show(Request $request)
    {
        // Validasi token
        $expected = (string) env('IOT_INGEST_TOKEN', '');
        $provided = (string) $request->header('X-IOT-TOKEN', '');
        abort_if($expected === '' || !hash_equals($expected, $provided), 401, 'Token tidak valid.');

        $mac = strtoupper(trim($request->header('X-DEVICE-MAC', '')));
        abort_if($mac === '', 400, 'Header X-DEVICE-MAC wajib diisi.');

        $device = IotDevice::where('mac_address', $mac)->where('is_active', true)->first();
        abort_if(!$device, 404, 'Perangkat tidak ditemukan. Daftarkan MAC address di dashboard.');

        // Catat waktu terakhir fetch config
        $device->update(['last_seen_at' => now()]);

        return response()->json([
            'ok'   => true,
            'config' => $device->toDeviceConfig(),
        ]);
    }
}
