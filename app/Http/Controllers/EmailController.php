<?php

namespace App\Http\Controllers;

use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Post;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailController extends Controller
{
    #[Get('/verify', name: 'verification.notice', middleware: 'auth')]
    public function warnVerifyEmail(Request $request)
    {
        return Inertia::render('Auth/VerifyEmail', []);
    }

    #[Get('/verify/{id}/{hash}', name: 'verification.verify', middleware: ['auth', 'signed'])]
    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();
        $request->user()->status = 'active';
        $request->user()->save();
        return Inertia::render('Auth/SuccessVerifyEmail', []);
    }

    #[Post('/verification-notification', name: 'verification.send', middleware: ['auth', 'throttle:6,1'])]
    public function resendVerifyEmail(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', 'Verification link sent!');
    }
}
