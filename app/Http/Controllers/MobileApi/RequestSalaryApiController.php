<?php

namespace App\Http\Controllers\MobileApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\Payroll\CompanySalary;
use App\Models\Payroll\EmployeeRequestedSalary;
use App\Models\UserManagement\User;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;

#[Prefix('Request-Salary'), Name('api-request-salary'), Middleware('auth:sanctum')]
class RequestSalaryApiController extends Controller
{
    #[Get('{user}/api/available-components', '.api.available-components')]
    public function getAvailableComponents(User $user)
    {
        $components = CompanySalary::query()
            ->whereHas('employeeSalary', fn($q) => $q->where('mst_user_id', $user->id)->where('available_to_request', true))
            ->whereNotIn('type', ['hourly', 'presence'])
            ->get();

        return new JsonBody($components);
    }

    #[Post('api/create/', '.api.create')]
    public function create(Request $request)
    {
        $request->validate([
            'mst_user_id' => 'required|exists:mst_users,id',
            'mst_company_salary_id' => 'required|exists:mst_company_salary,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $existing = EmployeeRequestedSalary::query()
            ->where('mst_user_id', $request->mst_user_id)
            ->where('mst_company_salary_id', $request->mst_company_salary_id)
            ->where('status', 'pending')
            ->where('is_realized', false)
            ->first();

        if ($existing) {
            return new JsonBody(null, message: 'You already have a pending request for this component', status_code: 409);
        }

        $component = CompanySalary::find($request->mst_company_salary_id);

        $requestedSalary = EmployeeRequestedSalary::create([
            'mst_user_id' => $request->mst_user_id,
            'mst_company_salary_id' => $request->mst_company_salary_id,
            'quantity' => $request->quantity,
            'quantity_snapshot' => $request->quantity,
            'salary_snapshot' => $component?->salary ?? 0,
            'status' => 'pending',
            'is_realized' => false,
        ]);

        return new JsonBody($requestedSalary, message: 'Request submitted successfully');
    }

    #[Get('{user}/api/all', '.api.all')]
    public function getAll(User $user, Request $request)
    {
        $data = EmployeeRequestedSalary::query()
            ->with('component')
            ->where('mst_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10, page: $request->currentPage ?? 1)
            ->withQueryString();

        return new JsonBody($data);
    }
}
