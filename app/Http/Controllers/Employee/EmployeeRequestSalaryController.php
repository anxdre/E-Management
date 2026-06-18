<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\Payroll\EmployeeRequestedSalary;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Dentro\Yalr\Attributes\Put;
use Illuminate\Http\Request;
use Inertia\Inertia;

#[Prefix('Employee/Request-Salary'), Name('employee-request-salary'), Middleware('auth')]
class EmployeeRequestSalaryController extends Controller
{
    #[Get('/', '.index')]
    public function index()
    {
        return Inertia::render('Admin/EmployeeRequestSalary/EmployeeRequestSalary');
    }

    #[Get('/all/json', '.json.all')]
    public function getAll(Request $request)
    {
        $data = EmployeeRequestedSalary::query()
            ->with(['user.userDetail', 'component', 'approvedBy.userDetail'])
            ->when($request->search, fn($q, $v) => $q->whereHas('user.userDetail', fn($q) => $q->where('fullname', 'like', "%$v%")))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->date_start, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_end, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return new JsonBody($data);
    }

    #[Put('/approve-reject/json', '.json.approve-reject')]
    public function approveReject(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:trx_employee_requested_salary,id',
            'status' => 'required|in:approved,rejected',
        ]);

        $requestedSalary = EmployeeRequestedSalary::query()->findOrFail($request->id);

        $requestedSalary->update([
            'status' => $request->status,
            'mst_approved_by' => auth()->id(),
            'approved_date' => $request->status === 'approved' ? now() : null,
        ]);

        return new JsonBody($requestedSalary, message: 'Request ' . $request->status . ' successfully');
    }
}
