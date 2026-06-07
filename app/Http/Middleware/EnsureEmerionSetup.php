<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSafeIdSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) return $next($request);

        // Allow these routes always
        $allowed = [
            'emergency-profile-setup',       // the setup page
            'logout',             // allow logout
        ];

        // Also allow Safe ID scan routes if you want public access
        // if ($request->routeIs('safeid.scan')) return $next($request);

        $isAllowedRoute = $request->route() && $request->routeIs(...$allowed);
        if ($isAllowedRoute) return $next($request);

        $hide = (bool) $user->safeid_hide_onboarding;
        $completed = !is_null($user->safeid_setup_completed_at);

        // Force only if NOT hidden and NOT completed
        if (!$hide && !$completed) {
            return redirect()->route('emergency-profile-setup');
        }

        return $next($request);
    }
}
