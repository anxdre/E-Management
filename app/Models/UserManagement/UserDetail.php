<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mst_user_details';
    protected $guarded = ['id'];

    public function user(){
        return $this->hasOne(User::class, 'mst_user_detail_id');
    }
}
