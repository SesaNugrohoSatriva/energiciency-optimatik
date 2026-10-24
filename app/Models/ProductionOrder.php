<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    protected $table = 'production_orders';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['routing_snapshot' => 'array', 'scheduled_at' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function routing(): BelongsTo
    {
        return $this->belongsTo(ProductRouting::class, 'product_routing_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function energyRecords(): HasMany
    {
        return $this->hasMany(EnergyRecord::class);
    }
}
