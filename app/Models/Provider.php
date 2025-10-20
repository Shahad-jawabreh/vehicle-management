<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Provider extends Model
{
    protected $fillable = ['name', 'type', 'data_key', 'device_id'];

    // Eager load the parent Device when retrieving a Provider
    protected $with = ['device'];

    // Define the inverse One-to-Many relationship: A Provider belongs to one Device
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
