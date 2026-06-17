<?php

namespace App\Models\PresenceManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceVerification extends Model
{
    use HasFactory;
    protected $table = 'trx_presence_verifications';
    protected $guarded = ['id'];

    function presenceLocation()
    {
        return $this->belongsTo(PresenceLocation::class, 'mst_presence_location_id','id');
    }

    function userPresence()
    {
        return $this->hasMany(PresenceEmployee::class, 'trx_presence_verification_id','id');
    }
}
