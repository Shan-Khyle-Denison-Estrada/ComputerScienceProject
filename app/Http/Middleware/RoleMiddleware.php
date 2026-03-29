<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Check if the user is authenticated at all
        if (! $request->user()) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        // 2. Extract ALL active roles for the user (base role + temporary roles)
        $userActiveRoles = $request->user()->active_roles;

        // 3. Make comparisons case-insensitive
        $userRolesLower = array_map('strtolower', $userActiveRoles);
        $allowedRolesLower = array_map('strtolower', $roles);

        // 4. Check if ANY of the user's active roles intersect with the allowed roles
        $hasAccess = count(array_intersect($userRolesLower, $allowedRolesLower)) > 0;

        if (!$hasAccess) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}