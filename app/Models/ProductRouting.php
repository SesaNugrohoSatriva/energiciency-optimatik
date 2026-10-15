<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductRouting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(RoutingOperation::class)->orderBy('sequence');
    }
}
