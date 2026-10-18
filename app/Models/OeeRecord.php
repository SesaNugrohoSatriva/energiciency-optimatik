<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OeeRecord extends Model
{
    protected $table = 'oee_records';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['recorded_on' => 'date'];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function routingOperation(): BelongsTo
    {
        return $this->belongsTo(RoutingOperation::class);
    }
}
