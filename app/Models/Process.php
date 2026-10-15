<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Process extends Model
{
    protected $table = 'processes';
    protected $guarded = [];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function routingOperations(): HasMany
    {
        return $this->hasMany(RoutingOperation::class);
    }
}
