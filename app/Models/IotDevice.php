<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotDevice extends Model
{
    protected $table = 'iot_devices';

    protected $guarded = [];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_code', 'code');
    }

    /** Ambil config yang dikirim ke ESP32 */
    public function toDeviceConfig(): array
    {
        return [
            'device_id'        => $this->device_id,
            'machine_code'     => $this->machine_code,
            'send_interval_ms' => $this->send_interval_ms,
        ];
    }
}
