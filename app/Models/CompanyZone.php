<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class CompanyZone extends Model
{
    use HasSpatial;

    protected $fillable = ['company_id','name','location','radius'];
    protected $casts = [
        'location' => \MatanYadaev\EloquentSpatial\Objects\Point::class,
    ];    protected $spatialFields = ['location'];

    public function company()
{
    return $this->belongsTo(Company::class);
}
}
