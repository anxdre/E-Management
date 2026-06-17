<?php

namespace App\Http\Controllers\MobileApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\Payroll\SalaryReceipt;
use App\Models\UserManagement\User;
use Carbon\Carbon;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

#[Prefix('Receipt/api'), Name('api-receipt'), Middleware('auth:sanctum')]
class ReceiptApiController extends Controller
{
    #[Get('/{id}', '.all',)]
    public function getAllPayrollByUser($id, request $request)
    {
        $request->validate([
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $user = User::query()->where('id', $id)->firstOrFail();

        $data = SalaryReceipt::query()
            ->with(['user', 'user.userDetail', 'user.groups', 'salaryReceiptItems'])
            ->where('mst_user_id', $user->id)
            ->when(!empty($request->date_start) && !empty($request->date_end), function ($query) use ($request) {
                $startDate = Carbon::parse($request->date_start)->startOfDay();
                $endDate = Carbon::parse($request->date_end)->endOfDay();
                $query->whereBetween('start_date', [$startDate, $endDate]);
            })
            ->paginate(10)
            ->withQueryString();

        return new JsonBody($data);
    }

     #[Get('/{payroll}/detail', '.json.detail')]
    public function detailReceiptJson(int $payroll, Request $request)
    {
        if ($payroll == null) {
            return new JsonBody(null, 'Data not found', 404);
        }

        $data = SalaryReceipt::query()->with(['user.userDetail','user.company.userDetail', 'companySalaryItem'])->findOrFail($payroll);
        return new JsonBody($data);
    }
}
