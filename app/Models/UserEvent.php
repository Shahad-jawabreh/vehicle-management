<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserEvent extends Model
{
    use HasFactory;

    protected $fillable = [
    'event_id',
    'details',
    'vehicle_id',
    'is_notified',
    'user_to_notify_id',
    'reference_id',
    'state_data',
    'last_triggered_at'
    ];

    protected $casts = [
        'details' => 'array',
        'state_data' => 'array',
        'last_triggered_at' => 'datetime'
    ];



    public function eventType()
    {
        return $this->belongsTo(EventType::class, 'event_id');
    }
    public function userToNotify()
    {
        return $this->belongsTo(User::class, 'user_to_notify_id');
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function hasChanged(array $newState): bool
    {
        return $this->state_data !== $newState;
    }
     public function canNotifyAgain(int $minutes = 15): bool
    {
        if (!$this->last_triggered_at) {
            return true;
        }
        return $this->last_triggered_at->diffInMinutes(now()) >= $minutes;
    }
    public function zone()
    {
        return $this->belongsTo(CompanyZone::class, 'reference_id');
    }
}
