<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = ['license_plate', 'model_name'];

    // Define the One-to-Many relationship: A Vehicle has many Devices
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
