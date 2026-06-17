<?php

namespace App\Models\PresenceManagement;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceLocation extends Model
{
    use HasFactory;
    protected $table = 'mst_presence_locations';
    protected $guarded = ['id'];

    function user()
    {
        return $this->belongsTo(User::class, 'mst_user_id', 'id');
    }

    function presenceEmployee()
    {
        return $this->hasMany(PresenceEmployee::class, 'mst_presence_location_id', 'id');
    }

    function presenceVerification()
    {
        return $this->hasMany(PresenceVerification::class, 'mst_presence_location_id', 'id');
    }
    function singleVerification()
    {
        return $this->hasMany(PresenceVerification::class, 'mst_presence_location_id', 'id')->latest()
            ->limit(1);
    }
}
