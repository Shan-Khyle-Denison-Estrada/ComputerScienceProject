<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // 1. Check if user is authenticated
        if (! $request->user()) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        // 2. Check if the user has the required permission
        if (! in_array($permission, $request->user()->permissions)) {
            // Optional: You can return a 403 abort, or redirect back with an error
            return redirect('/')->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}