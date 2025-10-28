<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable,HasApiTokens, Notifiable;
    protected $fillable = [
        'name', 'email', 'password', 'company_id',
        'role', 'created_by', 'notification_recipient_id'
    ];

    protected $hidden = ['password'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notificationRecipient()
    {
        return $this->belongsTo(User::class, 'notification_recipient_id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function userEvents()
    {
        return $this->hasMany(UserEvent::class, 'user_to_notify_id');
    }
}
