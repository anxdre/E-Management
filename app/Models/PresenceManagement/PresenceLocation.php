<?php

namespace App\Models\PresenceManagement;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceLocation extends Model
{
    use HasFactory;
    protected $table = 'presence_locations';
    protected $guarded = ['id'];

    function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    function presenceEmployee()
    {
        return $this->hasMany(PresenceEmployee::class, 'presence_location_id', 'id');
    }

    function presenceVerification()
    {
        $this->hasMany(PresenceVerification::class, 'presence_location_id', 'id');
    }
}
