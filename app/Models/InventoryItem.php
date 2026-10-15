<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';
    protected $guarded = [];

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->latest('created_at');
    }
}
