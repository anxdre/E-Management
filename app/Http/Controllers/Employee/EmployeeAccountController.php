<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\JsonBody;
use App\Models\UserManagement\CompanyGroup;
use App\Models\UserManagement\GroupHasUser;
use App\Models\UserManagement\User;
use App\Models\UserManagement\UserDetail;
use Dentro\Yalr\Attributes\Delete;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Dentro\Yalr\Attributes\Put;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
    #[Get('/{id}', '.detail')]
    public function detail(int $id)
    {
        $data = User::query()
            ->where('company_id',Auth::id())
            ->where('type','employee')
            ->with('userDetail')
            ->find($id);
        if($data == null){
            return new JsonBody(null,'User not found',404);
        }
        return Inertia::render('Admin/EmployeeAccount/EmployeeAccountDetail',['account'=>$data]);
    }

    #[Get('/all/json', '.json.all')]
    public function getAllAccount(request $request)
    {
        $data = User::query()
            ->with('userDetail')
            ->where('type','employee')
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
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'phone' => 'required|min:10|unique:' . UserDetail::class,
            'address' => 'required|string|lowercase|max:255',
            'password' => ['required','confirmed', Password::defaults()],
        ]);

        DB::beginTransaction();
        try {
            $companyDetail = UserDetail::query()->create([
                'fullname' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            $user = User::query()->create(['name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_detail_id' => $companyDetail->id,
                'company_id' => Auth::id(),
                'type' => 'employee']);
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }

        event(new Registered($user));

        return new JsonBody($user, message: 'Group added successfully');
    }

    #[Put('/update/json', '.json.update')]
    public function updateAccount(Request $request)
    {
        $request->validate(['name' => 'required|string',
            'id' => 'required|numeric|exists:company_groups,id']);

        CompanyGroup::query()->find($request->get('id'))->update([
            'name' => $request->get('name')
        ]);

        return new JsonBody(null, message: 'Group updated successfully');
    }

    #[Delete('/delete/json', '.json.delete')]
    public function deleteAccount(Request $request)
    {
        $request->validate(['data_id' => 'required|numeric|exists:company_groups,id']);

        CompanyGroup::destroy($request->data_id);

        return new JsonBody(null, message: 'Group deleted successfully');
    }
}
