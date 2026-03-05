<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is logged in AND their status is inactive
        if (Auth::check() && Auth::user()->status === 'inactive') {
            
            // Log the user out
            Auth::logout();

            // Invalidate the session and regenerate the CSRF token for security
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect to login page with an error message
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }

        return $next($request);
    }
}