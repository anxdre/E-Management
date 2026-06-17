<?php

namespace App\Models\PresenceManagement;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceEmployee extends Model
{
    use HasFactory;
    protected $table = 'trx_presence_employees';
    protected $guarded = ['id'];

    function presenceVerification()
    {
        return $this->belongsTo(PresenceVerification::class, 'trx_presence_verification_id','id');
    }

    function presenceLocation()
    {
        return $this->belongsTo(PresenceLocation::class, 'mst_presence_location_id','id');
    }

    function user()
    {
        return $this->belongsTo(User::class, 'mst_user_id','id');
    }
}
