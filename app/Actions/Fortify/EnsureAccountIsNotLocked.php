<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * FR-4: per-account lockout gate — runs after the email|IP throttle and
 * before the credential check, so a locked account fails regardless of
 * source IP (2.1's email|IP limiter alone is bypassable by rotating IPs).
 */
class EnsureAccountIsNotLocked
{
    /**
     * Handle the incoming request.
     *
     *
     * @throws ValidationException
     */
    public function handle(Request $request, callable $next): mixed
    {
        $user = User::query()
            ->where('email', Str::lower($request->input(Fortify::username())))
            ->first();

        if ($user?->isLockedUntil()) {
            // FR-4: same generic message as failed auth — no "locked" reveal.
            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }

        return $next($request);
    }
}
