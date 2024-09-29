<?php

namespace App\Http\Controllers;

use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Inertia\Inertia;
use Illuminate\Http\Request;

#[Prefix('Dashboard'),Name('dashboard'),Middleware('auth')]
class DashboardController extends Controller
{
    #[Get('/',name:'.index')]
    public function login(Request $request)
    {
        return Inertia::render('Admin/Dashboard', [
            'status' => session('status'),
        ]);
    }
}
