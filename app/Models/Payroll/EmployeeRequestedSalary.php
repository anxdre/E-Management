<?php

namespace App\Models\Payroll;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRequestedSalary extends Model
{
    use HasFactory;

    protected $table = "trx_employee_requested_salary";
    protected $guarded = ['id'];

    protected $casts = [
        'is_realized' => 'boolean',
        'approved_date' => 'datetime',
    ];

    public function employeeSalary()
    {
        return $this->belongsTo(EmployeeSalary::class, 'pivot_employee_salary_id', 'id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'mst_approved_by', 'id');
    }
}
