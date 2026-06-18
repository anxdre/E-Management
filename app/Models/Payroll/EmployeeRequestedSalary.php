<?php

namespace App\Models\Payroll;

use App\Models\UserManagement\User;
use App\Models\Payroll\CompanySalary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeRequestedSalary extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "trx_employee_requested_salary";
    protected $guarded = ['id'];

    protected $casts = [
        'is_realized' => 'boolean',
        'approved_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'mst_user_id', 'id');
    }

    public function component()
    {
        return $this->belongsTo(CompanySalary::class, 'mst_company_salary_id', 'id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'mst_approved_by', 'id')->withTrashed();
    }
}
