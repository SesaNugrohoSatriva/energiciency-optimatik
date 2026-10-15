<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingOperation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['requires_quality_inspection' => 'boolean', 'is_optional' => 'boolean'];
    }

    public function routing(): BelongsTo
    {
        return $this->belongsTo(ProductRouting::class, 'product_routing_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}
