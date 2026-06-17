<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupHasUser extends Pivot
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'pivot_group_has_users';
    public $incrementing = true;
}
