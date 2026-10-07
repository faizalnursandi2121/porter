<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    // FR-4: one generic message for every failure mode — no hint which part failed,
    // no account-existence oracle. English per ui-design.md copy rule (PRD decision #19:
    // FR-4's Indonesian quoted string is superseded by the locked English UI copy).
    'failed' => 'Email or password is incorrect',
    'password' => 'Email or password is incorrect',

    // FR-4: the lockout must not reveal whether the account exists.
    'throttle' => 'Email or password is incorrect',

];
