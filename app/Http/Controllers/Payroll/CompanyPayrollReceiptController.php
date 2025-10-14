<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
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

#[Prefix('Company/{user}/receipt'), Name('company-receipt'), Middleware(['only-company', 'scope-company'])]
class CompanyPayrollReceiptController extends Controller
{
    #[Get('/', '.index')]
    public function index(User $company)
    {
        return Inertia::render('Admin/PayrollManagement/PayrollReceipt');
    }

    #[Get('/all/json', '.json.all',)]
    public function getAllPayroll(request $request)
    {
        $listOfEmployee = new Collection();
        if ($request->filled('user')) {
            $listOfEmployee->push(User::query()->where('id', $request->user)->firstOrFail());
        }

        if (!$request->filled('user')) {
            $listOfEmployee = User::query()->where('company_id', Auth::id())->get();
        }

        $data = SalaryReceipt::query()
            ->with(['user', 'user.userDetail', 'user.groups', 'salaryReceiptItems'])
            ->whereIn('user_id', $listOfEmployee->pluck('id'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('email', 'like', "%{$request->search}%");
                })->orWhereHas('user.userDetail', function ($q) use ($request) {
                    $q->where('fullname', 'like', "%{$request->search}%");
                });
            })
            ->when($request->has('date_filter'), function ($query) use ($request) {
                $startDate = $request->date_filter['start'];
                $endDate = $request->date_filter['end'];
                $query->whereBetween('start_date', [$startDate, $endDate]);
            })
            ->paginate(10)
            ->withQueryString();

        return new JsonBody($data);
    }

    #[Post('/add/bulk/json', '.json.add.bulk')]
    public function addBulkPayroll(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'user_id.*' => 'required|exists:users,id',
            'date_start' => 'required',
            'date_end' => 'required',]);

        try {
            $calculatedData = [];
            foreach ($request->user_id as $user) {
                $user = User::query()->find($user);
                if (!$user) {
                    continue;
                }
                $calculatedData[] = $this->calculateEmployeeSalary($user, $request->date_start, $request->date_end);
            }
            $calculatedData = collect($calculatedData);
            DB::beginTransaction();
            $calculatedData->each(function ($item) use ($request) {
                $receipt = SalaryReceipt::query()->create([
                    'user_id' => $item['user_id'],
                    'start_date' => $request->date_start,
                    'end_date' => $request->date_end,
                    'total_salary' => $item['total_salary'],
                    'salary_after_tax' => $item['salary_after_tax'],
                    'total_tax' => $item['total_tax'],
                    'work_hour' => $item['work_hours'],
                    'total_presence_record' => $item['total_presence_record'],
                ]);
                $item['salary_components'] = collect($item['salary_components']);
                $item['salary_components']->each(function ($component) use ($receipt) {
                    SalaryReceiptItem::query()->create([
                        'salary_receipt_id' => $receipt->id,
                        'company_salary_id' => $component['id'],
                        'quantity' => $component['quantity'],
                        'total_value' => $component['total_ammount'],
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

    #[Put('/update/json', '.json.update')]
    public function updatePayroll(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:salary_receipt,id',
            'company_salary_item' => 'required|array',
            'company_salary_item.*.id' => 'required|exists:company_salary,id',
            'start_date' => 'required',
            'end_date' => 'required']);

        $salaryReceipt = SalaryReceipt::query()->findOrFail($request->id);
        $user = $salaryReceipt->user;
        try {
            DB::transaction(function () use ($request, $salaryReceipt, $user) {
                // Calculate updated salary data
                $calculatedData = $this->calculateEmployeeSalary($user, $request->start_date, $request->end_date,$request->company_salary_item);

                // Update main receipt
                $salaryReceipt->update([
                    'work_hour' => $calculatedData['work_hours'],
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'total_salary' => $calculatedData['total_salary'],
                    'salary_after_tax' => $calculatedData['salary_after_tax'],
                    'total_tax' => $calculatedData['total_tax'],
                    'total_presence_record' => $calculatedData['total_presence_record'],
                ]);

                // Delete existing receipt items
                SalaryReceiptItem::where('salary_receipt_id', $salaryReceipt->id)->delete();

                // Create new receipt items based on calculated components
                collect($calculatedData['salary_components'])->each(function ($component) use ($salaryReceipt) {
                    SalaryReceiptItem::create([
                        'salary_receipt_id' => $salaryReceipt->id,
                        'company_salary_id' => $component['id'],
                        'quantity' => $component['quantity'],
                        'total_value' => $component['total_ammount'],
                    ]);
                });
            });
        } catch (\Exception $exception) {
            return new JsonBody(null, message: $exception->getMessage(), status_code: 500);
        }
        return new JsonBody(null, message: 'Company payroll updated successfully');
    }

    #[Delete('/delete/json', '.json.delete')]
    public function deletePayroll(Request $request)
    {
        $request->validate(['id' => 'required|numeric|exists:salary_receipt,id']);

        SalaryReceipt::destroy($request->id);

        return new JsonBody(null, message: 'Company payroll deleted successfully');
    }

    #[Put('/confirm/json', '.json.confirm')]
    public function confirmPayroll(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:salary_receipt,id',
            'status' => ['required', 'boolean'],
        ]);
        $companySalary = SalaryReceipt::query()->findOrFail($request->id);
        $companySalary->update([
            'status' => $request->status ? 'approved' : 'denied',
        ]);

        return new JsonBody(null, message: 'Company payroll '. $request->status ? 'approved' : 'rejected'. ' successfully');
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
        $specificAmmount = 0;
        foreach ($presenceRecords as $record) {
            // Calculate hours between check-in and check-out times
            if ($record->check_in && $record->check_out) {
                $checkIn = Carbon::parse($record->check_in);
                $checkOut = Carbon::parse($record->check_out);
                $hoursWorked = $checkOut->diffInHours($checkIn);
                $totalWorkHours += $hoursWorked;
            }
        }

        if (!$specificComponent) {
            // 2. Get employee's default salary components (included by default)
            $defaultSalaryComponents = $user->defaultSalaries;

            // 3. Get requested salary components that were accepted
            $requestedSalaryComponents = EmployeeRequestedSalary::query()
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'accepted')
                ->get();
        }

        if ($specificComponent) {
            $defaultSalaryComponents = CompanySalary::query()
                ->whereIn('id', array_map(fn ($item) => $item['id'], $specificComponent))
                ->get();

            $requestedSalaryComponents = [];
        }

        // 4. Calculate total salary
        $salaryComponents = [];
        $taxComponents = [];
        $totalSalary = 0;
        $totalTax = 0;

        // Process default salary components
        foreach ($defaultSalaryComponents as $component) {
            $calculatedComponent = $this->calculateComponentAmount($component, $totalWorkHours, $presenceRecords);

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
                    'calculation_type' => $component->calculation_type
                ];
            }
        }

        // Process requested salary components
        foreach ($requestedSalaryComponents as $request) {
            $component = CompanySalary::find($request->company_salary_id);
            if ($component) {

                if($specificAmmount){
                    $calculatedComponent = $this->calculateComponentAmount($component, $specificAmmount, $presenceRecords);
                }
                $calculatedComponent = $this->calculateComponentAmount($component, $totalWorkHours, $presenceRecords);

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
                        'calculation_type' => $component->calculation_type
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
                'calculation_type' => 'subtract'
            ];
        }

        // 6. Apply tax deduction
        $salaryAfterTax = $totalSalary - $totalTax;

        return [
            'user_id' => $user->id,
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
    private function calculateComponentAmount($component, $workHours, $presenceRecords = null)
    {
        switch ($component->type) {
            case 'fixed':
                $component->quantity = 1;
                $component->totalSalary = $component->salary;
                return $component;

            case 'hourly':
                $component->quantity = $workHours;
                $component->totalSalary = $component->salary * $workHours;
                return $component;

            case 'presence':
                // Count the number of presence records where both check_in and check_out are filled
                if ($presenceRecords) {
                    $presenceCount = 0;
                    foreach ($presenceRecords as $record) {
                        if ($record->check_in && $record->check_out) {
                            $presenceCount++;
                        }
                    }
                    $component->quantity = $presenceCount;
                    $component->totalSalary = $component->salary * $presenceCount;
                    return $component;
                }
                $component->quantity = 0;
                return $component;

            case 'tax':
                // For tax components, we return the base amount to be taxed
                $component->quantity = 1;
                $component->totalSalary = $component->salary;
                return $component;

            default:
                $component->quantity = 0;
                $component->totalSalary = 0;
                return $component;
        }
    }
}
