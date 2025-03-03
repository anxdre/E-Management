<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
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
        $userGroup = CompanyGroup::query()->where('user_id', Auth::id())->get();
        return Inertia::render('Admin/EmployeeAccount/EmployeeAccountDetail',['companyGroup' => $userGroup]);
    }

    #[Get('/{id}', '.detail')]
    public function detail(int $id)
    {
        $data = User::query()
            ->where('company_id', Auth::id())
            ->where('type', 'employee')
            ->with(['userDetail','groups'])
            ->find($id);
        $userGroup = CompanyGroup::query()->where('user_id', Auth::id())->get();
        if ($data == null) {
            return new JsonBody(null, 'User not found', 404);
        }
        return Inertia::render('Admin/EmployeeAccount/EmployeeAccountDetail', ['account' => $data, 'companyGroup' => $userGroup]);
    }

    #[Get('/all/json', '.json.all')]
    public function getAllAccount(request $request)
    {
        $data = User::query()
            ->with('userDetail')
            ->where('type', 'employee')
            ->where('company_id', Auth::id())
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

    #[Post('/add/json', '.json.add')]
    public function addAccount(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'phone' => 'required|min:10|unique:' . UserDetail::class,
            'address' => 'required|string|max:255',
            'profile_picture' => ['sometimes', File::image()->max(15 * 1024)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::beginTransaction();
        try {
            $companyDetail = UserDetail::query()->create([
                'fullname' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            if ($request->has('profile_picture')) {
                $companyDetail->update(['picture_profile' => $request->get('profile_picture')]);
            }

            $user = User::query()->create(['name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_detail_id' => $companyDetail->id,
                'is_suspended' => $request->is_suspended,
                'company_id' => Auth::id(),
                'type' => 'employee']);
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
            'id' => 'required|numeric|exists:users,id',
            'profile_picture' => ['sometimes', File::image()->max(15 * 1024)]
        ]);

        $user = User::query()->find($request->id);

        DB::beginTransaction();
        try {
            $user->update(['email' => $request->email,
                'is_suspended' => $request->is_suspended]);

            if ($request->password != null) {
                $user->update(['password' => Hash::make($request->password)]);
            }

            $user->userDetail()->update([
                'fullname' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            if ($request->hasFile('profile_picture')) {
                $path = $request->file('profile_picture')->store('profile_pictures', 'public'); // Simpan file ke storage

                $user->userDetail()->update([
                    'picture_profile' => $path,
                ]);
            }
            $user->groups()->sync($request->company_group);

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
        $request->validate(['data_id' => 'required|numeric|exists:company_groups,id']);

        CompanyGroup::destroy($request->data_id);

        return new JsonBody(null, message: 'Account deleted successfully');
    }
}
