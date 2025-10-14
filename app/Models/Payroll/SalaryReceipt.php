<?php

namespace App\Models\Payroll;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryReceipt extends Model
{
    use HasFactory;

    protected $table = 'salary_receipt';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function salaryReceiptItems()
    {
        return $this->hasMany(SalaryReceiptItem::class, 'salary_receipt_id', 'id');
    }

    public function companySalaryItem()
    {
        return $this->belongsToMany(CompanySalary::class, 'salary_receipt_item', 'salary_receipt_id', 'company_salary_id','id', 'id')
            ->withPivot(['quantity', 'total_value'])
            ->withTimestamps()
            ->as('detail_item')
            ->orderBy('is_tax');
    }
}
