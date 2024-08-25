<?php

namespace App\Http\Controllers;

use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Inertia\Inertia;

#[Prefix('Auth'), Name('auth')]
class AuthController extends Controller
{
    #[Get('/sign-in', name: '.sign-in')]
    public function login(Request $request)
    {
        return Inertia::render('Auth/Authentication', []);
    }
}
