<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDetail extends Model
{
    protected $table = 'company_details';
    protected $guarded = ['id'];
    use HasFactory;
}
