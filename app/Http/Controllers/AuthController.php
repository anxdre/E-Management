<?php

namespace App\Http\Controllers;

use App\Models\User;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

#[Prefix('Auth'), Name('auth')]
class AuthController extends Controller
{
    #[Get('/sign-in', name: '.sign-in')]
    public function login(Request $request)
    {
        return Inertia::render('Auth/Authentication', []);
    }

    public function signIn(Request $request){

    }

    public function signOut(Request $request){

    }

    public function signUp(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => 'required|number|digits:15|unique:'.User::class,
            'username' => 'required|number|digits:15|unique:'.User::class,
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

//        TODO::Create users

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
