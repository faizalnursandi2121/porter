<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Reset Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the password broker for
    | password reset outcomes. English UI copy per AGENTS.md.
    |
    */

    // FR-4a: reset outcome copy.
    'reset' => 'Your password has been reset.',
    'sent' => 'We have emailed your password reset link.',

    // FR-4a: the retry hint must not differ from the success status — any
    // differentiator leaks that an earlier request was honored (account
    // oracle). The failed-request response renders 'sent' instead.
    'throttled' => 'We have emailed your password reset link.',

    // FR-4a: unknown emails are indistinguishable from known ones —
    // App\Http\Responses\FailedPasswordResetLinkRequestResponse renders
    // 'sent' for every failed link request.
    'user' => 'We have emailed your password reset link.',

];
