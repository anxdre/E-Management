<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRequestedSalary extends Model
{
    use HasFactory;
    protected $table = "employee_requested_salary";
    protected $guarded = ['id'];
}
