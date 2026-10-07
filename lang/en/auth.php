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
    // no account-existence oracle. Used for both wrong password and unknown email.
    'failed' => 'Email atau kata sandi salah',
    'password' => 'Email atau kata sandi salah',

    // FR-4: the lockout must not reveal whether the account exists.
    'throttle' => 'Email atau kata sandi salah',

];
