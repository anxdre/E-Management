<?php

namespace App\Http\Controllers\Payroll;

use App\Exports\PayrollReceiptExport;
use App\Exports\ReceiptDetailExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\CompanyProfile;
use App\Models\Payroll\CompanySalary;
use App\Models\Payroll\EmployeeRequestedSalary;
use App\Models\Payroll\SalaryReceipt;
use App\Models\Payroll\SalaryReceiptItem;
use App\Models\UserManagement\User;
use Carbon\Carbon;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Dentro\Yalr\Attributes\Put;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

#[Prefix('receipt'), Name('company-receipt'), Middleware(['only-company'])]
class CompanyPayrollReceiptController extends Controller
{
    #[Get('/', '.index')]
    public function index(Request $request)
    {
        return Inertia::render('Admin/PayrollManagement/PayrollReceipt');
    }

    //user receipt user
    #[Get('/{employee}/payroll', '.user.index')]
    public function indexByUser(int $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        return Inertia::render('Employee/EmployeeSalary/EmployeePayrollReceipt', ['user' => $user]);
    }

    #[Get('/all/json', '.json.all',)]
    public function getAllPayroll(request $request)
    {
        $listOfEmployee = new Collection();
        if ($request->filled('employee_id')) {
            $listOfEmployee->push(User::withTrashed()->where('id', $request->employee_id)->firstOrFail());
        }

        if (!$request->filled('employee_id')) {
            $listOfEmployee = User::withTrashed()->get();
        }

        $data = SalaryReceipt::query()
            ->with(['user', 'user.userDetail', 'user.groups', 'salaryReceiptItems'])
            ->whereIn('mst_user_id', $listOfEmployee->pluck('id'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('email', 'like', "%{$request->search}%");
                })->orWhereHas('user.userDetail', function ($q) use ($request) {
                    $q->where('fullname', 'like', "%{$request->search}%");
                });
            })
            ->when($request->has('date_filter'), function ($query) use ($request) {
                $startDate = Carbon::parse($request->date_filter['start']);
                $endDate = Carbon::parse($request->date_filter['end']);
                $query->whereBetween('start_date', [$startDate, $endDate]);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('order_by'), function ($query) use ($request) {
                $query->orderBy('created_at', $request->order_by === 'oldest' ? 'asc' : 'desc');
            }, function ($query) {
                $query->orderBy('created_at', 'desc');
            })
            ->paginate(10)
            ->withQueryString();

        return new JsonBody($data);
    }

    #[Post('/add/bulk/json', '.json.add.bulk')]
    public function addBulkPayroll(Request $request)
    {
        $request->validate([
            'user_id' => 'required|array',
            'user_id.*' => 'required|exists:mst_users,id',
            'date_start' => 'required',
            'date_end' => 'required',]);

        try {
            $calculatedData = [];
            foreach ($request->user_id as $user) {
                $user = User::withTrashed()->find($user);
                if (!$user) {
                    continue;
                }
                $calculatedData[] = $this->calculateEmployeeSalary($user, $request->date_start, $request->date_end);
            }
            $calculatedData = collect($calculatedData);
            DB::beginTransaction();
            $calculatedData->each(function ($item) use ($request) {
                $receipt = SalaryReceipt::query()->create([
                    'mst_user_id' => $item['mst_user_id'],
                    'start_date' => Carbon::parse($request->date_start),
                    'end_date' => Carbon::parse($request->date_end),
                    'total_salary' => $item['total_salary'],
                    'salary_after_tax' => $item['salary_after_tax'],
                    'total_tax' => $item['total_tax'],
                    'work_hour' => $item['work_hours'],
                    'total_presence_record' => $item['total_presence_record'],
                ]);
                $item['salary_components'] = collect($item['salary_components']);
                $item['salary_components']->each(function ($component) use ($receipt) {
                    SalaryReceiptItem::query()->create([
                        'trx_salary_receipt_id' => $receipt->id,
                        'mst_company_salary_id' => $component['id'],
                        'quantity' => $component['quantity'],
                        'total_value' => $component['total_ammount'],
                        'trx_employee_requested_salary_id' => $component['trx_employee_requested_salary_id'] ?? null,
                        'salary_name_snapshot' => $component['salary_name_snapshot'] ?? null,
                        'salary_rate_snapshot' => $component['salary_rate_snapshot'] ?? null,
                    ]);
                });
            });
            DB::commit();
            return new JsonBody($calculatedData, message: 'Payroll company added successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            return new JsonBody(null, message: $exception->getMessage(), status_code: 500);
        }
    }

    private function calculateEmployeeSalary(User $user, string $startDate, string $endDate, ?array $specificComponent = null): array
    {
        // Validate that the user is an employee
        if (!$user->isEmployee()) {
            throw new \InvalidArgumentException('User must be an employee');
        }

        // Convert dates to Carbon instances for easier manipulation
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        // 1. Calculate work hours from presence records
        $presenceRecords = $user->presence()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalWorkHours = 0;
        foreach ($presenceRecords as $record) {
            // Calculate hours between check-in and check-out times
            if ($record->time_in && $record->time_out) {
                $checkIn = Carbon::parse($record->time_in);
                $checkOut = Carbon::parse($record->time_out);
                $hoursWorked = max(0, $checkOut->diffInHours($checkIn));
                $totalWorkHours += $hoursWorked;
            }
        }

        if (!$specificComponent) {
            // 2. Get employee's default salary components (included by default)
            $defaultSalaryComponents = $user->defaultSalaries;

            // 3. Get requested salary components that were approved and not yet realized
            $requestedSalaryComponents = EmployeeRequestedSalary::query()
                ->where('mst_user_id', $user->id)
                ->whereBetween('approved_date', [$startDate, $endDate])
                ->where('status', 'approved')
                ->where('is_realized', false)
                ->get();
        }

        if ($specificComponent) {
            $defaultSalaryComponents = [];
            foreach ($specificComponent as $component) {
                $dataComponent = CompanySalary::query()
                    ->find($component['id']);
                if (!$dataComponent) {
                    continue;
                }
                $dataComponent->quantity = $component['quantity'] ?? 1;
                $defaultSalaryComponents[] = $dataComponent;
            }
            $requestedSalaryComponents = [];
        }

        // 4. Calculate total salary
        $salaryComponents = [];
        $taxComponents = [];
        $totalSalary = 0;
        $totalTax = 0;

        // Process default salary components
        foreach ($defaultSalaryComponents as $component) {
            $calculatedComponent = $this->calculateComponentAmount($component, $totalWorkHours, $presenceRecords, $component->quantity);

            if ($component->is_tax) {
                // Store tax components for later calculation
                $taxComponents[] = [
                    'id' => $component->id,
                    'name' => $component->name,
                    'quantity' => $calculatedComponent->quantity,
                    'rate' => $calculatedComponent->totalSalary, // Tax rate in percentage
                    'base_amount' => $calculatedComponent->salary
                ];
            } else {
                // Add or subtract based on calculation type
                if ($component->calculation_type === 'add') {
                    $totalSalary += $calculatedComponent->totalSalary;
                } else {
                    $totalSalary -= $calculatedComponent->totalSalary;
                }

                $salaryComponents[] = [
                    'id' => $component->id,
                    'name' => $component->name,
                    'type' => $component->type,
                    'quantity' => $calculatedComponent->quantity,
                    'total_ammount' => $calculatedComponent->totalSalary,
                    'calculation_type' => $component->calculation_type,
                    'salary_name_snapshot' => $component->name,
                    'salary_rate_snapshot' => $component->salary,
                ];
            }
        }

        // Process requested salary components
        foreach ($requestedSalaryComponents as $requestedSalary) {
            $component = CompanySalary::find($requestedSalary->mst_company_salary_id);
            if ($component) {
                $calculatedComponent = $this->calculateComponentAmount($component, $totalWorkHours, $presenceRecords, $requestedSalary->quantity, $requestedSalary->salary_snapshot);

                if ($component->is_tax) {
                    // Store tax components for later calculation
                    $taxComponents[] = [
                        'id' => $component->id,
                        'name' => $component->name,
                        'quantity' => $calculatedComponent->quantity,
                        'rate' => $calculatedComponent->totalSalary, // Tax rate in percentage
                        'base_amount' => $calculatedComponent->salary,
                        'trx_employee_requested_salary_id' => $requestedSalary->id,
                        'salary_name_snapshot' => $component->name,
                        'salary_rate_snapshot' => $requestedSalary->salary_snapshot ?? $component->salary,
                    ];
                } else {
                    // Add or subtract based on calculation type
                    if ($component->calculation_type === 'add') {
                        $totalSalary += $calculatedComponent->totalSalary;
                    } else {
                        $totalSalary -= $calculatedComponent->totalSalary;
                    }

                    $salaryComponents[] = [
                        'id' => $component->id,
                        'name' => $component->name,
                        'type' => $component->type,
                        'quantity' => $calculatedComponent->quantity,
                        'total_ammount' => $calculatedComponent->totalSalary,
                        'calculation_type' => $component->calculation_type,
                        'trx_employee_requested_salary_id' => $requestedSalary->id,
                        'salary_name_snapshot' => $component->name,
                        'salary_rate_snapshot' => $requestedSalary->salary_snapshot ?? $component->salary,
                    ];
                }
            }
        }

        // 5. Calculate tax after all salary components are summed
        foreach ($taxComponents as $taxComponent) {
            $taxAmount = ($totalSalary * $taxComponent['rate']) / 100;
            $totalTax += $taxAmount;

            $salaryComponents[] = [
                'id' => $taxComponent['id'],
                'name' => $taxComponent['name'],
                'type' => 'tax',
                'quantity' => $taxComponent['quantity'],
                'total_ammount' => $taxAmount,
                'percentage' => $taxComponent['rate'],
                'calculation_type' => 'subtract',
                'salary_name_snapshot' => $taxComponent['name'] ?? null,
                'salary_rate_snapshot' => $taxComponent['rate'] ?? null,
            ];
        }

        // 6. Apply tax deduction
        $salaryAfterTax = $totalSalary - $totalTax;

        return [
            'mst_user_id' => $user->id,
            'work_hours' => $totalWorkHours,
            'salary_components' => $salaryComponents,
            'total_salary' => $totalSalary,
            'total_tax' => $totalTax,
            'total_presence_record' => $presenceRecords->count(),
            'salary_after_tax' => $salaryAfterTax,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString()
        ];
    }

    /**
     * Calculate amount for a salary component based on its type
     *
     * @param CompanySalary $component
     * @param int $workHours
     * @param array $presenceRecords Optional presence records for presence-based calculation
     * @return CompanySalary
     */
    private function calculateComponentAmount($component, $workHours, $presenceRecords = null, $specificAmmount = null, $overrideRate = null)
    {
        $effectiveRate = $overrideRate ?? $component->salary;

        switch ($component->type) {
            case 'fixed':
                $component->quantity = $specificAmmount ?? 1;
                $component->totalSalary = $effectiveRate * $component->quantity;
                return $component;

            case 'hourly':
                $component->quantity = $workHours;
                $component->totalSalary = $effectiveRate * $workHours;
                return $component;

            case 'presence':
                // Count the number of presence records where both check_in and check_out are filled
                if ($presenceRecords) {
                    $presenceCount = 0;
                    foreach ($presenceRecords as $record) {
                        if ($record->time_in && $record->time_out) {
                            $presenceCount++;
                        }
                    }
                    $component->quantity = $presenceCount;
                    $component->totalSalary = $effectiveRate * $presenceCount;
                    return $component;
                }
                $component->quantity = 0;
                return $component;

            case 'tax':
                // For tax components, we return the base amount to be taxed
                $component->quantity = $specificAmmount ?? 1;
                $component->totalSalary = $effectiveRate * $component->quantity;
                return $component;

            default:
                $component->quantity = 0;
                $component->totalSalary = 0;
                return $component;
        }
    }

    #[Put('/update/json', '.json.update')]
    public function updatePayroll(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:trx_salary_receipt,id',
            'company_salary_item' => 'required|array',
            'company_salary_item.*.id' => 'required|exists:mst_company_salary,id',
            'start_date' => 'required',
            'end_date' => 'required']);

        $salaryReceipt = SalaryReceipt::query()->findOrFail($request->id);
        $user = $salaryReceipt->user;

        if ($user->trashed()) {
            return new JsonBody(null, message: 'Cannot edit receipt for deleted user', status_code: 403);
        }

        try {
            DB::transaction(function () use ($request, $salaryReceipt, $user) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);

                // Load existing items keyed by mst_company_salary_id
                $existingItems = SalaryReceiptItem::where('trx_salary_receipt_id', $salaryReceipt->id)
                    ->get()
                    ->keyBy('mst_company_salary_id');

                // Calculate presence data for hourly/presence types
                $presenceRecords = $user->presence()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $totalWorkHours = 0;
                $presenceCount = 0;
                foreach ($presenceRecords as $record) {
                    if ($record->time_in && $record->time_out) {
                        $checkIn = Carbon::parse($record->time_in);
                        $checkOut = Carbon::parse($record->time_out);
                        $hoursWorked = max(0, $checkOut->diffInHours($checkIn));
                        $totalWorkHours += $hoursWorked;
                        $presenceCount++;
                    }
                }

                $totalPresenceRecord = $presenceRecords->count();

                // First pass: process non-tax components
                $nonTaxTotal = 0;
                $processedIds = [];
                $taxComponents = [];

                foreach ($request->company_salary_item as $item) {
                    $componentId = $item['id'];
                    $dataComponent = CompanySalary::query()->find($componentId);
                    if (!$dataComponent) {
                        continue;
                    }
                    $processedIds[] = $componentId;
                    $match = $existingItems->get($componentId);

                    // Preserve snapshot & requested_salary_id for existing items
                    $rateSnapshot = $match ? $match->salary_rate_snapshot : $dataComponent->salary;
                    $nameSnapshot = $match ? $match->salary_name_snapshot : $dataComponent->name;
                    $requestedSalaryId = $match ? $match->trx_employee_requested_salary_id : null;

                    if ($dataComponent->is_tax) {
                        $quantity = $match ? $match->quantity : ($item['quantity'] ?? 1);
                        $taxComponents[] = compact('componentId', 'quantity', 'rateSnapshot', 'nameSnapshot', 'requestedSalaryId', 'dataComponent');
                        continue;
                    }

                    // Calculate quantity and total value based on type
                    switch ($dataComponent->type) {
                        case 'fixed':
                            $quantity = $item['quantity'] ?? ($match ? $match->quantity : 1);
                            $totalValue = $rateSnapshot * $quantity;
                            break;
                        case 'hourly':
                            $quantity = $totalWorkHours;
                            $totalValue = $rateSnapshot * $totalWorkHours;
                            break;
                        case 'presence':
                            $quantity = $presenceCount;
                            $totalValue = $rateSnapshot * $presenceCount;
                            break;
                        default:
                            $quantity = 1;
                            $totalValue = 0;
                    }

                    $nonTaxTotal += $dataComponent->calculation_type === 'subtract' ? -$totalValue : $totalValue;

                    if ($match) {
                        $match->update([
                            'quantity' => $quantity,
                            'total_value' => $totalValue,
                        ]);
                    } else {
                        SalaryReceiptItem::create([
                            'trx_salary_receipt_id' => $salaryReceipt->id,
                            'mst_company_salary_id' => $componentId,
                            'quantity' => $quantity,
                            'total_value' => $totalValue,
                            'trx_employee_requested_salary_id' => $requestedSalaryId,
                            'salary_name_snapshot' => $nameSnapshot,
                            'salary_rate_snapshot' => $rateSnapshot,
                        ]);
                    }
                }

                // Second pass: process tax components
                $totalTax = 0;
                foreach ($taxComponents as $tax) {
                    $taxAmount = $nonTaxTotal * $tax['rateSnapshot'] / 100;
                    $totalTax += $taxAmount;

                    if ($match = $existingItems->get($tax['componentId'])) {
                        $match->update([
                            'quantity' => $tax['quantity'],
                            'total_value' => $taxAmount,
                        ]);
                    } else {
                        SalaryReceiptItem::create([
                            'trx_salary_receipt_id' => $salaryReceipt->id,
                            'mst_company_salary_id' => $tax['componentId'],
                            'quantity' => $tax['quantity'],
                            'total_value' => $taxAmount,
                            'trx_employee_requested_salary_id' => $tax['requestedSalaryId'],
                            'salary_name_snapshot' => $tax['nameSnapshot'],
                            'salary_rate_snapshot' => $tax['rateSnapshot'],
                        ]);
                    }
                }

                // Delete items removed by admin (no longer in request)
                SalaryReceiptItem::where('trx_salary_receipt_id', $salaryReceipt->id)
                    ->whereNotIn('mst_company_salary_id', $processedIds)
                    ->delete();

                // Update main receipt
                $salaryReceipt->update([
                    'work_hour' => $totalWorkHours,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_salary' => $nonTaxTotal,
                    'salary_after_tax' => $nonTaxTotal - $totalTax,
                    'total_tax' => $totalTax,
                    'total_presence_record' => $totalPresenceRecord,
                ]);
            });
        } catch (\Exception $exception) {
            return new JsonBody(null, message: $exception->getMessage(), status_code: 500);
        }
        return new JsonBody(null, message: 'Company payroll updated successfully');
    }

    #[Delete('/delete/json', '.json.delete')]
    public function deletePayroll(Request $request)
    {
        $request->validate(['id' => 'required|numeric|exists:trx_salary_receipt,id']);

        SalaryReceipt::destroy($request->id);

        return new JsonBody(null, message: 'Company payroll deleted successfully');
    }

    #[Put('/confirm/json', '.json.confirm')]
    public function confirmPayroll(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:trx_salary_receipt,id',
            'status' => ['required', 'boolean'],
        ]);
        $salaryReceipt = SalaryReceipt::query()->findOrFail($request->id);

        if ($salaryReceipt->user->trashed()) {
            return new JsonBody(null, message: 'Cannot confirm receipt for deleted user', status_code: 403);
        }

        $salaryReceipt->update([
            'status' => $request->status ? 'approved' : 'denied',
        ]);

        if ($request->status) {
            $requestIds = SalaryReceiptItem::where('trx_salary_receipt_id', $salaryReceipt->id)
                ->whereNotNull('trx_employee_requested_salary_id')
                ->pluck('trx_employee_requested_salary_id');

            EmployeeRequestedSalary::whereIn('id', $requestIds)
                ->where('is_realized', false)
                ->update(['is_realized' => true]);
        }

        return new JsonBody(null, message: 'Company payroll ' . ($request->status ? 'approved' : 'rejected') . ' successfully');
    }

    #[Get('/export/excel', '.export.excel')]
    public function exportExcel(Request $request)
    {
        $filters = [
            'employee_id' => $request->employee_id,
            'search' => $request->search,
            'date_start' => $request->date_start,
            'date_end' => $request->date_end,
            'status' => $request->status,
        ];

        $fileName = 'payroll-receipts-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new PayrollReceiptExport($filters), $fileName);
    }

    #[Get('/{payroll}/export/detail-excel', '.export.detail.excel')]
    public function exportDetailExcel(int $payroll)
    {
        $fileName = 'payroll-receipt-detail-' . $payroll . '-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new ReceiptDetailExport($payroll), $fileName);
    }

    #[Get('/{payroll}/export/detail-pdf', '.export.detail.pdf')]
    public function exportDetailPdf(int $payroll)
    {
        $receipt = SalaryReceipt::query()
            ->with(['user.userDetail', 'companySalaryItem'])
            ->findOrFail($payroll);

        $receipt->total_subtract = $receipt->companySalaryItem
            ->where('calculation_type', 'subtract')
            ->where('is_tax', false)
            ->sum('salary');

        $receiptItemIds = SalaryReceiptItem::query()
            ->where('trx_salary_receipt_id', $payroll)
            ->whereNotNull('trx_employee_requested_salary_id')
            ->pluck('trx_employee_requested_salary_id', 'mst_company_salary_id');

        $requestedSalaries = EmployeeRequestedSalary::query()
            ->with('approvedBy.userDetail')
            ->whereIn('id', $receiptItemIds->values())
            ->get()
            ->keyBy('id');

        $receipt->companySalaryItem->each(function ($item) use ($receiptItemIds, $requestedSalaries) {
            $reqId = $receiptItemIds->get($item->id);
            $item->is_requested = !is_null($reqId);
            $item->request_info = $reqId ? $requestedSalaries->get($reqId) : null;
        });

        $company = CompanyProfile::query()->first();

        $html = view('pdf.receipt-detail', compact('receipt','company'))->render();

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('receipt-detail-' . $payroll . '-' . now()->format('Ymd-His') . '.pdf', 'I');
    }
}
