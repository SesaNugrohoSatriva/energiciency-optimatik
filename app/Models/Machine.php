<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $table = 'machines';

    protected $guarded = [];

    public function processes(): HasMany
    {
        return $this->hasMany(Process::class);
    }

    public function routingOperations(): HasMany
    {
        return $this->hasMany(RoutingOperation::class);
    }

    public function energyRecords(): HasMany
    {
        return $this->hasMany(EnergyRecord::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function oeeRecords(): HasMany
    {
        return $this->hasMany(OeeRecord::class);
    }
}
