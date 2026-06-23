<?php

namespace App\Models\Payroll;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'trx_salary_receipt';
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'mst_user_id', 'id')->withTrashed();
    }

    public function salaryReceiptItems()
    {
        return $this->hasMany(SalaryReceiptItem::class, 'trx_salary_receipt_id', 'id');
    }

    public function companySalaryItem()
    {
        return $this->belongsToMany(CompanySalary::class, 'trx_salary_receipt_item', 'trx_salary_receipt_id', 'mst_company_salary_id','id', 'id')
            ->withPivot(['quantity', 'total_value', 'trx_employee_requested_salary_id', 'salary_name_snapshot', 'salary_rate_snapshot'])
            ->withTimestamps()
            ->as('detail_item')
            ->orderBy('is_tax');
    }
}
