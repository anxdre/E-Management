<?php

namespace App\Http\Controllers\Presence;

use App\Http\Controllers\Controller;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Inertia\Inertia;

#[Prefix('Employee'), Name('employee-presence'), Middleware('auth')]
class EmployeePresenceController extends Controller
{
    #[Get('/{user}/Presence-History', '.index',['scope-company'])]
    public function index(Request $request)
    {
        return Inertia::render('Employee/EmployeePresence/PresenceHistory');
    }
}
