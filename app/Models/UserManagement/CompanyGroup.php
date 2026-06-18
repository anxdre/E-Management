<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyGroup extends Model
{
    protected $table = 'mst_company_groups';
    protected $guarded = ['id'];
    use HasFactory;

    public function employee(){
        return $this->belongsToMany(User::class, 'pivot_group_has_users','mst_company_group_id','mst_user_id');
    }
}
