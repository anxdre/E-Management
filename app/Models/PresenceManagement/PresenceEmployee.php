<?php

namespace App\Models\PresenceManagement;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceEmployee extends Model
{
    use HasFactory;
    protected $table = 'presence_employees';
    protected $guarded = ['id'];

    function presenceVerification()
    {
        return $this->belongsTo(PresenceVerification::class, 'presence_verification_id','id');
    }

    function presenceLocation()
    {
        return $this->belongsTo(PresenceLocation::class, 'presence_location_id','id');
    }

    function user()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }
}
