<?php

namespace App\Http\Middleware;

use App\Models\UserManagement\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserWithinCompanyScope
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userParam = $request->route('user');
        $targetUser = null;
        if ($userParam instanceof User) {
            $targetUser = $userParam;
        } else {
            $targetUser = User::findOrFail($userParam);
        }
        if (!$targetUser) {
            abort(404, 'User not found.');
        }

        $current = auth()->user();

        // Superadmin bypass all scope restrictions
        if ($current->isSuper()) {
            return $next($request);
        }

        if ($current->isCompany()) {
            $isSelf = $targetUser->id === $current->id;
            $isEmployee = $targetUser->isEmployee();

            if (!$isSelf && !$isEmployee) {
                abort(403, 'Forbidden access.');
            }
        }

        if ($current->isEmployee() && $current->id !== $targetUser->id) {
            abort(403, 'Forbidden access.');
        }
        return $next($request);
    }
}
