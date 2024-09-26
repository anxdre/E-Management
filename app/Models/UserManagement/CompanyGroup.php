<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyGroup extends Model
{
    protected $table = 'company_groups';
    protected $guarded = ['id'];
    use HasFactory;

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id','id');
    }

    public function employee(){
        return $this->belongsToMany(User::class, 'group_has_users','group_id','user_id');
    }
}
