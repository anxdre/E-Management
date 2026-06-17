<?php

namespace App\Models\Payroll;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySalary extends Model
{
    use HasFactory;
    protected $table = "mst_company_salary";
    protected $guarded = ['id'];

    protected $casts = [
        'is_tax' => 'boolean',
    ];

    public function employeeSalary(){
        return $this->belongsToMany(User::class, 'pivot_employee_salary','mst_company_salary_id','mst_user_id')
            ->using(EmployeeSalary::class)
            ->withPivot(['available_to_request','included_at_default']);
    }
}
