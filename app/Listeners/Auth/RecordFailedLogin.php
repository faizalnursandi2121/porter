<?php

namespace App\Listeners\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Failed;

/**
 * FR-4: "berturut-turut" — count consecutive failed credential attempts on
 * the account. Unknown emails (no resolved user) are ignored: the lock key
 * is the account, not the submitted address.
 */
class RecordFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        if ($event->user instanceof User) {
            $event->user->recordFailedLogin();
        }
    }
}
