<?php

namespace App\Exports;

use App\Models\Payroll\SalaryReceipt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReceiptDetailExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected int $receiptId;

    public function __construct(int $receiptId)
    {
        $this->receiptId = $receiptId;
    }

    public function collection(): Collection
    {
        $receipt = SalaryReceipt::query()
            ->with(['companySalaryItem', 'user.userDetail'])
            ->findOrFail($this->receiptId);

        $items = $receipt->companySalaryItem->map(function ($item) use ($receipt) {
            return (object) [
                'employee_name' => $receipt->user?->userDetail?->fullname ?? '-',
                'employee_email' => $receipt->user?->email ?? '-',
                'period_start' => $receipt->start_date,
                'period_end' => $receipt->end_date,
                'component_name' => $item->name,
                'component_type' => $item->type,
                'calculation_type' => $item->calculation_type,
                'is_tax' => $item->is_tax ? 'Yes' : 'No',
                'base_salary' => $item->salary,
                'quantity' => $item->detail_item?->quantity ?? 0,
                'total_value' => $item->detail_item?->total_value ?? 0,
                'receipt_status' => $receipt->status,
            ];
        });

        if ($items->isEmpty()) {
            $items = collect([
                (object) [
                    'employee_name' => $receipt->user?->userDetail?->fullname ?? '-',
                    'employee_email' => $receipt->user?->email ?? '-',
                    'period_start' => $receipt->start_date,
                    'period_end' => $receipt->end_date,
                    'component_name' => '-',
                    'component_type' => '-',
                    'calculation_type' => '-',
                    'is_tax' => '-',
                    'base_salary' => 0,
                    'quantity' => 0,
                    'total_value' => 0,
                    'receipt_status' => $receipt->status,
                ],
            ]);
        }

        return $items;
    }

    public function headings(): array
    {
        return [
            'No',
            'Employee Name',
            'Employee Email',
            'Period Start',
            'Period End',
            'Component Name',
            'Component Type',
            'Calculation Type',
            'Is Tax',
            'Base Salary',
            'Quantity',
            'Total Value',
            'Receipt Status',
        ];
    }

    public function map($item): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $item->employee_name,
            $item->employee_email,
            $item->period_start,
            $item->period_end,
            $item->component_name,
            $item->component_type,
            $item->calculation_type,
            $item->is_tax,
            $item->base_salary,
            $item->quantity,
            $item->total_value,
            $item->receipt_status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
