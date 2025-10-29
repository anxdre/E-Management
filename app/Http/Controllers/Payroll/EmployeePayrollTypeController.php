<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\Payroll\CompanySalary;
use App\Models\Payroll\SalaryReceipt;
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

        $data = SalaryReceipt::query()->with(['user.userDetail','user.company.userDetail', 'companySalaryItem'])->find($payroll);
        return new JsonBody($data);
    }

    #[Get('/all/json', '.json.all',)]
    public function getAllPayroll(Request $request)
    {
        $data = CompanySalary::query()
            ->where('user_id', Auth::id())
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
        $request->validate(['company_salary_id' => 'required|exists:company_salary,id',
            'salary' => 'required|numeric',
            'is_task' => 'nullable|boolean',
            'type' => ['nullable', Rule::in(['fixed', 'hourly', 'presence', 'tax'])]]);

        $companySalary = CompanySalary::query()->create([
            'user_id' => Auth::id(),
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
            'id' => 'required|exists:company_salary,id',
            'name' => 'required|string',
            'salary' => 'required|numeric',
            'is_task' => 'nullable|boolean',
            'type' => ['nullable', Rule::in(['fixed', 'hourly', 'presence', 'tax'])],
        ]);
        $companySalary = CompanySalary::query()->findOrFail($request->id);
        $companySalary->update([
            'user_id' => Auth::id(),
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
        $request->validate(['id' => 'required|numeric|exists:company_salary,id']);

        CompanySalary::destroy($request->id);

        return new JsonBody(null, message: 'Company payroll deleted successfully');
    }


    //user receipt user
    #[Get('/{employee}/payroll', '.user.index')]
    public function indexByUser(int $companyId, int $userId)
    {
        $user = User::query()->findOrFail($userId);
        return Inertia::render('Employee/EmployeeSalary/EmployeePayrollReceipt',['user' => $user]);
    }

}
