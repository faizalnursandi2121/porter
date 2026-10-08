<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * FR-1: server-side role gate for the four PORTER roles. The route
     * parameter is a comma-separated list of Role:: codes — `role:EOS` or
     * `role:SUPERVISI,ADMINISTRATOR` — each arriving as its own argument.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()?->hasRole(...$roles)) {
            abort(403);
        }

        return $next($request);
    }
}
