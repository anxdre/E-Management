<?php

namespace App\Exports;

use App\Models\Payroll\SalaryReceipt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollReceiptExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        return SalaryReceipt::query()
            ->with(['user.userDetail', 'user.groups'])
            ->when(!empty($this->filters['employee_id']), function ($q) {
                $q->where('mst_user_id', $this->filters['employee_id']);
            })
            ->when(!empty($this->filters['search']), function ($q) {
                $q->whereHas('user.userDetail', function ($sub) {
                    $sub->where('fullname', 'like', "%{$this->filters['search']}%");
                })->orWhereHas('user', function ($sub) {
                    $sub->where('email', 'like', "%{$this->filters['search']}%");
                });
            })
            ->when(!empty($this->filters['date_start']) && !empty($this->filters['date_end']), function ($q) {
                $q->whereBetween('start_date', [$this->filters['date_start'], $this->filters['date_end']]);
            })
            ->when(!empty($this->filters['status']), function ($q) {
                $q->where('status', $this->filters['status']);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Employee Name',
            'Email',
            'Phone',
            'Group',
            'Total Salary',
            'Total Tax',
            'Salary After Tax',
            'Work Hours',
            'Total Presence',
            'Period Start',
            'Period End',
            'Status',
            'Created At',
        ];
    }

    public function map($receipt): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $receipt->user?->userDetail?->fullname ?? '-',
            $receipt->user?->email ?? '-',
            $receipt->user?->userDetail?->phone ?? '-',
            $receipt->user?->groups?->pluck('name')->implode(', ') ?? '-',
            $receipt->total_salary ?? 0,
            $receipt->total_tax ?? 0,
            $receipt->salary_after_tax ?? 0,
            $receipt->work_hour ?? 0,
            $receipt->total_presence_record ?? 0,
            $receipt->start_date ?? '-',
            $receipt->end_date ?? '-',
            $receipt->status ?? 'pending',
            $receipt->created_at ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
