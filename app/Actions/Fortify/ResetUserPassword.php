<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Create a new action instance.
     *
     * FR-4a: a self-service reset (default) proves mailbox ownership, so it
     * stamps the forced-change marker as done. The administrator reset path
     * (epic 2.6) constructs the action with marksPasswordChanged: false —
     * the account keeps a temporary password and must change it at next
     * login.
     */
    public function __construct(
        protected bool $marksPasswordChanged = true,
    ) {}

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        // FR-4a: only a self-service reset (mailbox proven) ends the forced
        // change; an admin-installed password stays temporary.
        $user->forceFill([
            'password' => $input['password'],
            'password_changed_at' => $this->marksPasswordChanged ? now() : null,
        ])->save();
    }
}
