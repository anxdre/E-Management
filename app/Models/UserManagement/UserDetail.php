<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $table = 'user_details';
    protected $guarded = ['id'];
    use HasFactory;
}
