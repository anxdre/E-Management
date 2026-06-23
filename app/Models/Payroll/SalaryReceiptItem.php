<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryReceiptItem extends Model
{
    use HasFactory;

    protected $table = 'trx_salary_receipt_item';
    protected $guarded = [''];

    public function salaryReceipt()
    {
        return $this->belongsTo(SalaryReceipt::class, 'trx_salary_receipt_id', 'id');
    }

    public function requestedSalary()
    {
        return $this->belongsTo(EmployeeRequestedSalary::class, 'trx_employee_requested_salary_id', 'id');
    }
}
