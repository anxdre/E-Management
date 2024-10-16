<?php

namespace App\Http\Controllers\Presence;

use App\Http\Controllers\Controller;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Inertia\Inertia;

#[Prefix('Presence/Location'), Name('presence-location'), Middleware('auth')]
class PresenceLocationController extends Controller
{
    #[Get('/', '.index')]
    public function index()
    {
        return Inertia::render('Admin/PresenceLocation/PresenceLocation');
    }
}
