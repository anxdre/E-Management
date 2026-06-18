<?php

namespace App\Http\Controllers;

use App\Models\Payroll\SalaryReceipt;
use App\Models\PresenceManagement\PresenceEmployee;
use App\Models\UserManagement\User;
use Carbon\Carbon;
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
        $totalEmployee = User::where('type', 'employee')->count();

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todayPresence = PresenceEmployee::whereDate('time_in', $today)->get();
        $yesterdayPresence = PresenceEmployee::whereDate('time_in', $yesterday)->get();

        $activeToday = $todayPresence->count();
        $activeYesterday = $yesterdayPresence->count();

        $lateToday = $todayPresence->filter(fn ($p) => str_contains($p->note ?? '', 'Terlambat'))->count();
        $lateYesterday = $yesterdayPresence->filter(fn ($p) => str_contains($p->note ?? '', 'Terlambat'))->count();

        $onTimeToday = $activeToday - $lateToday;
        $noPresenceToday = $totalEmployee - $activeToday;
        $noPresenceYesterday = $totalEmployee - $activeYesterday;

        $weeklyPresence = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayPresence = PresenceEmployee::whereDate('time_in', $date)->get();
            $hadir = $dayPresence->count();
            $terlambat = $dayPresence->filter(fn ($p) => str_contains($p->note ?? '', 'Terlambat'))->count();
            $weeklyPresence[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D'),
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'alpha' => $totalEmployee - $hadir,
            ];
        }

        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $prevMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $currentPresences = PresenceEmployee::whereBetween('time_in', [$currentMonthStart, $currentMonthEnd])->get();
        $prevPresences = PresenceEmployee::whereBetween('time_in', [$prevMonthStart, $prevMonthEnd])->get();

        $currentSalaryReceipts = SalaryReceipt::whereBetween('created_at', [$currentMonthStart, $currentMonthEnd]);
        $prevSalaryReceipts = SalaryReceipt::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd]);

        $monthlyStats = [
            'current' => [
                'total_presence' => $currentPresences->count(),
                'avg_work_hours' => round($currentSalaryReceipts->avg('work_hour') ?? 0, 1),
                'total_salary' => (float) ($currentSalaryReceipts->sum('salary_after_tax') ?? 0),
                'on_time_rate' => $currentPresences->count() > 0
                    ? round($currentPresences->filter(fn ($p) => $p->note === null || !str_contains($p->note ?? '', 'Terlambat'))->count() / $currentPresences->count() * 100, 1)
                    : 0,
            ],
            'previous' => [
                'total_presence' => $prevPresences->count(),
                'avg_work_hours' => round($prevSalaryReceipts->avg('work_hour') ?? 0, 1),
                'total_salary' => (float) ($prevSalaryReceipts->sum('salary_after_tax') ?? 0),
                'on_time_rate' => $prevPresences->count() > 0
                    ? round($prevPresences->filter(fn ($p) => $p->note === null || !str_contains($p->note ?? '', 'Terlambat'))->count() / $prevPresences->count() * 100, 1)
                    : 0,
            ],
        ];

        $recentSalary = SalaryReceipt::with(['user.userDetail'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'employee_name' => $r->user?->userDetail?->fullname ?? $r->user?->email,
                'employee_email' => $r->user?->email,
                'start_date' => $r->start_date ? Carbon::parse($r->start_date)->format('Y-m-d') : null,
                'end_date' => $r->end_date ? Carbon::parse($r->end_date)->format('Y-m-d') : null,
                'total' => (float) $r->salary_after_tax,
                'status' => $r->status,
            ]);

        $recentPresence = PresenceEmployee::with(['user.userDetail', 'presenceLocation'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'employee_name' => $p->user?->userDetail?->fullname ?? 'Unknown',
                'avatar' => $p->user?->userDetail?->picture_profile,
                'location' => $p->presenceLocation?->name ?? '-',
                'time_in' => $p->time_in ? Carbon::parse($p->time_in)->format('H:i') : null,
                'note' => $p->note,
                'status_by_admin' => $p->status_by_admin,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_employee' => $totalEmployee,
                'active_today' => $activeToday,
                'active_yesterday' => $activeYesterday,
                'late_today' => $lateToday,
                'late_yesterday' => $lateYesterday,
                'on_time_today' => $onTimeToday,
                'no_presence_today' => $noPresenceToday,
                'no_presence_yesterday' => $noPresenceYesterday,
                'weekly_presence' => $weeklyPresence,
                'monthly_stats' => $monthlyStats,
                'recent_salary' => $recentSalary,
                'recent_presence' => $recentPresence,
            ],
            'status' => session('status'),
        ]);
    }
}
