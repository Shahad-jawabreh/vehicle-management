<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name'];
    protected $dates = ['deleted_at'];
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function zones()
    {
        return $this->hasMany(CompanyZone::class);
    }

}
