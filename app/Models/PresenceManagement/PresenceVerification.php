<?php

namespace App\Models\PresenceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceVerification extends Model
{
    use HasFactory;
    protected $table = 'presence_verifications';
    protected $guarded = ['id'];

    function presenceLocation()
    {
        return $this->belongsTo(PresenceLocation::class, 'presence_location_id','id');
    }

    function userPresence()
    {
        return $this->hasMany(PresenceEmployee::class, 'presence_verification_id','id');
    }
}
