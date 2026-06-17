<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\Payroll\CompanySalary;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

#[Prefix('Company/{user}'), Name('company-payroll'), Middleware(['only-company', 'scope-company'])]
class CompanySalaryController extends Controller
{
    #[Get('/payroll', '.index')]
    public function index(User $company)
    {
        return Inertia::render('Admin/PayrollManagement/PayrollManagement');
    }

    #[Get('/all/json', '.json.all',)]
    public function getAllPayroll(request $request)
    {
        $data = CompanySalary::query()
//            ->where('user_id', Auth::id())
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%");
            })
            ->paginate(10)
            ->withQueryString();
        array_map(function ($item) {
            $item['assigned_to'] = $item->employeeSalary()->with(['userDetail'])->get();
            return $item;
        }, $data->items());
        return new JsonBody($data);
    }


    #[Post('/add/json', '.json.add')]
    public function addPayroll(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'salary' => 'required|numeric',
            'is_tax' => 'nullable|boolean',
            'type' => ['nullable', Rule::in(['fixed', 'hourly', 'presence', 'tax'])],
            'calculation_type' => ['nullable', Rule::in(['add', 'subtract'])]]);

        try {
            DB::transaction(function () use ($request) {
                $companySalary = CompanySalary::query()->create([
//                    'user_id' => Auth::id(),
                    'name' => $request->name,
                    'salary' => $request->salary,
                    'is_tax' => $request->is_tax,
                    'calculation_type' => $request->is_tax ? 'subtract' : $request->calculation_type,
                    'type' => $request->type,
                ]);

                if ($request->has('assigned_to')) {
                    $syncData = [];
                    foreach ($request->assigned_to as $item) {
                        $syncData[$item['id']] = [
                            'available_to_request' => $item['pivot']['available_to_request'] ?? false,
                            'included_at_default' => true,
                        ];
                    }
                    // Attach semua sekaligus
                    $companySalary->employeeSalary()->sync($syncData);
                }
            });
            return new JsonBody(null, message: 'Payroll company added successfully');
        } catch (\Exception $exception) {
            return new JsonBody(null, message: $exception->getMessage(), status_code: 500);
        }
    }

    #[Put('/update/json', '.json.update')]
    public function updatePayroll(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:mst_company_salary,id',
            'name' => 'required|string',
            'salary' => 'required|numeric',
            'is_tax' => 'nullable|boolean',
            'type' => ['nullable', Rule::in(['fixed', 'hourly', 'presence', 'tax'])],
            'calculation_type' => ['nullable', Rule::in(['add', 'subtract'])]]);
        $companySalary = CompanySalary::query()->findOrFail($request->id);
        try {
            DB::transaction(function () use ($request,$companySalary) {
                $companySalary->update([
//                    'user_id' => Auth::id(),
                    'name' => $request->name,
                    'salary' => $request->salary,
                    'is_tax' => $request->is_tax,
                    'calculation_type' => $request->calculation_type,
                    'type' => $request->type,
                ]);

                if ($request->has('assigned_to')) {
                    $syncData = [];

                    foreach ($request->assigned_to as $item) {
                        $syncData[$item['id']] = [
                            'available_to_request' => $item['pivot']['available_to_request'] ?? false,
                            'included_at_default' => true,
                        ];
                    }
                    $companySalary->employeeSalary()->sync($syncData);
                }
            });
        } catch (\Exception $exception) {
            return new JsonBody(null, message: $exception->getMessage(), status_code: 500);
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
