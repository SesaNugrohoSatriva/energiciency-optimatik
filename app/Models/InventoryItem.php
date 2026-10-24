<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';

    protected $guarded = [];

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->latest('created_at');
    }
}
