<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryReceiptItem extends Model
{
    use HasFactory, SoftDeletes;

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
