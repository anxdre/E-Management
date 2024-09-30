<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserManagement\UserDetail;
use App\Models\UserManagement\User;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

#[Prefix('Auth'), Name('auth')]
class AuthController extends Controller
{
    #[Get('/sign-in', name: '.sign-in')]
    public function login(Request $request)
    {
        return Inertia::render('Auth/Authentication', [
            'status' => session('status'),
        ]);
    }

    #[Post('/sign-in', name: '.sign-in')]
    public function signIn(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->route('dashboard.index');

    }

    #[Post('/sign-out', name: '.sign-out')]
    public function signOut(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    #[Post('/sign-up', name: '.sign-up')]
    public function signUp(Request $request)
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
                'type' => 'company']);
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }

        event(new Registered($user));

//        Auth::login($user);

        return redirect()->back();
    }
}
