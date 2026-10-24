<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionBatch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'routing_snapshot' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProductionBatchEvent::class)->latest('created_at');
    }

    public function energyRecords(): HasMany
    {
        return $this->hasMany(EnergyRecord::class);
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(ProductionBatchCostEntry::class, 'production_batch_id')->latest('created_at');
    }

    public function oeeRecords(): HasMany
    {
        return $this->hasMany(OeeRecord::class)->orderByDesc('recorded_on');
    }
}
