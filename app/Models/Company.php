<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Factories\HasFactory;class Company extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function zones()
    {
        return $this->hasMany(CompanyZone::class);
    }
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
    
}
