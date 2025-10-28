<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class CompanyZone extends Model
{
    use HasSpatial;

    protected $fillable = ['company_id','name','location','radius','created_by'];
    protected $casts = [
        'location' => \MatanYadaev\EloquentSpatial\Objects\Point::class,
    ];    protected $spatialFields = ['location'];
    public function userToNotify()
    {
        return $this->belongsTo(User::class, 'user_to_notify_id');
    }
    public function company()  
{
    return $this->belongsTo(Company::class);
}
}
