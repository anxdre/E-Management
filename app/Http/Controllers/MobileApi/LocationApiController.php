<?php

namespace App\Http\Controllers\MobileApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\Payroll\SalaryReceipt;
use App\Models\PresenceManagement\PresenceLocation;
use App\Models\UserManagement\User;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

#[Prefix('Location/api'), Name('api-location'), Middleware('auth:sanctum')]
class LocationApiController extends Controller
{
    #[Get('/all/json', '.json.all')]
    public function getAllLocation(request $request)
    {
        $user = Auth::user();

        $companyId = $user->isEmployee()
            ? $user->groups()->first()?->mst_user_id
            : $user->id;

        $data = PresenceLocation::query()
            ->where('mst_user_id', $companyId)
            ->with('singleVerification')
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%");
            })
            ->get();

        return new JsonBody($data);
    }
}
