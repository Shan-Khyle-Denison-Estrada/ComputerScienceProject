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

        // 2. Safely extract the string value from the BackedEnum (or fallback if it's already a string)
        $userRole = $request->user()->role instanceof \BackedEnum 
            ? $request->user()->role->value 
            : $request->user()->role;

        // 3. Make comparisons case-insensitive to avoid accidental capitalization mismatches
        $userRoleLower = strtolower($userRole);
        $allowedRolesLower = array_map('strtolower', $roles);

        // 4. Check if the user's role exists in the array of allowed roles
        if (!in_array($userRoleLower, $allowedRolesLower)) {
            // Redirect unauthorized users to their appropriate dashboard or home
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}