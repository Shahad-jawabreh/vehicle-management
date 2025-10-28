<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Vehicle extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = [
        'user_id', 'license_plate', 'model_name', 'location', 'speed'
    ];

    protected $casts = [
        'location' => \MatanYadaev\EloquentSpatial\Objects\Point::class,
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
