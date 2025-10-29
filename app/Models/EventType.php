<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventType extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = ['type', 'description', 'is_active', 'is_special'];
    public function userEvents()
    {
        return $this->hasMany(UserEvent::class, 'event_id');
    }
}
