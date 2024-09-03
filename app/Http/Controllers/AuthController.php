<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserManagement\CompanyDetail;
use App\Models\UserManagement\User;
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

        return redirect()->intended(route('home', absolute: false));
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
            'phone' => 'required|min:10|unique:' . User::class,
            'address' => 'required|string|lowercase|max:255',
            'password' => ['required','confirmed', Password::defaults()],
        ]);

        DB::beginTransaction();
        try {
            $companyDetail = CompanyDetail::query()->create([
                'name' => $request->name,
                'address' => $request->address
            ]);

            $user = User::query()->create(['name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'account_detail_id' => $companyDetail->id,
                'type' => CompanyDetail::class]);
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return $exception->getMessage();
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->back();
    }
}
