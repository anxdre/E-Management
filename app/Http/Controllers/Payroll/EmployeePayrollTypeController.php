<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\Payroll\CompanySalary;
use App\Models\Payroll\EmployeeRequestedSalary;
use App\Models\Payroll\SalaryReceipt;
use App\Models\Payroll\SalaryReceiptItem;
use App\Models\UserManagement\User;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Dentro\Yalr\Attributes\Put;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

#[Prefix('{user}/Employee'), Name('employee-payroll'), Middleware(['scope-company'])]
class EmployeePayrollTypeController extends Controller
{
    #[Get('/payroll', '.index')]
    public function index(User $company)
    {
        return Inertia::render('Admin/PayrollManagement/EmployeePayroll');
    }

    #[Get('/payroll/{payroll}/detail', '.detail')]
    public function detailReceipt(User $user, int $payroll)
    {
        return Inertia::render('Admin/ReceiptDetail/ReceiptDetail', ['id' => $payroll]);
    }

    #[Get('/payroll/{payroll}/detail/json', '.json.detail')]
    public function detailReceiptJson(User $user,int $payroll, Request $request)
    {
        if ($payroll == null) {
            return new JsonBody(null, 'Data not found', 404);
        }

        $data = SalaryReceipt::query()
            ->with(['user.userDetail', 'companySalaryItem'])
            ->find($payroll);

        $data->company_profile = \App\Models\CompanyProfile::query()->first();

        $data->total_subtract = $data->companySalaryItem
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

        $data->companySalaryItem->each(function ($item) use ($receiptItemIds, $requestedSalaries) {
            $reqId = $receiptItemIds->get($item->id);
            $item->is_requested = !is_null($reqId);
            $item->request_info = $reqId ? $requestedSalaries->get($reqId) : null;
        });

        return new JsonBody($data);
    }

    #[Get('/all/json', '.json.all',)]
    public function getAllPayroll(Request $request)
    {
        $data = CompanySalary::query()
//            ->where('mst_user_id', Auth::id())
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%");
            })
            ->paginate(10)
            ->withQueryString();
        return new JsonBody($data);
    }

    #[Post('/add/json', '.json.add')]
    public function requestPayroll(User $user, Request $request)
    {
        $request->validate(['company_salary_id' => 'required|exists:mst_company_salary,id',
            'salary' => 'required|numeric',
            'is_task' => 'nullable|boolean',
            'type' => ['nullable', Rule::in(['fixed', 'hourly', 'presence', 'tax'])]]);

        $companySalary = CompanySalary::query()->create([
//            'mst_user_id' => Auth::id(),
            'name' => $request->name,
            'salary' => $request->salary,
            'is_task' => $request->is_tax,
            'type' => $request->type,
        ]);

        if ($request->has('assigned_to')) {
            $syncData = [];
            foreach ($request->assigned_to as $item) {
                $syncData[$item['user_id']] = [
                    'type' => $item['pivot_type'],
                    'available_to_request' => $item['available_to_request'],
                    'included_at_default' => $item['included_at_default'],
                ];
            }
            // Attach semua sekaligus
            $companySalary->employeeSalary()->sync($syncData);
        }

        return new JsonBody(null, message: 'Payroll company added successfully');
    }

    #[Put('/update/json', '.json.update')]
    public function updatePayroll(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:mst_company_salary,id',
            'name' => 'required|string',
            'salary' => 'required|numeric',
            'is_task' => 'nullable|boolean',
            'type' => ['nullable', Rule::in(['fixed', 'hourly', 'presence', 'tax'])],
        ]);
        $companySalary = CompanySalary::query()->findOrFail($request->id);
        $companySalary->update([
//            'mst_user_id' => Auth::id(),
            'name' => $request->name,
            'salary' => $request->salary,
            'is_task' => $request->is_task,
            'type' => $request->type,
        ]);

        if ($request->has('assigned_to')) {
            $syncData = [];

            foreach ($request->assigned_to as $item) {
                $syncData[$item['user_id']] = [
                    'type' => $item['pivot_type'],
                    'available_to_request' => $item['available_to_request'],
                    'included_at_default' => $item['included_at_default'],
                ];
            }
            $companySalary->employeeSalary()->sync($syncData);
        }

        return new JsonBody(null, message: 'Company payroll updated successfully');
    }

    #[Delete('/delete/json', '.json.delete')]
    public function deletePayroll(Request $request)
    {
        $request->validate(['id' => 'required|numeric|exists:mst_company_salary,id']);

        CompanySalary::destroy($request->id);

        return new JsonBody(null, message: 'Company payroll deleted successfully');
    }
}
