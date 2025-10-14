<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EmployeeSalary extends Pivot
{
    use HasFactory;
    protected $casts = [
        'available_to_request' => 'boolean',
        'included_at_default' => 'boolean',
    ];
    protected $table = "employee_salary";
}
