<?php

namespace App\Http\Controllers;

use App\Models\PresenceManagement\PresenceEmployee;
use App\Models\PresenceManagement\PresenceLocation;
use App\Models\UserManagement\User;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

#[Prefix('Dashboard'), Name('dashboard'), Middleware('auth')]
class DashboardController extends Controller
{
    #[Get('/', name: '.index')]
    public function login(Request $request)
    {
        $employeeStatistics = [];

        $todayPresenceBuilder = Presenceemployee::query()
            ->whereToday('time_in');

        $yesterdayPresenceBuilder = Presenceemployee::query()
            ->whereBeforeToday('time_in');

        $activeEmployee = $todayPresenceBuilder->count();
        $yesterdayActiveEmployee = $yesterdayPresenceBuilder->count();


        $nonActiveEmployee = User::query()
            ->whereNotIn('id', $todayPresenceBuilder->pluck('mst_user_id'))
            ->where('type', 'employee')
            ->count();

        $yesterdayNonActiveEmployee = User::query()
            ->whereNotIn('id', $yesterdayPresenceBuilder->pluck('mst_user_id'))
            ->where('type', 'employee')
            ->count();


        $employeeStatistics['active_employee'] = $activeEmployee;
        $employeeStatistics['non_active_employee'] = $nonActiveEmployee;
        $employeeStatistics['yesterday_active_employee'] = $yesterdayActiveEmployee;
        $employeeStatistics['yesterday_non_active_employee'] = $yesterdayNonActiveEmployee;
        $employeeStatistics['recent_presence'] = $todayPresenceBuilder->latest()->get();

//        $locationWithTimeLimit = PresenceLocation::query()
//            ->where('time_limit', '>', 0)

        return Inertia::render('Admin/Dashboard', [
            'employeeStatistics' => $employeeStatistics,
            'status' => session('status'),
        ]);
    }
}
