<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class geofences extends Model
{
    /** @use HasFactory<\Database\Factories\GeofencesFactory> */
    use HasFactory;
    protected $fillable = [
        'name',
        'center_latitude',
        'center_longitude',
        'radius_meters',
    ];
}
