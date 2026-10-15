<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyRecord extends Model {
 protected $table='energy_records';
 protected $guarded=[];
 protected function casts(): array { return ['payload'=>'array','recorded_at'=>'datetime']; }

 public function machine(): BelongsTo { return $this->belongsTo(Machine::class); }
 public function process(): BelongsTo { return $this->belongsTo(Process::class); }
 public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
 public function productionBatch(): BelongsTo { return $this->belongsTo(ProductionBatch::class); }
 public function routingOperation(): BelongsTo { return $this->belongsTo(RoutingOperation::class); }
}
