<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\CompanyProfile;
use App\Models\UserManagement\CompanyGroup;
use App\Models\UserManagement\User;
use App\Models\UserManagement\UserDetail;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

#[Prefix('Employee/Account'), Name('employee-account'), Middleware('auth')]
class EmployeeAccountController extends Controller
{
    #[Get('/', '.index')]
    public function index()
    {
        return Inertia::render('Admin/EmployeeAccount/EmployeeAccount');
    }

    #[Get('/create', '.create')]
    public function createPage()
    {
        $userGroup = CompanyGroup::query()->get();

        return Inertia::render('Admin/EmployeeAccount/EmployeeAccountDetail',[
            'companyGroup' => $userGroup,
            'companyProfile' => CompanyProfile::first(),
        ]);
    }

    #[Get('/{id}', '.detail')]
    public function detail(int $id)
    {
        $data = User::with('groups')->find($id);

        $userGroup = CompanyGroup::query()->get();

        if ($data == null) {
            return new JsonBody(null, 'User not found', 404);
        }
        if ($data->type == 'superadmin' && Auth::user()->type != 'superadmin'){
             abort(403, 'Unauthorized action');
        }

        if ($data->isEmployee()) {
            $data->load('userDetail');
        }

        return Inertia::render('Admin/EmployeeAccount/EmployeeAccountDetail', [
            'account' => $data,
            'companyGroup' => $userGroup,
            'companyProfile' => CompanyProfile::first(),
        ]);
    }

    #[Get('/json/{user}', '.json.detail',['scope-company'])]
    public function detailJson(int $id)
    {
        $data = User::query()->find($id);

        if ($data == null) {
            return new JsonBody(null, 'User not found', 404);
        }

        if ($data->type == 'superadmin' && Auth::user()->type != 'superadmin'){
            return new JsonBody(null, 'Unauthorized', 401);
        }

        if ($data->isEmployee()) {
            $data->load(['userDetail','groups']);
        } else {
            $data->load('groups');
        }

        return new JsonBody($data, 'User found');
    }

    #[Get('/all/json', '.json.all')]
    public function getAllAccount(request $request)
    {
        $query = User::query()->with('userDetail')->where('type', 'employee');

        $data = $query
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('email', 'like', "%{$request->search}%")
                        ->orWhereHas('userDetail', function ($query) use ($request) {
                            $query->where('fullname', 'like', "%{$request->search}%")
                                ->orWhere('phone', 'like', "%{$request->search}%")
                                ->orWhere('address', 'like', "%{$request->search}%");
                        });
                });
            })
            ->paginate(10)
            ->withQueryString();

        return new JsonBody($data);
    }

    #[Get('/all/by-group/json', '.json.group')]
    public function getAccountByGroup(request $request)
    {
        $request->validate(['group_id' => 'required|numeric|exists:mst_company_groups,id']);

        $data = CompanyGroup::query()->with(['employee','employee.userDetail'])
//            ->where('mst_user_id', Auth::id())
            ->where('id',$request->group_id)
            ->first();

        return new JsonBody($data->employee);
    }

    #[Post('/add/json', '.json.add')]
    public function addAccount(Request $request)
    {
        $isAdmin = $request->boolean('is_admin');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'phone' => [
                'required', 'min:10',
                $isAdmin
                    ? Rule::unique('mst_company_profile', 'company_phone')
                    : Rule::unique('mst_user_details', 'phone')->whereNull('deleted_at'),
            ],
            'address' => 'required|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'is_admin' => 'nullable|boolean',
            'company_group.*' => 'required|exists:'.CompanyGroup::class.',id',
            'profile_picture' => ['sometimes', File::image()->max(15 * 1024)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::beginTransaction();
        try {
            $user = User::query()->create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_suspended' => $request->is_suspended,
                'type' => $isAdmin ? 'company' : 'employee',
            ]);

            if ($isAdmin) {
                // Single company — no new CompanyProfile created for each admin
            } else {
                $employeeDetail = UserDetail::query()->create([
                    'fullname' => $request->name,
                    'address' => $request->address,
                    'phone' => $request->phone,
                ]);

                if ($request->has('profile_picture')) {
                    $employeeDetail->update(['picture_profile' => $request->get('profile_picture')]);
                }

                $user->update(['mst_user_detail_id' => $employeeDetail->id]);
                $user->groups()->sync($request->company_group);
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }

        event(new Registered($user));

        return new JsonBody($user, message: 'Account added successfully');
    }

    #[Post('/update/json', '.json.update')]
    public function updateAccount(Request $request)
    {
        $request->validate(['name' => 'required|string',
            'id' => 'required|numeric|exists:mst_users,id',
            'profile_picture' => ['sometimes', File::image()->max(15 * 1024)]
        ]);

        $user = User::query()->find($request->id);

        if (!$user) {
            return new JsonBody(null, 'User not found', 404);
        }

        DB::beginTransaction();
        try {
            $user->update([
                'email' => $request->email,
                'is_suspended' => $request->is_suspended,
            ]);

            if ($request->password != null) {
                $user->update(['password' => Hash::make($request->password)]);
            }

            if ($user->isEmployee()) {
                $user->userDetail()->update([
                    'fullname' => $request->name,
                    'address' => $request->address,
                    'phone' => $request->phone,
                ]);

                if ($request->hasFile('profile_picture')) {
                    $path = $request->file('profile_picture')->store('profile_pictures', 'public');
                    $user->userDetail()->update(['picture_profile' => $path]);
                }

                $user->groups()->sync($request->company_group);
            } else {
                $profile = CompanyProfile::first();
                if ($profile) {
                    $profile->update([
                        'company_name' => $request->name,
                        'company_address' => $request->address,
                        'company_phone' => $request->phone,
                        'npwp' => $request->npwp,
                    ]);

                    if ($request->hasFile('profile_picture')) {
                        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
                        $profile->update(['company_logo' => $path]);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }
        return new JsonBody(null, message: "Account updated successfully");
    }

    #[Delete('/delete/json', '.json.delete')]
    public function deleteAccount(Request $request)
    {
        $request->validate(['data_id' => 'required|numeric|exists:mst_users,id']);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($request->data_id);

            $user->tokens()->delete();
            $user->userDetail()?->delete();
            $user->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonBody(null, message: 'Failed to delete account: ' . $e->getMessage(), status_code: 500);
        }

        return new JsonBody(null, message: 'Account deleted successfully');
    }
}
