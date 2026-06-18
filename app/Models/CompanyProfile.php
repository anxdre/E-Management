<?php

namespace App\Models;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $table = 'mst_company_profile';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'mst_user_id');
    }
}
