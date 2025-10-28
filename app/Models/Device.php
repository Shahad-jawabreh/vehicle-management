<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;
    protected $fillable = ['serial_number', 'device_type', 'is_active', 'vehicle_id'];

    protected $with = ['vehicle'];

    /**
     * A Device belongs to one Vehicle. (One-to-One inverse)
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * A Device has many Providers (Sensors).
     */
    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }
}
