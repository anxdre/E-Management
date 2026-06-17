<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\JsonBody;
use App\MobileDecryptor;
use App\Models\PersonalAccessToken;
use App\Models\UserManagement\User;
use App\Models\UserManagement\UserDetail;
use Carbon\Carbon;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

#[Prefix('Management/{user}/device-management'), Name('device-management')]
class DeviceManagementController extends Controller
{
    use MobileDecryptor;

    #[Delete('/delete-device/{id}/json', name: '.json.delete-device', middleware: ['only-company', 'scope-company'])]
    public function forceLogoutDevice($companyId, $id, Request $request)
    {
        $employee = User::findOrFail($id);

        if (!$employee) {
            return new JsonBody(null, message: 'Employee not found.', status_code: 404);
        }

        if (!$employee->isEmployee()) {
            return new JsonBody($employee, message: 'User is not an employee.', status_code: 403);
        }

        $query = $employee->tokens();

        // kalau admin ingin logout semua device:
        if (!$request->device_token) {
            $query->delete();
        } else {
            // logout satu device aja
            $query->where('device_token', $request->device_token)->delete();
        }

        return new JsonBody(null, message: 'Device logged out successfully.');
    }

    #[Get('/all/json', name: '.json.all', middleware: ['only-company', 'scope-company'])]
    public function getDeviceEmployee(User $user, Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $employee = User::query()
            ->where('mst_company_id', $user->id)
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('email', 'like', "%{$request->search}%")
                        ->orWhereRelation('userDetail', 'fullname', 'like', "%{$request->search}%");
                });
            })
            ->whereHas('tokens')
            ->with('tokens')
            ->paginate(10)
            ->withQueryString();

        return new JsonBody($employee);
    }

    #[Get('/', name: '.index', middleware: ['only-company', 'scope-company'])]
    public function manageDevice(User $user, Request $request)
    {
        return Inertia::render('Admin/DeviceManagement/DeviceManagement');
    }
}
