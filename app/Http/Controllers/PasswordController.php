<?php

namespace App\Http\Controllers;

use App\Models\UserManagement\User;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Patch;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

#[Prefix('Auth/password'), Name('password')]
class PasswordController extends Controller
{

    #[Get('/forgot', name: '.forgot')]
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    #[Post('/send', name: '.send')]
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:mst_users,email',
        ], ['email.exists' => 'Account with related email doesnt exist']);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    #[get('/reset/{token}', name: '.reset')]
    public function resetPassword(Request $request): Response
    {
        return Inertia::render('Auth/NewPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    #[Patch('/update', name: '.update')]
    function update(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required','confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('auth.sign-in')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
