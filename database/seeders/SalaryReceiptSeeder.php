<?php

namespace Database\Seeders;

use App\Models\Payroll\CompanySalary;
use App\Models\Payroll\EmployeeRequestedSalary;
use App\Models\Payroll\SalaryReceipt;
use App\Models\Payroll\SalaryReceiptItem;
use App\Models\UserManagement\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaryReceiptSeeder extends Seeder
{
    public function run(): void
    {
        $employees = User::where('type', 'employee')->get();
        $periods = [
            ['start' => '2026-04-01', 'end' => '2026-04-30'],
            ['start' => '2026-05-01', 'end' => '2026-05-31'],
            ['start' => '2026-06-01', 'end' => '2026-06-30'],
        ];

        foreach ($employees as $employee) {
            foreach ($periods as $period) {
                $this->generateReceipt($employee, $period['start'], $period['end']);
            }
        }
    }

    private function generateReceipt(User $user, string $startDate, string $endDate): void
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $presences = DB::table('trx_presence_employees')
            ->where('mst_user_id', $user->id)
            ->whereBetween('time_in', [$start, $end])
            ->whereNotNull('time_out')
            ->get();

        $totalPresenceRecord = $presences->count();

        $totalWorkHours = 0;
        foreach ($presences as $p) {
            $in = Carbon::parse($p->time_in);
            $out = Carbon::parse($p->time_out);
            $totalWorkHours += $out->diffInHours($in);
        }

        $defaultPivots = DB::table('pivot_employee_salary')
            ->where('mst_user_id', $user->id)
            ->where('included_at_default', true)
            ->get();

        $requestedSalaries = EmployeeRequestedSalary::query()
            ->with('employeeSalary')
            ->whereHas('employeeSalary', function ($q) use ($user) {
                $q->where('mst_user_id', $user->id);
            })
            ->where('status', 'approved')
            ->where('is_realized', false)
            ->get();

        $salaryComponents = [];
        $taxComponents = [];
        $totalSalary = 0;
        $totalTax = 0;

        foreach ($defaultPivots as $pivot) {
            $salary = CompanySalary::find($pivot->mst_company_salary_id);
            if (!$salary) continue;

            $result = $this->calc($salary, $totalWorkHours, $totalPresenceRecord);

            if ($salary->is_tax) {
                $taxComponents[] = ['id' => $salary->id, 'rate' => $result['total']];
            } else {
                $totalSalary += $salary->calculation_type === 'add' ? $result['total'] : -$result['total'];
                $salaryComponents[] = [
                    'id' => $result['id'],
                    'quantity' => $result['quantity'],
                    'total_value' => $result['total'],
                ];
            }
        }

        foreach ($requestedSalaries as $requested) {
            $salary = CompanySalary::find($requested->employeeSalary->mst_company_salary_id);
            if (!$salary) continue;

            $result = $this->calc($salary, $totalWorkHours, $totalPresenceRecord, $requested->quantity);

            if ($salary->is_tax) {
                $taxComponents[] = [
                    'id' => $salary->id,
                    'rate' => $result['total'],
                    'requested_id' => $requested->id,
                ];
            } else {
                $totalSalary += $salary->calculation_type === 'add' ? $result['total'] : -$result['total'];
                $salaryComponents[] = [
                    'id' => $result['id'],
                    'quantity' => $result['quantity'],
                    'total_value' => $result['total'],
                    'requested_id' => $requested->id,
                ];
            }
        }

        foreach ($taxComponents as $tax) {
            $taxAmount = (int) round(($totalSalary * $tax['rate']) / 100);
            $totalTax += $taxAmount;
            $salaryComponents[] = [
                'id' => $tax['id'],
                'quantity' => 1,
                'total_value' => $taxAmount,
                'requested_id' => $tax['requested_id'] ?? null,
            ];
        }

        $salaryAfterTax = $totalSalary - $totalTax;

        if ($totalSalary <= 0) return;

        $receipt = SalaryReceipt::create([
            'mst_user_id' => $user->id,
            'work_hour' => $totalWorkHours,
            'total_salary' => $totalSalary,
            'total_tax' => $totalTax,
            'salary_after_tax' => $salaryAfterTax,
            'total_presence_record' => $totalPresenceRecord,
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'approved',
        ]);

        foreach ($salaryComponents as $component) {
            SalaryReceiptItem::create([
                'trx_salary_receipt_id' => $receipt->id,
                'mst_company_salary_id' => $component['id'],
                'quantity' => $component['quantity'],
                'total_value' => $component['total_value'],
                'trx_employee_requested_salary_id' => $component['requested_id'] ?? null,
            ]);
        }

        foreach ($requestedSalaries as $requested) {
            $requested->update(['is_realized' => true]);
        }
    }

    private function calc(CompanySalary $component, int $workHours, int $presenceCount, ?int $specificQuantity = null): array
    {
        return match ($component->type) {
            'fixed' => [
                'id' => $component->id,
                'quantity' => $specificQuantity ?? 1,
                'total' => $component->salary * ($specificQuantity ?? 1),
            ],
            'hourly' => [
                'id' => $component->id,
                'quantity' => $specificQuantity ?? $workHours,
                'total' => $component->salary * ($specificQuantity ?? $workHours),
            ],
            'presence' => [
                'id' => $component->id,
                'quantity' => $specificQuantity ?? $presenceCount,
                'total' => $component->salary * ($specificQuantity ?? $presenceCount),
            ],
            'tax' => [
                'id' => $component->id,
                'quantity' => $specificQuantity ?? 1,
                'total' => $component->salary * ($specificQuantity ?? 1),
            ],
            default => ['id' => $component->id, 'quantity' => 0, 'total' => 0],
        };
    }
}
