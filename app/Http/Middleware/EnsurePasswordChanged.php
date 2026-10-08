<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // FR-2: accounts with a pending forced change may only reach the
        // change page, its store endpoint, and logout. Runs in the web group
        // before route middleware, so it must resolve the user itself and
        // leave guests to the auth middleware.
        if ($request->user()?->mustChangePassword() !== true) {
            return $next($request);
        }

        if ($request->routeIs('password.change.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()->route('password.change.show');
    }
}
