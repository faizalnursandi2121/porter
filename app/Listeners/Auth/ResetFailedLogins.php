<?php

namespace App\Listeners\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * FR-4: a successful login ends the consecutive-failure streak.
 */
class ResetFailedLogins
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->resetFailedLogins();
        }
    }
}
