<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryReceiptItem extends Model
{
    use HasFactory;
    protected $table = 'salary_receipt_item';
    protected $guarded = [''];

    public function salaryReceipt()
    {
        return $this->belongsTo(SalaryReceipt::class, 'salary_receipt_id', 'id');
    }
}
