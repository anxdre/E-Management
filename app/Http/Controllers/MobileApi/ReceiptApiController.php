<?php

namespace App\Http\Controllers\MobileApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\Payroll\SalaryReceipt;
use App\Models\UserManagement\User;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

#[Prefix('Receipt'), Name('api-receipt'), Middleware('auth:sanctum')]
class ReceiptApiController extends Controller
{
    #[Get('/{id}', '.all',)]
    public function getAllPayrollByUser(request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $user = User::query()->where('id', $request->user)->firstOrFail();

        $data = SalaryReceipt::query()
            ->with(['user', 'user.userDetail', 'user.groups', 'salaryReceiptItems'])
            ->where('user_id', $user->id)
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
}
