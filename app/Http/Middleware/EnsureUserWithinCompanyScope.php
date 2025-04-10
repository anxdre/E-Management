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
        $targetUser = User::query()->findOrFail($request->route('user'));

        $current = auth()->user();

        if ($current->isCompany() && $targetUser->company_id !== $current->id) {
            abort(403, 'Forbidden access.');
        }

        if ($current->isEmployee() && $current->id !== $targetUser->id) {
            abort(403, 'Forbidden access.');
        }
        return $next($request);
    }
}
