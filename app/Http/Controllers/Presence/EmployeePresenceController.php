<?php

namespace App\Http\Controllers\Presence;

use App\Http\Controllers\Controller;
use Dentro\Yalr\Attributes\Get;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeePresenceController extends Controller
{
    #[Get('/', '.index')]
    public function index(Request $request)
    {
        return Inertia::render('Admin/PresenceLocation/PresenceLocation');
    }
}
